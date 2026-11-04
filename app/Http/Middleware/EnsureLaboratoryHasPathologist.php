<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Organizations\CurrentOrganization;
use App\Domain\Organizations\Enums\OrganizationType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLaboratoryHasPathologist
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $org = $this->current->organization();

        if ($org && $org->isType(OrganizationType::Laboratory) && $org->isActive()) {
            $hasPathologist = $org->memberships()
                ->where('status', 'active')
                ->whereHas('role', function ($q) {
                    $q->where('key', 'lab_pathologist')
                      ->orWhereHas('permissions', fn ($p) => $p->where('name', 'results.release'));
                })
                ->exists();

            if (! $hasPathologist) {
                // Allow team management, invitations, pathologist setup, and qualifications routes
                if ($request->routeIs('lab.team.*') || $request->routeIs('lab.pathologist.setup') || $request->routeIs('lab.qualifications.*')) {
                    return $next($request);
                }

                // If not on the dashboard, redirect to dashboard which shows the mandatory setup gate
                if (! $request->routeIs('lab.dashboard')) {
                    return redirect()->route('lab.dashboard')
                        ->with('error', 'Clinical Governance Notice: Your laboratory must provision an Authorising Pathologist before accessing fulfillment queues.');
                }
            }

            // Regulatory Governance: Laboratory must have at least one valid qualification verified by Platform Operations
            if (! $org->hasVerifiedQualifications()) {
                // Block intake queues and fulfillment processing until verified by Platform Admin
                if ($request->routeIs('lab.reception.*') || $request->routeIs('lab.results.*') || $request->routeIs('lab.orders.*')) {
                    return redirect()->route('lab.qualifications.index')
                        ->with('error', 'Licensing & Accreditation Required: Your regulatory qualifications must be verified and approved by the Platform Administrator before your laboratory can access intake queues and process live patient referrals.');
                }
            }
        }

        return $next($request);
    }
}
