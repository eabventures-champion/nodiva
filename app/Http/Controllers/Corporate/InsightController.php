<?php

declare(strict_types=1);

namespace App\Http\Controllers\Corporate;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\EmployeeRecord;
use App\Models\ProgramParticipant;
use App\Models\ScreeningProgram;
use Illuminate\Http\Request;

class InsightController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();

        $totalEmployees = EmployeeRecord::where('organization_id', $org->id)->count();
        $totalParticipants = ProgramParticipant::whereHas('program', fn ($q) => $q->where('organization_id', $org->id))->count();
        $completedScreenings = ProgramParticipant::whereHas('program', fn ($q) => $q->where('organization_id', $org->id))->where('status', 'completed')->count();

        $participationRate = $totalEmployees > 0 ? round(($totalParticipants / $totalEmployees) * 100, 1) : 0;
        $completionRate = $totalParticipants > 0 ? round(($completedScreenings / $totalParticipants) * 100, 1) : 0;

        // Department breakdown with small-cell privacy rule (< 5 suppressed)
        $departmentStats = EmployeeRecord::where('organization_id', $org->id)
            ->selectRaw('department, count(*) as count')
            ->groupBy('department')
            ->get()
            ->map(function ($row) {
                return [
                    'department' => $row->department ?? 'General Workforce',
                    'count' => $row->count < 5 ? '< 5 (Suppressed for Privacy)' : $row->count,
                    'is_suppressed' => $row->count < 5,
                ];
            });

        $programs = ScreeningProgram::where('organization_id', $org->id)->with('package')->get();

        return view('corporate.insights.index', [
            'organization' => $org,
            'totalEmployees' => $totalEmployees,
            'totalParticipants' => $totalParticipants,
            'completedScreenings' => $completedScreenings,
            'participationRate' => $participationRate,
            'completionRate' => $completionRate,
            'departmentStats' => $departmentStats,
            'programs' => $programs,
        ]);
    }
}
