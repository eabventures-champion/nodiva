<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratory;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\LaboratorySettlement;
use App\Models\OrderFinancial;
use App\Models\ReferralOrder;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();

        // Financial items for orders fulfilled by this laboratory
        $orderFinancials = OrderFinancial::whereHas('order', function ($q) use ($org) {
            $q->where('laboratory_id', $org->id);
        })->with(['order.requestingOrganization', 'order.items.test'])->latest('id')->paginate(15);

        $settlements = LaboratorySettlement::where('laboratory_id', $org->id)->latest('id')->get();

        $allFinancials = OrderFinancial::whereHas('order', function ($q) use ($org) {
            $q->where('laboratory_id', $org->id);
        })->get();

        $metrics = [
            'gross_service_value' => $allFinancials->sum('service_value'),
            'platform_fees' => $allFinancials->sum('platform_fee'),
            'laboratory_payable' => $allFinancials->sum('laboratory_payable'),
            'completed_orders_count' => ReferralOrder::where('laboratory_id', $org->id)->where('status', 'completed')->count(),
        ];

        return view('laboratory.settlements.index', [
            'organization' => $org,
            'orderFinancials' => $orderFinancials,
            'settlements' => $settlements,
            'metrics' => $metrics,
        ]);
    }
}
