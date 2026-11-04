<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only, hash-chained audit trail. Updates and deletes are refused at the model layer.
 */
class AuditLog extends Model
{
    use HasPublicUuid;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['context' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Audit logs are immutable.'));
        static::deleting(fn () => throw new \LogicException('Audit logs cannot be deleted.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function actorOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'actor_organization_id');
    }
}
