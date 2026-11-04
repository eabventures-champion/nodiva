<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Finance\Money;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasPublicUuid;

    protected $guarded = ['id', 'uuid']; // written only by InvoiceService

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total' => 'decimal:2',
            'amount_paid' => 'decimal:2', 'amount_credited' => 'decimal:2', 'balance_due' => 'decimal:2',
            'issued_on' => 'date', 'due_on' => 'date',
        ];
    }

    public function scopeOutstanding(Builder $q): Builder
    {
        return $q->whereIn('status', ['issued', 'partially_paid']);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ReferralOrder::class, 'referral_order_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ScreeningProgram::class, 'screening_program_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function money(string $field): Money
    {
        return Money::of((string) $this->{$field}, $this->currency);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['issued', 'partially_paid'], true) && $this->due_on->isPast();
    }
}
