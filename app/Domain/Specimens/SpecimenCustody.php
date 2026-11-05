<?php

declare(strict_types=1);

namespace App\Domain\Specimens;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Domain\Referrals\OrderWorkflow;
use App\Domain\Specimens\Enums\CustodyEvent;
use App\Domain\Specimens\Enums\SpecimenStatus;
use App\Domain\Support\DomainException;
use App\Domain\Support\ReferenceGenerator;
use App\Models\ChainOfCustodyEvent;
use App\Models\Dispatch;
use App\Models\ReferralOrder;
use App\Models\Specimen;
use Illuminate\Support\Facades\DB;

/**
 * Specimen chain of custody, identification and lifecycle management.
 */
final class SpecimenCustody
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OrderWorkflow $workflow,
    ) {
    }

    /**
     * Create specimen container and barcode/identifier for an order.
     */
    public function register(ReferralOrder $order, int $specimenTypeId, Actor $actor): Specimen
    {
        return DB::transaction(function () use ($order, $specimenTypeId, $actor) {
            $identifier = ReferenceGenerator::specimen();

            $specimen = Specimen::query()->create([
                'identifier' => $identifier,
                'referral_order_id' => $order->id,
                'specimen_type_id' => $specimenTypeId,
                'status' => SpecimenStatus::PendingCollection,
                'sequence' => $order->specimens()->count() + 1,
                'custodian_organization_id' => $order->requesting_organization_id,
            ]);

            $this->audit->log('specimen.registered', $specimen, ['identifier' => $identifier], 'success', $actor);

            return $specimen;
        });
    }

    /**
     * Record collection of specimen at referring facility.
     */
    public function recordCollection(Specimen $specimen, Actor $actor, ?string $location = null): Specimen
    {
        $actor->authorize('specimens.dispatch');

        $this->recordCustodyEvent($specimen, CustodyEvent::Collected, $actor, [
            'location' => $location ?? 'Collection Clinic',
            'notes' => 'Specimen collected and packaged according to transport specifications',
        ]);

        $order = $specimen->order;
        if (in_array($order->status, [OrderStatus::AwaitingLabAcceptance, OrderStatus::Accepted, OrderStatus::CollectionScheduled], true)) {
            $this->workflow->transition($order, OrderStatus::SpecimenCollected, $actor, 'Specimen collected');
            $order->save();
        }

        return $specimen;
    }

    /**
     * Dispatch specimen via courier or internal transport.
     */
    public function dispatch(ReferralOrder $order, array $dispatchData, Actor $actor): Dispatch
    {
        $actor->authorize('specimens.dispatch');

        if (! $order->laboratory_id || $order->status === OrderStatus::Matching) {
            throw new DomainException("Cannot dispatch specimens for order [{$order->reference}]: A fulfilling partner laboratory must claim or be assigned to the referral before courier dispatch.");
        }

        return DB::transaction(function () use ($order, $dispatchData, $actor) {
            $dispatch = Dispatch::query()->create([
                'referral_order_id' => $order->id,
                'type' => $dispatchData['type'] ?? $order->collection_method,
                'carrier_name' => $dispatchData['carrier_name'] ?? 'NODIVA Logistics Network',
                'tracking_reference' => $dispatchData['tracking_reference'] ?? null,
                'pickup_location' => $dispatchData['pickup_location'] ?? null,
                'scheduled_for' => now(),
                'dispatched_at' => now(),
                'status' => 'in_transit',
                'notes' => $dispatchData['notes'] ?? null,
                'created_by' => $actor->user->id,
            ]);

            foreach ($order->specimens()->get() as $specimen) {
                if ($specimen->status === SpecimenStatus::Collected) {
                    $this->recordCustodyEvent($specimen, CustodyEvent::Dispatched, $actor, [
                        'dispatch_id' => $dispatch->id,
                        'handed_to' => $dispatch->carrier_name,
                        'notes' => 'Dispatched with carrier',
                    ]);
                }
            }

            if ($order->status !== OrderStatus::InTransit) {
                if ($order->status === OrderStatus::AwaitingLabAcceptance) {
                    $order->assignments()->where('status', 'offered')->update([
                        'status' => 'accepted',
                        'decided_at' => now(),
                    ]);
                }

                $this->workflow->transition($order, OrderStatus::InTransit, $actor, 'Specimen dispatched in transit');
                $order->save();
            }

            $this->audit->log('order.dispatched', $order, ['dispatch_id' => $dispatch->id], 'success', $actor);

            return $dispatch;
        });
    }

    /**
     * Laboratory records receipt of specimen container.
     */
    public function recordReceipt(Specimen $specimen, Actor $actor, ?string $location = null): Specimen
    {
        $actor->authorize('specimens.receive');

        if (in_array($specimen->status, [SpecimenStatus::Received, SpecimenStatus::Accepted], true)) {
            return $specimen;
        }

        return DB::transaction(function () use ($specimen, $actor, $location) {
            $this->recordCustodyEvent($specimen, CustodyEvent::Received, $actor, [
                'location' => $location ?? 'Laboratory Specimen Reception',
                'notes' => 'Container received and temperature verified',
            ]);

            $specimen->custodian_organization_id = $actor->organizationId();
            $specimen->received_at = now();
            $specimen->save();

            $order = $specimen->order;
            if (! in_array($order->status, [
                OrderStatus::SpecimenReceived,
                OrderStatus::Processing,
                OrderStatus::AwaitingResultReview,
                OrderStatus::ResultReleased,
                OrderStatus::Completed,
            ], true)) {
                $order->specimen_received_at = now();
                $this->workflow->transition($order, OrderStatus::SpecimenReceived, $actor, 'Specimen received at laboratory');
                $order->save();
            }

            return $specimen;
        });
    }

    /**
     * Laboratory accepts specimen for analytical processing.
     */
    public function acceptSpecimen(Specimen $specimen, Actor $actor): Specimen
    {
        $actor->authorize('specimens.receive');

        if ($specimen->status === SpecimenStatus::Accepted) {
            return $specimen;
        }

        return DB::transaction(function () use ($specimen, $actor) {
            $this->recordCustodyEvent($specimen, CustodyEvent::Accepted, $actor, [
                'notes' => 'Specimen verified, integrity confirmed, accepted for processing',
            ]);

            $order = $specimen->order;
            if (! in_array($order->status, [
                OrderStatus::Processing,
                OrderStatus::AwaitingResultReview,
                OrderStatus::ResultReleased,
                OrderStatus::Completed,
            ], true)) {
                $this->workflow->transition($order, OrderStatus::Processing, $actor, 'Specimen accepted; analytical processing initiated');
                $order->save();
            }

            return $specimen;
        });
    }

    /**
     * Laboratory rejects specimen (hemolysis, unlabelled, clotted, temperature excursion, etc.)
     */
    public function rejectSpecimen(Specimen $specimen, string $reason, Actor $actor): Specimen
    {
        $actor->authorize('specimens.receive');

        if (empty(trim($reason))) {
            throw new DomainException('A documented reason is required to reject a clinical specimen.');
        }

        $this->recordCustodyEvent($specimen, CustodyEvent::Rejected, $actor, [
            'notes' => "Specimen rejected: {$reason}",
        ]);

        $specimen->rejection_reason = $reason;
        $specimen->save();

        $order = $specimen->order;
        $this->workflow->transition($order, OrderStatus::SpecimenRejected, $actor, "Specimen rejected: {$reason}");
        $order->save();

        return $specimen;
    }

    private function recordCustodyEvent(Specimen $specimen, CustodyEvent $event, Actor $actor, array $details = []): void
    {
        $courierReq = $specimen->order->requiresCourier();
        $allowed = $event->requiredFrom($courierReq);

        if (! in_array($specimen->status, $allowed, true)) {
            throw new DomainException("Cannot record event [{$event->value}] when specimen is [{$specimen->status->value}].");
        }

        $from = $specimen->status;
        $to = $event->resultingStatus($from);

        $specimen->status = $to;
        if ($event === CustodyEvent::Collected) {
            $specimen->collected_at = now();
        }
        $specimen->save();

        ChainOfCustodyEvent::query()->create([
            'specimen_id' => $specimen->id,
            'event' => $event->value,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'actor_id' => $actor->user->id,
            'actor_organization_id' => $actor->organizationId(),
            'dispatch_id' => $details['dispatch_id'] ?? null,
            'location' => $details['location'] ?? null,
            'handed_to' => $details['handed_to'] ?? null,
            'notes' => $details['notes'] ?? null,
            'occurred_at' => now(),
        ]);
    }
}
