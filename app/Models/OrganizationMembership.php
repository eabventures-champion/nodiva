<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMembership extends Model
{
    protected $fillable = ['user_id', 'organization_id', 'role_id', 'department_id', 'invited_by', 'status', 'permissions'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return (bool) ($this->role?->is_admin ?? false);
    }

    public function can(string $permission): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->role?->is_admin) {
            return true;
        }

        $custom = $this->permissions ?? [];
        if (in_array($permission, $custom, true)) {
            return true;
        }

        return $this->role !== null && $this->role->hasPermission($permission);
    }
}
