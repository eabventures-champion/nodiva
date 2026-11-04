<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hospital;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();
        $statusFilter = $request->query('status');

        $query = Invoice::where('organization_id', $org->id)
            ->with(['order.laboratory', 'items']);

        if ($statusFilter && in_array($statusFilter, ['issued', 'paid', 'partially_paid', 'cancelled'])) {
            $query->where('status', $statusFilter);
        }

        $invoices = $query->latest('id')->paginate(15)->withQueryString();

        $allInvoices = Invoice::where('organization_id', $org->id)->get();
        $metrics = [
            'total_invoiced' => $allInvoices->sum('total'),
            'total_paid' => $allInvoices->sum('amount_paid'),
            'balance_due' => $allInvoices->sum('balance_due'),
            'count_outstanding' => $allInvoices->whereIn('status', ['issued', 'partially_paid'])->count(),
        ];

        return view('hospital.invoices.index', [
            'organization' => $org,
            'invoices' => $invoices,
            'metrics' => $metrics,
            'currentStatus' => $statusFilter,
        ]);
    }

    public function show(Invoice $invoice)
    {
        $org = $this->current->organization();

        if ($invoice->organization_id !== $org->id) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $invoice->load(['order.laboratory', 'order.patient', 'items', 'allocations.payment']);

        return view('hospital.invoices.show', [
            'organization' => $org,
            'invoice' => $invoice,
        ]);
    }
}
