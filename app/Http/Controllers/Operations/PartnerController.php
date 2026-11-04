<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Domain\Organizations\CurrentOrganization;
use App\Domain\Organizations\Enums\OrganizationStatus;
use App\Http\Controllers\Controller;
use App\Mail\OrganizationApprovedNotification;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\ServiceRegion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PartnerController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();
        $typeFilter = $request->query('type');
        $statusFilter = $request->query('status');
        $searchQuery = $request->query('q');

        // Strictly external institutional partners (Hospitals, Laboratories, Corporate)
        // Internal NODIVA Operations Network is managed in Team & Access Governance (/ops/team)
        $query = Organization::query()
            ->where('type', '!=', 'platform')
            ->with([
                'region',
                'branches',
                'memberships' => fn ($q) => $q->with(['user', 'role.permissions', 'department'])->latest('id'),
            ])
            ->withCount(['branches', 'memberships']);

        // Type filtering for partner categories
        if ($typeFilter && in_array($typeFilter, ['hospital', 'laboratory', 'corporate'], true)) {
            $query->where('type', $typeFilter);
        }

        if ($statusFilter && in_array($statusFilter, ['active', 'pending', 'suspended', 'rejected'], true)) {
            $query->where('status', $statusFilter);
        }

        if ($searchQuery) {
            $query->where(function ($sub) use ($searchQuery) {
                $sub->where('name', 'like', "%{$searchQuery}%")
                    ->orWhere('email', 'like', "%{$searchQuery}%")
                    ->orWhere('city', 'like', "%{$searchQuery}%")
                    ->orWhere('registration_number', 'like', "%{$searchQuery}%")
                    ->orWhereHas('memberships.user', function ($uq) use ($searchQuery) {
                        $uq->where('name', 'like', "%{$searchQuery}%")
                            ->orWhere('email', 'like', "%{$searchQuery}%")
                            ->orWhere('job_title', 'like', "%{$searchQuery}%")
                            ->orWhere('phone', 'like', "%{$searchQuery}%");
                    })
                    ->orWhereHas('memberships.role', function ($rq) use ($searchQuery) {
                        $rq->where('name', 'like', "%{$searchQuery}%")
                            ->orWhere('key', 'like', "%{$searchQuery}%");
                    });
            });
        }

        $partners = $query->latest('id')->paginate(15)->withQueryString();
        $regions = ServiceRegion::all();

        // Scope query for status tabs (strictly partner organizations)
        $baseCountQuery = Organization::query()->where('type', '!=', 'platform');
        if ($typeFilter && in_array($typeFilter, ['hospital', 'laboratory', 'corporate'], true)) {
            $baseCountQuery->where('type', $typeFilter);
        }

        // Count partner users: only users belonging to partner organizations (excluding internal platform staff)
        $partnerUsersCount = User::whereHas('memberships.organization', fn ($q) => $q->where('type', '!=', 'platform'))
            ->whereDoesntHave('memberships.organization', fn ($q) => $q->where('type', 'platform'))
            ->distinct()
            ->count();

        // Count partner memberships
        $partnerMembershipsCount = OrganizationMembership::whereHas('organization', fn ($q) => $q->where('type', '!=', 'platform'))
            ->where('status', 'active')
            ->count();

        // Status counts for quick filtering
        $counts = [
            'total' => (clone $baseCountQuery)->count(),
            'active' => (clone $baseCountQuery)->where('status', 'active')->count(),
            'pending' => (clone $baseCountQuery)->where('status', 'pending')->count(),
            'rejected' => (clone $baseCountQuery)->where('status', 'rejected')->count(),
            'total_users' => $partnerUsersCount,
            'total_memberships' => $partnerMembershipsCount,
        ];

        // Fetch unassigned users (if any exist without membership)
        $unassignedUsers = User::doesntHave('memberships')->get();

        // Partner personnel for flat directory view (strictly partner organizations)
        $usersQuery = User::whereHas('memberships.organization', fn ($q) => $q->where('type', '!=', 'platform'))
            ->whereDoesntHave('memberships.organization', fn ($q) => $q->where('type', 'platform'))
            ->with(['memberships' => fn ($mq) => $mq->whereHas('organization', fn ($oq) => $oq->where('type', '!=', 'platform'))->with(['organization', 'role'])])
            ->latest('id');

        if ($searchQuery) {
            $usersQuery->where(function ($uq) use ($searchQuery) {
                $uq->where('name', 'like', "%{$searchQuery}%")
                    ->orWhere('email', 'like', "%{$searchQuery}%")
                    ->orWhere('job_title', 'like', "%{$searchQuery}%")
                    ->orWhere('phone', 'like', "%{$searchQuery}%")
                    ->orWhereHas('memberships.organization', function ($oq) use ($searchQuery) {
                        $oq->where('type', '!=', 'platform')
                            ->where(function ($sq) use ($searchQuery) {
                                $sq->where('name', 'like', "%{$searchQuery}%")
                                    ->orWhere('city', 'like', "%{$searchQuery}%");
                            });
                    })
                    ->orWhereHas('memberships.role', function ($rq) use ($searchQuery) {
                        $rq->where('name', 'like', "%{$searchQuery}%")
                            ->orWhere('key', 'like', "%{$searchQuery}%");
                    });
            });
        }
        if ($typeFilter && in_array($typeFilter, ['hospital', 'laboratory', 'corporate'], true)) {
            $usersQuery->whereHas('memberships.organization', function ($oq) use ($typeFilter) {
                $oq->where('type', $typeFilter);
            });
        }
        $allPlatformUsers = $usersQuery->get();
        $currentUserId = auth()->id();
        $deletableUserIds = $allPlatformUsers
            ->reject(fn ($u) => $u->isCto() || $u->id === $currentUserId)
            ->pluck('id')
            ->values()
            ->all();

        return view('operations.partners', [
            'organization' => $org,
            'partners' => $partners,
            'currentType' => $typeFilter,
            'currentStatus' => $statusFilter,
            'searchQuery' => $searchQuery,
            'regions' => $regions,
            'counts' => $counts,
            'unassignedUsers' => $unassignedUsers,
            'allPlatformUsers' => $allPlatformUsers,
            'deletableUserIds' => $deletableUserIds,
            'isCto' => auth()->user()?->isCto() ?? false,
        ]);
    }

    public function updateStatus(Request $request, Organization $partner)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,rejected,suspended,pending'],
            'status_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $previousStatus = $partner->status->value;
        $newStatus = $validated['status'];

        $partner->status = match ($newStatus) {
            'active' => OrganizationStatus::Active,
            'rejected' => OrganizationStatus::Rejected,
            'suspended' => OrganizationStatus::Suspended,
            'pending' => OrganizationStatus::Pending,
        };

        $partner->status_reason = $validated['status_reason'] ?? null;

        if ($newStatus === 'active') {
            $partner->approved_at = now();
            $partner->approved_by = auth()->id();
        }

        $partner->save();

        // Audit Logging
        $actor = Actor::for(auth()->user(), $this->current->organization());
        $this->audit->log('organization.status_updated', $partner, [
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'reason' => $partner->status_reason,
        ], 'success', $actor);

        // Notify institutional administrator if confirmed/activated
        if ($newStatus === 'active') {
            try {
                $primaryAdmin = $partner->memberships()
                    ->whereHas('role', fn ($q) => $q->where('is_admin', true))
                    ->with('user')
                    ->first()
                    ?->user;

                if ($primaryAdmin) {
                    Mail::to($primaryAdmin->email)->send(new OrganizationApprovedNotification($partner, $primaryAdmin));
                }
            } catch (\Throwable $e) {
                Log::warning("Failed sending organization approval email: {$e->getMessage()}");
            }
        }

        $actionVerb = match ($newStatus) {
            'active' => 'verified and activated',
            'rejected' => 'declined/rejected',
            'suspended' => 'suspended',
            'pending' => 'set to pending review',
        };

        return redirect()->back()->with('success', "Institution '{$partner->name}' has been successfully {$actionVerb}.");
    }

    public function destroy(Organization $partner)
    {
        if (! auth()->user()?->isCto()) {
            abort(403, 'Privilege Denied: Only the Chief Technical Officer has the privilege to remove or delete institutions.');
        }

        if ($partner->isPlatform()) {
            return back()->with('error', 'Privilege Denied: The Platform Operations organization cannot be deleted.');
        }

        $partnerName = $partner->name;
        $partnerType = $partner->type->value;
        $partnerId = $partner->id;

        $this->deleteOrganizationCascade($partner);

        // Audit Log
        $actor = Actor::for(auth()->user(), $this->current->organization());
        $this->audit->log('organization.deleted', null, [
            'deleted_organization_id' => $partnerId,
            'deleted_organization_name' => $partnerName,
            'deleted_organization_type' => $partnerType,
        ], 'success', $actor);

        return redirect()->route('ops.partners.index')
            ->with('success', "Institution '{$partnerName}' and its associated team members have been permanently deleted.");
    }

    public function destroyAll(Request $request)
    {
        if (! auth()->user()?->isCto()) {
            abort(403, 'Privilege Denied: Only the Chief Technical Officer has the privilege to remove or delete institutions.');
        }

        $rawIds = $request->input('ids');
        $ids = is_string($rawIds) ? json_decode($rawIds, true) : $rawIds;

        $query = Organization::where('type', '!=', 'platform');
        if (is_array($ids) && count($ids) > 0) {
            $query->whereIn('id', $ids);
        }

        $partners = $query->get();
        $count = 0;
        foreach ($partners as $partner) {
            $this->deleteOrganizationCascade($partner);
            $count++;
        }

        $actor = Actor::for(auth()->user(), $this->current->organization());
        $this->audit->log('organizations.bulk_deleted', null, [
            'count' => $count,
            'scope' => is_array($ids) && count($ids) > 0 ? 'selected' : 'all',
        ], 'success', $actor);

        return redirect()->route('ops.partners.index')
            ->with('success', "{$count} institution(s) and all associated team members have been successfully removed.");
    }

    public function destroyMember(Organization $partner, OrganizationMembership $membership)
    {
        if (! auth()->user()?->isCto()) {
            abort(403, 'Privilege Denied: Only the Chief Technical Officer has the privilege to remove or delete team members.');
        }

        if ($membership->organization_id !== $partner->id) {
            abort(404, 'Membership does not belong to this institution.');
        }

        $user = $membership->user;
        if ($user && $user->isCto()) {
            return back()->with('error', 'Privilege Denied: The Chief Technical Officer account cannot be removed or deleted.');
        }

        $userName = $user?->name ?? 'Team Member';
        $userEmail = $user?->email ?? '';

        DB::transaction(function () use ($membership, $user) {
            $membership->delete();

            // If user has no other memberships and is not CTO, disable or delete user record
            if ($user && ! $user->isCto() && $user->memberships()->count() === 0) {
                $user->current_membership_id = null;
                $user->status = 'disabled';
                $user->save();

                try {
                    $user->delete();
                } catch (\Throwable $e) {
                    // Retained as disabled if referenced by historical clinical orders
                }
            }
        });

        $actor = Actor::for(auth()->user(), $this->current->organization());
        $this->audit->log('partner.member_removed', $partner, [
            'removed_user_name' => $userName,
            'removed_user_email' => $userEmail,
        ], 'success', $actor);

        return redirect()->back()->with('success', "Team member '{$userName}' ({$userEmail}) has been successfully removed from {$partner->name}.");
    }

    public function destroyUser(User $user)
    {
        if (! auth()->user()?->isCto()) {
            abort(403, 'Privilege Denied: Only the Chief Technical Officer has the privilege to remove or delete users.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Action Denied: You cannot delete your own active platform account.');
        }

        if ($user->isCto()) {
            return back()->with('error', 'Privilege Denied: The Chief Technical Officer account cannot be removed or deleted.');
        }

        if ($user->memberships()->whereHas('organization', fn ($q) => $q->where('type', 'platform'))->exists()) {
            return back()->with('error', 'Privilege Denied: Platform operations personnel cannot be deleted from the partner directory. Manage operations personnel under Team & Access Governance.');
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $userId = $user->id;

        DB::transaction(function () use ($user) {
            // 1. Remove memberships
            $user->memberships()->delete();

            // 2. Remove pending invitations
            \App\Models\OrganizationInvitation::where('email', $user->email)->delete();

            // 3. Clear sessions
            DB::table('sessions')->where('user_id', $user->id)->delete();

            // 4. Delete or disable user
            $user->current_membership_id = null;
            $user->status = 'disabled';
            $user->save();

            try {
                $user->delete();
            } catch (\Throwable $e) {
                // If referenced by foreign keys, kept disabled
            }
        });

        $actor = Actor::for(auth()->user(), $this->current->organization());
        $this->audit->log('platform.user_deleted', null, [
            'deleted_user_id' => $userId,
            'deleted_user_name' => $userName,
            'deleted_user_email' => $userEmail,
        ], 'success', $actor);

        return redirect()->back()
            ->with('success', "User '{$userName}' ({$userEmail}) has been successfully deleted from the platform roster.");
    }

    public function destroyAllUsers(Request $request)
    {
        if (! auth()->user()?->isCto()) {
            abort(403, 'Privilege Denied: Only the Chief Technical Officer has the privilege to remove or delete users.');
        }

        $rawIds = $request->input('ids');
        $ids = is_string($rawIds) ? json_decode($rawIds, true) : $rawIds;

        $query = User::query()
            ->where('id', '!=', auth()->id())
            ->whereHas('memberships.organization', fn ($q) => $q->where('type', '!=', 'platform'))
            ->whereDoesntHave('memberships.organization', fn ($q) => $q->where('type', 'platform'));
        if (is_array($ids) && count($ids) > 0) {
            $query->whereIn('id', $ids);
        }

        $users = $query->get();
        $count = 0;

        foreach ($users as $user) {
            if ($user->isCto()) {
                continue; // Preserved
            }

            DB::transaction(function () use ($user) {
                $user->memberships()->delete();
                \App\Models\OrganizationInvitation::where('email', $user->email)->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();

                $user->current_membership_id = null;
                $user->status = 'disabled';
                $user->save();

                try {
                    $user->delete();
                } catch (\Throwable $e) {
                    // Fallback
                }
            });

            $count++;
        }

        $actor = Actor::for(auth()->user(), $this->current->organization());
        $this->audit->log('platform.users_bulk_deleted', null, [
            'count' => $count,
            'scope' => is_array($ids) && count($ids) > 0 ? 'selected' : 'all',
        ], 'success', $actor);

        return redirect()->back()
            ->with('success', "{$count} user account(s) have been successfully deleted from the platform roster.");
    }

    private function deleteOrganizationCascade(Organization $partner): void
    {
        DB::transaction(function () use ($partner) {
            // 1. Remove pending invitations
            \App\Models\OrganizationInvitation::where('organization_id', $partner->id)->delete();

            // 2. Identify user IDs before deleting memberships
            $userIds = $partner->memberships()->pluck('user_id')->unique();

            // 3. Delete memberships
            $partner->memberships()->delete();

            // 4. Clean up orphan user accounts (not CTO, no other memberships)
            foreach ($userIds as $uid) {
                $user = User::find($uid);
                if ($user && ! $user->isCto() && $user->memberships()->count() === 0) {
                    $user->current_membership_id = null;
                    $user->status = 'disabled';
                    $user->save();

                    try {
                        $user->delete();
                    } catch (\Throwable $e) {
                        // Keep disabled if foreign key references exist (e.g. referral_orders.created_by)
                    }
                }
            }

            // 5. Clean up associated qualifications, catalogue entries, profiles, branches, etc.
            $partner->qualifications()->delete();
            $partner->catalogueEntries()->delete();
            $partner->branches()->delete();
            $partner->departments()->delete();
            $partner->screeningPrograms()->delete();
            $partner->employeeRecords()->delete();
            $partner->contracts()->delete();

            if ($partner->laboratoryProfile) {
                $partner->laboratoryProfile->delete();
            }

            // 6. Delete the organization (soft delete)
            $partner->delete();
        });
    }
}
