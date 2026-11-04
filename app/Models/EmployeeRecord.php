<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeRecord extends Model
{
    use HasPublicUuid;

    protected $fillable = ['organization_id', 'employee_number', 'full_name', 'email', 'phone', 'sex', 'birth_year', 'department', 'organization_branch_id'];

    protected $hidden = ['full_name', 'email', 'phone'];

    protected function casts(): array
    {
        return ['full_name' => 'encrypted', 'email' => 'encrypted', 'phone' => 'encrypted'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function participations(): HasMany
    {
        return $this->hasMany(ProgramParticipant::class);
    }
}
