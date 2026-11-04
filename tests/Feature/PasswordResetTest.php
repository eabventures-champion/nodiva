<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->user = User::where('email', 'admin@nodiva.com')->firstOrFail();
    }

    public function test_login_page_renders_forgot_password_link(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee(route('password.request'));
        $response->assertSee('Forgot password?');
    }

    public function test_guest_can_view_forgot_password_screen(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertSee('Reset Your Password');
        $response->assertSee('Institutional Email Address');
        $response->assertSee('Send Password Reset Link');
    }

    public function test_password_reset_link_can_be_requested_for_existing_active_user(): void
    {
        Mail::fake();

        $response = $this->post(route('password.email'), [
            'email' => $this->user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'If an active account exists for that email, a secure password reset link has been dispatched to your inbox.');

        // Token should be created in DB
        $record = DB::table('password_reset_tokens')->where('email', $this->user->email)->first();
        $this->assertNotNull($record);
        $this->assertNotEmpty($record->token);

        Mail::assertSent(PasswordResetMail::class, function ($mail) use ($record) {
            return $mail->hasTo($this->user->email)
                && $mail->token === $record->token;
        });

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.password_reset_requested',
            'auditable_id' => $this->user->id,
            'auditable_type' => 'User',
        ]);
    }

    public function test_password_reset_request_for_nonexistent_email_does_not_reveal_user_absence(): void
    {
        Mail::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'doesnotexist@institution.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'If an active account exists for that email, a secure password reset link has been dispatched to your inbox.');

        Mail::assertNothingSent();
    }

    public function test_guest_can_view_reset_password_screen_with_valid_token(): void
    {
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $this->user->email,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Set New Password');
        $response->assertSee($this->user->email);
        $response->assertSee('New Password (Minimum 8 characters)');
        $response->assertSee('Confirm New Password');
    }

    public function test_reset_password_screen_rejects_invalid_token(): void
    {
        $response = $this->get(route('password.reset', [
            'token' => 'invalid-token-12345',
            'email' => $this->user->email,
        ]));

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHasErrors(['email']);
    }

    public function test_reset_password_screen_rejects_expired_token(): void
    {
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => $token,
            'created_at' => now()->subMinutes(65),
        ]);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $this->user->email,
        ]));

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHasErrors(['email']);

        // Should clean up expired token
        $this->assertNull(DB::table('password_reset_tokens')->where('email', $this->user->email)->first());
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $newPassword = 'NewSecurePassword2026!';

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Your password has been successfully reset. You may now sign in with your new credentials.');

        // Token should be deleted from DB
        $this->assertNull(DB::table('password_reset_tokens')->where('email', $this->user->email)->first());

        // Password hash updated
        $this->user->refresh();
        $this->assertTrue(Hash::check($newPassword, $this->user->password));

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.password_reset_completed',
            'auditable_id' => $this->user->id,
            'auditable_type' => 'User',
        ]);

        // User can now log in with the new password
        $loginResponse = $this->post(route('login'), [
            'email' => $this->user->email,
            'password' => $newPassword,
        ]);

        $loginResponse->assertRedirect();
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_password_reset_fails_if_passwords_do_not_match(): void
    {
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'MismatchPassword456',
        ]);

        $response->assertSessionHasErrors(['password']);
    }
}
