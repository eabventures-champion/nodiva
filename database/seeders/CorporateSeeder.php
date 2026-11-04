<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Finance\Money;
use App\Domain\Support\ReferenceGenerator;
use App\Models\CollectionSession;
use App\Models\DiagnosticTest;
use App\Models\EmployeeRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\ProgramParticipant;
use App\Models\ScreeningPackage;
use App\Models\ScreeningProgram;
use App\Models\User;
use Illuminate\Database\Seeder;

class CorporateSeeder extends Seeder
{
    public function run(): void
    {
        $corporateOrg = Organization::where('name', 'Tullow Oil Ghana Ltd')->first();
        $lab = Organization::where('name', 'SynLab Diagnostics West Africa')->first();
        $hrUser = User::where('email', 'hr@tullow.example')->first();

        if (! $corporateOrg || ! $lab) {
            return;
        }

        // 1. Standard Corporate Screening Packages
        $p1 = ScreeningPackage::firstOrCreate(['code' => 'ANNUAL_CORP'], [
            'name' => 'Comprehensive Annual Corporate Checkup',
            'description' => 'Complete hemogram, lipid risk stratification, and fasting glycemic evaluation.',
            'purpose' => 'annual_medical',
            'is_active' => true,
        ]);

        $p2 = ScreeningPackage::firstOrCreate(['code' => 'EXEC_WELLNESS'], [
            'name' => 'Executive Cardio-Metabolic & Viral Wellness',
            'description' => 'Comprehensive executive evaluation including Hepatitis B viral DNA PCR and full lipid panel.',
            'purpose' => 'occupational_health',
            'is_active' => true,
        ]);

        $p3 = ScreeningPackage::firstOrCreate(['code' => 'PRE_EMPLOYMENT'], [
            'name' => 'Pre-Employment Diagnostic Screening',
            'description' => 'Baseline medical fitness, complete blood count and hepatitis screening.',
            'purpose' => 'pre_employment',
            'is_active' => true,
        ]);

        // Attach tests
        $allTests = DiagnosticTest::all()->pluck('id');
        if ($allTests->isNotEmpty()) {
            $p1->tests()->syncWithoutDetaching($allTests->take(2));
            $p2->tests()->syncWithoutDetaching($allTests->take(3));
            $p3->tests()->syncWithoutDetaching($allTests->take(1));
        }

        // 2. Seed Employees if none exist
        if (EmployeeRecord::where('organization_id', $corporateOrg->id)->count() === 0) {
            $sampleEmployees = [
                ['num' => 'TUL-GH-0104', 'name' => 'Kwabena Darko', 'email' => 'k.darko@tullow.example', 'phone' => '+233244111222', 'dept' => 'Offshore Engineering', 'sex' => 'male', 'year' => 1982],
                ['num' => 'TUL-GH-0105', 'name' => 'Nana Aba Osei', 'email' => 'n.osei@tullow.example', 'phone' => '+233244111223', 'dept' => 'Subsea Operations', 'sex' => 'female', 'year' => 1988],
                ['num' => 'TUL-GH-0106', 'name' => 'Samuel Addo', 'email' => 's.addo@tullow.example', 'phone' => '+233244111224', 'dept' => 'Health & Safety (HSE)', 'sex' => 'male', 'year' => 1979],
                ['num' => 'TUL-GH-0107', 'name' => 'Akosua Mensah', 'email' => 'a.mensah@tullow.example', 'phone' => '+233244111225', 'dept' => 'Finance & Accounting', 'sex' => 'female', 'year' => 1991],
                ['num' => 'TUL-GH-0108', 'name' => 'Emmanuel Tetteh', 'email' => 'e.tetteh@tullow.example', 'phone' => '+233244111226', 'dept' => 'Offshore Engineering', 'sex' => 'male', 'year' => 1985],
                ['num' => 'TUL-GH-0109', 'name' => 'Grace Kumi', 'email' => 'g.kumi@tullow.example', 'phone' => '+233244111227', 'dept' => 'Subsea Operations', 'sex' => 'female', 'year' => 1993],
            ];

            foreach ($sampleEmployees as $emp) {
                EmployeeRecord::create([
                    'organization_id' => $corporateOrg->id,
                    'employee_number' => $emp['num'],
                    'full_name' => $emp['name'],
                    'email' => $emp['email'],
                    'phone' => $emp['phone'],
                    'sex' => $emp['sex'],
                    'birth_year' => $emp['year'],
                    'department' => $emp['dept'],
                ]);
            }
        }

        // 3. Active Screening Program
        $program = ScreeningProgram::firstOrCreate([
            'organization_id' => $corporateOrg->id,
            'name' => 'Tullow 2026 Annual Offshore & Subsea Health Screening',
        ], [
            'screening_package_id' => $p1->id,
            'laboratory_id' => $lab->id,
            'purpose' => 'annual_medical',
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => now()->addMonths(2)->endOfMonth()->toDateString(),
            'budget' => '45000.00',
            'currency' => 'GHS',
            'pricing_model' => 'per_participant',
            'unit_price' => '250.00',
            'status' => 'active',
            'eligibility_notes' => 'All active permanent staff in Accra HQ and Takoradi Base.',
        ]);

        // 4. Enroll Participants
        $employees = EmployeeRecord::where('organization_id', $corporateOrg->id)->get();
        foreach ($employees as $idx => $employee) {
            $status = $idx < 4 ? 'completed' : 'invited';
            ProgramParticipant::firstOrCreate([
                'screening_program_id' => $program->id,
                'employee_record_id' => $employee->id,
            ], [
                'status' => $status,
                'consent_to_screening' => true,
                'consent_to_employer_results' => true,
                'consent_recorded_at' => now()->subDays(10),
                'outcome' => $status === 'completed' ? ($idx === 0 ? 'referred' : 'fit') : null,
            ]);
        }

        // 5. Mobile Phlebotomy Collection Session
        CollectionSession::firstOrCreate([
            'screening_program_id' => $program->id,
            'location' => 'Tullow Oil Ghana Head Office — Executive Boardroom Phlebotomy Station',
        ], [
            'mode' => 'onsite_mobile',
            'starts_at' => now()->addDays(3)->setTime(8, 0),
            'ends_at' => now()->addDays(3)->setTime(16, 0),
            'capacity' => 50,
            'booked_count' => 14,
        ]);

        // 6. Corporate Program Invoice
        if (Invoice::where('organization_id', $corporateOrg->id)->count() === 0) {
            $invNum = ReferenceGenerator::make('INV', 'invoices', 'number');
            $invoice = Invoice::create([
                'number' => $invNum,
                'organization_id' => $corporateOrg->id,
                'screening_program_id' => $program->id,
                'status' => 'issued',
                'currency' => 'GHS',
                'subtotal' => '12500.00',
                'tax_amount' => '0.00',
                'total' => '12500.00',
                'amount_paid' => '0.00',
                'amount_credited' => '0.00',
                'balance_due' => '12500.00',
                'issued_on' => now()->toDateString(),
                'due_on' => now()->addDays(30)->toDateString(),
                'created_by' => $hrUser?->id ?? 1,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'category' => 'program_fee',
                'description' => "Corporate Screening Consignment — 50 Enrolled Participants ({$program->name})",
                'quantity' => 50,
                'unit_amount' => '250.00',
                'amount' => '12500.00',
            ]);
        }
    }
}
