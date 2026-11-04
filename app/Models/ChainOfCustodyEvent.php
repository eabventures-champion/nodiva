<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only chain-of-custody record. */
class ChainOfCustodyEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['specimen_id', 'event', 'from_status', 'to_status', 'actor_id', 'actor_organization_id', 'dispatch_id', 'location', 'handed_to', 'notes', 'occurred_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'created_at' => 'datetime'];
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
