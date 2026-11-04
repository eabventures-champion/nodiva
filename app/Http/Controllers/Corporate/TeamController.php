<?php

declare(strict_types=1);

namespace App\Http\Controllers\Corporate;

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
            abort(403, 'Unauthorized. Corporate administrative access required to manage personnel.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $org = $this->current->organization();

        // 1. Active corporate team members
        $members = OrganizationMembership::with(['user', 'role.permissions'])
            ->where('organization_id', $org->id)
            ->get();

        // 2. Pending invitations
        $invitations = OrganizationInvitation::with(['role', 'inviter'])
            ->where('organization_id', $org->id)
            ->latest()
            ->get();

        // 3. Corporate Roles
        $roles = Role::with('permissions')
            ->where('organization_type', 'corporate')
            ->get();

        // 4. Corporate Protocol Definitions
        $protocols = [
            'corporate_physician' => [
                'name' => 'Occupational Health Physician Protocol',
                'badge' => 'Occupational Health',
                'standard' => 'Ghana Data Protection Act 2012 (Act 843) / Employee Health Consent',
                'description' => 'Clinical evaluation of employee health screenings where explicit employee consent is granted. Confidential medical oversight.',
                'permissions' => ['program_reports.view', 'program_results.view_individual'],
            ],
            'corporate_hr' => [
                'name' => 'Workplace Health & Scheduling Protocol',
                'badge' => 'HR & Program Scheduling',
                'standard' => 'Privacy-Preserving Aggregate Analytics',
                'description' => 'Employee roster management, booking screening sessions, aggregate anonymized wellness insights. Strictly blocked from individual medical results.',
                'permissions' => ['programs.manage', 'employees.manage', 'program_reports.view'],
            ],
            'corporate_admin' => [
                'name' => 'Corporate Institutional Administration Protocol',
                'badge' => 'Full Corporate Admin',
                'standard' => 'Corporate Governance',
                'description' => 'Full administrative control of the corporate account, screening program creation, corporate invoicing, employee roster governance, staff provisioning.',
                'permissions' => ['users.manage', 'org.manage', 'finance.pay', 'programs.manage', 'employees.manage', 'program_reports.view'],
            ],
        ];

        return view('corporate.team.index', [
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
        if ($role->organization_type !== 'corporate') {
            return back()->with('error', 'Selected role is not a valid corporate archetype.');
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

                $this->audit->log('corporate.staff.created', $user, [
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
            Log::warning("Failed to dispatch corporate staff invitation email: {$e->getMessage()}");
        }

        $this->audit->log('corporate.staff.invited', $invitation, [
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
        if ($role->organization_type !== 'corporate') {
            return back()->with('error', 'Selected role is not a valid corporate archetype.');
        }

        $membership->role_id = $role->id;
        $membership->status = $validated['status'];
        $membership->save();

        $actor = Actor::for(auth()->user(), $org);
        $this->audit->log('corporate.staff.updated', $membership, [
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
            Log::warning("Failed to resend corporate staff invitation email: {$e->getMessage()}");
        }

        return back()->with('success', "Invitation resent to {$invitation->email}.");
    }
}
