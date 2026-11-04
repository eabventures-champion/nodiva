<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Organizations\Enums\OrganizationType;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReferralOrder extends Model
{
    use HasPublicUuid;

    // status, laboratory_id and lifecycle timestamps are not fillable — they are changed only by OrderWorkflow.
    protected $fillable = ['organization_branch_id', 'priority', 'collection_method', 'logistics_charge', 'clinical_notes', 'clinical_indication'];

    protected $hidden = ['clinical_notes', 'idempotency_key'];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'clinical_notes' => 'encrypted',
            'selection_rationale' => 'array',
            'submitted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'specimen_received_at' => 'datetime',
            'result_released_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'escalated_at' => 'datetime',
        ];
    }

    /**
     * Tenant boundary for orders: the requesting institution, the assigned laboratory (or a laboratory that holds a
     * live offer) and NODIVA operations (coordination view only — clinical access is checked separately).
     */
    public function scopeVisibleTo(Builder $q, Organization $org): Builder
    {
        return match ($org->type) {
            OrganizationType::Platform => $q,
            OrganizationType::Hospital, OrganizationType::Corporate => $q->where('requesting_organization_id', $org->id),
            OrganizationType::Laboratory => $q->where(fn ($w) => $w->where('laboratory_id', $org->id)
                ->orWhereHas('assignments', fn ($a) => $a->where('laboratory_id', $org->id)->whereIn('status', ['offered', 'clarification_requested']))),
        };
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->whereNotNull('due_at')->where('due_at', '<', now())
            ->whereIn('status', array_map(fn ($s) => $s->value, OrderStatus::open()));
    }

    public function requestingOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'requesting_organization_id');
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'laboratory_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(OrganizationBranch::class, 'organization_branch_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReferralOrderItem::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(OrderAssignment::class)->latest('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderStatusEvent::class)->orderBy('id');
    }

    public function specimens(): HasMany
    {
        return $this->hasMany(Specimen::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class)->latest('id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(DiagnosticResult::class)->orderByDesc('version');
    }

    public function currentResult(): HasOne
    {
        return $this->hasOne(DiagnosticResult::class)->whereIn('status', ['draft', 'pending_review', 'released'])->latestOfMany('version');
    }

    public function releasedResult(): HasOne
    {
        return $this->hasOne(DiagnosticResult::class)->where('status', 'released')->latestOfMany('version');
    }

    public function financials(): HasOne
    {
        return $this->hasOne(OrderFinancial::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function activeSpecimen(): ?Specimen
    {
        return $this->specimens->sortByDesc('sequence')->first();
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast() && in_array($this->status, OrderStatus::open(), true);
    }

    public function requiresCourier(): bool
    {
        return $this->collection_method === 'courier';
    }
}
