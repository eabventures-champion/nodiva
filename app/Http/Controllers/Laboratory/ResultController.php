<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratory;

use App\Domain\Finance\InvoiceService;
use App\Domain\Organizations\Actor;
use App\Domain\Organizations\CurrentOrganization;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Domain\Referrals\OrderWorkflow;
use App\Domain\Results\Enums\ResultStatus;
use App\Domain\Results\ResultWorkflow;
use App\Http\Controllers\Controller;
use App\Models\DiagnosticResult;
use App\Models\ReferralOrder;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly ResultWorkflow $resultWorkflow,
        private readonly OrderWorkflow $orderWorkflow,
        private readonly InvoiceService $invoiceService,
    ) {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();
        $tab = $request->query('tab', 'processing');

        $query = ReferralOrder::where('laboratory_id', $org->id)
            ->with(['requestingOrganization', 'items.test.specialty', 'specimens.type', 'results.items', 'results.releasedBy']);

        if ($tab === 'processing') {
            $query->whereIn('status', [OrderStatus::Processing, OrderStatus::SpecimenReceived]);
        } elseif ($tab === 'review') {
            $query->where('status', OrderStatus::AwaitingResultReview);
        } elseif ($tab === 'released') {
            $query->whereIn('status', [OrderStatus::ResultReleased, OrderStatus::Completed]);
        }

        $orders = $query->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'processing' => ReferralOrder::where('laboratory_id', $org->id)->whereIn('status', [OrderStatus::Processing, OrderStatus::SpecimenReceived])->count(),
            'review' => ReferralOrder::where('laboratory_id', $org->id)->where('status', OrderStatus::AwaitingResultReview)->count(),
            'released' => ReferralOrder::where('laboratory_id', $org->id)->whereIn('status', [OrderStatus::ResultReleased, OrderStatus::Completed])->count(),
        ];

        $actor = Actor::for($request->user(), $org);
        $canRelease = $actor->can('results.release');

        $pathologist = $org->memberships()
            ->whereHas('role', fn ($q) => $q->where('key', 'lab_pathologist')->orWhere('permissions', 'like', '%results.release%'))
            ->with('user')
            ->first()
            ?->user;

        return view('laboratory.results.index', [
            'organization' => $org,
            'orders' => $orders,
            'metrics' => $metrics,
            'currentTab' => $tab,
            'canRelease' => $canRelease,
            'pathologist' => $pathologist,
        ]);
    }

    public function create(Request $request, ReferralOrder $order)
    {
        $org = $this->current->organization();

        if ($order->laboratory_id !== $org->id) {
            abort(403, 'Order not assigned to this laboratory.');
        }

        $order->load([
            'requestingOrganization',
            'items.test.specialty',
            'specimens.type',
            'results.items',
            'results.enteredBy',
            'results.releasedBy',
        ]);

        $actor = Actor::for($request->user(), $org);
        $canRelease = $actor->can('results.release');

        return view('laboratory.results.create', [
            'organization' => $org,
            'order' => $order,
            'canRelease' => $canRelease,
        ]);
    }

    public function store(Request $request, ReferralOrder $order)
    {
        $org = $this->current->organization();
        $actor = Actor::for($request->user(), $org);

        if ($order->laboratory_id !== $org->id) {
            abort(403, 'Unauthorized order action.');
        }

        $validated = $request->validate([
            'interpretation' => ['nullable', 'string'],
            'is_critical' => ['nullable', 'boolean'],
            'submit_for_review' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.analyte' => ['required', 'string', 'max:150'],
            'items.*.value' => ['required', 'string', 'max:100'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.reference_range' => ['nullable', 'string', 'max:100'],
            'items.*.flag' => ['required', 'in:normal,low,high,abnormal,critical'],
            'items.*.loinc_code' => ['nullable', 'string', 'max:50'],
            'items.*.referral_order_item_id' => ['nullable', 'exists:referral_order_items,id'],
        ]);

        $result = $this->resultWorkflow->enterResult($order, [
            'interpretation' => $validated['interpretation'] ?? null,
            'is_critical' => $request->boolean('is_critical'),
            'items' => $validated['items'],
        ], $actor);

        if ($request->boolean('submit_for_review')) {
            $this->resultWorkflow->submitForReview($result, $actor);
            return redirect()->route('lab.results.index', ['tab' => 'review'])
                ->with('success', "Analytical result for {$order->reference} submitted for pathologist review.");
        }

        return redirect()->route('lab.results.index', ['tab' => 'processing'])
            ->with('success', "Draft result for {$order->reference} saved.");
    }

    public function release(Request $request, DiagnosticResult $result)
    {
        $org = $this->current->organization();
        $actor = Actor::for($request->user(), $org);

        if ($result->order->laboratory_id !== $org->id) {
            abort(403, 'Unauthorized result release.');
        }

        if (! $actor->can('results.release')) {
            return redirect()->route('lab.results.index', ['tab' => 'review'])
                ->with('error', "Dual-Control Access Restricted: Your account ({$request->user()->name} — {$request->user()->job_title}) is provisioned for Analytical Testing & Entry. Under ISO 15189 and Allied Health Professions Council (AHPC) standards, only an accredited Consultant Pathologist (e.g. Prof. Kwame Boateng, FRCPath — pathologist@synlab-gh.example) has the clinical privilege to sign and release diagnostic reports.");
        }

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->resultWorkflow->releaseResult($result, $actor, $validated['review_notes'] ?? 'Authorized by Consultant Pathologist');
        } catch (\DomainException $e) {
            return redirect()->route('lab.results.index', ['tab' => 'review'])
                ->with('error', 'Clinical release restricted: ' . $e->getMessage());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->route('lab.results.index', ['tab' => 'review'])
                ->with('error', 'Dual-control authorization error: ' . $e->getMessage());
        }

        $order = $result->order->fresh();
        if ($order->status !== OrderStatus::Completed) {
            $this->orderWorkflow->transition($order, OrderStatus::Completed, $actor, 'Diagnostic report verified, released and order completed');
            $order->save();
        }

        // Generate invoice if financials calculated
        if ($order->financials && ! $order->invoice) {
            try {
                $this->invoiceService->createForOrder($order, $actor);
            } catch (\Throwable $e) {
                // Non-blocking invoice generation
            }
        }

        return redirect()->route('lab.results.index', ['tab' => 'released'])
            ->with('success', "Result for order {$order->reference} successfully verified and released to {$order->requestingOrganization?->name}.");
    }
}
