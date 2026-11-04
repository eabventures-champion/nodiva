<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\DiagnosticSpecialty;
use App\Models\DiagnosticTest;
use App\Models\SpecimenType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $status = $request->query('status');

        $testsQuery = DiagnosticTest::query()
            ->with(['specialty', 'specimenType', 'catalogueEntries.laboratory'])
            ->withCount('catalogueEntries');

        if ($query) {
            $testsQuery->search($query);
        }

        if ($specialtyId) {
            $testsQuery->where('diagnostic_specialty_id', $specialtyId);
        }

        if ($status === 'active') {
            $testsQuery->where('is_active', true);
        } elseif ($status === 'inactive') {
            $testsQuery->where('is_active', false);
        }

        $tests = $testsQuery->orderBy('name')->paginate(15)->withQueryString();
        $specialties = DiagnosticSpecialty::withCount('tests')->orderBy('name')->get();
        $specimenTypes = SpecimenType::withCount('tests')->orderBy('name')->get();

        return view('operations.catalogue.index', [
            'organization' => $org,
            'tests' => $tests,
            'specialties' => $specialties,
            'specimenTypes' => $specimenTypes,
            'currentSpecialty' => $specialtyId,
            'currentStatus' => $status,
            'searchQuery' => $query,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->filled('new_specialty_name')) {
            $name = trim($request->input('new_specialty_name'));
            $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9_]/', '', str_replace([' ', '&', '-', '/'], '_', $name)), 0, 32));
            $code = $baseCode ?: 'SPECIALTY_' . time();
            $counter = 1;
            while (DiagnosticSpecialty::where('code', $code)->exists()) {
                $code = substr($baseCode, 0, 28) . '_' . $counter++;
            }
            $specialty = DiagnosticSpecialty::firstOrCreate(
                ['name' => $name],
                ['code' => $code]
            );
            $request->merge(['diagnostic_specialty_id' => $specialty->id]);
        }

        if ($request->filled('new_specimen_container')) {
            $container = trim($request->input('new_specimen_container'));
            $name = $request->input('new_specimen_name') ?: $container;
            $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9_]/', '', str_replace([' ', '&', '-', '/', '(', ')'], '_', $container)), 0, 32));
            $code = $baseCode ?: 'SPECIMEN_' . time();
            $counter = 1;
            while (SpecimenType::where('code', $code)->exists()) {
                $code = substr($baseCode, 0, 28) . '_' . $counter++;
            }
            $specimenType = SpecimenType::firstOrCreate(
                ['container' => $container],
                [
                    'name' => $name,
                    'code' => $code,
                    'transport_requirements' => $request->input('new_specimen_transport', 'ambient'),
                    'stability_hours' => $request->input('new_specimen_stability'),
                ]
            );
            $request->merge(['specimen_type_id' => $specimenType->id]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:diagnostic_tests,code', 'regex:/^[A-Z0-9_]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'diagnostic_specialty_id' => ['required', 'exists:diagnostic_specialties,id'],
            'specimen_type_id' => ['required', 'exists:specimen_types,id'],
            'loinc_code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'patient_preparation' => ['nullable', 'string'],
            'requires_clinical_notes' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'The test code must contain only uppercase letters, numbers, and underscores (e.g. LIPID_PANEL).',
        ]);

        $test = DiagnosticTest::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'diagnostic_specialty_id' => $validated['diagnostic_specialty_id'],
            'specimen_type_id' => $validated['specimen_type_id'],
            'loinc_code' => $validated['loinc_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'patient_preparation' => $validated['patient_preparation'] ?? null,
            'requires_clinical_notes' => $request->boolean('requires_clinical_notes'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('ops.catalogue.index')
            ->with('success', "Master diagnostic test '{$test->name}' ({$test->code}) has been created successfully.");
    }

    public function update(Request $request, DiagnosticTest $test)
    {
        if ($request->filled('new_specialty_name')) {
            $name = trim($request->input('new_specialty_name'));
            $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9_]/', '', str_replace([' ', '&', '-', '/'], '_', $name)), 0, 32));
            $code = $baseCode ?: 'SPECIALTY_' . time();
            $counter = 1;
            while (DiagnosticSpecialty::where('code', $code)->exists()) {
                $code = substr($baseCode, 0, 28) . '_' . $counter++;
            }
            $specialty = DiagnosticSpecialty::firstOrCreate(
                ['name' => $name],
                ['code' => $code]
            );
            $request->merge(['diagnostic_specialty_id' => $specialty->id]);
        }

        if ($request->filled('new_specimen_container')) {
            $container = trim($request->input('new_specimen_container'));
            $name = $request->input('new_specimen_name') ?: $container;
            $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9_]/', '', str_replace([' ', '&', '-', '/', '(', ')'], '_', $container)), 0, 32));
            $code = $baseCode ?: 'SPECIMEN_' . time();
            $counter = 1;
            while (SpecimenType::where('code', $code)->exists()) {
                $code = substr($baseCode, 0, 28) . '_' . $counter++;
            }
            $specimenType = SpecimenType::firstOrCreate(
                ['container' => $container],
                [
                    'name' => $name,
                    'code' => $code,
                    'transport_requirements' => $request->input('new_specimen_transport', 'ambient'),
                    'stability_hours' => $request->input('new_specimen_stability'),
                ]
            );
            $request->merge(['specimen_type_id' => $specimenType->id]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('diagnostic_tests', 'code')->ignore($test->id), 'regex:/^[A-Z0-9_]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'diagnostic_specialty_id' => ['required', 'exists:diagnostic_specialties,id'],
            'specimen_type_id' => ['required', 'exists:specimen_types,id'],
            'loinc_code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'patient_preparation' => ['nullable', 'string'],
            'requires_clinical_notes' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'The test code must contain only uppercase letters, numbers, and underscores (e.g. LIPID_PANEL).',
        ]);

        $test->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'diagnostic_specialty_id' => $validated['diagnostic_specialty_id'],
            'specimen_type_id' => $validated['specimen_type_id'],
            'loinc_code' => $validated['loinc_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'patient_preparation' => $validated['patient_preparation'] ?? null,
            'requires_clinical_notes' => $request->boolean('requires_clinical_notes'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('ops.catalogue.index')
            ->with('success', "Master diagnostic test '{$test->name}' ({$test->code}) updated successfully.");
    }

    public function storeSpecialty(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:diagnostic_specialties,name'],
            'code' => ['nullable', 'string', 'max:32', 'unique:diagnostic_specialties,code', 'regex:/^[A-Z0-9_]+$/'],
            'required_qualification_type' => ['nullable', 'string', 'max:40'],
        ], [
            'code.regex' => 'Specialty code must be alphanumeric and underscores only.',
            'name.unique' => 'A diagnostic specialty with this name already exists.',
        ]);

        $baseCode = !empty($validated['code'])
            ? strtoupper($validated['code'])
            : strtoupper(substr(preg_replace('/[^A-Za-z0-9_]/', '', str_replace([' ', '&', '-', '/'], '_', $validated['name'])), 0, 32));

        $code = $baseCode ?: 'SPECIALTY_' . time();
        $counter = 1;
        while (DiagnosticSpecialty::where('code', $code)->exists()) {
            $code = substr($baseCode, 0, 28) . '_' . $counter++;
        }

        $specialty = DiagnosticSpecialty::create([
            'name' => $validated['name'],
            'code' => $code,
            'required_qualification_type' => $validated['required_qualification_type'] ?? null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'specialty' => [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'code' => $specialty->code,
                ],
                'message' => "Diagnostic specialty '{$specialty->name}' added successfully.",
            ]);
        }

        return redirect()->back()->with('success', "Diagnostic specialty '{$specialty->name}' created successfully.");
    }

    public function storeSpecimenType(Request $request)
    {
        $validated = $request->validate([
            'container' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32', 'unique:specimen_types,code', 'regex:/^[A-Z0-9_]+$/'],
            'transport_requirements' => ['nullable', 'string', 'in:ambient,refrigerated,frozen'],
            'stability_hours' => ['nullable', 'integer', 'min:1'],
        ], [
            'code.regex' => 'Specimen code must be alphanumeric and underscores only.',
        ]);

        $name = !empty($validated['name']) ? $validated['name'] : $validated['container'];
        $baseCode = !empty($validated['code'])
            ? strtoupper($validated['code'])
            : strtoupper(substr(preg_replace('/[^A-Za-z0-9_]/', '', str_replace([' ', '&', '-', '/', '(', ')'], '_', $validated['container'])), 0, 32));

        $code = $baseCode ?: 'SPECIMEN_' . time();
        $counter = 1;
        while (SpecimenType::where('code', $code)->exists()) {
            $code = substr($baseCode, 0, 28) . '_' . $counter++;
        }

        $specimenType = SpecimenType::create([
            'container' => $validated['container'],
            'name' => $name,
            'code' => $code,
            'transport_requirements' => $validated['transport_requirements'] ?? 'ambient',
            'stability_hours' => $validated['stability_hours'] ?? null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'specimenType' => [
                    'id' => $specimenType->id,
                    'container' => $specimenType->container,
                    'name' => $specimenType->name,
                    'code' => $specimenType->code,
                    'transport_requirements' => $specimenType->transport_requirements,
                    'stability_hours' => $specimenType->stability_hours,
                    'tests_count' => 0,
                ],
                'message' => "Specimen tube '{$specimenType->container}' added successfully.",
            ]);
        }

        return redirect()->back()->with('success', "Specimen tube '{$specimenType->container}' created successfully.");
    }

    public function updateSpecialty(Request $request, DiagnosticSpecialty $specialty)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('diagnostic_specialties', 'name')->ignore($specialty->id)],
            'code' => ['nullable', 'string', 'max:32', Rule::unique('diagnostic_specialties', 'code')->ignore($specialty->id), 'regex:/^[A-Z0-9_]+$/'],
            'required_qualification_type' => ['nullable', 'string', 'max:40'],
        ], [
            'code.regex' => 'Specialty code must be alphanumeric and underscores only.',
            'name.unique' => 'A diagnostic specialty with this name already exists.',
        ]);

        $code = !empty($validated['code'])
            ? strtoupper($validated['code'])
            : $specialty->code;

        $specialty->update([
            'name' => $validated['name'],
            'code' => $code,
            'required_qualification_type' => $validated['required_qualification_type'] ?? $specialty->required_qualification_type,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'specialty' => [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'code' => $specialty->code,
                    'tests_count' => $specialty->tests()->count(),
                ],
                'message' => "Diagnostic specialty '{$specialty->name}' updated successfully.",
            ]);
        }

        return redirect()->back()->with('success', "Diagnostic specialty '{$specialty->name}' updated successfully.");
    }

    public function destroySpecialty(Request $request, DiagnosticSpecialty $specialty)
    {
        $testCount = $specialty->tests()->count();
        if ($testCount > 0) {
            $msg = "Cannot delete specialty '{$specialty->name}': {$testCount} diagnostic test(s) are currently assigned to it. Reassign or remove those tests first.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }
            return redirect()->back()->withErrors(['specialty' => $msg]);
        }

        $name = $specialty->name;
        $specialty->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'id' => $specialty->id,
                'message' => "Diagnostic specialty '{$name}' removed successfully.",
            ]);
        }

        return redirect()->back()->with('success', "Diagnostic specialty '{$name}' removed successfully.");
    }

    public function updateSpecimenType(Request $request, SpecimenType $specimenType)
    {
        $validated = $request->validate([
            'container' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32', Rule::unique('specimen_types', 'code')->ignore($specimenType->id), 'regex:/^[A-Z0-9_]+$/'],
            'transport_requirements' => ['nullable', 'string', 'in:ambient,refrigerated,frozen'],
            'stability_hours' => ['nullable', 'integer', 'min:1'],
        ], [
            'code.regex' => 'Specimen code must be alphanumeric and underscores only.',
        ]);

        $name = !empty($validated['name']) ? $validated['name'] : $validated['container'];
        $code = !empty($validated['code']) ? strtoupper($validated['code']) : $specimenType->code;

        $specimenType->update([
            'container' => $validated['container'],
            'name' => $name,
            'code' => $code,
            'transport_requirements' => $validated['transport_requirements'] ?? $specimenType->transport_requirements,
            'stability_hours' => $validated['stability_hours'] ?? $specimenType->stability_hours,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'specimenType' => [
                    'id' => $specimenType->id,
                    'container' => $specimenType->container,
                    'name' => $specimenType->name,
                    'code' => $specimenType->code,
                    'transport_requirements' => $specimenType->transport_requirements,
                    'stability_hours' => $specimenType->stability_hours,
                    'tests_count' => $specimenType->tests()->count(),
                ],
                'message' => "Specimen tube '{$specimenType->container}' updated successfully.",
            ]);
        }

        return redirect()->back()->with('success', "Specimen tube '{$specimenType->container}' updated successfully.");
    }

    public function destroySpecimenType(Request $request, SpecimenType $specimenType)
    {
        $testCount = $specimenType->tests()->count();
        if ($testCount > 0) {
            $msg = "Cannot delete specimen tube '{$specimenType->container}': {$testCount} diagnostic test(s) currently require this tube. Reassign or remove those tests first.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }
            return redirect()->back()->withErrors(['specimenType' => $msg]);
        }

        $container = $specimenType->container;
        $specimenType->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'id' => $specimenType->id,
                'message' => "Specimen tube '{$container}' removed successfully.",
            ]);
        }

        return redirect()->back()->with('success', "Specimen tube '{$container}' removed successfully.");
    }
}
