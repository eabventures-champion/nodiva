<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultDocument extends Model
{
    use HasPublicUuid;

    protected $fillable = ['disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'sha256', 'uploaded_by'];

    protected $hidden = ['disk', 'path'];

    public function result(): BelongsTo
    {
        return $this->belongsTo(DiagnosticResult::class, 'diagnostic_result_id');
    }
}
