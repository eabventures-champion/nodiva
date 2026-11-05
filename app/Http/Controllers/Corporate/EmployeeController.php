<?php

declare(strict_types=1);

namespace App\Http\Controllers\Corporate;

use App\Domain\Organizations\CurrentOrganization;
use App\Domain\Support\ReferenceGenerator;
use App\Http\Controllers\Controller;
use App\Models\EmployeeRecord;
use App\Models\ScreeningProgram;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();
        $query = $request->query('q');
        $dept = $request->query('department');

        $employeesQuery = EmployeeRecord::where('organization_id', $org->id)
            ->with(['participations.program']);

        if ($query) {
            $employeesQuery->where(function ($q) use ($query) {
                $q->where('employee_number', 'like', "%{$query}%")
                    ->orWhere('department', 'like', "%{$query}%");
            });
        }

        if ($dept) {
            $employeesQuery->where('department', $dept);
        }

        $employees = $employeesQuery->latest('id')->paginate(15)->withQueryString();

        $departments = EmployeeRecord::where('organization_id', $org->id)
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');

        $programs = ScreeningProgram::where('organization_id', $org->id)->get();

        return view('corporate.employees.index', [
            'organization' => $org,
            'employees' => $employees,
            'departments' => $departments,
            'programs' => $programs,
            'searchQuery' => $query,
            'currentDept' => $dept,
        ]);
    }

    public function store(Request $request)
    {
        $org = $this->current->organization();

        $validated = $request->validate([
            'employee_number' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'sex' => ['required', 'in:male,female'],
            'birth_year' => ['nullable', 'integer', 'min:1940', 'max:2010'],
            'department' => ['required', 'string', 'max:100'],
        ]);

        EmployeeRecord::create([
            'organization_id' => $org->id,
            'employee_number' => $validated['employee_number'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? '+233240000000',
            'sex' => $validated['sex'],
            'birth_year' => $validated['birth_year'] ?? 1990,
            'department' => $validated['department'],
        ]);

        return redirect()->back()->with('success', "Employee {$validated['full_name']} ({$validated['employee_number']}) enrolled into organizational roster.");
    }
}
