<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use App\Domain\Organizations\Actor;
use App\Models\DiagnosticSpecialty;
use App\Models\OrderFinancial;
use App\Models\PlatformFeeRule;
use App\Models\ReferralOrder;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;

/**
 * Calculates order financials, fees, logistics, payable to laboratory, and platform revenue.
 * Strictly avoids confusing Gross Merchandise Value (GMV) with platform revenue.
 */
final class FinancialCalculator
{
    public function calculate(ReferralOrder $order): OrderFinancial
    {
        $currency = $order->currency;
        $serviceValue = Money::zero($currency);

        foreach ($order->items as $item) {
            $price = $item->agreed_price ?? $item->quoted_price ?? '0.00';
            $serviceValue = $serviceValue->plus(Money::of((string) $price, $currency));
        }

        // Logistics charges (determined by laboratory routing, location, and collection method)
        $logisticsCharge = Money::zero($currency);
        if ($order->collection_method === 'courier') {
            if ($order->logistics_charge !== null && $order->logistics_charge !== '') {
                $logisticsCharge = Money::of((string) $order->logistics_charge, $currency);
            } elseif ($order->laboratory) {
                $charge = app(\App\Domain\Logistics\LogisticsPricingService::class)
                    ->calculateDispatchCharge($order->requestingOrganization, $order->laboratory);
                $logisticsCharge = Money::of((string) $charge, $currency);
            }
        }

        // Platform fee rule lookup: Contract -> Org -> Specialty -> Global
        $rule = $this->resolveFeeRule($order);
        $ratePercent = $rule ? (string) $rule->rate_percent : (string) SystemSetting::get('finance.default_platform_fee_percent', '12.00');
        $feeBearer = $rule ? $rule->fee_bearer : (string) SystemSetting::get('finance.default_fee_bearer', 'laboratory'); // default: deducted from lab payable

        $platformFee = $serviceValue->percentage($ratePercent);
        if ($rule && ! empty($rule->flat_fee) && bccomp((string) $rule->flat_fee, '0', 2) > 0) {
            $platformFee = $platformFee->plus(Money::of((string) $rule->flat_fee, $currency));
        }

        // Tax calculation (e.g. VAT / NHIL / GETFund if applicable on platform fee or service)
        $taxRate = '0.00';
        $taxAmount = Money::zero($currency);

        // Calculate totals based on fee bearer
        if ($feeBearer === 'institution') {
            // Fee added on top of diagnostic GMV for the hospital
            $institutionTotal = $serviceValue->plus($platformFee)->plus($logisticsCharge)->plus($taxAmount);
            $labPayable = $serviceValue;
        } else {
            // Standard: fee deducted from laboratory's gross service amount
            $institutionTotal = $serviceValue->plus($logisticsCharge)->plus($taxAmount);
            $labPayable = $serviceValue->minus($platformFee);
        }

        // Platform revenue = Platform fee + Logistics margin (or full charge if direct carrier cost handled by platform)
        $platformRevenue = $platformFee->plus($logisticsCharge);

        return DB::transaction(function () use ($order, $currency, $serviceValue, $logisticsCharge, $rule, $ratePercent, $platformFee, $feeBearer, $taxRate, $taxAmount, $institutionTotal, $labPayable, $platformRevenue) {
            return OrderFinancial::query()->updateOrCreate(
                ['referral_order_id' => $order->id],
                [
                    'currency' => $currency,
                    'service_value' => $serviceValue->amount,
                    'discount_amount' => '0.00',
                    'logistics_charge' => $logisticsCharge->amount,
                    'platform_fee_rule_id' => $rule?->id,
                    'platform_fee_rate' => $ratePercent,
                    'platform_fee' => $platformFee->amount,
                    'fee_bearer' => $feeBearer,
                    'fee_basis' => "Diagnostic GMV {$serviceValue->format()} @ {$ratePercent}%",
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount->amount,
                    'institution_total' => $institutionTotal->amount,
                    'laboratory_payable' => $labPayable->amount,
                    'platform_revenue' => $platformRevenue->amount,
                    'status' => 'quoted',
                ]
            );
        });
    }

    private function resolveFeeRule(ReferralOrder $order): ?PlatformFeeRule
    {
        $date = now();

        // 1. Specific to requesting institution or laboratory
        $rule = PlatformFeeRule::query()
            ->effectiveOn($date)
            ->where(fn ($q) => $q->where('organization_id', $order->laboratory_id)->orWhere('organization_id', $order->requesting_organization_id))
            ->orderByDesc('priority')
            ->first();

        if ($rule) {
            return $rule;
        }

        // 2. Global rule
        return PlatformFeeRule::query()
            ->effectiveOn($date)
            ->where('scope', 'global')
            ->orderByDesc('priority')
            ->first();
    }
}
