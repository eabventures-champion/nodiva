<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratory;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Mail\TeamMemberInvitationMail;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class TeamController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly AuditLogger $audit,
    ) {
    }

    private function authorizeAdmin(): void
    {
        $org = $this->current->organization();
        $actor = Actor::for(auth()->user(), $org);

        if (! $actor->can('users.manage') && ! $actor->membership->role?->is_admin) {
            abort(403, 'Unauthorized. Laboratory administrative access required to manage personnel.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $org = $this->current->organization();

        // 1. Active and provisioned laboratory personnel
        $members = OrganizationMembership::with(['user', 'role.permissions'])
            ->where('organization_id', $org->id)
            ->get();

        // 2. Pending invitations
        $invitations = OrganizationInvitation::with(['role', 'inviter'])
            ->where('organization_id', $org->id)
            ->latest()
            ->get();

        // 3. Laboratory Roles
        $roles = Role::with('permissions')
            ->where('organization_type', 'laboratory')
            ->get();

        // 4. Clinical & Analytical Protocol Definitions
        $protocols = [
            'lab_scientist' => [
                'name' => 'Analytical Testing Protocol',
                'badge' => 'Analytical Entry',
                'standard' => 'AHPC Ghana / ISO 15189 §5.3',
                'description' => 'Specimen accessioning, pre-analytical verification, bench testing and quantitative analyte entry. Segregated from final release under dual-control compliance.',
                'permissions' => ['orders.view', 'orders.respond', 'specimens.receive', 'results.enter'],
            ],
            'lab_pathologist' => [
                'name' => 'Clinical Correlation & Release Protocol',
                'badge' => 'Clinical Sign-off',
                'standard' => 'ISO 15189 §5.8 Dual Control',
                'description' => 'Medical evaluation, clinical interpretation, and official report release authorization to referring physicians.',
                'permissions' => ['orders.view', 'results.enter', 'results.release'],
            ],
            'lab_admin' => [
                'name' => 'Institutional Administration Protocol',
                'badge' => 'Full Lab Admin',
                'standard' => 'Governance & Staff Provisioning',
                'description' => 'Personnel management, accreditation compliance, test catalogue configuration and operational oversight.',
                'permissions' => ['users.manage', 'catalogue.manage', 'lab.qualifications.manage', 'orders.view', 'specimens.receive'],
            ],
            'lab_finance' => [
                'name' => 'Financial Reconciliation Protocol',
                'badge' => 'Finance & Billing',
                'standard' => 'Financial Operations',
                'description' => 'Access to settlement statements, payout reconciliation, and fee schedules.',
                'permissions' => ['finance.view', 'orders.view', 'support.manage'],
            ],
        ];

        return view('laboratory.team.index', [
            'organization' => $org,
            'members' => $members,
            'invitations' => $invitations,
            'roles' => $roles,
            'protocols' => $protocols,
        ]);
    }

    public function invite(Request $request)
    {
        $this->authorizeAdmin();

        $org = $this->current->organization();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'job_title' => ['required', 'string', 'max:100'],
            'role_id' => ['required', 'exists:roles,id'],
            'mode' => ['required', 'in:invite_email,create_direct'],
            'password' => ['nullable', 'required_if:mode,create_direct', 'string', 'min:8'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        if ($role->organization_type !== 'laboratory') {
            return back()->with('error', 'Selected role is not a valid laboratory archetype.');
        }

        $email = strtolower(trim($validated['email']));

        // Check if user already active member
        $existing = OrganizationMembership::where('organization_id', $org->id)
            ->whereHas('user', fn ($q) => $q->where('email', $email))
            ->where('status', 'active')
            ->exists();

        if ($existing) {
            return back()->with('error', "User '{$email}' is already an active member of {$org->name}.");
        }

        $actor = Actor::for(auth()->user(), $org);

        if ($validated['mode'] === 'create_direct') {
            DB::transaction(function () use ($email, $validated, $org, $role, $actor) {
                $user = User::firstOrNew(['email' => $email]);
                $user->name = trim($validated['name']);
                $user->job_title = trim($validated['job_title']);
                $user->password = Hash::make($validated['password']);
                $user->status = 'active';
                $user->save();

                $membership = OrganizationMembership::firstOrNew([
                    'user_id' => $user->id,
                    'organization_id' => $org->id,
                ]);
                $membership->role_id = $role->id;
                $membership->status = 'active';
                $membership->invited_by = auth()->id();
                $membership->save();

                if (! $user->current_membership_id) {
                    $user->current_membership_id = $membership->id;
                    $user->save();
                }

                $this->audit->log('laboratory.staff.created', $user, [
                    'email' => $email,
                    'role' => $role->name,
                    'role_key' => $role->key,
                ], 'success', $actor);
            });

            return back()->with('success', "Personnel '{$validated['name']}' provisioned immediately under the {$role->name} protocol. Sign-in credentials are active.");
        }

        // Email Invitation Mode
        OrganizationInvitation::where('organization_id', $org->id)
            ->where('email', $email)
            ->where('status', 'pending')
            ->delete();

        $invitation = OrganizationInvitation::create([
            'organization_id' => $org->id,
            'email' => $email,
            'name' => trim($validated['name']),
            'job_title' => trim($validated['job_title']),
            'role_id' => $role->id,
            'token' => Str::random(48),
            'invited_by' => auth()->id(),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        try {
            Mail::to($invitation->email)->send(new TeamMemberInvitationMail($invitation));
        } catch (\Throwable $e) {
            Log::warning("Failed to dispatch lab staff invitation email: {$e->getMessage()}");
        }

        $this->audit->log('laboratory.staff.invited', $invitation, [
            'email' => $invitation->email,
            'role' => $role->name,
            'expires_at' => $invitation->expires_at->toIso8601String(),
        ], 'success', $actor);

        return back()->with('success', "Invitation sent to {$invitation->email} with role '{$role->name}'.");
    }

    public function update(Request $request, OrganizationMembership $membership)
    {
        $this->authorizeAdmin();

        $org = $this->current->organization();
        if ($membership->organization_id !== $org->id) {
            abort(403, 'Unauthorized organization membership access.');
        }

        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        if ($membership->user_id === auth()->id() && $validated['status'] === 'suspended') {
            return back()->with('error', 'You cannot suspend your own administrative session.');
        }

        $role = Role::findOrFail($validated['role_id']);
        if ($role->organization_type !== 'laboratory') {
            return back()->with('error', 'Selected role is not a valid laboratory archetype.');
        }

        $membership->role_id = $role->id;
        $membership->status = $validated['status'];
        $membership->save();

        $actor = Actor::for(auth()->user(), $org);
        $this->audit->log('laboratory.staff.updated', $membership, [
            'user_id' => $membership->user_id,
            'role' => $role->name,
            'status' => $membership->status,
        ], 'success', $actor);

        return back()->with('success', "Updated profile & role protocol for {$membership->user->name}.");
    }

    public function revokeInvitation(OrganizationInvitation $invitation)
    {
        $this->authorizeAdmin();

        $org = $this->current->organization();
        if ($invitation->organization_id !== $org->id) {
            abort(403);
        }

        $invitation->delete();

        return back()->with('success', "Invitation for {$invitation->email} revoked.");
    }

    public function resendInvitation(OrganizationInvitation $invitation)
    {
        $this->authorizeAdmin();

        $org = $this->current->organization();
        if ($invitation->organization_id !== $org->id) {
            abort(403);
        }

        $invitation->token = Str::random(48);
        $invitation->expires_at = now()->addDays(7);
        $invitation->save();

        try {
            Mail::to($invitation->email)->send(new TeamMemberInvitationMail($invitation));
        } catch (\Throwable $e) {
            Log::warning("Failed to resend lab staff invitation email: {$e->getMessage()}");
        }

        return back()->with('success', "Invitation resent to {$invitation->email}.");
    }
}
