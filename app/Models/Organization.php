<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Organizations\Enums\OrganizationStatus;
use App\Domain\Organizations\Enums\OrganizationType;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, HasPublicUuid, SoftDeletes;

    // `type` and `status` are deliberately not fillable: they are set by onboarding/approval services.
    protected $fillable = ['name', 'legal_name', 'registration_number', 'email', 'phone', 'address_line', 'city', 'service_region_id', 'country_code', 'currency'];

    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'status' => OrganizationStatus::class,
            'approved_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function isType(OrganizationType $type): bool
    {
        return $this->type === $type;
    }

    public function isActive(): bool
    {
        return $this->status === OrganizationStatus::Active;
    }

    public function isPlatform(): bool
    {
        return $this->type === OrganizationType::Platform;
    }

    public function isHospital(): bool
    {
        return $this->type === OrganizationType::Hospital;
    }

    public function isLaboratory(): bool
    {
        return $this->type === OrganizationType::Laboratory;
    }

    public function isCorporate(): bool
    {
        return $this->type === OrganizationType::Corporate;
    }

    public function scopeOfType(Builder $q, OrganizationType $type): Builder
    {
        return $q->where('type', $type->value);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', OrganizationStatus::Active->value);
    }

    public function hasVerifiedQualifications(): bool
    {
        $compulsoryCodes = CredentialCategory::where('is_active', true)
            ->where('is_compulsory', true)
            ->pluck('code');

        if ($compulsoryCodes->isEmpty()) {
            return $this->qualifications()
                ->where('status', 'verified')
                ->where(fn ($w) => $w->whereNull('expires_on')->orWhere('expires_on', '>=', now()->toDateString()))
                ->exists();
        }

        $verifiedCodes = $this->qualifications()
            ->where('status', 'verified')
            ->where(fn ($w) => $w->whereNull('expires_on')->orWhere('expires_on', '>=', now()->toDateString()))
            ->pluck('type')
            ->unique();

        return $compulsoryCodes->diff($verifiedCodes)->isEmpty();
    }

    public function scopeWithVerifiedQualifications(Builder $q): Builder
    {
        $compulsoryCodes = CredentialCategory::where('is_active', true)
            ->where('is_compulsory', true)
            ->pluck('code');

        if ($compulsoryCodes->isEmpty()) {
            return $q->whereHas('qualifications', function ($sub) {
                $sub->where('status', 'verified')
                    ->where(fn ($w) => $w->whereNull('expires_on')->orWhere('expires_on', '>=', now()->toDateString()));
            });
        }

        foreach ($compulsoryCodes as $code) {
            $q->whereHas('qualifications', function ($sub) use ($code) {
                $sub->where('type', $code)
                    ->where('status', 'verified')
                    ->where(fn ($w) => $w->whereNull('expires_on')->orWhere('expires_on', '>=', now()->toDateString()));
            });
        }

        return $q;
    }

    public function compulsoryQualificationsStatus(): array
    {
        $compulsoryCategories = CredentialCategory::where('is_active', true)
            ->where('is_compulsory', true)
            ->orderBy('sort_order')
            ->get();

        $verifiedQuals = $this->qualifications()
            ->where('status', 'verified')
            ->where(fn ($w) => $w->whereNull('expires_on')->orWhere('expires_on', '>=', now()->toDateString()))
            ->get();

        $pendingQuals = $this->qualifications()
            ->where('status', 'pending')
            ->get();

        $status = [];
        foreach ($compulsoryCategories as $cat) {
            $isVerified = $verifiedQuals->contains('type', $cat->code);
            $isPending = ! $isVerified && $pendingQuals->contains('type', $cat->code);

            $status[] = [
                'category' => $cat,
                'code' => $cat->code,
                'name' => $cat->name,
                'is_satisfied' => $isVerified,
                'status' => $isVerified ? 'verified' : ($isPending ? 'pending' : 'missing'),
            ];
        }

        return $status;
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(ServiceRegion::class, 'service_region_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(OrganizationBranch::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function laboratoryProfile(): HasOne
    {
        return $this->hasOne(LaboratoryProfile::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(LaboratoryQualification::class);
    }

    public function catalogueEntries(): HasMany
    {
        return $this->hasMany(TestCatalogueEntry::class);
    }

    public function serviceRegions(): BelongsToMany
    {
        return $this->belongsToMany(ServiceRegion::class, 'laboratory_service_region');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function screeningPrograms(): HasMany
    {
        return $this->hasMany(ScreeningProgram::class);
    }

    public function employeeRecords(): HasMany
    {
        return $this->hasMany(EmployeeRecord::class);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default);
    }
}
