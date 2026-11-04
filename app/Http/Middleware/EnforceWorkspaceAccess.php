<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Organizations\CurrentOrganization;
use App\Domain\Organizations\Enums\OrganizationType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceWorkspaceAccess
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function handle(Request $request, Closure $next, string $workspace): Response
    {
        $org = $this->current->organization();

        if (! $org) {
            return redirect()->route('login')->with('error', 'Please sign in to access your organization workspace.');
        }

        if (! $org->isActive() && ! $org->isPlatform()) {
            return response()->view('errors.organization-pending', ['organization' => $org], 403);
        }

        $allowed = match ($workspace) {
            'hospital' => $org->isType(OrganizationType::Hospital),
            'lab' => $org->isType(OrganizationType::Laboratory),
            'corporate' => $org->isType(OrganizationType::Corporate),
            'ops' => $org->isType(OrganizationType::Platform),
            default => false,
        };

        if (! $allowed) {
            // Redirect to appropriate workspace for the active org
            $route = match ($org->type) {
                OrganizationType::Hospital => 'hospital.dashboard',
                OrganizationType::Laboratory => 'lab.dashboard',
                OrganizationType::Corporate => 'corporate.dashboard',
                OrganizationType::Platform => 'ops.dashboard',
            };

            return redirect()->route($route);
        }

        return $next($request);
    }
}
