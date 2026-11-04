<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Domain\Support\DomainException;
use App\Domain\Support\ReferenceGenerator;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OrderFinancial;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\ReferralOrder;
use Illuminate\Support\Facades\DB;

/**
 * Institutional Invoicing & Payment Allocation Service.
 */
final class InvoiceService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * Create an invoice for a referral order based on calculated order financials.
     */
    public function createForOrder(ReferralOrder $order, Actor $actor): Invoice
    {
        $financials = $order->financials;
        if (! $financials) {
            throw new DomainException('Cannot generate invoice before order financials are calculated.');
        }

        return DB::transaction(function () use ($order, $financials, $actor) {
            $number = ReferenceGenerator::make('INV', 'invoices', 'number');

            $invoice = Invoice::query()->create([
                'number' => $number,
                'organization_id' => $order->requesting_organization_id,
                'referral_order_id' => $order->id,
                'status' => 'issued',
                'currency' => $financials->currency,
                'subtotal' => $financials->institution_total,
                'tax_amount' => $financials->tax_amount,
                'total' => $financials->institution_total,
                'amount_paid' => '0.00',
                'amount_credited' => '0.00',
                'balance_due' => $financials->institution_total,
                'issued_on' => now()->toDateString(),
                'due_on' => now()->addDays(30)->toDateString(),
                'created_by' => $actor->user->id,
            ]);

            // Add breakdown items
            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                'category' => 'diagnostic_service',
                'description' => "Diagnostic tests referral ({$order->reference})",
                'quantity' => 1,
                'unit_amount' => $financials->service_value,
                'amount' => $financials->service_value,
            ]);

            if (bccomp((string) $financials->logistics_charge, '0', 2) > 0) {
                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'category' => 'logistics',
                    'description' => 'Specimen collection and transport coordination',
                    'quantity' => 1,
                    'unit_amount' => $financials->logistics_charge,
                    'amount' => $financials->logistics_charge,
                ]);
            }

            if ($financials->fee_bearer === 'institution' && bccomp((string) $financials->platform_fee, '0', 2) > 0) {
                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'category' => 'platform_fee',
                    'description' => 'NODIVA platform coordination fee',
                    'quantity' => 1,
                    'unit_amount' => $financials->platform_fee,
                    'amount' => $financials->platform_fee,
                ]);
            }

            $financials->status = 'invoiced';
            $financials->save();

            $this->audit->log('invoice.issued', $invoice, ['number' => $number], 'success', $actor);

            return $invoice;
        });
    }

    /**
     * Record payment notification and allocate to outstanding invoices.
     */
    public function recordPayment(array $data, Actor $actor): Payment
    {
        return DB::transaction(function () use ($data, $actor) {
            $reference = ReferenceGenerator::make('PAY', 'payments', 'reference');
            $amount = Money::of((string) $data['amount'], $data['currency']);

            $payment = Payment::query()->create([
                'reference' => $reference,
                'organization_id' => $data['organization_id'],
                'currency' => $amount->currency,
                'amount' => $amount->amount,
                'unallocated_amount' => $amount->amount,
                'method' => $data['method'] ?? 'bank_transfer',
                'provider' => $data['provider'] ?? 'manual',
                'provider_reference' => $data['provider_reference'] ?? null,
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => $actor->user->id,
                'recorded_by' => $actor->user->id,
            ]);

            if (! empty($data['invoice_id'])) {
                $invoice = Invoice::findOrFail($data['invoice_id']);
                $this->allocate($payment, $invoice, $amount, $actor);
            }

            $this->audit->log('payment.recorded', $payment, ['amount' => $amount->amount], 'success', $actor);

            return $payment;
        });
    }

    public function allocate(Payment $payment, Invoice $invoice, Money $amountToAllocate, Actor $actor): PaymentAllocation
    {
        if ($payment->currency !== $invoice->currency) {
            throw new DomainException('Currency mismatch between payment and invoice.');
        }

        $unallocated = Money::of((string) $payment->unallocated_amount, $payment->currency);
        $due = Money::of((string) $invoice->balance_due, $invoice->currency);

        $allocationAmount = $amountToAllocate->min($unallocated)->min($due);

        if ($allocationAmount->isZero()) {
            throw new DomainException('No amount available to allocate.');
        }

        return DB::transaction(function () use ($payment, $invoice, $allocationAmount, $unallocated, $due, $actor) {
            $allocation = PaymentAllocation::query()->create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $allocationAmount->amount,
                'allocated_by' => $actor->user->id,
            ]);

            $newUnallocated = $unallocated->minus($allocationAmount);
            $payment->unallocated_amount = $newUnallocated->amount;
            $payment->save();

            $newPaid = Money::of((string) $invoice->amount_paid, $invoice->currency)->plus($allocationAmount);
            $newDue = $due->minus($allocationAmount);

            $invoice->amount_paid = $newPaid->amount;
            $invoice->balance_due = $newDue->amount;
            $invoice->status = $newDue->isZero() ? 'paid' : 'partially_paid';
            $invoice->save();

            return $allocation;
        });
    }
}
