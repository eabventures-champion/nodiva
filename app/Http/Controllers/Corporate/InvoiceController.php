<?php

declare(strict_types=1);

namespace App\Http\Controllers\Corporate;

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

        $query = Invoice::where('organization_id', $org->id)->with(['program.laboratory', 'items']);

        if ($statusFilter) {
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

        return view('corporate.invoices.index', [
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
            abort(403, 'Unauthorized access to corporate invoice.');
        }

        $invoice->load(['program.laboratory', 'items', 'allocations.payment']);

        return view('corporate.invoices.show', [
            'organization' => $org,
            'invoice' => $invoice,
        ]);
    }
}
