<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientConsent extends Model
{
    public const PURPOSES = [
        'external_referral_testing' => 'External referral testing',
        'result_sharing' => 'Result sharing with referring clinicians',
    ];

    protected $fillable = ['patient_id', 'organization_id', 'purpose', 'method', 'granted_at', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return ['granted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
