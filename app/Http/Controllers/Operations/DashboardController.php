<?php

namespace App\Http\Controllers\Operations;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\ReferralOrder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index()
    {
        $org = $this->current->organization();

        $networkOrders = ReferralOrder::with(['requestingOrganization', 'laboratory', 'items.test'])
            ->latest('id')
            ->take(10)
            ->get();

        $counts = [
            'total_orders' => ReferralOrder::count(),
            'active_hospitals' => Organization::ofType(\App\Domain\Organizations\Enums\OrganizationType::Hospital)->active()->count(),
            'verified_labs' => Organization::ofType(\App\Domain\Organizations\Enums\OrganizationType::Laboratory)->active()->count(),
            'corporate_clients' => Organization::ofType(\App\Domain\Organizations\Enums\OrganizationType::Corporate)->active()->count(),
        ];

        $hospitals = Organization::ofType(\App\Domain\Organizations\Enums\OrganizationType::Hospital)
            ->with(['region', 'branches', 'departments', 'memberships.user'])
            ->withCount(['branches'])
            ->get();

        $laboratories = Organization::ofType(\App\Domain\Organizations\Enums\OrganizationType::Laboratory)
            ->with([
                'region',
                'laboratoryProfile',
                'qualifications' => fn ($q) => $q->valid(),
                'serviceRegions',
                'catalogueEntries.test.specialty',
                'memberships.user',
            ])
            ->withCount(['catalogueEntries'])
            ->get();

        $corporateClients = Organization::ofType(\App\Domain\Organizations\Enums\OrganizationType::Corporate)
            ->with([
                'region',
                'screeningPrograms.package',
                'screeningPrograms.laboratory',
                'screeningPrograms.participants',
                'employeeRecords',
                'memberships.user',
            ])
            ->withCount(['screeningPrograms', 'employeeRecords'])
            ->get();

        $allOrders = ReferralOrder::with([
            'requestingOrganization.region',
            'laboratory.region',
            'branch',
            'items.test',
            'specimens',
            'invoices',
        ])->latest('id')->get();

        $recentAudits = AuditLog::with('actorOrganization')->latest('id')->take(6)->get();

        return view('operations.dashboard', [
            'organization' => $org,
            'networkOrders' => $networkOrders,
            'allOrders' => $allOrders,
            'counts' => $counts,
            'recentAudits' => $recentAudits,
            'hospitals' => $hospitals,
            'laboratories' => $laboratories,
            'corporateClients' => $corporateClients,
        ]);
    }
}
