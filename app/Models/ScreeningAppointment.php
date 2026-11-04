<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreeningAppointment extends Model
{
    protected $fillable = ['program_participant_id'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CollectionSession::class, 'collection_session_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(ProgramParticipant::class, 'program_participant_id');
    }
}
