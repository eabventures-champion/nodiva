<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Operations\TeamController;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class InvitationController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function showAcceptForm(string $token)
    {
        $invitation = OrganizationInvitation::with(['organization', 'role.permissions', 'inviter'])
            ->where('token', $token)
            ->first();

        if (! $invitation || ! $invitation->isPending()) {
            $reason = ! $invitation 
                ? 'The invitation token was not found in our security registry.'
                : ($invitation->isExpired() ? 'This invitation link has expired. Please contact Platform Operations to request a new invitation.' : 'This invitation link has already been accepted or revoked.');

            return view('auth.invitation-invalid', [
                'reason' => $reason,
            ]);
        }

        $sidebarPermissions = TeamController::getSidebarPermissionsDefinition();

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'sidebarPermissions' => $sidebarPermissions,
        ]);
    }

    public function processAccept(Request $request, string $token)
    {
        $invitation = OrganizationInvitation::with(['organization', 'role.permissions'])
            ->where('token', $token)
            ->first();

        if (! $invitation || ! $invitation->isPending()) {
            return redirect()->route('login')->with('error', 'This invitation is no longer valid or has expired.');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($invitation, $validated) {
            // Find existing user or create a new user account
            $user = User::firstOrNew(['email' => $invitation->email]);
            $user->name = $invitation->name ?: ($user->name ?: 'Operations Officer');
            $user->job_title = $invitation->job_title ?: ($user->job_title ?: 'Operations Specialist');
            $user->password = Hash::make($validated['password']);
            $user->status = 'active';
            $user->save();

            // Link membership with designated role & custom sidebar permissions
            $membership = OrganizationMembership::firstOrNew([
                'user_id' => $user->id,
                'organization_id' => $invitation->organization_id,
            ]);
            $membership->role_id = $invitation->role_id;
            $membership->permissions = $invitation->permissions;
            $membership->invited_by = $invitation->invited_by;
            $membership->status = 'active';
            $membership->save();

            $user->current_membership_id = $membership->id;
            $user->save();

            // Mark invitation accepted
            $invitation->status = 'accepted';
            $invitation->accepted_at = now();
            $invitation->save();

            // Audit Log
            $actor = Actor::for($user, $invitation->organization);
            $this->audit->log('staff.invitation_accepted', $user, [
                'invitation_id' => $invitation->id,
                'email' => $user->email,
                'role_id' => $invitation->role_id,
            ], 'success', $actor);

            return $user;
        });

        // Automatically log the user in to their newly configured account
        Auth::login($user);
        session(['active_membership_id' => $user->current_membership_id]);

        return redirect()->route('ops.dashboard')->with('success', "Welcome to the NODIVA Operations Network, {$user->name}! Your operational account has been successfully configured.");
    }
}
