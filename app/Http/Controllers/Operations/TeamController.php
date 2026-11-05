<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

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

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $org = $this->current->organization() ?? \App\Models\Organization::where('type', 'platform')->first();

        // 1. Fetch active team members of the operations organization
        $members = OrganizationMembership::with(['user', 'role.permissions', 'department'])
            ->where('organization_id', $org->id)
            ->get();

        // 2. Fetch pending invitations
        $invitations = OrganizationInvitation::with(['role', 'inviter'])
            ->where('organization_id', $org->id)
            ->latest()
            ->get();

        // 3. Available platform roles
        $roles = Role::with('permissions')
            ->where('organization_type', 'platform')
            ->get();

        // 4. Granular Sidebar Menu Permissions mapping
        $sidebarPermissions = $this->getSidebarPermissionsDefinition();

        return view('operations.team.index', [
            'organization' => $org,
            'members' => $members,
            'invitations' => $invitations,
            'roles' => $roles,
            'sidebarPermissions' => $sidebarPermissions,
        ]);
    }

    public function invite(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'name' => ['required', 'string', 'max:100'],
            'job_title' => ['required', 'string', 'max:100'],
            'role_id' => ['required', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $org = $this->current->organization() ?? \App\Models\Organization::where('type', 'platform')->first();

        // Check if user is already an active member of the platform organization
        $existingMember = OrganizationMembership::where('organization_id', $org->id)
            ->whereHas('user', fn ($q) => $q->where('email', $validated['email']))
            ->where('status', 'active')
            ->exists();

        if ($existingMember) {
            return back()->with('error', "User with email '{$validated['email']}' is already an active member of the Operations Network Console.");
        }

        // Cancel any previous pending invitation for this email in this org
        OrganizationInvitation::where('organization_id', $org->id)
            ->where('email', $validated['email'])
            ->where('status', 'pending')
            ->delete();

        $expiresAt = now()->addDays((int) ($validated['expires_in_days'] ?? 7));

        $invitation = OrganizationInvitation::create([
            'organization_id' => $org->id,
            'email' => strtolower(trim($validated['email'])),
            'name' => trim($validated['name']),
            'job_title' => trim($validated['job_title']),
            'role_id' => $validated['role_id'],
            'permissions' => $validated['permissions'] ?? null,
            'token' => Str::random(48),
            'invited_by' => auth()->id(),
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        // Send institutional invitation mailable
        try {
            Mail::to($invitation->email)->send(new TeamMemberInvitationMail($invitation));
        } catch (\Throwable $e) {
            Log::warning("Failed to dispatch team member invitation email: {$e->getMessage()}");
        }

        // Audit Logging
        $actor = Actor::for(auth()->user(), $org);
        $this->audit->log('staff.invited', $invitation, [
            'email' => $invitation->email,
            'role_id' => $invitation->role_id,
            'permissions' => $invitation->permissions,
            'expires_at' => $invitation->expires_at->toIso8601String(),
        ], 'success', $actor);

        return back()->with('success', "Invitation dispatched to {$invitation->email}. An onboarding email has been sent with delegated sidebar permissions.");
    }

    public function updatePermissions(Request $request, OrganizationMembership $membership)
    {
        $this->authorizeAdmin();

        $currentUser = auth()->user();
        $isCto = (bool) $currentUser?->isCto();

        // Non-CTO team members can ONLY edit their own record
        if (! $isCto && $membership->user_id !== $currentUser?->id) {
            abort(403, 'Privilege Denied: Only the Root Chief Technical Officer has the privilege to edit other team members.');
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'role_id' => ['required', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        // 1. Update user profile details (Name & Job Title)
        $user = $membership->user;
        $userUpdates = [];
        if (! empty($validated['name'])) {
            $userUpdates['name'] = trim($validated['name']);
        }
        if (isset($validated['job_title'])) {
            $title = trim($validated['job_title']);
            // Prevent non-CTO from giving themselves CTO designation
            if (! $isCto && (str_contains(strtolower($title), 'cto') || str_contains(strtolower($title), 'chief technical officer'))) {
                return back()->with('error', 'Privilege Denied: The Chief Technical Officer designation is root-protected.');
            }
            $userUpdates['job_title'] = $title;
        }
        if (! empty($userUpdates)) {
            $user->update($userUpdates);
        }

        // 2. The role of the Chief Technical Officer cannot be edited by any other user
        if ($membership->user->isCto() && $currentUser->id !== $membership->user_id) {
            return back()->with('error', 'Privilege Denied: The Chief Technical Officer role is root-protected and cannot be edited.');
        }

        // 3. Prevent self-suspension
        if ($membership->user_id === $currentUser->id && $validated['status'] === 'suspended') {
            return back()->with('error', 'You cannot suspend your own administrative session.');
        }

        // 4. Update roles & permissions (CTO has full control; non-CTO keeps assigned role/permissions)
        if ($isCto) {
            $membership->role_id = $validated['role_id'];
            $membership->permissions = $validated['permissions'] ?? null;
            $membership->status = $validated['status'];
            $membership->save();
        }

        // Audit Log
        $actor = Actor::for(auth()->user(), $this->current->organization());
        $this->audit->log('staff.permissions_updated', $membership, [
            'target_user' => $membership->user->email,
            'new_role_id' => $membership->role_id,
            'status' => $membership->status,
            'permissions' => $membership->permissions,
        ], 'success', $actor);

        return back()->with('success', "Details and access permissions updated for {$membership->user->name}.");
    }

    public function destroy(OrganizationMembership $membership)
    {
        $this->authorizeAdmin();

        $currentUser = auth()->user();
        if (! $currentUser?->isCto()) {
            abort(403, 'Privilege Denied: Only the Root Chief Technical Officer has the privilege to remove or delete team members.');
        }

        if ($membership->user->isCto() || $membership->user->email === 'admin@nodiva.com') {
            return back()->with('error', 'Privilege Denied: The Root Chief Technical Officer account is root-protected and cannot be removed.');
        }

        if ($membership->user_id === $currentUser->id) {
            return back()->with('error', 'Privilege Denied: You cannot remove your own account from the Operations Console.');
        }

        $user = $membership->user;
        $userName = $user->name;
        $userEmail = $user->email;

        DB::transaction(function () use ($membership, $user) {
            $membership->delete();
            if ($user && ! $user->isCto() && $user->memberships()->count() === 0) {
                $user->delete();
            }
        });

        $actor = Actor::for(auth()->user(), $this->current->organization());
        $this->audit->log('staff.member_deleted', $membership, [
            'user' => $userEmail,
        ], 'success', $actor);

        return back()->with('success', "Operations team member '{$userName}' has been successfully removed.");
    }

    public function revokeInvitation(OrganizationInvitation $invitation)
    {
        $this->authorizeAdmin();

        if ($invitation->status === 'pending') {
            $invitation->status = 'revoked';
            $invitation->save();
            $msg = "Invitation for {$invitation->email} has been revoked.";
        } else {
            $email = $invitation->email;
            $invitation->delete();
            $msg = "Invitation record for {$email} removed.";
        }

        $actor = Actor::for(auth()->user(), $this->current->organization());
        $this->audit->log('staff.invitation_revoked', $invitation, [
            'email' => $invitation->email,
        ], 'success', $actor);

        return back()->with('success', $msg);
    }

    public function resendInvitation(OrganizationInvitation $invitation)
    {
        $this->authorizeAdmin();

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'Only pending invitations can be resent.');
        }

        $invitation->expires_at = now()->addDays(7);
        $invitation->save();

        try {
            Mail::to($invitation->email)->send(new TeamMemberInvitationMail($invitation));
        } catch (\Throwable $e) {
            Log::warning("Failed resending invitation email: {$e->getMessage()}");
        }

        return back()->with('success', "Invitation email re-sent to {$invitation->email}. Validity extended by 7 days.");
    }

    public function storeRole(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description' => ['required', 'string', 'max:255'],
            'is_admin' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $baseKey = 'platform_' . Str::slug($validated['name'], '_');
        $key = $baseKey;
        $counter = 1;
        while (Role::where('key', $key)->exists()) {
            $key = $baseKey . '_' . $counter++;
        }

        $role = Role::create([
            'key' => $key,
            'name' => trim($validated['name']),
            'organization_type' => 'platform',
            'is_admin' => (bool) ($validated['is_admin'] ?? false),
            'description' => trim($validated['description']),
        ]);

        if (! empty($validated['permissions'])) {
            $permIds = \App\Models\Permission::whereIn('key', $validated['permissions'])->pluck('id');
            $role->permissions()->sync($permIds);
        }

        $role->load('permissions');

        // Audit Log
        $actor = Actor::for(auth()->user(), $this->current->organization());
        $this->audit->log('staff.role_created', $role, [
            'role_id' => $role->id,
            'key' => $role->key,
            'name' => $role->name,
            'is_admin' => $role->is_admin,
            'permissions' => $validated['permissions'] ?? [],
        ], 'success', $actor);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'role' => $role,
                'message' => "Role '{$role->name}' created successfully.",
            ]);
        }

        return back()->with('success', "New platform role '{$role->name}' defined and added to the governance registry.");
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        if ($user?->isCto()) {
            return;
        }

        abort_unless($this->current->can('users.manage'), 403, 'Unauthorized. Requires users.manage permission.');
    }

    /**
     * Definitive mapping of Sidebar Menu items to permission keys.
     *
     * @return array<string, array{name: string, description: string, icon: string, route: string}>
     */
    public static function getSidebarPermissionsDefinition(): array
    {
        return [
            'ops.orders.monitor' => [
                'name' => 'Network Operations',
                'description' => 'View live network referral orders, TAT status, and test fulfillment telemetry.',
                'icon' => 'activity',
                'route' => 'ops.dashboard',
            ],
            'ops.organizations.manage' => [
                'name' => 'Institutional Partners',
                'description' => 'Review, verify, activate or suspend hospitals, testing laboratories, and corporate clients.',
                'icon' => 'building',
                'route' => 'ops.partners.index',
            ],
            'ops.catalogue.manage' => [
                'name' => 'Master Test Catalogue',
                'description' => 'Manage diagnostic formulary tests, clinical specialties, and specimen tube requirements.',
                'icon' => 'book-open',
                'route' => 'ops.catalogue.index',
            ],
            'ops.qualifications.verify' => [
                'name' => 'Laboratory Qualifications',
                'description' => 'Inspect AHPC operating licences, ISO 15189 scopes, and accreditation records.',
                'icon' => 'shield-check',
                'route' => 'ops.qualifications.index',
            ],
            'ops.logistics.manage' => [
                'name' => 'Logistics & Couriers',
                'description' => 'Manage specimen transit manifests, dispatch bags, and courier chain-of-custody handoffs.',
                'icon' => 'truck',
                'route' => 'ops.logistics.index',
            ],
            'ops.finance.manage' => [
                'name' => 'Financial Reconciliation',
                'description' => 'Audit institutional fee schedules, monthly statements, settlements, and payment clearing.',
                'icon' => 'banknotes',
                'route' => 'ops.finance.index',
            ],
            'ops.audit.view' => [
                'name' => 'Security & Audit Trail',
                'description' => 'Access SHA-256 hash-chained cryptographic audit logs for Act 843 compliance.',
                'icon' => 'lock-closed',
                'route' => 'ops.audit.index',
            ],
            'users.manage' => [
                'name' => 'Team & Access Governance',
                'description' => 'Invite new operations personnel, delegate roles, and manage sidebar menu visibility.',
                'icon' => 'users',
                'route' => 'ops.team.index',
            ],
        ];
    }
}
