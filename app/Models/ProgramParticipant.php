<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramParticipant extends Model
{
    use HasPublicUuid;

    public const STATUSES = ['invited', 'scheduled', 'attended', 'collected', 'completed', 'no_show', 'withdrawn'];

    public const OUTCOMES = ['fit' => 'Fit', 'fit_with_restrictions' => 'Fit with restrictions', 'unfit' => 'Unfit', 'referred' => 'Referred for follow-up'];

    protected $fillable = ['screening_program_id', 'employee_record_id', 'status', 'outcome', 'consent_to_screening', 'consent_to_employer_results', 'consent_recorded_at'];

    protected function casts(): array
    {
        return ['consent_to_screening' => 'boolean', 'consent_to_employer_results' => 'boolean', 'consent_recorded_at' => 'datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ScreeningProgram::class, 'screening_program_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeRecord::class, 'employee_record_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ScreeningAppointment::class);
    }
}
