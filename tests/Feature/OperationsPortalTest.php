<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ReferralOrder;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\HospitalReferralSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private Organization $platform;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(HospitalReferralSeeder::class);

        $this->admin = User::where('email', 'admin@nodiva.com')->firstOrFail();
        $this->doctor = User::where('email', 'doctor@ridgespecialist.com')->firstOrFail();
    }

    public function test_can_view_operations_dashboard_with_network_orders(): void
    {
        $response = $this->actingAs($this->admin)->get(route('ops.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Central Network Operations Console');
        $response->assertSee('Live Network Referral Activity');
        $response->assertSee('Inspect Order');
        $response->assertSee('Click to view corporate client profiles & programs', false);
        $response->assertSee('Tullow Oil Ghana Ltd');
        $response->assertSee('Click to view all network referral orders & status', false);
        $response->assertSee('Total Diagnostic Referrals Ledger');
    }

    public function test_can_inspect_order_in_operations_console(): void
    {
        $order = ReferralOrder::firstOrFail();

        $response = $this->actingAs($this->admin)->get(route('ops.orders.show', $order));
        $response->assertStatus(200);
        $response->assertSee($order->reference);
        $response->assertSee('Diagnostic Lifecycle Stepper');
        $response->assertSee('Requesting Institution');
        $response->assertSee('Fulfilling Laboratory');
        $response->assertSee('Specimen Custody');
        $response->assertSee('Cryptographic Hash-Chained Audit Trail');
        $response->assertSee('Back to Network Operations');
    }

    public function test_non_operations_user_cannot_inspect_order_in_ops_portal(): void
    {
        $order = ReferralOrder::firstOrFail();

        $response = $this->actingAs($this->doctor)->get(route('ops.orders.show', $order));
        $response->assertRedirect(route('hospital.dashboard'));
    }

    public function test_operations_can_view_master_diagnostic_catalogue(): void
    {
        $response = $this->actingAs($this->admin)->get(route('ops.catalogue.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Diagnostic Test Catalogue');
        $response->assertSee('HBV_VIRAL_LOAD');
    }

    public function test_operations_can_create_new_master_diagnostic_test(): void
    {
        $specialty = \App\Models\DiagnosticSpecialty::firstOrFail();
        $specimenType = \App\Models\SpecimenType::firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('ops.catalogue.store'), [
            'code' => 'TROPONIN_I_TEST',
            'name' => 'High Sensitivity Cardiac Troponin I',
            'diagnostic_specialty_id' => $specialty->id,
            'specimen_type_id' => $specimenType->id,
            'loinc_code' => '49563-0',
            'description' => 'Early biomarker evaluation for acute coronary syndrome.',
            'patient_preparation' => 'No special patient fasting needed.',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('ops.catalogue.index'));
        $this->assertDatabaseHas('diagnostic_tests', [
            'code' => 'TROPONIN_I_TEST',
            'name' => 'High Sensitivity Cardiac Troponin I',
            'loinc_code' => '49563-0',
        ]);
    }

    public function test_operations_can_update_master_diagnostic_test(): void
    {
        $test = \App\Models\DiagnosticTest::where('code', 'HBV_VIRAL_LOAD')->firstOrFail();

        $response = $this->actingAs($this->admin)->patch(route('ops.catalogue.update', $test), [
            'code' => 'HBV_VIRAL_LOAD',
            'name' => 'Hepatitis B Virus (HBV) Quantitative DNA PCR - Updated',
            'diagnostic_specialty_id' => $test->diagnostic_specialty_id,
            'specimen_type_id' => $test->specimen_type_id,
            'loinc_code' => '29893-5',
            'description' => 'Updated clinical description for PCR amplification.',
            'patient_preparation' => 'No special preparation required.',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('ops.catalogue.index'));
        $this->assertDatabaseHas('diagnostic_tests', [
            'id' => $test->id,
            'name' => 'Hepatitis B Virus (HBV) Quantitative DNA PCR - Updated',
            'loinc_code' => '29893-5',
        ]);
    }

    public function test_operations_can_add_new_diagnostic_specialty_via_ajax(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('ops.specialties.store'), [
            'name' => 'Immunology & Serology',
            'code' => 'IMMUNO_SEROLOGY',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'specialty' => [
                'name' => 'Immunology & Serology',
                'code' => 'IMMUNO_SEROLOGY',
            ],
        ]);

        $this->assertDatabaseHas('diagnostic_specialties', [
            'name' => 'Immunology & Serology',
            'code' => 'IMMUNO_SEROLOGY',
        ]);
    }

    public function test_operations_can_create_test_with_inline_new_specialty(): void
    {
        $specimenType = \App\Models\SpecimenType::firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('ops.catalogue.store'), [
            'code' => 'TOX_DRUG_PANEL',
            'name' => 'Comprehensive Clinical Toxicology Panel',
            'new_specialty_name' => 'Toxicology & Pharmacology',
            'diagnostic_specialty_id' => '1', // fallback id overridden by new specialty
            'specimen_type_id' => $specimenType->id,
            'loinc_code' => '54321-0',
            'description' => 'Screening for controlled pharmaceutical and toxicological compounds.',
            'patient_preparation' => 'Random urine specimen collection.',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('ops.catalogue.index'));
        $this->assertDatabaseHas('diagnostic_specialties', [
            'name' => 'Toxicology & Pharmacology',
        ]);

        $newSpecialty = \App\Models\DiagnosticSpecialty::where('name', 'Toxicology & Pharmacology')->firstOrFail();

        $this->assertDatabaseHas('diagnostic_tests', [
            'code' => 'TOX_DRUG_PANEL',
            'diagnostic_specialty_id' => $newSpecialty->id,
        ]);
    }

    public function test_operations_can_add_new_specimen_type_via_ajax(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('ops.specimen-types.store'), [
            'container' => 'Light Blue top',
            'name' => 'Sodium Citrate Plasma',
            'code' => 'PLAS_CITRATE',
            'transport_requirements' => 'refrigerated',
            'stability_hours' => 24,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'specimenType' => [
                'container' => 'Light Blue top',
                'name' => 'Sodium Citrate Plasma',
                'code' => 'PLAS_CITRATE',
                'transport_requirements' => 'refrigerated',
                'stability_hours' => 24,
            ],
        ]);

        $this->assertDatabaseHas('specimen_types', [
            'container' => 'Light Blue top',
            'code' => 'PLAS_CITRATE',
        ]);
    }

    public function test_operations_can_create_test_with_inline_new_specimen_type(): void
    {
        $specialty = \App\Models\DiagnosticSpecialty::firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('ops.catalogue.store'), [
            'code' => 'D_DIMER_QUANT',
            'name' => 'D-Dimer Quantitative Assay',
            'diagnostic_specialty_id' => $specialty->id,
            'new_specimen_container' => 'Citrate 3.2% Tube',
            'new_specimen_name' => 'Citrated Plasma',
            'new_specimen_transport' => 'frozen',
            'new_specimen_stability' => 4,
            'specimen_type_id' => '1', // fallback id overridden by new specimen type
            'loinc_code' => '48065-7',
            'description' => 'Fibrin degradation product assessment for thrombosis evaluation.',
            'patient_preparation' => 'No special preparation required.',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('ops.catalogue.index'));
        $this->assertDatabaseHas('specimen_types', [
            'container' => 'Citrate 3.2% Tube',
        ]);

        $newSpecimen = \App\Models\SpecimenType::where('container', 'Citrate 3.2% Tube')->firstOrFail();

        $this->assertDatabaseHas('diagnostic_tests', [
            'code' => 'D_DIMER_QUANT',
            'specimen_type_id' => $newSpecimen->id,
        ]);
    }

    public function test_operations_can_view_qualifications_grouped_by_laboratory(): void
    {
        $response = $this->actingAs($this->admin)->get(route('ops.qualifications.index'));
        $response->assertStatus(200);
        $response->assertSee('Laboratory Qualification & License Registry', false);
        $response->assertSee('SynLab Diagnostics West Africa');
        $response->assertSee('ISO-15189-GH-044');
        $response->assertSee('AHPC/LAB/2026/089');
        $response->assertSee('Accreditation');
        $response->assertSee('Operating licence');
        $response->assertSee('Qualifications Filed', false);
    }

    public function test_operations_can_verify_pending_qualification(): void
    {
        $qual = \App\Models\LaboratoryQualification::firstOrFail();
        $qual->update(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->post(route('ops.qualifications.verify', $qual), [
            'action' => 'verified',
            'notes' => 'Verified against AHPC official gazette.',
        ]);

        $response->assertRedirect();
        $this->assertEquals('verified', $qual->fresh()->status);
        $this->assertEquals($this->admin->id, $qual->fresh()->verified_by);
    }

    public function test_operations_can_update_specialty(): void
    {
        $specialty = \App\Models\DiagnosticSpecialty::create([
            'name' => 'Temporary Virology',
            'code' => 'TEMP_VIROL',
        ]);

        $response = $this->actingAs($this->admin)->patchJson(route('ops.specialties.update', $specialty), [
            'name' => 'Clinical Virology & Serology',
            'code' => 'CLIN_VIROL',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('diagnostic_specialties', [
            'id' => $specialty->id,
            'name' => 'Clinical Virology & Serology',
            'code' => 'CLIN_VIROL',
        ]);
    }

    public function test_operations_can_delete_unused_specialty(): void
    {
        $specialty = \App\Models\DiagnosticSpecialty::create([
            'name' => 'Unused Specialty',
            'code' => 'UNUSED_SPEC',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson(route('ops.specialties.destroy', $specialty));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('diagnostic_specialties', [
            'id' => $specialty->id,
        ]);
    }

    public function test_operations_cannot_delete_specialty_with_associated_tests(): void
    {
        $specialty = \App\Models\DiagnosticSpecialty::whereHas('tests')->firstOrFail();

        $response = $this->actingAs($this->admin)->deleteJson(route('ops.specialties.destroy', $specialty));

        $response->assertStatus(422);
        $this->assertDatabaseHas('diagnostic_specialties', [
            'id' => $specialty->id,
        ]);
    }

    public function test_operations_can_update_specimen_type(): void
    {
        $specimen = \App\Models\SpecimenType::create([
            'container' => 'Temporary Heparin Tube',
            'name' => 'Heparin Plasma',
            'code' => 'TEMP_HEP',
            'transport_requirements' => 'ambient',
        ]);

        $response = $this->actingAs($this->admin)->patchJson(route('ops.specimen-types.update', $specimen), [
            'container' => 'Green top (Sodium Heparin)',
            'name' => 'Sodium Heparinized Whole Blood',
            'code' => 'GREEN_HEP',
            'transport_requirements' => 'refrigerated',
            'stability_hours' => 48,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('specimen_types', [
            'id' => $specimen->id,
            'container' => 'Green top (Sodium Heparin)',
            'transport_requirements' => 'refrigerated',
            'stability_hours' => 48,
        ]);
    }

    public function test_operations_can_delete_unused_specimen_type(): void
    {
        $specimen = \App\Models\SpecimenType::create([
            'container' => 'Unused Tube',
            'name' => 'Unused Specimen',
            'code' => 'UNUSED_TUBE',
            'transport_requirements' => 'ambient',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson(route('ops.specimen-types.destroy', $specimen));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('specimen_types', [
            'id' => $specimen->id,
        ]);
    }

    public function test_operations_cannot_delete_specimen_type_with_associated_tests(): void
    {
        $specimen = \App\Models\SpecimenType::whereHas('tests')->firstOrFail();

        $response = $this->actingAs($this->admin)->deleteJson(route('ops.specimen-types.destroy', $specimen));

        $response->assertStatus(422);
        $this->assertDatabaseHas('specimen_types', [
            'id' => $specimen->id,
        ]);
    }

    public function test_cto_can_delete_partner_at_verification_action(): void
    {
        $hospital = Organization::where('type', 'hospital')->firstOrFail();

        $response = $this->actingAs($this->admin)->delete(route('ops.partners.destroy', $hospital));

        $response->assertRedirect(route('ops.partners.index'));
        $response->assertSessionHas('success');

        // Soft deleted in DB
        $this->assertSoftDeleted('organizations', ['id' => $hospital->id]);
    }

    public function test_cto_can_delete_all_partners(): void
    {
        $nonPlatformCount = Organization::where('type', '!=', 'platform')->count();
        $this->assertGreaterThan(0, $nonPlatformCount);

        $response = $this->actingAs($this->admin)->delete(route('ops.partners.destroyAll'));

        $response->assertRedirect(route('ops.partners.index'));
        $response->assertSessionHas('success');

        $remainingCount = Organization::where('type', '!=', 'platform')->count();
        $this->assertEquals(0, $remainingCount);
    }

    public function test_cto_can_remove_team_member_at_ops_partners(): void
    {
        $partner = Organization::where('type', 'hospital')->firstOrFail();
        $membership = $partner->memberships()->firstOrFail();
        $memberUser = $membership->user;

        $response = $this->actingAs($this->admin)->delete(route('ops.partners.destroyMember', [$partner, $membership]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('organization_memberships', [
            'id' => $membership->id,
        ]);
    }

    public function test_non_cto_cannot_delete_partner_or_team_member(): void
    {
        // Create an operations staff who is NOT a CTO
        $opsRole = \App\Models\Role::where('key', 'platform_operations_manager')
            ->orWhere('organization_type', 'platform')
            ->firstOrFail();

        $staff = User::create([
            'name' => 'Kofi Mensah',
            'email' => 'kofi.ops@nodiva.com',
            'job_title' => 'Logistics Support Officer',
            'password' => bcrypt('Password2026!'),
        ]);

        $platformOrg = Organization::where('type', 'platform')->firstOrFail();
        \App\Models\OrganizationMembership::create([
            'user_id' => $staff->id,
            'organization_id' => $platformOrg->id,
            'role_id' => $opsRole->id,
            'status' => 'active',
        ]);

        $this->assertFalse($staff->isCto());

        $partner = Organization::where('type', 'hospital')->firstOrFail();
        $membership = $partner->memberships()->firstOrFail();

        // 1. Attempt to delete partner should be 403
        $resp1 = $this->actingAs($staff)->delete(route('ops.partners.destroy', $partner));
        $resp1->assertStatus(403);

        // 2. Attempt to delete all partners should be 403
        $resp2 = $this->actingAs($staff)->delete(route('ops.partners.destroyAll'));
        $resp2->assertStatus(403);

        // 3. Attempt to delete partner team member should be 403
        $resp3 = $this->actingAs($staff)->delete(route('ops.partners.destroyMember', [$partner, $membership]));
        $resp3->assertStatus(403);
    }

    public function test_cto_can_impersonate_laboratory_administrator_and_view_lab_dashboard(): void
    {
        $lab = Organization::where('type', 'laboratory')->firstOrFail();
        $labAdmin = User::where('email', 'admin@synlab-gh.example')->firstOrFail();

        // 1. CTO impersonates lab administrator
        $response = $this->actingAs($this->admin)->post(route('ops.impersonate.user', $labAdmin));

        $response->assertRedirect(route('lab.dashboard'));
        $this->assertEquals($labAdmin->id, auth()->id());
        $this->assertEquals($this->admin->id, session('impersonated_by'));

        // 2. View lab dashboard and verify CTO Impersonation banner is visible
        $dashboardResp = $this->get(route('lab.dashboard'));
        $dashboardResp->assertStatus(200);
        $dashboardResp->assertSee('CTO Impersonation Active:');
        $dashboardResp->assertSee('Exit Impersonation');

        // 3. Exit impersonation
        $leaveResp = $this->post(route('ops.impersonate.leave'));
        $leaveResp->assertRedirect(route('ops.partners.index'));
        $this->assertEquals($this->admin->id, auth()->id());
        $this->assertFalse(session()->has('impersonated_by'));
    }

    public function test_cto_can_impersonate_hospital_administrator_and_view_hospital_dashboard(): void
    {
        $hospitalAdmin = User::where('email', 'admin@ridgespecialist.com')->firstOrFail();

        // 1. CTO impersonates hospital administrator
        $response = $this->actingAs($this->admin)->post(route('ops.impersonate.user', $hospitalAdmin));

        $response->assertRedirect(route('hospital.dashboard'));
        $this->assertEquals($hospitalAdmin->id, auth()->id());
        $this->assertEquals($this->admin->id, session('impersonated_by'));

        // 2. View hospital dashboard
        $dashboardResp = $this->get(route('hospital.dashboard'));
        $dashboardResp->assertStatus(200);
        $dashboardResp->assertSee('CTO Impersonation Active:');

        // 3. Exit impersonation
        $leaveResp = $this->post(route('ops.impersonate.leave'));
        $leaveResp->assertRedirect(route('ops.partners.index'));
        $this->assertEquals($this->admin->id, auth()->id());
    }

    public function test_cto_can_impersonate_corporate_administrator_and_view_corporate_dashboard(): void
    {
        $corpAdmin = User::where('email', 'hr@tullow.example')
            ->orWhere('email', 'admin@tullow.example')
            ->firstOrFail();

        // 1. CTO impersonates corporate administrator
        $response = $this->actingAs($this->admin)->post(route('ops.impersonate.user', $corpAdmin));

        $response->assertRedirect(route('corporate.dashboard'));
        $this->assertEquals($corpAdmin->id, auth()->id());
        $this->assertEquals($this->admin->id, session('impersonated_by'));

        // 2. View corporate dashboard
        $dashboardResp = $this->get(route('corporate.dashboard'));
        $dashboardResp->assertStatus(200);
        $dashboardResp->assertSee('CTO Impersonation Active:');

        // 3. Exit impersonation
        $leaveResp = $this->post(route('ops.impersonate.leave'));
        $leaveResp->assertRedirect(route('ops.partners.index'));
        $this->assertEquals($this->admin->id, auth()->id());
    }

    public function test_non_cto_cannot_impersonate_accounts(): void
    {
        $opsRole = \App\Models\Role::where('key', 'platform_operations_manager')
            ->orWhere('organization_type', 'platform')
            ->firstOrFail();

        $staff = User::create([
            'name' => 'Akosua Darko',
            'email' => 'akosua.ops@nodiva.com',
            'job_title' => 'Operations Analyst',
            'password' => bcrypt('Password2026!'),
        ]);

        $platformOrg = Organization::where('type', 'platform')->firstOrFail();
        \App\Models\OrganizationMembership::create([
            'user_id' => $staff->id,
            'organization_id' => $platformOrg->id,
            'role_id' => $opsRole->id,
            'status' => 'active',
        ]);

        $this->assertFalse($staff->isCto());

        $labAdmin = User::where('email', 'admin@synlab-gh.example')->firstOrFail();

        $response = $this->actingAs($staff)->post(route('ops.impersonate.user', $labAdmin));
        $response->assertStatus(403);
    }
}
