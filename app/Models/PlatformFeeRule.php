<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformFeeRule extends Model
{
    protected $fillable = ['name', 'scope', 'organization_id', 'diagnostic_specialty_id', 'contract_id', 'rate_percent', 'flat_fee', 'currency', 'fee_bearer', 'priority', 'effective_from', 'effective_to', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['rate_percent' => 'decimal:2', 'flat_fee' => 'decimal:2', 'effective_from' => 'date', 'effective_to' => 'date', 'is_active' => 'boolean'];
    }

    public function scopeEffectiveOn(Builder $q, \DateTimeInterface $date): Builder
    {
        $d = $date->format('Y-m-d');

        return $q->where('is_active', true)->where('effective_from', '<=', $d)
            ->where(fn ($w) => $w->whereNull('effective_to')->orWhere('effective_to', '>=', $d));
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(DiagnosticSpecialty::class, 'diagnostic_specialty_id');
    }
}
