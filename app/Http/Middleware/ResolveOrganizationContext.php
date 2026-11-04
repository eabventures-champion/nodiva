<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Organizations\CurrentOrganization;
use App\Models\OrganizationMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganizationContext
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $membershipId = session('active_membership_id') ?? $user->current_membership_id;

        $membership = null;
        if ($membershipId) {
            $membership = OrganizationMembership::with(['organization', 'role.permissions', 'user'])
                ->where('id', $membershipId)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
        }

        if (! $membership) {
            $membership = $user->activeMemberships()->with(['organization', 'role.permissions', 'user'])->first();
        }

        if ($membership) {
            $this->current->set($membership);
            session(['active_membership_id' => $membership->id]);
        }

        return $next($request);
    }
}
