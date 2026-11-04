<?php

declare(strict_types=1);

namespace App\Http\Controllers\Corporate;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\CollectionSession;
use App\Models\ScreeningProgram;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();

        $sessions = CollectionSession::whereHas('program', function ($q) use ($org) {
            $q->where('organization_id', $org->id);
        })->with(['program.laboratory', 'appointments'])->latest('starts_at')->paginate(15);

        $programs = ScreeningProgram::where('organization_id', $org->id)->get();

        $metrics = [
            'total_sessions' => CollectionSession::whereHas('program', fn ($q) => $q->where('organization_id', $org->id))->count(),
            'total_capacity' => CollectionSession::whereHas('program', fn ($q) => $q->where('organization_id', $org->id))->sum('capacity'),
            'total_booked' => CollectionSession::whereHas('program', fn ($q) => $q->where('organization_id', $org->id))->sum('booked_count'),
        ];

        return view('corporate.sessions.index', [
            'organization' => $org,
            'sessions' => $sessions,
            'programs' => $programs,
            'metrics' => $metrics,
        ]);
    }

    public function store(Request $request)
    {
        $org = $this->current->organization();

        $validated = $request->validate([
            'screening_program_id' => ['required', 'exists:screening_programs,id'],
            'location' => ['required', 'string', 'max:150'],
            'mode' => ['required', 'in:mobile_onsite,partner_walk_in,onsite_mobile,facility'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['required', 'integer', 'min:5', 'max:500'],
        ]);

        $program = ScreeningProgram::where('organization_id', $org->id)->findOrFail($validated['screening_program_id']);

        CollectionSession::create([
            'screening_program_id' => $program->id,
            'location' => $validated['location'],
            'mode' => $validated['mode'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'capacity' => $validated['capacity'],
        ]);

        return redirect()->back()->with('success', 'Mobile phlebotomy & collection session scheduled.');
    }
}
