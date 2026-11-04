<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A licence/accreditation claim. Registration never implies accreditation: only status "verified" with a
 * non-expired date counts as a valid qualification.
 */
class LaboratoryQualification extends Model
{
    use HasPublicUuid;

    public const TYPES = [
        'operating_license' => 'Operating licence',
        'accreditation' => 'Accreditation',
        'quality_assurance' => 'External quality assurance',
        'professional_registration' => 'Professional registration',
    ];

    protected $fillable = [
        'organization_id',
        'type',
        'issuing_body',
        'reference_number',
        'scope',
        'issued_on',
        'expires_on',
        'document_path',
        'document_name',
        'status',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return ['issued_on' => 'date', 'expires_on' => 'date', 'verified_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(QualificationVerification::class)->latest('id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->endOfDay()->isPast();
    }

    public function isValid(): bool
    {
        return $this->status === 'verified' && ! $this->isExpired();
    }

    /** Display status accounting for expiry even before the nightly job marks it. */
    public function effectiveStatus(): string
    {
        return $this->status === 'verified' && $this->isExpired() ? 'expired' : $this->status;
    }

    public function scopeValid(Builder $q): Builder
    {
        return $q->where('status', 'verified')
            ->where(fn ($w) => $w->whereNull('expires_on')->orWhere('expires_on', '>=', now()->toDateString()));
    }

    public function typeLabel(): string
    {
        return CredentialCategory::where('code', $this->type)->value('name')
            ?? self::TYPES[$this->type]
            ?? ucwords(str_replace('_', ' ', $this->type));
    }
}
