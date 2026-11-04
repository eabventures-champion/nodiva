<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CollectionSession;
use App\Models\EmployeeRecord;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\ScreeningPackage;
use App\Models\ScreeningProgram;
use App\Models\User;
use Database\Seeders\CorporateSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorporatePortalTest extends TestCase
{
    use RefreshDatabase;

    private User $hr;
    private Organization $corporate;
    private User $doctor;
    private Organization $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(CorporateSeeder::class);

        $this->corporate = Organization::where('name', 'Tullow Oil Ghana Ltd')->firstOrFail();
        $this->hr = User::where('email', 'hr@tullow.example')->firstOrFail();

        $this->hospital = Organization::where('name', 'Ridge Specialist Hospital')->firstOrFail();
        $this->doctor = User::where('email', 'doctor@ridgespecialist.com')->firstOrFail();
    }

    public function test_can_view_corporate_dashboard_and_programs(): void
    {
        $response = $this->actingAs($this->hr)->get(route('corporate.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Corporate Health Screening Console');
        $response->assertSee('Tullow 2026 Annual Offshore & Subsea Health Screening');
    }

    public function test_can_create_new_screening_program(): void
    {
        $package = ScreeningPackage::firstOrFail();
        $lab = Organization::where('type', 'laboratory')->firstOrFail();

        $response = $this->actingAs($this->hr)
            ->from(route('corporate.dashboard'))
            ->post(route('corporate.programs.store'), [
                'name' => 'Q4 Executive Risk Screening 2026',
                'screening_package_id' => $package->id,
                'laboratory_id' => $lab->id,
                'purpose' => 'occupational_health',
                'starts_on' => now()->addDays(5)->toDateString(),
                'ends_on' => now()->addDays(20)->toDateString(),
                'budget' => '30000.00',
                'eligibility_notes' => 'Executive committee and lead field engineers',
            ]);

        $response->assertRedirect(route('corporate.dashboard'));
        $this->assertDatabaseHas('screening_programs', [
            'name' => 'Q4 Executive Risk Screening 2026',
            'organization_id' => $this->corporate->id,
        ]);
    }

    public function test_can_view_and_enroll_employees(): void
    {
        $response = $this->actingAs($this->hr)->get(route('corporate.employees.index'));
        $response->assertStatus(200);
        $response->assertSee('Corporate Employee Rosters');
        $response->assertSee('Kwabena Darko');

        // Enroll new employee
        $postResponse = $this->actingAs($this->hr)
            ->from(route('corporate.employees.index'))
            ->post(route('corporate.employees.store'), [
                'employee_number' => 'TUL-GH-9999',
                'full_name' => 'Kofi Annan Mensah',
                'email' => 'kofi.mensah@tullow.example',
                'phone' => '+233240001122',
                'department' => 'Exploration & Geophysics',
                'sex' => 'male',
                'birth_year' => 1985,
            ]);

        $postResponse->assertRedirect(route('corporate.employees.index'));
        $this->assertDatabaseHas('employee_records', [
            'organization_id' => $this->corporate->id,
            'employee_number' => 'TUL-GH-9999',
        ]);
    }

    public function test_can_view_and_create_collection_sessions(): void
    {
        $response = $this->actingAs($this->hr)->get(route('corporate.sessions.index'));
        $response->assertStatus(200);
        $response->assertSee('Phlebotomy');

        $program = ScreeningProgram::where('organization_id', $this->corporate->id)->firstOrFail();

        $postResponse = $this->actingAs($this->hr)
            ->from(route('corporate.sessions.index'))
            ->post(route('corporate.sessions.store'), [
                'screening_program_id' => $program->id,
                'location' => 'Takoradi Shorebase Medical Bay Station',
                'mode' => 'onsite_mobile',
                'starts_at' => now()->addDays(7)->setTime(9, 0)->toDateTimeString(),
                'ends_at' => now()->addDays(7)->setTime(17, 0)->toDateTimeString(),
                'capacity' => 60,
            ]);

        $postResponse->assertRedirect(route('corporate.sessions.index'));
        $this->assertDatabaseHas('collection_sessions', [
            'screening_program_id' => $program->id,
            'location' => 'Takoradi Shorebase Medical Bay Station',
        ]);
    }

    public function test_can_view_aggregate_health_insights(): void
    {
        $response = $this->actingAs($this->hr)->get(route('corporate.insights.index'));
        $response->assertStatus(200);
        $response->assertSee('Aggregate Population Health Insights');
        $response->assertSee('Ghana Data Protection Act 2012 (Act 843)');
    }

    public function test_can_view_invoices_and_statements(): void
    {
        $response = $this->actingAs($this->hr)->get(route('corporate.invoices.index'));
        $response->assertStatus(200);
        $response->assertSee('Corporate Program Invoices');

        $invoice = Invoice::where('organization_id', $this->corporate->id)->firstOrFail();
        $showResponse = $this->actingAs($this->hr)->get(route('corporate.invoices.show', $invoice));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($invoice->number);
        $showResponse->assertSee('Tullow Oil Ghana Ltd');
    }

    public function test_non_corporate_user_cannot_access_corporate_portal(): void
    {
        $response = $this->actingAs($this->doctor)->get(route('corporate.dashboard'));
        $response->assertRedirect(route('hospital.dashboard'));
    }
}
