<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    use HasPublicUuid;

    public const CATEGORIES = ['support' => 'Support request', 'dispute' => 'Service / billing dispute', 'complaint' => 'Service complaint', 'incident' => 'Operational incident'];

    protected $fillable = ['referral_order_id', 'category', 'severity', 'subject', 'description'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ReferralOrder::class, 'referral_order_id');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
}
