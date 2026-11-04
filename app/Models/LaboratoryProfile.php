<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryProfile extends Model
{
    protected $fillable = ['organization_id', 'description', 'operating_hours', 'daily_capacity', 'is_accepting_orders', 'offers_collection', 'restriction_note', 'restricted_until', 'reliability_score'];

    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'is_accepting_orders' => 'boolean',
            'offers_collection' => 'boolean',
            'restricted_until' => 'datetime',
            'reliability_score' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isTemporarilyRestricted(): bool
    {
        return $this->restricted_until !== null && $this->restricted_until->isFuture();
    }
}
