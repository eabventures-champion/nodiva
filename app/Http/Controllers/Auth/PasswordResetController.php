<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::where('email', $email)->first();

        if ($user && $user->isActive()) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => $token,
                    'created_at' => now(),
                ]
            );

            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);

            try {
                Mail::to($user->email)->send(new PasswordResetMail($user, $token, $resetUrl));
            } catch (\Throwable $e) {
                Log::error("Failed to dispatch password reset email to {$user->email}: {$e->getMessage()}");

                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Unable to dispatch email via the mail gateway (' . $e->getMessage() . '). Please try again or contact system administration.']);
            }

            try {
                $org = $user->activeMemberships()->first()?->organization;
                $actor = Actor::for($user, $org);
                $this->audit->log('user.password_reset_requested', $user, [
                    'email' => $user->email,
                ], 'success', $actor);
            } catch (\Throwable $e) {
                Log::warning("Audit logging failed during password reset request: {$e->getMessage()}");
            }
        }

        return back()->with('status', 'If an active account exists for that email, a secure password reset link has been dispatched to your inbox.');
    }

    public function showResetForm(Request $request, string $token)
    {
        $email = $request->query('email');

        $record = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->when($email, fn ($q) => $q->where('email', $email))
            ->first();

        if (! $record) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'This password reset link is invalid or has already been used. Please request a new link.']);
        }

        // 60-minute token lifetime
        if (now()->subMinutes(60)->greaterThan($record->created_at)) {
            DB::table('password_reset_tokens')->where('email', $record->email)->delete();

            return redirect()->route('password.request')
                ->withErrors(['email' => 'This password reset link has expired (validity is 60 minutes). Please request a new link.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $record->email,
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $email = strtolower(trim($validated['email']));

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $validated['token'])
            ->first();

        if (! $record) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'This password reset verification token is invalid or has already been used.']);
        }

        if (now()->subMinutes(60)->greaterThan($record->created_at)) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return redirect()->route('password.request')
                ->withErrors(['email' => 'This password reset link has expired. Please request a new link.']);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'No institutional user found with this email address.']);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        // Invalidate used token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Audit Log
        try {
            $org = $user->activeMemberships()->first()?->organization;
            $actor = Actor::for($user, $org);
            $this->audit->log('user.password_reset_completed', $user, [
                'email' => $user->email,
            ], 'success', $actor);
        } catch (\Throwable $e) {
            Log::warning("Audit logging failed during password reset completion: {$e->getMessage()}");
        }

        return redirect()->route('login')
            ->with('status', 'Your password has been successfully reset. You may now sign in with your new credentials.');
    }
}
