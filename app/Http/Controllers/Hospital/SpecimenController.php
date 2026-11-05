<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hospital;

use App\Domain\Organizations\Actor;
use App\Domain\Organizations\CurrentOrganization;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Domain\Specimens\Enums\SpecimenStatus;
use App\Domain\Specimens\SpecimenCustody;
use App\Domain\Support\DomainException;
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

        // Active orders that have collected specimens AND an assigned laboratory ready to dispatch
        $pricingService = app(\App\Domain\Logistics\LogisticsPricingService::class);
        $readyToDispatchOrders = ReferralOrder::where('requesting_organization_id', $org->id)
            ->whereNotNull('laboratory_id')
            ->where('status', '!=', OrderStatus::Matching)
            ->whereHas('specimens', fn ($q) => $q->where('status', SpecimenStatus::Collected))
            ->with(['specimens' => fn ($q) => $q->where('status', SpecimenStatus::Collected), 'laboratory.region', 'requestingOrganization.region'])
            ->get();

        $readyOrdersData = $readyToDispatchOrders->mapWithKeys(function ($o) use ($org, $pricingService) {
            $suggestedFee = ($o->logistics_charge !== null && $o->logistics_charge !== '')
                ? $o->logistics_charge
                : ($o->collection_method === 'walk_in' ? '0.00' : number_format($pricingService->calculateDispatchCharge($org, $o->laboratory), 2, '.', ''));
            $isSameRegion = ($org->service_region_id && $o->laboratory?->service_region_id)
                ? ($org->service_region_id === $o->laboratory->service_region_id)
                : false;
            return [
                $o->id => [
                    'id' => $o->id,
                    'reference' => $o->reference,
                    'lab_name' => $o->laboratory?->name ?? 'Partner Laboratory',
                    'lab_location' => ($o->laboratory?->city ?? 'Accra') . ($o->laboratory?->region ? ' (' . $o->laboratory->region->name . ')' : ''),
                    'tube_count' => $o->specimens->count(),
                    'is_same_region' => $isSameRegion,
                    'logistics_charge' => $suggestedFee,
                ],
            ];
        });

        return view('hospital.specimens.index', [
            'organization' => $org,
            'specimens' => $specimens,
            'metrics' => $metrics,
            'currentStatus' => $statusFilter,
            'readyToDispatchOrders' => $readyToDispatchOrders,
            'readyOrdersData' => $readyOrdersData,
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
            'logistics_charge' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = ReferralOrder::where('requesting_organization_id', $org->id)->findOrFail($validated['order_id']);

        if (! $order->laboratory_id || $order->status === OrderStatus::Matching) {
            return redirect()->back()->with('error', "Cannot dispatch order {$order->reference}: It is an open broadcast referral currently awaiting laboratory acceptance. A partner laboratory must accept or claim the order before courier dispatch.");
        }

        // Finalize logistics dispatch charge entered at specimen handover
        if ($order->collection_method === 'walk_in') {
            $order->logistics_charge = '0.00';
        } elseif (isset($validated['logistics_charge']) && $validated['logistics_charge'] !== '') {
            $order->logistics_charge = number_format((float) $validated['logistics_charge'], 2, '.', '');
        } elseif (! $order->logistics_charge) {
            $pricingService = app(\App\Domain\Logistics\LogisticsPricingService::class);
            $order->logistics_charge = number_format($pricingService->calculateDispatchCharge($org, $order->laboratory), 2, '.', '');
        }
        $order->save();

        // Recalculate financial breakdown & ledgers
        app(\App\Domain\Finance\FinancialCalculator::class)->calculate($order);

        try {
            $this->custody->dispatch($order, [
                'carrier_name' => $validated['carrier_name'],
                'tracking_reference' => $validated['tracking_reference'],
                'pickup_location' => $validated['pickup_location'] ?? 'Hospital Central Specimen Handover Point',
                'notes' => $validated['notes'] ?? null,
            ], $actor);

            $chargeNote = ($order->collection_method === 'courier' && $order->logistics_charge)
                ? " Logistics transport charge: GHS {$order->logistics_charge}."
                : '';

            return redirect()->back()->with('success', "Order {$order->reference} specimens dispatched to {$order->laboratory?->name} via {$validated['carrier_name']} (Tracking #{$validated['tracking_reference']}).{$chargeNote}");
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
