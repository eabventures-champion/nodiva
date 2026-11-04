<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Immutable order status history. */
class OrderStatusEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['referral_order_id', 'from_status', 'to_status', 'actor_id', 'actor_organization_id', 'reason', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
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
