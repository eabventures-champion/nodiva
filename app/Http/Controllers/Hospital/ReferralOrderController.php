<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hospital;

use App\Domain\Network\MatchingEngine;
use App\Domain\Organizations\Actor;
use App\Domain\Organizations\CurrentOrganization;
use App\Domain\Organizations\Enums\OrganizationType;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Domain\Referrals\OrderWorkflow;
use App\Domain\Specimens\SpecimenCustody;
use App\Domain\Support\ReferenceGenerator;
use App\Http\Controllers\Controller;
use App\Mail\ReferralRequestNotificationMail;
use App\Models\DiagnosticTest;
use App\Models\OrderAssignment;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\ReferralOrder;
use App\Models\ReferralOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReferralOrderController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly OrderWorkflow $workflow,
        private readonly MatchingEngine $matching,
        private readonly SpecimenCustody $specimenCustody,
    ) {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();
        $statusFilter = $request->query('status');

        $query = ReferralOrder::where('requesting_organization_id', $org->id)
            ->with(['laboratory', 'items.test.specialty', 'currentResult', 'specimens']);

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $orders = $query->latest('id')->paginate(15);

        return view('hospital.orders.index', [
            'organization' => $org,
            'orders' => $orders,
            'currentStatus' => $statusFilter,
        ]);
    }

    public function create(Request $request)
    {
        $org = $this->current->organization();

        // Load active diagnostic tests with their specialties and specimen types
        $tests = DiagnosticTest::where('is_active', true)
            ->with(['specialty', 'specimenType', 'catalogueEntries.laboratory'])
            ->orderBy('name')
            ->get();

        // Load active verified laboratories with catalogue entries (must have valid qualifications verified by Ops)
        $laboratories = Organization::ofType(OrganizationType::Laboratory)
            ->where('status', 'active')
            ->withVerifiedQualifications()
            ->with(['qualifications' => fn ($q) => $q->valid(), 'catalogueEntries.test', 'region', 'serviceRegions', 'laboratoryProfile'])
            ->orderBy('name')
            ->get();

        $pricingService = app(\App\Domain\Logistics\LogisticsPricingService::class);

        $labCoverage = $laboratories->mapWithKeys(function ($lab) use ($org, $pricingService) {
            $dispatchFee = $pricingService->calculateDispatchCharge($org, $lab);
            $isSameRegion = ($org && $lab->service_region_id && $org->service_region_id)
                ? ($lab->service_region_id === $org->service_region_id)
                : false;

            return [
                (string)$lab->id => [
                    'id' => $lab->id,
                    'name' => $lab->name,
                    'city' => $lab->city ?? 'Accra',
                    'region_name' => $lab->region?->name ?? 'Greater Accra',
                    'is_same_region' => $isSameRegion,
                    'dispatch_charge' => number_format($dispatchFee, 2, '.', ''),
                    'reliability_score' => (string) ($lab->laboratoryProfile?->reliability_score ?? '95.00'),
                    'available_test_ids' => $lab->catalogueEntries->where('is_available', true)->pluck('diagnostic_test_id')->map(fn($id) => (string)$id)->values()->all(),
                ]
            ];
        });

        // Load existing patients of this hospital
        $patients = $org
            ? Patient::where('organization_id', $org->id)->latest('id')->take(50)->get()
            : collect();

        $preselectedTestId = $request->query('test_id');

        return view('hospital.orders.create', [
            'organization' => $org,
            'tests' => $tests,
            'patients' => $patients,
            'laboratories' => $laboratories,
            'labCoverage' => $labCoverage,
            'preselectedTestId' => $preselectedTestId ? (int) $preselectedTestId : null,
        ]);
    }

    public function store(Request $request)
    {
        $org = $this->current->organization();
        $user = $request->user();
        $actor = Actor::for($user, $org);

        $validated = $request->validate([
            'patient_mode' => ['required', 'in:new,existing'],
            'existing_patient_id' => ['nullable', 'required_if:patient_mode,existing', 'exists:patients,id'],
            'first_name' => ['nullable', 'required_if:patient_mode,new', 'string', 'max:100'],
            'last_name' => ['nullable', 'required_if:patient_mode,new', 'string', 'max:100'],
            'medical_record_number' => ['nullable', 'required_if:patient_mode,new', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'required_if:patient_mode,new', 'date', 'before:today'],
            'sex' => ['nullable', 'required_if:patient_mode,new', 'in:male,female'],
            'phone' => ['nullable', 'string', 'max:30'],
            'consent_confirmed' => ['required', 'accepted'],
            'clinical_indication' => ['required', 'string', 'max:255'],
            'clinical_notes' => ['nullable', 'string'],
            'priority' => ['required', 'in:routine,urgent'],
            'collection_method' => ['required', 'in:courier,walk_in'],
            'logistics_charge' => ['nullable', 'numeric', 'min:0'],
            'routing_mode' => ['required', 'in:broadcast,direct,automated'],
            'specific_laboratory_id' => ['nullable', 'required_if:routing_mode,direct', 'exists:organizations,id'],
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['required', 'exists:diagnostic_tests,id'],
        ]);

        $order = DB::transaction(function () use ($validated, $org, $user, $actor) {
            // 1. Resolve or create patient
            if ($validated['patient_mode'] === 'existing') {
                $patient = Patient::where('organization_id', $org->id)->findOrFail($validated['existing_patient_id']);
            } else {
                $mrn = trim($validated['medical_record_number']);
                $mrnHash = Patient::mrnHash($org->uuid, $mrn);

                $patient = Patient::firstOrNew(['organization_id' => $org->id, 'mrn_hash' => $mrnHash]);
                $patient->medical_record_number = $mrn;
                $patient->first_name = $validated['first_name'];
                $patient->last_name = $validated['last_name'];
                $patient->date_of_birth = $validated['date_of_birth'];
                $patient->sex = $validated['sex'];
                $patient->phone = $validated['phone'] ?? '+233000000000';
                $patient->created_by = $user->id;
                if (! $patient->reference) {
                    $patient->reference = ReferenceGenerator::make('PT', 'patients', 'reference');
                }
                $patient->save();
            }

            // Ensure patient consent record exists
            if (! $patient->hasActiveConsent('external_referral_testing')) {
                PatientConsent::create([
                    'patient_id' => $patient->id,
                    'organization_id' => $org->id,
                    'purpose' => 'external_referral_testing',
                    'method' => 'written',
                    'granted_at' => now(),
                    'notes' => 'Patient signed standard referral consent form for diagnostic processing.',
                    'recorded_by' => $user->id,
                ]);
            }

            // 2. Create Draft Referral Order
            $order = new ReferralOrder();
            $order->reference = ReferenceGenerator::make('RF', 'referral_orders', 'reference');
            $order->requesting_organization_id = $org->id;
            $order->patient_id = $patient->id;
            $order->created_by = $user->id;
            $order->status = OrderStatus::Draft;
            $order->priority = $validated['priority'];
            $order->collection_method = $validated['collection_method'];
            $routingMode = $validated['routing_mode'];
            $pricingService = app(\App\Domain\Logistics\LogisticsPricingService::class);

            if ($validated['collection_method'] === 'walk_in') {
                $order->logistics_charge = '0.00';
            } elseif ($routingMode === 'broadcast') {
                // For open broadcast with courier, dispatch charge is determined after a particular lab accepts
                $order->logistics_charge = null;
            } else {
                $order->logistics_charge = isset($validated['logistics_charge']) && $validated['logistics_charge'] !== ''
                    ? number_format((float) $validated['logistics_charge'], 2, '.', '')
                    : null;
            }

            $order->clinical_indication = $validated['clinical_indication'];
            $order->clinical_notes = $validated['clinical_notes'] ?? null;
            $order->currency = 'GHS';
            $order->save();

            // 3. Attach Referral Order Items
            $diagnosticTests = DiagnosticTest::whereIn('id', $validated['test_ids'])->with('specimenType')->get();
            $neededSpecimenTypeIds = [];

            foreach ($diagnosticTests as $test) {
                ReferralOrderItem::create([
                    'referral_order_id' => $order->id,
                    'diagnostic_test_id' => $test->id,
                ]);

                if ($test->specimen_type_id) {
                    $neededSpecimenTypeIds[$test->specimen_type_id] = true;
                }
            }

            // 4. Handle Routing & Submission
            if ($routingMode === 'direct') {
                $specificLab = Organization::ofType(OrganizationType::Laboratory)
                    ->where('status', 'active')
                    ->withVerifiedQualifications()
                    ->findOrFail($validated['specific_laboratory_id']);

                if ($validated['collection_method'] === 'courier') {
                    $order->logistics_charge = isset($validated['logistics_charge']) && $validated['logistics_charge'] !== ''
                        ? number_format((float) $validated['logistics_charge'], 2, '.', '')
                        : number_format($pricingService->calculateDispatchCharge($org, $specificLab), 2, '.', '');
                    $order->save();
                }

                $order = $this->workflow->submit($order, $actor);
                $order = $this->workflow->assignLaboratory(
                    $order,
                    $specificLab,
                    'manual',
                    ['Direct referral requested by referring hospital clinician'],
                    null,
                    $actor
                );
            } elseif ($routingMode === 'automated') {
                $matches = $this->matching->match($order);
                $order = $this->workflow->submit($order, $actor);

                if ($matches->isNotEmpty()) {
                    $topMatch = $matches->first();
                    $lab = $topMatch['laboratory'];
                    if ($validated['collection_method'] === 'courier') {
                        $order->logistics_charge = number_format((float) ($topMatch['dispatch_charge'] ?? $pricingService->calculateDispatchCharge($org, $lab)), 2, '.', '');
                        $order->save();
                    }
                    $order = $this->workflow->assignLaboratory(
                        $order,
                        $lab,
                        'recommended',
                        $topMatch['rationale'] ?? ['Matched via automated capacity algorithm'],
                        null,
                        $actor
                    );
                } else {
                    $fallbackLab = Organization::ofType(OrganizationType::Laboratory)
                        ->where('status', 'active')
                        ->withVerifiedQualifications()
                        ->first();
                    if ($fallbackLab) {
                        if ($validated['collection_method'] === 'courier') {
                            $order->logistics_charge = number_format($pricingService->calculateDispatchCharge($org, $fallbackLab), 2, '.', '');
                            $order->save();
                        }
                        $order = $this->workflow->assignLaboratory(
                            $order,
                            $fallbackLab,
                            'recommended',
                            ['Auto-assigned to regional reference laboratory'],
                            null,
                            $actor
                        );
                    }
                }
            } else {
                // Broadcast mode: Open referral request to all qualified labs ("First to accept")
                $order = $this->workflow->submit($order, $actor);

                $candidateLabs = Organization::ofType(OrganizationType::Laboratory)
                    ->where('status', 'active')
                    ->withVerifiedQualifications()
                    ->get();

                foreach ($candidateLabs as $candidateLab) {
                    OrderAssignment::query()->updateOrCreate(
                        [
                            'referral_order_id' => $order->id,
                            'laboratory_id' => $candidateLab->id,
                        ],
                        [
                            'status' => 'offered',
                            'match_score' => 100.0,
                            'selection_mode' => 'broadcast',
                            'rationale' => ['Open referral broadcast to accredited partner laboratories (first to accept claims order)'],
                            'assigned_by' => $user->id,
                        ]
                    );
                }
            }

            // 5. Register specimen containers for this order
            foreach (array_keys($neededSpecimenTypeIds) as $specimenTypeId) {
                $this->specimenCustody->register($order, (int) $specimenTypeId, $actor);
            }

            return $order;
        });

        // 6. Send Email Notifications to Laboratory Partners
        try {
            $order->load(['requestingOrganization', 'items.test.specialty', 'items.test.specimenType']);

            if ($validated['routing_mode'] === 'broadcast') {
                $recipientLabs = Organization::ofType(OrganizationType::Laboratory)
                    ->where('status', 'active')
                    ->withVerifiedQualifications()
                    ->with('memberships.user')
                    ->get();

                foreach ($recipientLabs as $lab) {
                    $emails = collect([$lab->email])
                        ->merge($lab->memberships->where('status', 'active')->pluck('user.email'))
                        ->filter()
                        ->unique()
                        ->all();

                    if (! empty($emails)) {
                        Mail::to($emails)->send(new ReferralRequestNotificationMail($order, $lab, isBroadcast: true));
                    }
                }
            } else {
                if ($order->laboratory) {
                    $lab = $order->laboratory;
                    $lab->loadMissing('memberships.user');
                    $emails = collect([$lab->email])
                        ->merge($lab->memberships->where('status', 'active')->pluck('user.email'))
                        ->filter()
                        ->unique()
                        ->all();

                    if (! empty($emails)) {
                        Mail::to($emails)->send(new ReferralRequestNotificationMail($order, $lab, isBroadcast: false));
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Failed sending referral notification email: {$e->getMessage()}");
        }

        $targetDesc = match ($validated['routing_mode']) {
            'direct' => "assigned directly to {$order->laboratory?->name}",
            'automated' => "automatically matched and routed to {$order->laboratory?->name}",
            'broadcast' => "broadcast to all qualified partner laboratories (first to accept claims order)",
        };

        return redirect()->route('hospital.orders.show', $order)
            ->with('success', "Diagnostic referral order {$order->reference} successfully {$targetDesc}!");
    }

    public function show(ReferralOrder $order)
    {
        $org = $this->current->organization();

        // Enforce tenant boundary
        if ($order->requesting_organization_id !== $org->id) {
            abort(403, 'Unauthorized access to referral order.');
        }

        $order->load([
            'patient',
            'laboratory',
            'items.test.specialty',
            'items.test.specimenType',
            'specimens.type',
            'specimens.custodyEvents.actor',
            'dispatches.creator',
            'results' => fn ($q) => $q->where('status', \App\Domain\Results\Enums\ResultStatus::Released)->with(['items', 'releasedBy']),
            'events.actor',
            'financials',
        ]);

        return view('hospital.orders.show', [
            'organization' => $org,
            'order' => $order,
        ]);
    }
}
