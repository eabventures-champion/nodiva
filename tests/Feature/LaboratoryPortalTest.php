<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Organizations\Actor;
use App\Models\DiagnosticTest;
use App\Models\Organization;
use App\Models\ReferralOrder;
use App\Models\TestCatalogueEntry;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaboratoryPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $scientist;
    private User $pathologist;
    private Organization $lab;
    private User $doctor;
    private Organization $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->lab = Organization::where('name', 'SynLab Diagnostics West Africa')->firstOrFail();
        $this->scientist = User::where('email', 'scientist@synlab-gh.example')->firstOrFail();
        $this->pathologist = User::where('email', 'pathologist@synlab-gh.example')->firstOrFail();

        $this->hospital = Organization::where('name', 'Ridge Specialist Hospital')->firstOrFail();
        $this->doctor = User::where('email', 'doctor@ridgespecialist.com')->firstOrFail();
    }

    public function test_can_view_specimen_reception_page(): void
    {
        $response = $this->actingAs($this->scientist)->get(route('lab.reception.index'));
        $response->assertStatus(200);
        $response->assertSee('Specimen Reception & Accessioning');
    }

    public function test_can_view_results_workflow_queue(): void
    {
        $response = $this->actingAs($this->scientist)->get(route('lab.results.index'));
        $response->assertStatus(200);
        $response->assertSee('Result Entry & Pathologist Release');
    }

    public function test_can_view_test_catalogue_management(): void
    {
        $response = $this->actingAs($this->scientist)->get(route('lab.catalogue.index'));
        $response->assertStatus(200);
        $response->assertSee('Test Catalogue');
        $response->assertSee('SL-MOL-001');
    }

    public function test_can_view_qualifications_page(): void
    {
        $response = $this->actingAs($this->scientist)->get(route('lab.qualifications.index'));
        $response->assertStatus(200);
        $response->assertSee('Laboratory Accreditations');
        $response->assertSee('ISO-15189-GH-044');
    }

    public function test_can_view_settlement_statements(): void
    {
        $response = $this->actingAs($this->scientist)->get(route('lab.settlements.index'));
        $response->assertStatus(200);
        $response->assertSee('Settlement Statements');
    }

    public function test_hospital_clinician_cannot_access_laboratory_portal(): void
    {
        $response = $this->actingAs($this->doctor)->get(route('lab.reception.index'));
        $response->assertRedirect(route('hospital.dashboard'));
    }

    public function test_laboratory_can_verify_and_accept_multiple_specimens_for_single_order(): void
    {
        $patient = \App\Models\Patient::create([
            'organization_id' => $this->hospital->id,
            'mrn_hash' => \App\Models\Patient::mrnHash($this->hospital->uuid, 'MRN-LAB-TEST-1'),
            'medical_record_number' => 'MRN-LAB-TEST-1',
            'first_name' => 'Kofi',
            'last_name' => 'Mensah',
            'date_of_birth' => '1988-03-20',
            'sex' => 'male',
            'reference' => 'PT-LAB-001',
            'created_by' => $this->doctor->id,
        ]);
        $specimenType = \App\Models\SpecimenType::firstOrFail();
        $doctorActor = Actor::for($this->doctor, $this->hospital);

        $order = new ReferralOrder();
        $order->reference = \App\Domain\Support\ReferenceGenerator::make('RF', 'referral_orders', 'reference');
        $order->requesting_organization_id = $this->hospital->id;
        $order->laboratory_id = $this->lab->id;
        $order->patient_id = $patient->id;
        $order->created_by = $this->doctor->id;
        $order->status = \App\Domain\Referrals\Enums\OrderStatus::InTransit;
        $order->priority = 'routine';
        $order->collection_method = 'courier';
        $order->clinical_indication = 'Multi-specimen test';
        $order->currency = 'GHS';
        $order->save();

        $custody = app(\App\Domain\Specimens\SpecimenCustody::class);
        $sp1 = $custody->register($order, $specimenType->id, $doctorActor);
        $sp2 = $custody->register($order, $specimenType->id, $doctorActor);

        $sp1->status = \App\Domain\Specimens\Enums\SpecimenStatus::InTransit;
        $sp1->save();

        $sp2->status = \App\Domain\Specimens\Enums\SpecimenStatus::InTransit;
        $sp2->save();

        // 1. Verify & Accept specimen 1
        $response1 = $this->actingAs($this->scientist)->post(route('lab.specimens.receive', $sp1), [
            'process_immediately' => '1',
        ]);
        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        $sp1->refresh();
        $order->refresh();
        $this->assertSame(\App\Domain\Specimens\Enums\SpecimenStatus::Accepted, $sp1->status);
        $this->assertSame(\App\Domain\Referrals\Enums\OrderStatus::Processing, $order->status);

        // 2. Verify & Accept specimen 2 (order is already processing)
        $response2 = $this->actingAs($this->scientist)->post(route('lab.specimens.receive', $sp2), [
            'process_immediately' => '1',
        ]);
        $response2->assertRedirect();
        $response2->assertSessionHas('success');

        $sp2->refresh();
        $order->refresh();
        $this->assertSame(\App\Domain\Specimens\Enums\SpecimenStatus::Accepted, $sp2->status);
        $this->assertSame(\App\Domain\Referrals\Enums\OrderStatus::Processing, $order->status);

        // 3. Repeated / idempotent submit does not throw exception
        $responseRepeat = $this->actingAs($this->scientist)->post(route('lab.specimens.receive', $sp2), [
            'process_immediately' => '1',
        ]);
        $responseRepeat->assertRedirect();
        $responseRepeat->assertSessionHas('success');
    }

    public function test_lab_admin_can_access_team_management_page(): void
    {
        $labAdmin = User::where('email', 'admin@synlab-gh.example')->firstOrFail();

        $response = $this->actingAs($labAdmin)->get(route('lab.team.index'));
        $response->assertStatus(200);
        $response->assertSee('Laboratory Team &amp; Access Governance', false);
        $response->assertSee('Medical Laboratory Scientist Protocol');
        $response->assertSee('Consultant Pathologist Protocol');
    }

    public function test_lab_scientist_cannot_access_team_management_page(): void
    {
        $response = $this->actingAs($this->scientist)->get(route('lab.team.index'));
        $response->assertStatus(403);
    }

    public function test_lab_admin_can_provision_new_scientist_and_pathologist(): void
    {
        $labAdmin = User::where('email', 'admin@synlab-gh.example')->firstOrFail();
        $scientistRole = \App\Models\Role::where('key', 'lab_scientist')->firstOrFail();

        // Provision a new scientist
        $response = $this->actingAs($labAdmin)->post(route('lab.team.invite'), [
            'name' => 'Kofi Boateng, MLS',
            'email' => 'kofi.scientist@synlab-gh.example',
            'job_title' => 'Clinical Microbiologist Specialist',
            'role_id' => $scientistRole->id,
            'mode' => 'create_direct',
            'password' => 'SecurePass2026!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'kofi.scientist@synlab-gh.example',
            'name' => 'Kofi Boateng, MLS',
        ]);

        $newUser = User::where('email', 'kofi.scientist@synlab-gh.example')->firstOrFail();
        $this->assertDatabaseHas('organization_memberships', [
            'user_id' => $newUser->id,
            'organization_id' => $this->lab->id,
            'role_id' => $scientistRole->id,
            'status' => 'active',
        ]);
    }
}
