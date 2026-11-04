<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\CorporateSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $coo;
    private User $doctor;
    private User $scientist;
    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(CorporateSeeder::class);

        $this->coo = User::where('email', 'admin@nodiva.com')->firstOrFail();
        $this->doctor = User::where('email', 'doctor@ridgespecialist.com')->firstOrFail();
        $this->scientist = User::where('email', 'scientist@synlab-gh.example')->firstOrFail();
        $this->hr = User::where('email', 'hr@tullow.example')->firstOrFail();
    }

    public function test_guest_cannot_view_profile(): void
    {
        $response = $this->get(route('profile.show'));
        $response->assertRedirect(route('login'));
    }

    public function test_chief_operations_officer_can_view_profile(): void
    {
        $response = $this->actingAs($this->coo)->get(route('profile.show'));
        $response->assertStatus(200);
        $response->assertSee('Institutional User Profile');
        $response->assertSee('Chief Operations Officer');
        $response->assertSee('Dr. Kojo Mensah');
        $response->assertSee('Return to NODIVA Operations');
    }

    public function test_chief_operations_officer_can_update_profile_details(): void
    {
        $response = $this->actingAs($this->coo)->put(route('profile.update'), [
            'name' => 'Dr. Kojo Mensah, FWACP',
            'job_title' => 'Chief Operations Officer & VP Medical Networks',
            'email' => 'admin@nodiva.com',
            'phone' => '+233244998877',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        $this->coo->refresh();
        $this->assertEquals('Dr. Kojo Mensah, FWACP', $this->coo->name);
        $this->assertEquals('Chief Operations Officer & VP Medical Networks', $this->coo->job_title);
        $this->assertEquals('+233244998877', $this->coo->phone);
    }

    public function test_hospital_physician_can_view_and_update_profile(): void
    {
        $response = $this->actingAs($this->doctor)->get(route('profile.show'));
        $response->assertStatus(200);
        $response->assertSee('Dr. Ama Serwaa');
        $response->assertSee('Senior Consultant Physician');
        $response->assertSee('Return to Hospital');

        $updateResponse = $this->actingAs($this->doctor)->put(route('profile.update'), [
            'name' => 'Dr. Ama Serwaa Boateng, MD',
            'job_title' => 'Lead Consultant Physician & Head of Internal Medicine',
            'email' => 'doctor@ridgespecialist.com',
            'phone' => '+233201112233',
        ]);

        $updateResponse->assertRedirect(route('profile.show'));
        $this->doctor->refresh();
        $this->assertEquals('Dr. Ama Serwaa Boateng, MD', $this->doctor->name);
        $this->assertEquals('Lead Consultant Physician & Head of Internal Medicine', $this->doctor->job_title);
    }

    public function test_laboratory_scientist_can_view_and_update_profile(): void
    {
        $response = $this->actingAs($this->scientist)->get(route('profile.show'));
        $response->assertStatus(200);
        $response->assertSee('Emmanuel Osei, MLS');
        $response->assertSee('Senior Medical Laboratory Scientist');
        $response->assertSee('Return to Laboratory');
    }

    public function test_corporate_hr_manager_can_view_and_update_profile(): void
    {
        $response = $this->actingAs($this->hr)->get(route('profile.show'));
        $response->assertStatus(200);
        $response->assertSee('Abena Nyarko');
        $response->assertSee('Occupational Health &amp; Wellness Manager', false);
        $response->assertSee('Return to Corporate');
    }

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->coo)->put(route('profile.password'), [
            'current_password' => 'NodivaSecure2026!',
            'password' => 'NewSecurePassword2026!',
            'password_confirmation' => 'NewSecurePassword2026!',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        $this->coo->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword2026!', $this->coo->password));
    }

    public function test_user_cannot_update_password_with_invalid_current_password(): void
    {
        $response = $this->actingAs($this->coo)->put(route('profile.password'), [
            'current_password' => 'WrongPassword123!',
            'password' => 'NewSecurePassword2026!',
            'password_confirmation' => 'NewSecurePassword2026!',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $response = $this->actingAs($this->coo)->put(route('profile.notifications'), [
            'notify_referral_email' => '1',
            'notify_referral_sms' => '1',
            'notify_results_email' => '1',
            'notify_security_email' => '1',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        $this->coo->refresh();
        $this->assertTrue($this->coo->wantsNotification('sms', 'referral_alerts'));
        $this->assertTrue($this->coo->wantsNotification('email', 'result_release'));
    }
}
