<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Client-specific contractual pricing negotiated between a laboratory and an institution. */
class LaboratoryPricingRule extends Model
{
    protected $fillable = ['laboratory_id', 'client_organization_id', 'diagnostic_test_id', 'discount_percent', 'fixed_price', 'effective_from', 'effective_to'];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_to' => 'date', 'fixed_price' => 'decimal:2', 'discount_percent' => 'decimal:2'];
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'laboratory_id');
    }
}
