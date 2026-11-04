<?php

declare(strict_types=1);

namespace App\Domain\Organizations;

use App\Models\Organization;
use App\Models\OrganizationMembership;

/**
 * Request-scoped holder of the active organisation membership (bound as a scoped singleton).
 * Populated by the ResolveOrganizationContext middleware from the user's selected membership.
 */
final class CurrentOrganization
{
    private ?OrganizationMembership $membership = null;

    public function set(OrganizationMembership $membership): void
    {
        $this->membership = $membership;
    }

    public function membership(): ?OrganizationMembership
    {
        return $this->membership;
    }

    public function organization(): ?Organization
    {
        return $this->membership?->organization;
    }

    public function id(): ?int
    {
        return $this->membership?->organization_id;
    }

    public function can(string $permission): bool
    {
        return $this->membership?->can($permission) ?? false;
    }

    public function actor(): Actor
    {
        if (! $this->membership) {
            throw new \RuntimeException('No organisation context.');
        }

        return new Actor($this->membership->user, $this->membership);
    }
}
