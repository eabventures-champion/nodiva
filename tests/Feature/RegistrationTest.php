<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_can_view_general_registration_page(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Request Institutional Access');
        $response->assertSee('Referring Hospital');
        $response->assertSee('Testing Laboratory');
        $response->assertSee('Corporate Client');
    }

    public function test_can_view_registration_page_with_preselected_types(): void
    {
        $hospitalResp = $this->get('/register?type=hospital');
        $hospitalResp->assertStatus(200);
        $hospitalResp->assertSee('selectedType: \'hospital\'', false);

        $labResp = $this->get('/register?type=laboratory');
        $labResp->assertStatus(200);
        $labResp->assertSee('selectedType: \'laboratory\'', false);

        $corpResp = $this->get('/register?type=corporate');
        $corpResp->assertStatus(200);
        $corpResp->assertSee('selectedType: \'corporate\'', false);
    }

    public function test_can_register_new_hospital_organization(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $payload = [
            'type' => 'hospital',
            'organization_name' => 'Nyaho Medical Centre',
            'registration_number' => 'HEFRA/HOSP/2026/099',
            'organization_email' => 'admin@nyaho.example',
            'organization_phone' => '+233302770000',
            'city' => 'Accra',
            'name' => 'Dr. Elikem Tamaklo',
            'job_title' => 'Managing Director',
            'email' => 'elikem@nyaho.example',
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
            'consent_data_protection' => '1',
        ];

        $response = $this->post('/register', $payload);

        $org = Organization::where('email', 'admin@nyaho.example')->first();
        $this->assertNotNull($org);
        $this->assertEquals('hospital', $org->type->value);
        // Organization status must be pending review, not immediately active
        $this->assertEquals('pending', $org->status->value);
        $this->assertNull($org->approved_at);

        $user = User::where('email', 'elikem@nyaho.example')->first();
        $this->assertNotNull($user);

        // Submitting access request shows pending view and does not log user into hospital workspace
        $response->assertStatus(200);
        $response->assertSee('Access Request Submitted');
        $response->assertSee('Status: Pending Verification');
        $this->assertGuest();

        // Operations notification email dispatched
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\NewAccessRequestNotification::class);
    }

    public function test_can_register_new_laboratory_organization(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $payload = [
            'type' => 'laboratory',
            'organization_name' => 'Lancet Laboratories Ghana',
            'registration_number' => 'AHPC/LAB/2026/045',
            'organization_email' => 'intake@lancet-gh.example',
            'organization_phone' => '+233302761999',
            'city' => 'Accra',
            'name' => 'Dr. Peter Lamptey',
            'job_title' => 'Laboratory Director',
            'email' => 'peter@lancet-gh.example',
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
            'consent_data_protection' => '1',
        ];

        $response = $this->post('/register', $payload);

        $org = Organization::where('email', 'intake@lancet-gh.example')->first();
        $this->assertNotNull($org);
        $this->assertEquals('laboratory', $org->type->value);
        $this->assertEquals('pending', $org->status->value);
        $this->assertNotNull($org->laboratoryProfile);

        $response->assertStatus(200);
        $response->assertSee('Access Request Submitted');
        $this->assertGuest();
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\NewAccessRequestNotification::class);
    }

    public function test_can_register_new_corporate_organization(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $payload = [
            'type' => 'corporate',
            'organization_name' => 'MTN Ghana Headquarters',
            'registration_number' => 'RGD-MTN-2026-001',
            'organization_email' => 'wellness@mtn.example',
            'organization_phone' => '+233244300000',
            'city' => 'Accra',
            'name' => 'Kweku Boadu',
            'job_title' => 'Chief Human Resources Officer',
            'email' => 'kweku@mtn.example',
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
            'consent_data_protection' => '1',
        ];

        $response = $this->post('/register', $payload);

        $org = Organization::where('email', 'wellness@mtn.example')->first();
        $this->assertNotNull($org);
        $this->assertEquals('corporate', $org->type->value);
        $this->assertEquals('pending', $org->status->value);

        $response->assertStatus(200);
        $response->assertSee('Access Request Submitted');
        $this->assertGuest();
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\NewAccessRequestNotification::class);
    }

    public function test_operations_can_confirm_and_activate_pending_partner(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $admin = User::where('email', 'admin@nodiva.com')->firstOrFail();

        // Register a hospital in pending state
        $hospital = new Organization([
            'name' => 'Trust Hospital Osu',
            'email' => 'trust@hospital.example',
            'phone' => '+233302761111',
            'city' => 'Accra',
        ]);
        $hospital->type = \App\Domain\Organizations\Enums\OrganizationType::Hospital;
        $hospital->status = \App\Domain\Organizations\Enums\OrganizationStatus::Pending;
        $hospital->save();

        $doctorRole = Role::where('key', 'hospital_admin')->firstOrFail();
        $doctor = User::create([
            'name' => 'Dr. Anthony Boateng',
            'email' => 'dr.anthony@hospital.example',
            'password' => \Illuminate\Support\Facades\Hash::make('SecurePassword2026!'),
            'job_title' => 'Chief Medical Officer',
        ]);

        $membership = OrganizationMembership::create([
            'user_id' => $doctor->id,
            'organization_id' => $hospital->id,
            'role_id' => $doctorRole->id,
            'status' => 'active',
        ]);
        $doctor->current_membership_id = $membership->id;
        $doctor->save();

        // 1. Pending hospital doctor cannot access hospital workspace before activation
        $attemptLogin = $this->post('/login', [
            'email' => 'dr.anthony@hospital.example',
            'password' => 'SecurePassword2026!',
        ]);
        $attemptLogin->assertSessionHasErrors('email');
        $this->assertGuest();

        // 2. Operations Console confirms & activates institution
        $response = $this->actingAs($admin)->patch(route('ops.partners.status', $hospital), [
            'status' => 'active',
            'status_reason' => 'HEFRA license confirmed against national database.',
        ]);

        $response->assertRedirect();
        $this->assertEquals('active', $hospital->fresh()->status->value);
        $this->assertNotNull($hospital->fresh()->approved_at);

        // Approval email sent to institutional lead
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\OrganizationApprovedNotification::class);

        // 3. Now hospital doctor can log in successfully
        auth()->logout();
        $loginSuccess = $this->post('/login', [
            'email' => 'dr.anthony@hospital.example',
            'password' => 'SecurePassword2026!',
        ]);
        $loginSuccess->assertRedirect(route('hospital.dashboard'));
        $this->assertAuthenticatedAs($doctor);
    }

    public function test_operations_can_activate_partner_using_numeric_id_or_uuid(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $admin = User::where('email', 'admin@nodiva.com')->firstOrFail();

        $hospital = new Organization([
            'name' => 'Kaneshie Polyclinic',
            'email' => 'kaneshie@hospital.example',
            'phone' => '+233302111222',
            'city' => 'Accra',
        ]);
        $hospital->type = \App\Domain\Organizations\Enums\OrganizationType::Hospital;
        $hospital->status = \App\Domain\Organizations\Enums\OrganizationStatus::Pending;
        $hospital->save();

        // Calling with numeric integer ID (as seen in browser url /ops/partners/{id}/status)
        $response = $this->actingAs($admin)->patch("/ops/partners/{$hospital->id}/status", [
            'status' => 'active',
            'status_reason' => 'Inspected and verified via numeric ID endpoint.',
        ]);

        $response->assertRedirect();
        $this->assertEquals('active', $hospital->fresh()->status->value);
    }

    public function test_registered_laboratory_administrator_can_provision_scientists_and_pathologists_after_platform_approval(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $opsAdmin = User::where('email', 'admin@nodiva.com')->firstOrFail();

        // 1. User registers as testing laboratory
        $payload = [
            'type' => 'laboratory',
            'organization_name' => 'Medlab Diagnostics Ghana',
            'registration_number' => 'AHPC/LAB/2026/999',
            'organization_email' => 'intake@medlab-gh.example',
            'organization_phone' => '+233302888999',
            'city' => 'Accra',
            'name' => 'Dr. Victoria Mensah',
            'job_title' => 'Laboratory Administrator & General Manager',
            'email' => 'victoria@medlab-gh.example',
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
            'consent_data_protection' => '1',
        ];

        $registerResp = $this->post('/register', $payload);
        $registerResp->assertStatus(200);

        $lab = Organization::where('email', 'intake@medlab-gh.example')->firstOrFail();
        $labAdmin = User::where('email', 'victoria@medlab-gh.example')->firstOrFail();
        $this->assertEquals('pending', $lab->status->value);

        // Verify registrant was assigned lab_admin role
        $membership = $labAdmin->memberships()->where('organization_id', $lab->id)->firstOrFail();
        $this->assertEquals('lab_admin', $membership->role?->key);

        // 2. Platform Admin approves the laboratory
        $approveResp = $this->actingAs($opsAdmin)->patch(route('ops.partners.status', $lab), [
            'status' => 'active',
            'status_reason' => 'AHPC license verified.',
        ]);
        $approveResp->assertRedirect();
        $this->assertEquals('active', $lab->fresh()->status->value);

        // 3. Laboratory Administrator signs in and accesses team governance
        auth()->logout();
        $loginResp = $this->post('/login', [
            'email' => 'victoria@medlab-gh.example',
            'password' => 'SecurePass2026!',
        ]);
        $loginResp->assertRedirect(route('lab.dashboard'));

        // Access team page
        $teamPageResp = $this->actingAs($labAdmin)->get(route('lab.team.index'));
        $teamPageResp->assertStatus(200);
        $teamPageResp->assertSee('Laboratory Team &amp; Access Governance', false);

        // 4. Lab Administrator provisions Medical Laboratory Scientist
        $scientistRole = Role::where('key', 'lab_scientist')->firstOrFail();
        $scientistResp = $this->actingAs($labAdmin)->post(route('lab.team.invite'), [
            'name' => 'Samuel Owusu, MLS',
            'email' => 'samuel.mls@medlab-gh.example',
            'job_title' => 'Senior Medical Laboratory Scientist',
            'role_id' => $scientistRole->id,
            'mode' => 'create_direct',
            'password' => 'ScientistPass2026!',
        ]);
        $scientistResp->assertRedirect();
        $scientistResp->assertSessionHas('success');

        // 5. Lab Administrator provisions Consultant Pathologist & Clinical Director
        $pathologistRole = Role::where('key', 'lab_pathologist')->firstOrFail();
        $pathologistResp = $this->actingAs($labAdmin)->post(route('lab.team.invite'), [
            'name' => 'Prof. Joseph Mensah, FRCPath',
            'email' => 'joseph.pathologist@medlab-gh.example',
            'job_title' => 'Consultant Pathologist & Clinical Director',
            'role_id' => $pathologistRole->id,
            'mode' => 'create_direct',
            'password' => 'PathologistPass2026!',
        ]);
        $pathologistResp->assertRedirect();
        $pathologistResp->assertSessionHas('success');

        // Verify both accounts exist in DB under this laboratory with respective roles
        $scientist = User::where('email', 'samuel.mls@medlab-gh.example')->firstOrFail();
        $this->assertEquals('lab_scientist', $scientist->memberships()->where('organization_id', $lab->id)->first()->role->key);

        $pathologist = User::where('email', 'joseph.pathologist@medlab-gh.example')->firstOrFail();
        $this->assertEquals('lab_pathologist', $pathologist->memberships()->where('organization_id', $lab->id)->first()->role->key);
    }

    public function test_registered_hospital_administrator_can_provision_clinicians_and_coordinators_after_platform_approval(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $opsAdmin = User::where('email', 'admin@nodiva.com')->firstOrFail();

        // 1. User registers as referring hospital
        $payload = [
            'type' => 'hospital',
            'organization_name' => 'Korle-Bu Premier Clinic',
            'registration_number' => 'HEFRA/HOSP/2026/777',
            'organization_email' => 'admin@korlebu-premier.example',
            'organization_phone' => '+233302555666',
            'city' => 'Accra',
            'name' => 'Dr. Kojo Frimpong',
            'job_title' => 'Chief Medical Officer & Hospital Administrator',
            'email' => 'kojo@korlebu-premier.example',
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
            'consent_data_protection' => '1',
        ];

        $registerResp = $this->post('/register', $payload);
        $registerResp->assertStatus(200);

        $hospital = Organization::where('email', 'admin@korlebu-premier.example')->firstOrFail();
        $hospAdmin = User::where('email', 'kojo@korlebu-premier.example')->firstOrFail();
        $this->assertEquals('pending', $hospital->status->value);

        // Verify registrant was assigned hospital_admin role
        $membership = $hospAdmin->memberships()->where('organization_id', $hospital->id)->firstOrFail();
        $this->assertEquals('hospital_admin', $membership->role?->key);

        // 2. Platform Admin approves the hospital
        $approveResp = $this->actingAs($opsAdmin)->patch(route('ops.partners.status', $hospital), [
            'status' => 'active',
            'status_reason' => 'HEFRA operating license confirmed.',
        ]);
        $approveResp->assertRedirect();
        $this->assertEquals('active', $hospital->fresh()->status->value);

        // 3. Hospital Administrator signs in and accesses team governance
        auth()->logout();
        $loginResp = $this->post('/login', [
            'email' => 'kojo@korlebu-premier.example',
            'password' => 'SecurePass2026!',
        ]);
        $loginResp->assertRedirect(route('hospital.dashboard'));

        // Access team page
        $teamPageResp = $this->actingAs($hospAdmin)->get(route('hospital.team.index'));
        $teamPageResp->assertStatus(200);
        $teamPageResp->assertSee('Hospital Team & Access Governance');

        // 4. Hospital Administrator provisions Consultant Physician
        $clinicianRole = Role::where('key', 'hospital_clinician')->firstOrFail();
        $clinicianResp = $this->actingAs($hospAdmin)->post(route('hospital.team.invite'), [
            'name' => 'Dr. Akua Donkor',
            'email' => 'akua.doctor@korlebu-premier.example',
            'job_title' => 'Consultant Physician & Specialist',
            'role_id' => $clinicianRole->id,
            'mode' => 'create_direct',
            'password' => 'ClinicianPass2026!',
        ]);
        $clinicianResp->assertRedirect();
        $clinicianResp->assertSessionHas('success');

        // 5. Hospital Administrator provisions Referral Coordinator
        $coordinatorRole = Role::where('key', 'hospital_coordinator')->firstOrFail();
        $coordinatorResp = $this->actingAs($hospAdmin)->post(route('hospital.team.invite'), [
            'name' => 'Kofi Asante',
            'email' => 'kofi.desk@korlebu-premier.example',
            'job_title' => 'Clinical Referral Desk Officer',
            'role_id' => $coordinatorRole->id,
            'mode' => 'create_direct',
            'password' => 'CoordinatorPass2026!',
        ]);
        $coordinatorResp->assertRedirect();
        $coordinatorResp->assertSessionHas('success');

        // Verify both accounts exist in DB under this hospital with respective roles
        $clinician = User::where('email', 'akua.doctor@korlebu-premier.example')->firstOrFail();
        $this->assertEquals('hospital_clinician', $clinician->memberships()->where('organization_id', $hospital->id)->first()->role->key);

        $coordinator = User::where('email', 'kofi.desk@korlebu-premier.example')->firstOrFail();
        $this->assertEquals('hospital_coordinator', $coordinator->memberships()->where('organization_id', $hospital->id)->first()->role->key);
    }

    public function test_registered_corporate_administrator_can_provision_physicians_and_hr_after_platform_approval(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $opsAdmin = User::where('email', 'admin@nodiva.com')->firstOrFail();

        // 1. User registers as corporate client
        $payload = [
            'type' => 'corporate',
            'organization_name' => 'Gold Fields Ghana Ltd',
            'registration_number' => 'RGD-CS-2026-8888',
            'organization_email' => 'wellness@goldfields.example',
            'organization_phone' => '+233302777888',
            'city' => 'Tarkwa',
            'name' => 'Kwame Baah',
            'job_title' => 'Corporate Health & Wellbeing Director',
            'email' => 'kwame.baah@goldfields.example',
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
            'consent_data_protection' => '1',
        ];

        $registerResp = $this->post('/register', $payload);
        $registerResp->assertStatus(200);

        $corp = Organization::where('email', 'wellness@goldfields.example')->firstOrFail();
        $corpAdmin = User::where('email', 'kwame.baah@goldfields.example')->firstOrFail();
        $this->assertEquals('pending', $corp->status->value);

        // Verify registrant was assigned corporate_admin role
        $membership = $corpAdmin->memberships()->where('organization_id', $corp->id)->firstOrFail();
        $this->assertEquals('corporate_admin', $membership->role?->key);

        // 2. Platform Admin approves the corporate client
        $approveResp = $this->actingAs($opsAdmin)->patch(route('ops.partners.status', $corp), [
            'status' => 'active',
            'status_reason' => 'Corporate commercial registration and employee wellness mandate verified.',
        ]);
        $approveResp->assertRedirect();
        $this->assertEquals('active', $corp->fresh()->status->value);

        // 3. Corporate Administrator signs in and accesses team governance
        auth()->logout();
        $loginResp = $this->post('/login', [
            'email' => 'kwame.baah@goldfields.example',
            'password' => 'SecurePass2026!',
        ]);
        $loginResp->assertRedirect(route('corporate.dashboard'));

        // Access team page
        $teamPageResp = $this->actingAs($corpAdmin)->get(route('corporate.team.index'));
        $teamPageResp->assertStatus(200);
        $teamPageResp->assertSee('Corporate Team & Access Governance');

        // 4. Corporate Administrator provisions Occupational Health Physician
        $physicianRole = Role::where('key', 'corporate_physician')->firstOrFail();
        $physicianResp = $this->actingAs($corpAdmin)->post(route('corporate.team.invite'), [
            'name' => 'Dr. Eric Agyemang',
            'email' => 'eric.physician@goldfields.example',
            'job_title' => 'Occupational Health Physician',
            'role_id' => $physicianRole->id,
            'mode' => 'create_direct',
            'password' => 'PhysicianPass2026!',
        ]);
        $physicianResp->assertRedirect();
        $physicianResp->assertSessionHas('success');

        // 5. Corporate Administrator provisions HR / Wellness Coordinator
        $hrRole = Role::where('key', 'corporate_hr')->firstOrFail();
        $hrResp = $this->actingAs($corpAdmin)->post(route('corporate.team.invite'), [
            'name' => 'Efua Boadi',
            'email' => 'efua.hr@goldfields.example',
            'job_title' => 'HR & Wellness Coordinator',
            'role_id' => $hrRole->id,
            'mode' => 'create_direct',
            'password' => 'HrPass2026!',
        ]);
        $hrResp->assertRedirect();
        $hrResp->assertSessionHas('success');

        // Verify both accounts exist in DB under this corporate org with respective roles
        $physician = User::where('email', 'eric.physician@goldfields.example')->firstOrFail();
        $this->assertEquals('corporate_physician', $physician->memberships()->where('organization_id', $corp->id)->first()->role->key);

        $hr = User::where('email', 'efua.hr@goldfields.example')->firstOrFail();
        $this->assertEquals('corporate_hr', $hr->memberships()->where('organization_id', $corp->id)->first()->role->key);
    }
}
