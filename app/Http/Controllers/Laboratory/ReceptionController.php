<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratory;

use App\Domain\Organizations\Actor;
use App\Domain\Organizations\CurrentOrganization;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Domain\Referrals\OrderWorkflow;
use App\Domain\Specimens\Enums\SpecimenStatus;
use App\Domain\Specimens\SpecimenCustody;
use App\Http\Controllers\Controller;
use App\Models\ReferralOrder;
use App\Models\Specimen;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly OrderWorkflow $workflow,
        private readonly SpecimenCustody $custody,
    ) {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();
        $statusFilter = $request->query('status');

        // Incoming orders assigned to this lab or broadcast to this lab
        $pendingAcceptanceOrders = ReferralOrder::where(function ($q) use ($org) {
                $q->where('laboratory_id', $org->id)
                  ->where('status', OrderStatus::AwaitingLabAcceptance);
            })
            ->orWhere(function ($q) use ($org) {
                $q->whereNull('laboratory_id')
                  ->where('status', OrderStatus::Matching)
                  ->whereHas('assignments', function ($a) use ($org) {
                      $a->where('laboratory_id', $org->id)->where('status', 'offered');
                  });
            })
            ->with(['requestingOrganization', 'items.test', 'specimens.type', 'assignments'])
            ->latest('id')
            ->get();

        // Specimens intended for this laboratory
        $specimensQuery = Specimen::whereHas('order', function ($q) use ($org) {
            $q->where('laboratory_id', $org->id);
        })->with(['order.requestingOrganization', 'order.items.test', 'type', 'custodyEvents']);

        if ($statusFilter && in_array($statusFilter, ['in_transit', 'received', 'processing', 'rejected', 'collected'])) {
            $specimensQuery->where('status', $statusFilter);
        }

        $specimens = $specimensQuery->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'awaiting_acceptance' => $pendingAcceptanceOrders->count(),
            'in_transit' => Specimen::whereHas('order', fn ($q) => $q->where('laboratory_id', $org->id))->where('status', SpecimenStatus::InTransit)->count(),
            'received' => Specimen::whereHas('order', fn ($q) => $q->where('laboratory_id', $org->id))->where('status', SpecimenStatus::Received)->count(),
            'rejected' => Specimen::whereHas('order', fn ($q) => $q->where('laboratory_id', $org->id))->where('status', SpecimenStatus::Rejected)->count(),
        ];

        return view('laboratory.reception.index', [
            'organization' => $org,
            'pendingAcceptanceOrders' => $pendingAcceptanceOrders,
            'specimens' => $specimens,
            'metrics' => $metrics,
            'currentStatus' => $statusFilter,
        ]);
    }

    public function acceptOrder(Request $request, ReferralOrder $order)
    {
        $org = $this->current->organization();
        $actor = Actor::for($request->user(), $org);

        // If order was broadcast to all qualified labs (first to accept)
        if (! $order->laboratory_id) {
            if ($order->status !== OrderStatus::Matching) {
                return redirect()->back()->with('error', 'This referral order is no longer available; it was already claimed by another laboratory.');
            }

            // Assign this laboratory as the fulfilling partner
            $order = $this->workflow->assignLaboratory(
                $order,
                $org,
                'broadcast_claimed',
                ["Claimed from open broadcast network by {$org->name}"],
                null,
                $actor
            );

            // Expire remaining offered assignments for other candidate laboratories
            $order->assignments()
                ->where('laboratory_id', '!=', $org->id)
                ->where('status', 'offered')
                ->update([
                    'status' => 'expired',
                    'decided_at' => now(),
                ]);
        } elseif ($order->laboratory_id !== $org->id) {
            abort(403, 'Order not assigned to your laboratory.');
        }

        $this->workflow->acceptByLaboratory($order, $actor);

        return redirect()->back()->with('success', "Order {$order->reference} successfully claimed and accepted! Referring hospital has been notified to hand over specimens to courier.");
    }

    public function receiveSpecimen(Request $request, Specimen $specimen)
    {
        $org = $this->current->organization();
        $actor = Actor::for($request->user(), $org);

        if ($specimen->order->laboratory_id !== $org->id) {
            abort(403, 'Specimen does not belong to an order assigned to this laboratory.');
        }

        $validated = $request->validate([
            'intake_bay' => ['nullable', 'string', 'max:100'],
            'process_immediately' => ['nullable', 'boolean'],
        ]);

        $location = $validated['intake_bay'] ?? 'Central Specimen Reception Bay';
        $this->custody->recordReceipt($specimen, $actor, $location);

        if ($request->boolean('process_immediately')) {
            $this->custody->acceptSpecimen($specimen->fresh(), $actor);
        }

        return redirect()->back()->with('success', "Specimen {$specimen->identifier} verified and logged into laboratory accessioning.");
    }

    public function acceptForProcessing(Request $request, Specimen $specimen)
    {
        $org = $this->current->organization();
        $actor = Actor::for($request->user(), $org);

        if ($specimen->order->laboratory_id !== $org->id) {
            abort(403, 'Unauthorized specimen action.');
        }

        $this->custody->acceptSpecimen($specimen, $actor);

        return redirect()->back()->with('success', "Specimen {$specimen->identifier} accepted for analytical processing.");
    }

    public function rejectSpecimen(Request $request, Specimen $specimen)
    {
        $org = $this->current->organization();
        $actor = Actor::for($request->user(), $org);

        if ($specimen->order->laboratory_id !== $org->id) {
            abort(403, 'Unauthorized specimen action.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:255'],
        ]);

        $this->custody->rejectSpecimen($specimen, $validated['rejection_reason'], $actor);

        return redirect()->back()->with('error', "Specimen {$specimen->identifier} rejected. Notice transmitted to referring hospital.");
    }
}
