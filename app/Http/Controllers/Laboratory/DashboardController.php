<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratory;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Domain\Organizations\CurrentOrganization;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrganizationMembership;
use App\Models\ReferralOrder;
use App\Models\Role;
use App\Models\TestCatalogueEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index()
    {
        $org = $this->current->organization();

        // Clinical Governance: Verify laboratory has an active Authorising Pathologist provisioned
        $hasPathologist = $org->memberships()
            ->where('status', 'active')
            ->whereHas('role', function ($q) {
                $q->where('key', 'lab_pathologist')
                  ->orWhereHas('permissions', fn ($p) => $p->where('name', 'results.release'));
            })
            ->exists();

        if (! $hasPathologist) {
            $pathologistRole = Role::where('organization_type', 'laboratory')
                ->where('key', 'lab_pathologist')
                ->first();

            return view('laboratory.onboarding-pathologist-required', [
                'organization' => $org,
                'pathologistRole' => $pathologistRole,
            ]);
        }

        // 1. Orders assigned directly to this lab OR open broadcast orders offered to this lab
        $incomingOrders = ReferralOrder::where(function ($q) use ($org) {
                $q->where('laboratory_id', $org->id);
            })
            ->orWhere(function ($q) use ($org) {
                $q->whereNull('laboratory_id')
                  ->where('status', OrderStatus::Matching)
                  ->whereHas('assignments', function ($a) use ($org) {
                      $a->where('laboratory_id', $org->id)->where('status', 'offered');
                  });
            })
            ->with(['requestingOrganization', 'items.test', 'currentResult', 'specimens'])
            ->latest('id')
            ->take(10)
            ->get();

        $awaitingCount = ReferralOrder::where(function ($q) use ($org) {
                $q->where('laboratory_id', $org->id)->where('status', OrderStatus::AwaitingLabAcceptance);
            })
            ->orWhere(function ($q) use ($org) {
                $q->whereNull('laboratory_id')
                  ->where('status', OrderStatus::Matching)
                  ->whereHas('assignments', function ($a) use ($org) {
                      $a->where('laboratory_id', $org->id)->where('status', 'offered');
                  });
            })
            ->count();

        $counts = [
            'awaiting_acceptance' => $awaitingCount,
            'processing' => ReferralOrder::where('laboratory_id', $org->id)->where('status', OrderStatus::Processing)->count(),
            'pending_review' => ReferralOrder::where('laboratory_id', $org->id)->where('status', OrderStatus::AwaitingResultReview)->count(),
            'completed' => ReferralOrder::where('laboratory_id', $org->id)->where('status', OrderStatus::Completed)->count(),
        ];

        $catalogueCount = TestCatalogueEntry::where('organization_id', $org->id)->count();

        return view('laboratory.dashboard', [
            'organization' => $org,
            'incomingOrders' => $incomingOrders,
            'counts' => $counts,
            'catalogueCount' => $catalogueCount,
        ]);
    }

    /**
     * Direct setup endpoint to provision the compulsory Authorising Pathologist role
     */
    public function setupPathologist(Request $request)
    {
        $org = $this->current->organization();
        $actor = Actor::for($request->user(), $org);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'job_title' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $role = Role::where('organization_type', 'laboratory')
            ->where('key', 'lab_pathologist')
            ->firstOrFail();

        DB::transaction(function () use ($validated, $org, $role, $actor) {
            $user = User::where('email', $validated['email'])->first();

            if (! $user) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'job_title' => $validated['job_title'],
                    'password' => Hash::make($validated['password']),
                    'status' => 'active',
                ]);
            } else {
                $user->update([
                    'name' => $validated['name'],
                    'job_title' => $validated['job_title'],
                    'password' => Hash::make($validated['password']),
                ]);
            }

            OrganizationMembership::updateOrCreate(
                [
                    'organization_id' => $org->id,
                    'user_id' => $user->id,
                ],
                [
                    'role_id' => $role->id,
                    'status' => 'active',
                    'joined_at' => now(),
                ]
            );

            $this->audit->log('laboratory.pathologist_provisioned', $org, [
                'pathologist_name' => $user->name,
                'pathologist_email' => $user->email,
                'role' => $role->name,
                'standard' => 'ISO 15189 §5.8 Dual Control',
            ], 'success', $actor);
        });

        return redirect()->route('lab.dashboard')
            ->with('success', "Authorising Pathologist ({$validated['name']}) successfully provisioned! Your laboratory dashboard and fulfillment queues are now fully unlocked.");
    }
}
