<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasPublicUuid;

    protected $guarded = ['id', 'uuid']; // written only by PaymentService

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'unallocated_amount' => 'decimal:2', 'confirmed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Transaction references are immutable once created.
        static::updating(function (self $p): void {
            if ($p->isDirty('reference') || $p->isDirty('amount') || $p->isDirty('currency')) {
                throw new \LogicException('Payment reference, amount and currency are immutable.');
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
