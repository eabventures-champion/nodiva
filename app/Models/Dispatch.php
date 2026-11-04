<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispatch extends Model
{
    use HasPublicUuid;

    protected $fillable = ['referral_order_id', 'type', 'carrier_name', 'tracking_reference', 'pickup_location', 'scheduled_for', 'dispatched_at', 'delivered_at', 'status', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['scheduled_for' => 'datetime', 'dispatched_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ReferralOrder::class, 'referral_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
