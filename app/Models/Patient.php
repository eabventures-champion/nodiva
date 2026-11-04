<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Patient record owned by a hospital. Identifiable fields are encrypted at rest; `reference` is the
 * pseudonymous identifier used in routine operations, notifications and by NODIVA operations staff.
 */
class Patient extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = ['organization_id', 'medical_record_number', 'mrn_hash', 'first_name', 'last_name', 'date_of_birth', 'sex', 'phone', 'reference', 'created_by'];

    protected $hidden = ['medical_record_number', 'mrn_hash', 'first_name', 'last_name', 'date_of_birth', 'phone'];

    protected function casts(): array
    {
        return [
            'medical_record_number' => 'encrypted',
            'first_name' => 'encrypted',
            'last_name' => 'encrypted',
            'date_of_birth' => 'encrypted',
            'phone' => 'encrypted',
        ];
    }

    public static function mrnHash(string $organizationUuid, string $mrn): string
    {
        return hash_hmac('sha256', $organizationUuid.'|'.mb_strtolower(trim($mrn)), (string) config('app.key'));
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(PatientConsent::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ReferralOrder::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function age(): ?int
    {
        try {
            return \Illuminate\Support\Carbon::parse($this->date_of_birth)->age;
        } catch (\Throwable) {
            return null;
        }
    }

    public function hasActiveConsent(string $purpose): bool
    {
        return $this->consents()->where('purpose', $purpose)->whereNull('revoked_at')->exists();
    }
}
