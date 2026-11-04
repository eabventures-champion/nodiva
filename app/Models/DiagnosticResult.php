<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Results\Enums\ResultStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticResult extends Model
{
    use HasPublicUuid;

    protected $fillable = ['referral_order_id', 'version', 'supersedes_id', 'status', 'interpretation', 'is_critical', 'amendment_reason', 'entered_by', 'submitted_by', 'submitted_at', 'released_by', 'released_at', 'review_notes'];

    protected $hidden = ['interpretation'];

    protected function casts(): array
    {
        return [
            'status' => ResultStatus::class,
            'interpretation' => 'encrypted',
            'is_critical' => 'boolean',
            'submitted_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ReferralOrder::class, 'referral_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DiagnosticResultItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ResultDocument::class);
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(CriticalResultAcknowledgement::class);
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function isEditable(): bool
    {
        return $this->status === ResultStatus::Draft;
    }
}
