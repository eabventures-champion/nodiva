<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Domain\Organizations\CurrentOrganization;
use App\Domain\Organizations\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * Impersonate a specific user account. Exclusively permitted to the Chief Technical Officer.
     */
    public function impersonateUser(Request $request, User $user)
    {
        $cto = auth()->user();

        if (! $cto?->isCto()) {
            abort(403, 'Privilege Denied: Only the Chief Technical Officer has the privilege to impersonate accounts.');
        }

        if ($user->id === $cto->id) {
            return back()->with('error', 'You cannot impersonate your own active session.');
        }

        // Locate active institutional membership for target user
        $membership = $user->activeMemberships()->with('organization')->first()
            ?? $user->memberships()->with('organization')->first();

        if (! $membership || ! $membership->organization) {
            return back()->with('error', "Target user {$user->name} has no valid institutional membership to impersonate.");
        }

        $org = $membership->organization;

        // Retain original CTO session identifier
        session(['impersonated_by' => $cto->id]);
        session(['active_membership_id' => $membership->id]);
        session(['impersonation_return_to' => $request->query('return_to') ?: url()->previous()]);

        // Audit Log
        $actor = Actor::for($cto, $this->current->organization());
        $this->audit->log('impersonation.started', $user, [
            'impersonator_id' => $cto->id,
            'impersonator_email' => $cto->email,
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'target_role' => $membership->role?->name ?? 'Staff',
            'organization_id' => $org->id,
            'organization_name' => $org->name,
            'organization_type' => $org->type->value,
        ], 'success', $actor);

        // Authenticate as target user
        auth()->login($user);

        // Redirect directly to the appropriate tenant dashboard
        $destination = match ($org->type) {
            OrganizationType::Hospital => route('hospital.dashboard'),
            OrganizationType::Laboratory => route('lab.dashboard'),
            OrganizationType::Corporate => route('corporate.dashboard'),
            default => route('ops.dashboard'),
        };

        return redirect($destination)->with('success', "CTO Premium Impersonation Active: You are now viewing the workspace as {$user->name} ({$user->job_title}) at {$org->name}.");
    }

    /**
     * Impersonate the primary administrator of an organization.
     */
    public function impersonateOrganization(Request $request, Organization $partner)
    {
        $cto = auth()->user();

        if (! $cto?->isCto()) {
            abort(403, 'Privilege Denied: Only the Chief Technical Officer has the privilege to impersonate accounts.');
        }

        // Find primary administrator membership (or first active member)
        $adminMembership = $partner->memberships()
            ->whereHas('role', fn ($q) => $q->where('is_admin', true))
            ->with(['user', 'organization'])
            ->first() ?? $partner->memberships()->with(['user', 'organization'])->first();

        if (! $adminMembership || ! $adminMembership->user) {
            return back()->with('error', "No administrator or personnel found for {$partner->name} to impersonate.");
        }

        return $this->impersonateUser($request, $adminMembership->user);
    }

    /**
     * Exit impersonation session and return to Operations Console as CTO.
     */
    public function leave(Request $request)
    {
        $ctoId = session('impersonated_by');

        if (! $ctoId) {
            return redirect('/');
        }

        $cto = User::find($ctoId);
        if (! $cto) {
            session()->forget('impersonated_by');
            auth()->logout();

            return redirect()->route('login');
        }

        $impersonatedUser = auth()->user();

        // Clear impersonation indicator
        session()->forget('impersonated_by');

        // Restore CTO platform membership
        $platformMembership = $cto->memberships()
            ->whereHas('organization', fn ($q) => $q->where('type', 'platform'))
            ->first();

        if ($platformMembership) {
            session(['active_membership_id' => $platformMembership->id]);
        }

        auth()->login($cto);

        // Audit Log
        $platformOrg = $platformMembership?->organization;
        if ($platformOrg) {
            $actor = Actor::for($cto, $platformOrg);
            $this->audit->log('impersonation.ended', $cto, [
                'impersonator_email' => $cto->email,
                'impersonated_user_id' => $impersonatedUser?->id,
                'impersonated_user_email' => $impersonatedUser?->email,
            ], 'success', $actor);
        }

        $returnUrl = session()->pull('impersonation_return_to') ?: route('ops.team.index');

        return redirect($returnUrl)
            ->with('success', "Exited impersonation session. Returned to Operations Network Console as Chief Technical Officer.");
    }
}
