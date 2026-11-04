<?php

namespace App\Http\Controllers\Hospital;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
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

        $activeOrders = ReferralOrder::where('requesting_organization_id', $org->id)
            ->with(['laboratory', 'items.test', 'currentResult'])
            ->latest('id')
            ->take(10)
            ->get();

        $counts = [
            'total' => ReferralOrder::where('requesting_organization_id', $org->id)->count(),
            'in_transit' => ReferralOrder::where('requesting_organization_id', $org->id)->where('status', 'in_transit')->count(),
            'processing' => ReferralOrder::where('requesting_organization_id', $org->id)->where('status', 'processing')->count(),
            'completed' => ReferralOrder::where('requesting_organization_id', $org->id)->where('status', 'completed')->count(),
        ];

        return view('hospital.dashboard', [
            'organization' => $org,
            'activeOrders' => $activeOrders,
            'counts' => $counts,
        ]);
    }
}
