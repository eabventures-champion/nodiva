<?php

declare(strict_types=1);

namespace App\Domain\Referrals;

use App\Domain\Audit\AuditLogger;
use App\Domain\Finance\FinancialCalculator;
use App\Domain\Organizations\Actor;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Domain\Support\DomainException;
use App\Models\OrderAssignment;
use App\Models\OrderStatusEvent;
use App\Models\Organization;
use App\Models\ReferralOrder;
use Illuminate\Support\Facades\DB;

/**
 * Central State Machine for Referral Orders.
 * Prevents arbitrary status changes and enforces business invariants across tenants.
 */
final class OrderWorkflow
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly FinancialCalculator $calculator,
    ) {
    }

    /**
     * Submit draft order into matching/acceptance queue.
     */
    public function submit(ReferralOrder $order, Actor $actor): ReferralOrder
    {
        $actor->authorize('orders.create');

        if ($order->requesting_organization_id !== $actor->organizationId()) {
            throw new DomainException('Cannot submit order on behalf of another institution.');
        }

        if (! in_array($order->status, [OrderStatus::Draft], true)) {
            throw new DomainException("Cannot submit order in status [{$order->status->value}].");
        }

        if ($order->items()->count() === 0) {
            throw new DomainException('Order must contain at least one diagnostic test.');
        }

        // Verify patient consent for external referral
        if (! $order->patient->hasActiveConsent('external_referral_testing')) {
            throw new DomainException('Patient consent for external referral testing has not been recorded or has been revoked.');
        }

        return DB::transaction(function () use ($order, $actor) {
            $targetStatus = $order->laboratory_id ? OrderStatus::AwaitingLabAcceptance : OrderStatus::Matching;

            $this->transition($order, $targetStatus, $actor, 'Order submitted for laboratory fulfillment');

            $order->submitted_at = now();
            $order->save();

            // Calculate initial quote & financials
            $this->calculator->calculate($order);

            $this->audit->log('order.submitted', $order, ['reference' => $order->reference], 'success', $actor);

            return $order;
        });
    }

    /**
     * Assign laboratory (either by automated recommendation or manual override).
     */
    public function assignLaboratory(ReferralOrder $order, Organization $lab, string $mode, array $rationale, ?string $overrideReason, Actor $actor): ReferralOrder
    {
        if ($mode === 'override') {
            $actor->authorize('ops.orders.reassign');
            if (empty($overrideReason)) {
                throw new DomainException('Manual override requires a documented justification.');
            }
        }

        return DB::transaction(function () use ($order, $lab, $mode, $rationale, $overrideReason, $actor) {
            $order->laboratory_id = $lab->id;
            $order->selection_mode = $mode;
            $order->selection_rationale = $rationale;
            $order->override_reason = $overrideReason;

            // Re-evaluate prices based on selected lab's catalogue entries
            $labEntries = $lab->catalogueEntries()->whereIn('diagnostic_test_id', $order->items->pluck('diagnostic_test_id'))->get()->keyBy('diagnostic_test_id');
            $maxTat = 0;

            foreach ($order->items as $item) {
                $entry = $labEntries->get($item->diagnostic_test_id);
                if ($entry) {
                    $item->test_catalogue_entry_id = $entry->id;
                    $item->quoted_price = $entry->price;
                    $item->agreed_price = $entry->price;
                    $item->turnaround_hours = $entry->turnaround_hours;
                    $item->save();

                    if ($entry->turnaround_hours > $maxTat) {
                        $maxTat = $entry->turnaround_hours;
                    }
                } else {
                    $item->quoted_price = $item->quoted_price ?? '250.00';
                    $item->agreed_price = $item->agreed_price ?? '250.00';
                    $item->turnaround_hours = $item->turnaround_hours ?? 24;
                    $item->save();

                    if ($item->turnaround_hours > $maxTat) {
                        $maxTat = $item->turnaround_hours;
                    }
                }
            }

            if ($maxTat > 0) {
                $order->due_at = now()->addHours($maxTat);
            }

            OrderAssignment::query()->updateOrCreate(
                [
                    'referral_order_id' => $order->id,
                    'laboratory_id' => $lab->id,
                ],
                [
                    'status' => 'offered',
                    'selection_mode' => $mode,
                    'rationale' => $rationale,
                    'assigned_by' => $actor->user->id,
                ]
            );

            $this->transition($order, OrderStatus::AwaitingLabAcceptance, $actor, "Assigned to {$lab->name} ({$mode})");

            $order->save();

            // Refresh financials
            $this->calculator->calculate($order);

            $this->audit->log('order.assigned', $order, ['laboratory_id' => $lab->id, 'mode' => $mode], 'success', $actor);

            return $order;
        });
    }

    /**
     * Laboratory accepts the referral order.
     */
    public function acceptByLaboratory(ReferralOrder $order, Actor $actor): ReferralOrder
    {
        $actor->authorize('orders.respond');

        if ($order->laboratory_id !== $actor->organizationId()) {
            throw new DomainException('Only the designated laboratory may accept this order.');
        }

        if ($order->status !== OrderStatus::AwaitingLabAcceptance) {
            throw new DomainException("Order cannot be accepted in status [{$order->status->value}].");
        }

        return DB::transaction(function () use ($order, $actor) {
            $order->accepted_at = now();

            // Update assignment status
            $order->assignments()->where('laboratory_id', $actor->organizationId())->where('status', 'offered')
                ->update([
                    'status' => 'accepted',
                    'decided_by' => $actor->user->id,
                    'decided_at' => now(),
                ]);

            $this->transition($order, OrderStatus::Accepted, $actor, 'Laboratory accepted order');

            // Automatically queue specimen collection or dispatch stage
            $this->transition($order, OrderStatus::CollectionScheduled, $actor, 'Specimen collection/dispatch pending');

            $order->save();

            $this->audit->log('order.accepted', $order, [], 'success', $actor);

            return $order;
        });
    }

    /**
     * Laboratory rejects referral order with auditable reason.
     */
    public function rejectByLaboratory(ReferralOrder $order, string $reason, Actor $actor): ReferralOrder
    {
        $actor->authorize('orders.respond');

        if ($order->laboratory_id !== $actor->organizationId()) {
            throw new DomainException('Only the designated laboratory may respond to this order.');
        }

        if ($order->status !== OrderStatus::AwaitingLabAcceptance) {
            throw new DomainException("Order cannot be rejected in status [{$order->status->value}].");
        }

        if (empty(trim($reason))) {
            throw new DomainException('Auditable reason required for order rejection.');
        }

        return DB::transaction(function () use ($order, $reason, $actor) {
            $order->assignments()->where('laboratory_id', $actor->organizationId())->where('status', 'offered')
                ->update([
                    'status' => 'rejected',
                    'decided_by' => $actor->user->id,
                    'decided_at' => now(),
                    'reason' => $reason,
                ]);

            // Re-enter matching state for platform operations or referring hospital to re-route
            $order->laboratory_id = null;
            $this->transition($order, OrderStatus::Matching, $actor, "Laboratory rejected: {$reason}");

            $order->save();

            $this->audit->log('order.rejected_by_lab', $order, ['reason' => $reason], 'success', $actor);

            return $order;
        });
    }

    /**
     * Cancel an order.
     */
    public function cancel(ReferralOrder $order, string $reason, Actor $actor): ReferralOrder
    {
        $canCancel = $actor->can('orders.cancel') || $actor->can('ops.orders.reassign');
        if (! $canCancel) {
            throw new DomainException('Not authorised to cancel orders.');
        }

        if (! in_array($order->status, OrderStatus::cancellable(), true)) {
            throw new DomainException("Order cannot be cancelled once processing has started.");
        }

        return DB::transaction(function () use ($order, $reason, $actor) {
            $order->cancelled_at = now();
            $order->cancellation_reason = $reason;

            $this->transition($order, OrderStatus::Cancelled, $actor, "Cancelled: {$reason}");
            $order->save();

            $this->audit->log('order.cancelled', $order, ['reason' => $reason], 'success', $actor);

            return $order;
        });
    }

    /**
     * Execute status transition while asserting invariants and creating history event.
     */
    public function transition(ReferralOrder $order, OrderStatus $to, ?Actor $actor, ?string $reason = null, ?array $metadata = null): void
    {
        $from = $order->status;

        if ($from === $to) {
            return;
        }

        if (! $from->canTransitionTo($to)) {
            throw new DomainException("Illegal order transition from [{$from->value}] to [{$to->value}].");
        }

        $order->status = $to;

        OrderStatusEvent::query()->create([
            'referral_order_id' => $order->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'actor_id' => $actor?->user->id,
            'actor_organization_id' => $actor?->organizationId(),
            'reason' => $reason,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
