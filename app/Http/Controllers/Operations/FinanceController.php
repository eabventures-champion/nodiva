<?php

namespace App\Http\Controllers\Operations;

use App\Domain\Audit\AuditLogger;
use App\Domain\Finance\Money;
use App\Domain\Finance\ReconciliationEngine;
use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReconciliationRun;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly ReconciliationEngine $reconcile,
        private readonly AuditLogger $audit
    ) {
    }

    public function index()
    {
        $org = $this->current->organization();

        $runs = ReconciliationRun::with('runner')->latest('id')->take(10)->get();
        $invoices = Invoice::with(['organization', 'order'])->latest('id')->take(10)->get();
        $payments = Payment::with('organization')->latest('id')->take(10)->get();

        $totals = [
            'total_invoiced' => Invoice::sum('total'),
            'total_paid' => Payment::where('status', 'confirmed')->sum('amount'),
            'outstanding' => Invoice::sum('balance_due'),
            'reconciliation_discrepancies' => ReconciliationRun::sum('discrepancy_count'),
        ];

        $feeSettings = [
            'platform_fee_percent' => (string) SystemSetting::get('finance.default_platform_fee_percent', '12.00'),
            'fee_bearer' => (string) SystemSetting::get('finance.default_fee_bearer', 'laboratory'),
            'courier_charge' => (string) SystemSetting::get('logistics.default_courier_charge', '35.00'),
        ];

        return view('operations.finance', [
            'organization' => $org,
            'runs' => $runs,
            'invoices' => $invoices,
            'payments' => $payments,
            'totals' => $totals,
            'feeSettings' => $feeSettings,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user->isCto() || $this->current->organization()->isPlatform())) {
            abort(403, 'Unauthorized. Platform tariff governance is restricted to Operations Network.');
        }

        $validated = $request->validate([
            'platform_fee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'fee_bearer' => ['required', 'in:laboratory,institution'],
        ]);

        $oldFee = SystemSetting::get('finance.default_platform_fee_percent', '12.00');
        $oldBearer = SystemSetting::get('finance.default_fee_bearer', 'laboratory');

        SystemSetting::put('finance.default_platform_fee_percent', number_format((float) $validated['platform_fee_percent'], 2, '.', ''), $user->id, 'Baseline platform fee percentage');
        SystemSetting::put('finance.default_fee_bearer', $validated['fee_bearer'], $user->id, 'Standard platform fee bearer model');

        $this->audit->log('platform.fee_settings_updated', $this->current->organization(), [
            'updated_by' => $user->name . ' (' . $user->email . ')',
            'old' => [
                'platform_fee_percent' => $oldFee,
                'fee_bearer' => $oldBearer,
            ],
            'new' => [
                'platform_fee_percent' => $validated['platform_fee_percent'],
                'fee_bearer' => $validated['fee_bearer'],
            ],
        ], 'success');

        return back()->with('success', "Platform fee policy successfully adjusted to {$validated['platform_fee_percent']}% (Fee Bearer: " . ucfirst($validated['fee_bearer']) . ").");
    }

    public function runReconciliation(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $actor = $this->current->actor();
        $run = $this->reconcile->run(
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date']),
            strtoupper($validated['currency']),
            $actor
        );

        return back()->with('success', "Reconciliation run completed. {$run->orders_checked} orders checked, {$run->discrepancy_count} discrepancies identified.");
    }
}
