<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hospital;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\DiagnosticSpecialty;
use App\Models\DiagnosticTest;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();
        $query = $request->query('q');
        $specialtyId = $request->query('specialty');

        $testsQuery = DiagnosticTest::where('is_active', true)
            ->with(['specialty', 'specimenType', 'catalogueEntries.laboratory']);

        if ($query) {
            $testsQuery->search($query);
        }

        if ($specialtyId) {
            $testsQuery->where('diagnostic_specialty_id', $specialtyId);
        }

        $tests = $testsQuery->orderBy('name')->paginate(12)->withQueryString();
        $specialties = DiagnosticSpecialty::orderBy('name')->get();

        return view('hospital.catalogue.index', [
            'organization' => $org,
            'tests' => $tests,
            'specialties' => $specialties,
            'currentSpecialty' => $specialtyId,
            'searchQuery' => $query,
        ]);
    }
}
