<?php

declare(strict_types=1);

namespace App\Domain\Organizations;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * The acting principal for a domain operation: a user acting through one active organisation membership.
 * Domain services receive an Actor explicitly so business rules never depend on ambient request state.
 */
final class Actor
{
    public function __construct(
        public readonly User $user,
        public readonly OrganizationMembership $membership,
    ) {
    }

    public static function for(User $user, Organization $organization): self
    {
        $membership = $user->membershipFor($organization);
        if (! $membership) {
            throw new AuthorizationException('User is not an active member of this organisation.');
        }

        return new self($user, $membership);
    }

    public function organization(): Organization
    {
        return $this->membership->organization;
    }

    public function organizationId(): int
    {
        return $this->membership->organization_id;
    }

    public function can(string $permission): bool
    {
        return $this->user->isActive() && $this->membership->can($permission);
    }

    public function isPlatform(): bool
    {
        return $this->organization()->isPlatform();
    }

    /** @throws AuthorizationException */
    public function authorize(string $permission): void
    {
        if (! $this->can($permission)) {
            throw new AuthorizationException("Missing permission [{$permission}].");
        }
    }
}
