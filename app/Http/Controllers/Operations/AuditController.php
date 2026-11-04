<?php

namespace App\Http\Controllers\Operations;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();

        $query = AuditLog::with(['actor', 'actorOrganization'])->latest('id');

        if ($action = $request->query('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        $logs = $query->paginate(20);
        $brokenId = AuditLogger::verifyChain();

        return view('operations.audit', [
            'organization' => $org,
            'logs' => $logs,
            'isChainValid' => $brokenId === null,
            'brokenRecordId' => $brokenId,
        ]);
    }
}
