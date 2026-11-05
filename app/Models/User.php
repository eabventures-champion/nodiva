<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasPublicUuid, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'job_title', 'password', 'notification_preferences'];

    protected $hidden = ['password', 'remember_token', 'mfa_secret'];

    protected $appends = ['is_cto'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'mfa_enabled_at' => 'datetime',
            'mfa_secret' => 'encrypted',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->where('status', 'active')
            ->whereHas('organization', fn ($q) => $q->whereIn('status', ['active', 'pending']));
    }

    public function currentMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'current_membership_id');
    }

    public function membershipFor(Organization $organization): ?OrganizationMembership
    {
        return $this->memberships()->with('role.permissions')
            ->where('organization_id', $organization->id)->where('status', 'active')->first();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasMfa(): bool
    {
        return $this->mfa_enabled_at !== null && $this->mfa_secret !== null;
    }

    public function canAccessMenu(string $permission): bool
    {
        $currentMembership = app()->bound(\App\Domain\Organizations\CurrentOrganization::class)
            ? app(\App\Domain\Organizations\CurrentOrganization::class)->membership()
            : null;

        $membership = $currentMembership ?? $this->currentMembership ?? $this->activeMemberships()->first();
        if (! $membership) {
            return false;
        }

        return $membership->can($permission);
    }

    public function wantsNotification(string $channel, string $type): bool
    {
        $prefs = $this->notification_preferences ?? [];

        return (bool) ($prefs[$type][$channel] ?? true);
    }

    public function isCto(): bool
    {
        $title = strtolower(trim($this->job_title ?? ''));

        return str_contains($title, 'chief technical officer')
            || preg_match('/\bcto\b/i', $title) === 1
            || $this->email === 'admin@nodiva.com';
    }

    public function getIsCtoAttribute(): bool
    {
        return $this->isCto();
    }

    public function isPlatformAdmin(): bool
    {
        if ($this->isCto()) {
            return true;
        }

        return $this->memberships()
            ->whereHas('organization', fn ($q) => $q->where('type', 'platform'))
            ->whereHas('role', fn ($q) => $q->where('is_admin', true))
            ->where('status', 'active')
            ->exists();
    }

    public function dashboardRoute(): string
    {
        try {
            $currentMembership = app()->bound(\App\Domain\Organizations\CurrentOrganization::class)
                ? app(\App\Domain\Organizations\CurrentOrganization::class)->membership()
                : null;

            $membershipId = session('active_membership_id') ?? $this->current_membership_id;

            $membership = $currentMembership;
            if (! $membership && $membershipId) {
                $membership = $this->memberships()->with('organization')->find($membershipId);
            }

            if (! $membership) {
                $membership = $this->activeMemberships()->with('organization')->first()
                    ?? $this->memberships()->with('organization')->first();
            }

            $org = $membership?->organization;
        } catch (\Throwable) {
            $org = null;
        }

        if (! $org) {
            return $this->isCto() ? 'ops.dashboard' : 'home';
        }

        $type = $org->type instanceof \App\Domain\Organizations\Enums\OrganizationType
            ? $org->type->value
            : (string) $org->type;

        return match ($type) {
            'hospital' => 'hospital.dashboard',
            'laboratory' => 'lab.dashboard',
            'corporate' => 'corporate.dashboard',
            'platform' => 'ops.dashboard',
            default => 'home',
        };
    }

    public function dashboardUrl(): string
    {
        return route($this->dashboardRoute());
    }
}
