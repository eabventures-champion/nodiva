<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratory;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\DiagnosticTest;
use App\Models\TestCatalogueEntry;
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

        $entriesQuery = TestCatalogueEntry::where('organization_id', $org->id)
            ->with(['test.specialty', 'test.specimenType']);

        if ($query) {
            $entriesQuery->whereHas('test', function ($t) use ($query) {
                $t->where('name', 'like', "%{$query}%")->orWhere('code', 'like', "%{$query}%");
            })->orWhere('lab_test_code', 'like', "%{$query}%");
        }

        $entries = $entriesQuery->latest('id')->paginate(15)->withQueryString();

        // Available master tests not yet in lab's catalogue
        $existingTestIds = TestCatalogueEntry::where('organization_id', $org->id)->pluck('diagnostic_test_id');
        $availableTests = DiagnosticTest::whereNotIn('id', $existingTestIds)->where('is_active', true)->with('specialty')->get();

        return view('laboratory.catalogue.index', [
            'organization' => $org,
            'entries' => $entries,
            'availableTests' => $availableTests,
            'searchQuery' => $query,
        ]);
    }

    public function store(Request $request)
    {
        $org = $this->current->organization();

        $validated = $request->validate([
            'diagnostic_test_id' => ['required', 'exists:diagnostic_tests,id'],
            'lab_test_code' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'turnaround_hours' => ['required', 'integer', 'min:1'],
            'daily_capacity' => ['nullable', 'integer', 'min:1'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        TestCatalogueEntry::create([
            'organization_id' => $org->id,
            'diagnostic_test_id' => $validated['diagnostic_test_id'],
            'lab_test_code' => $validated['lab_test_code'],
            'price' => $validated['price'],
            'currency' => 'GHS',
            'turnaround_hours' => $validated['turnaround_hours'],
            'daily_capacity' => $validated['daily_capacity'] ?? 100,
            'is_available' => $request->boolean('is_available', true),
        ]);

        return redirect()->back()->with('success', 'Diagnostic test successfully added to laboratory active menu.');
    }

    public function update(Request $request, TestCatalogueEntry $entry)
    {
        $org = $this->current->organization();

        if ($entry->organization_id !== $org->id) {
            abort(403, 'Unauthorized catalogue action.');
        }

        $validated = $request->validate([
            'lab_test_code' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'turnaround_hours' => ['required', 'integer', 'min:1'],
            'daily_capacity' => ['nullable', 'integer', 'min:1'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        $entry->update([
            'lab_test_code' => $validated['lab_test_code'],
            'price' => $validated['price'],
            'turnaround_hours' => $validated['turnaround_hours'],
            'daily_capacity' => $validated['daily_capacity'] ?? $entry->daily_capacity,
            'is_available' => $request->boolean('is_available'),
        ]);

        return redirect()->back()->with('success', "Test {$entry->lab_test_code} pricing & capacity updated.");
    }

    public function destroy(TestCatalogueEntry $entry)
    {
        $org = $this->current->organization();

        if ($entry->organization_id !== $org->id) {
            abort(403, 'Unauthorized catalogue action.');
        }

        $testName = $entry->test?->name ?? $entry->lab_test_code;
        $entry->delete();

        return redirect()->route('lab.catalogue.index')
            ->with('success', "Test '{$testName}' successfully removed from your active laboratory menu.");
    }

    public function initializeFromMaster(Request $request)
    {
        $org = $this->current->organization();
        $masterTests = DiagnosticTest::where('is_active', true)->get();
        $existingTestIds = TestCatalogueEntry::where('organization_id', $org->id)->pluck('diagnostic_test_id')->all();

        $rawPrefix = $request->input('code_prefix');
        $prefix = $rawPrefix ? strtoupper(trim($rawPrefix)) : (strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $org->name), 0, 2)) ?: 'LAB');
        $defaultPrice = (float) $request->input('default_price', 250.00);
        $defaultTat = (int) $request->input('default_turnaround_hours', 24);
        $defaultCapacity = (int) $request->input('default_capacity', 50);
        $added = 0;

        foreach ($masterTests as $test) {
            if (! in_array($test->id, $existingTestIds, true)) {
                $code = $prefix . '-' . substr($test->code, 0, 3) . '-' . str_pad((string)($test->id * 5), 3, '0', STR_PAD_LEFT);
                TestCatalogueEntry::create([
                    'organization_id' => $org->id,
                    'diagnostic_test_id' => $test->id,
                    'lab_test_code' => $code,
                    'price' => $defaultPrice,
                    'currency' => 'GHS',
                    'turnaround_hours' => $defaultTat,
                    'daily_capacity' => $defaultCapacity,
                    'is_available' => true,
                ]);
                $added++;
            }
        }

        return redirect()->route('lab.catalogue.index')
            ->with('success', "Initialized {$added} diagnostic investigation(s) from Master Formulary. You can now customize internal codes, contract pricing, and daily throughput.");
    }
}
