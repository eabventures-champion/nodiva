<?php

declare(strict_types=1);

namespace App\Http\Controllers\Corporate;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\EmployeeRecord;
use App\Models\Organization;
use App\Models\ProgramParticipant;
use App\Models\ScreeningPackage;
use App\Models\ScreeningProgram;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index()
    {
        $org = $this->current->organization();

        $programs = ScreeningProgram::where('organization_id', $org->id)
            ->with(['laboratory', 'package', 'participants'])
            ->latest('id')
            ->get();

        $counts = [
            'active_programs' => ScreeningProgram::where('organization_id', $org->id)->where('status', 'active')->count(),
            'total_participants' => EmployeeRecord::where('organization_id', $org->id)->count(),
            'completed_screenings' => ProgramParticipant::whereHas('program', fn ($q) => $q->where('organization_id', $org->id))->where('status', 'completed')->count(),
        ];

        $packages = ScreeningPackage::where('is_active', true)->with('tests')->get();
        $laboratories = Organization::where('type', 'laboratory')->where('status', 'active')->get();

        return view('corporate.dashboard', [
            'organization' => $org,
            'programs' => $programs,
            'counts' => $counts,
            'packages' => $packages,
            'laboratories' => $laboratories,
        ]);
    }

    public function storeProgram(Request $request)
    {
        $org = $this->current->organization();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'screening_package_id' => ['required', 'exists:screening_packages,id'],
            'laboratory_id' => ['required', 'exists:organizations,id'],
            'purpose' => ['required', 'string', 'in:pre_employment,annual_medical,occupational_health,risk_screening'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $program = ScreeningProgram::create([
            'organization_id' => $org->id,
            'screening_package_id' => $validated['screening_package_id'],
            'laboratory_id' => $validated['laboratory_id'],
            'name' => $validated['name'],
            'purpose' => $validated['purpose'],
            'starts_on' => $validated['starts_on'],
            'ends_on' => $validated['ends_on'],
            'budget' => $validated['budget'] ?? 0,
            'status' => 'active',
            'currency' => 'GHS',
            'pricing_model' => 'per_participant',
            'unit_price' => 150.00,
        ]);

        return redirect()->back()->with('success', "Screening program '{$program->name}' successfully scheduled and active.");
    }
}
