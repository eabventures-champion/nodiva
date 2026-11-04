<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrganizationInvitation extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = [
        'organization_id',
        'email',
        'name',
        'job_title',
        'role_id',
        'permissions',
        'token',
        'invited_by',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->token)) {
                $model->token = Str::random(48);
            }
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addDays(7);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function hasPermission(string $key): bool
    {
        $perms = $this->permissions ?? [];
        if (in_array($key, $perms, true)) {
            return true;
        }

        return $this->role !== null && $this->role->hasPermission($key);
    }
}
