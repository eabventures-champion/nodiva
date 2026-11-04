<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hospital;

use App\Domain\Organizations\Actor;
use App\Domain\Organizations\CurrentOrganization;
use App\Domain\Specimens\Enums\SpecimenStatus;
use App\Domain\Specimens\SpecimenCustody;
use App\Http\Controllers\Controller;
use App\Models\ReferralOrder;
use App\Models\Specimen;
use Illuminate\Http\Request;

class SpecimenController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly SpecimenCustody $custody,
    ) {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();
        $statusFilter = $request->query('status');

        $query = Specimen::whereHas('order', function ($q) use ($org) {
            $q->where('requesting_organization_id', $org->id);
        })->with(['order.patient', 'order.laboratory', 'type', 'custodyEvents.actor']);

        if ($statusFilter && in_array($statusFilter, ['pending_collection', 'collected', 'in_transit', 'received', 'accepted', 'rejected'])) {
            if ($statusFilter === 'received') {
                $query->whereIn('status', [SpecimenStatus::Received, SpecimenStatus::Accepted]);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        $specimens = $query->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'pending' => Specimen::whereHas('order', fn ($q) => $q->where('requesting_organization_id', $org->id))
                ->where('status', SpecimenStatus::PendingCollection)->count(),
            'collected' => Specimen::whereHas('order', fn ($q) => $q->where('requesting_organization_id', $org->id))
                ->where('status', SpecimenStatus::Collected)->count(),
            'in_transit' => Specimen::whereHas('order', fn ($q) => $q->where('requesting_organization_id', $org->id))
                ->where('status', SpecimenStatus::InTransit)->count(),
            'received' => Specimen::whereHas('order', fn ($q) => $q->where('requesting_organization_id', $org->id))
                ->whereIn('status', [SpecimenStatus::Received, SpecimenStatus::Accepted])->count(),
        ];

        // Active orders that have collected specimens ready to dispatch
        $readyToDispatchOrders = ReferralOrder::where('requesting_organization_id', $org->id)
            ->whereHas('specimens', fn ($q) => $q->where('status', SpecimenStatus::Collected))
            ->with(['specimens' => fn ($q) => $q->where('status', SpecimenStatus::Collected), 'laboratory'])
            ->get();

        return view('hospital.specimens.index', [
            'organization' => $org,
            'specimens' => $specimens,
            'metrics' => $metrics,
            'currentStatus' => $statusFilter,
            'readyToDispatchOrders' => $readyToDispatchOrders,
        ]);
    }

    public function recordCollection(Request $request, Specimen $specimen)
    {
        $org = $this->current->organization();
        $user = $request->user();
        $actor = Actor::for($user, $org);

        // Security check: specimen order belongs to this hospital
        if ($specimen->order->requesting_organization_id !== $org->id) {
            abort(403, 'Unauthorized specimen access.');
        }

        $validated = $request->validate([
            'location' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $this->custody->recordCollection($specimen, $actor, $validated['location'] ?? 'Hospital Phlebotomy Wing');

        return redirect()->back()->with('success', "Specimen {$specimen->identifier} marked as collected and ready for dispatch.");
    }

    public function dispatch(Request $request)
    {
        $org = $this->current->organization();
        $user = $request->user();
        $actor = Actor::for($user, $org);

        $validated = $request->validate([
            'order_id' => ['required', 'exists:referral_orders,id'],
            'carrier_name' => ['required', 'string', 'max:100'],
            'tracking_reference' => ['required', 'string', 'max:100'],
            'pickup_location' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = ReferralOrder::where('requesting_organization_id', $org->id)->findOrFail($validated['order_id']);

        $this->custody->dispatch($order, [
            'carrier_name' => $validated['carrier_name'],
            'tracking_reference' => $validated['tracking_reference'],
            'pickup_location' => $validated['pickup_location'] ?? 'Hospital Central Specimen Handover Point',
            'notes' => $validated['notes'] ?? null,
        ], $actor);

        return redirect()->back()->with('success', "Order {$order->reference} specimens dispatched via {$validated['carrier_name']} (Tracking #{$validated['tracking_reference']}).");
    }
}
