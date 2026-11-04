<?php

namespace App\Http\Controllers\Operations;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use App\Models\Specimen;
use Illuminate\Http\Request;

class LogisticsController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index()
    {
        $org = $this->current->organization();

        $dispatches = Dispatch::with(['order.requestingOrganization', 'order.laboratory', 'order.specimens.type'])
            ->latest('id')
            ->paginate(15);

        $specimens = Specimen::with(['order.requestingOrganization', 'order.laboratory', 'type', 'custodyEvents'])
            ->latest('id')
            ->take(10)
            ->get();

        $counts = [
            'in_transit' => Dispatch::where('status', 'in_transit')->count(),
            'scheduled' => Dispatch::where('status', 'scheduled')->count(),
            'delivered' => Dispatch::where('status', 'delivered')->count(),
            'total_specimens' => Specimen::count(),
        ];

        return view('operations.logistics', [
            'organization' => $org,
            'dispatches' => $dispatches,
            'specimens' => $specimens,
            'counts' => $counts,
        ]);
    }
}
