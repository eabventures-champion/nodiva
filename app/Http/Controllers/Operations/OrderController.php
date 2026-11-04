<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ReferralOrder;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function show(ReferralOrder $order): View
    {
        $org = $this->current->organization();

        $order->load([
            'requestingOrganization.region',
            'laboratory.region',
            'branch',
            'patient',
            'creator',
            'items.test.specialty',
            'assignments.laboratory',
            'events.actor',
            'specimens',
            'dispatches',
            'results.items',
            'results.releasedBy',
            'financials',
            'invoices.items',
        ]);

        $auditLogs = AuditLog::with(['actor', 'actorOrganization'])
            ->where(function ($q) use ($order) {
                $q->where(function ($sub) use ($order) {
                    $sub->where('auditable_type', 'ReferralOrder')
                        ->where('auditable_id', $order->id);
                })->orWhere('context->reference', $order->reference);
            })
            ->latest('id')
            ->get();

        return view('operations.orders.show', [
            'organization' => $org,
            'order' => $order,
            'auditLogs' => $auditLogs,
        ]);
    }
}
