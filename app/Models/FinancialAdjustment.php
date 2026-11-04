<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Immutable record of any monetary adjustment (credit notes, refunds, write-offs, corrections). */
class FinancialAdjustment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['reference', 'type', 'amount', 'currency', 'reason', 'actor_id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function adjustable(): MorphTo
    {
        return $this->morphTo();
    }
}
