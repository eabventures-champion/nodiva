<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly AuditLogger $audit
    ) {
    }

    public function show(Request $request): View
    {
        $user = $request->user();
        $org = $this->current->organization();
        $membership = $this->current->membership();

        $dashboardRoute = $org ? route($org->type->workspace() . '.dashboard') : route('home');
        $workspaceLabel = $org ? $org->type->label() : 'Network Operations';
        $canManageOrg = $org && ($membership?->isAdmin() || $membership?->can('org.manage') || $membership?->can('lab.profile.manage'));

        return view('profile.show', [
            'user' => $user,
            'organization' => $org,
            'membership' => $membership,
            'dashboardRoute' => $dashboardRoute,
            'workspaceLabel' => $workspaceLabel,
            'canManageOrg' => $canManageOrg,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'job_title' => ['required', 'string', 'max:100'],
        ]);

        $user->update($validated);

        try {
            $actor = $this->current->actor();
            $this->audit->log('user.profile_updated', $user, [
                'name' => $user->name,
                'job_title' => $user->job_title,
                'email' => $user->email,
            ], 'success', $actor);
        } catch (\Throwable) {
            // Non-blocking if actor context is unavailable
        }

        return redirect()->route('profile.show')->with('success', 'Your institutional profile details have been updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        try {
            $actor = $this->current->actor();
            $this->audit->log('user.password_changed', $user, [
                'user_id' => $user->id,
            ], 'success', $actor);
        } catch (\Throwable) {
            // Non-blocking
        }

        return redirect()->route('profile.show')->with('success', 'Your account credentials and institutional password have been securely updated.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $user = $request->user();

        $prefs = [
            'referral_alerts' => [
                'email' => (bool) $request->boolean('notify_referral_email'),
                'sms' => (bool) $request->boolean('notify_referral_sms'),
            ],
            'result_release' => [
                'email' => (bool) $request->boolean('notify_results_email'),
                'sms' => (bool) $request->boolean('notify_results_sms'),
            ],
            'security_digest' => [
                'email' => (bool) $request->boolean('notify_security_email'),
            ],
        ];

        $user->update([
            'notification_preferences' => $prefs,
        ]);

        return redirect()->route('profile.show')->with('success', 'Notification and dispatch alert preferences updated.');
    }

    public function updateOrganization(Request $request): RedirectResponse
    {
        $org = $this->current->organization();
        $membership = $this->current->membership();

        if (! $org || (! $membership?->isAdmin() && ! $membership?->can('org.manage') && ! $membership?->can('lab.profile.manage'))) {
            abort(403, 'Unauthorized. Administrator credentials required to update institution profile.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:100'],
            'address_line' => ['nullable', 'string', 'max:255'],
        ]);

        $oldName = $org->name;
        $org->update($validated);

        try {
            $actor = $this->current->actor();
            $this->audit->log('organization.profile_updated', $org, [
                'old_name' => $oldName,
                'new_name' => $org->name,
                'email' => $org->email,
                'city' => $org->city,
            ], 'success', $actor);
        } catch (\Throwable $e) {
            // Non-blocking
        }

        return redirect()->route('profile.show')
            ->with('success', "Institution details successfully updated. Facility name is now '{$org->name}'.");
    }
}
