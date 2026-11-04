<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Domain\Support\ReferenceGenerator;
use App\Models\Invoice;
use App\Models\OrderFinancial;
use App\Models\ReconciliationItem;
use App\Models\ReconciliationRun;
use App\Models\ReferralOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Platform Financial Reconciliation Engine.
 * Identifies discrepancies between diagnostic orders, invoices, payments, and laboratory liabilities.
 */
final class ReconciliationEngine
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {
    }

    public function run(Carbon $startDate, Carbon $endDate, string $currency, Actor $actor): ReconciliationRun
    {
        return DB::transaction(function () use ($startDate, $endDate, $currency, $actor) {
            $run = ReconciliationRun::query()->create([
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
                'currency' => $currency,
                'orders_checked' => 0,
                'discrepancy_count' => 0,
                'run_by' => $actor->user->id,
            ]);

            $orders = ReferralOrder::query()
                ->where('currency', $currency)
                ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                ->with(['financials', 'invoices'])
                ->get();

            $discrepancies = 0;
            $totalGmv = Money::zero($currency);
            $totalPlatformFees = Money::zero($currency);
            $totalLabPayables = Money::zero($currency);

            foreach ($orders as $order) {
                $financials = $order->financials;
                if (! $financials) {
                    continue;
                }

                $gmv = Money::of((string) $financials->service_value, $currency);
                $fee = Money::of((string) $financials->platform_fee, $currency);
                $payable = Money::of((string) $financials->laboratory_payable, $currency);

                $totalGmv = $totalGmv->plus($gmv);
                $totalPlatformFees = $totalPlatformFees->plus($fee);
                $totalLabPayables = $totalLabPayables->plus($payable);

                // Check 1: Completed orders without invoices
                if ($order->status === \App\Domain\Referrals\Enums\OrderStatus::Completed && $order->invoices->isEmpty()) {
                    ReconciliationItem::query()->create([
                        'reconciliation_run_id' => $run->id,
                        'referral_order_id' => $order->id,
                        'type' => 'not_invoiced',
                        'expected_amount' => $financials->institution_total,
                        'actual_amount' => '0.00',
                        'difference' => $financials->institution_total,
                        'note' => "Completed order {$order->reference} has not been invoiced.",
                    ]);
                    $discrepancies++;
                }

                // Check 2: Invoice amount mismatch
                foreach ($order->invoices as $inv) {
                    if (bccomp((string) $inv->total, (string) $financials->institution_total, 2) !== 0) {
                        ReconciliationItem::query()->create([
                            'reconciliation_run_id' => $run->id,
                            'referral_order_id' => $order->id,
                            'type' => 'invoice_mismatch',
                            'expected_amount' => $financials->institution_total,
                            'actual_amount' => $inv->total,
                            'difference' => bcsub((string) $inv->total, (string) $financials->institution_total, 2),
                            'note' => "Invoice {$inv->number} differs from order calculation.",
                        ]);
                        $discrepancies++;
                    }
                }
            }

            $run->orders_checked = $orders->count();
            $run->discrepancy_count = $discrepancies;
            $run->totals = [
                'total_gmv' => $totalGmv->amount,
                'total_platform_fees' => $totalPlatformFees->amount,
                'total_lab_payables' => $totalLabPayables->amount,
                'currency' => $currency,
            ];
            $run->save();

            $this->audit->log('finance.reconciliation_completed', $run, ['discrepancies' => $discrepancies], 'success', $actor);

            return $run;
        });
    }
}
