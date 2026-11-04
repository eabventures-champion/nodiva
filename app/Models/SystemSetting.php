<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Data-driven platform configuration (matching weights, logistics charges, tax, retention...).
 */
class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'description', 'updated_by'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember("setting:{$key}", 300, fn () => static::query()->where('key', $key)->first()?->value);

        return $value === null ? $default : ($value['v'] ?? $value);
    }

    public static function put(string $key, mixed $value, ?int $userId = null, ?string $description = null): void
    {
        static::query()->updateOrCreate(['key' => $key], array_filter([
            'value' => ['v' => $value],
            'updated_by' => $userId,
            'description' => $description,
        ], fn ($v) => $v !== null));
        Cache::forget("setting:{$key}");
    }
}
