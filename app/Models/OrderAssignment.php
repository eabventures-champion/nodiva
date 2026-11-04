<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAssignment extends Model
{
    protected $fillable = ['referral_order_id', 'laboratory_id', 'status', 'match_score', 'rationale', 'selection_mode', 'assigned_by'];

    protected function casts(): array
    {
        return ['rationale' => 'array', 'decided_at' => 'datetime', 'match_score' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ReferralOrder::class, 'referral_order_id');
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'laboratory_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
