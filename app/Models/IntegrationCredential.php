<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IntegrationCredential extends Model
{
    use HasPublicUuid;

    public const SCOPES = ['catalogue:read', 'orders:read', 'results:read'];

    protected $fillable = ['name', 'scopes', 'expires_at', 'created_by'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['scopes' => 'array', 'last_used_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /** @return array{0: self, 1: string} the credential and the plaintext token (shown once) */
    public static function issue(Organization $org, string $name, array $scopes, ?int $userId = null): array
    {
        $plain = 'ndv_'.Str::random(48);
        $cred = new self(['name' => $name, 'scopes' => array_values(array_intersect($scopes, self::SCOPES)), 'created_by' => $userId]);
        $cred->organization_id = $org->id;
        $cred->token_prefix = substr($plain, 0, 8);
        $cred->token_hash = hash('sha256', $plain);
        $cred->save();

        return [$cred, $plain];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
