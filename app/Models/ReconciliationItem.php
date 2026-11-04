<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expected_amount' => 'decimal:2', 'actual_amount' => 'decimal:2', 'difference' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ReferralOrder::class, 'referral_order_id');
    }
}
