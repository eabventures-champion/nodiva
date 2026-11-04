<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Domain\Organizations\Actor;
use App\Domain\Organizations\CurrentOrganization;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes append-only, hash-chained audit records. Context is redacted: clinical values, names and secrets are
 * never written to the audit trail — only identifiers and minimal operational context.
 */
final class AuditLogger
{
    private const REDACT = ['password', 'token', 'secret', 'value', 'interpretation', 'clinical_notes', 'first_name', 'last_name', 'full_name', 'date_of_birth', 'phone', 'email', 'medical_record_number', 'mfa'];

    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function log(string $action, ?Model $subject = null, array $context = [], string $outcome = 'success', ?Actor $actor = null): AuditLog
    {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();
        $actorId = $actor?->user->id ?? auth()->id();
        $orgId = $actor?->organizationId() ?? $this->current->id();

        return DB::transaction(function () use ($action, $subject, $context, $outcome, $actorId, $orgId, $request) {
            $previous = AuditLog::query()->orderByDesc('id')->lockForUpdate()->value('hash');
            $record = [
                'uuid' => (string) Str::uuid(),
                'actor_id' => $actorId,
                'actor_organization_id' => $orgId,
                'action' => $action,
                'auditable_type' => $subject ? class_basename($subject) : null,
                'auditable_id' => $subject?->getKey(),
                'outcome' => $outcome,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
                'context' => $this->redact($context),
                'previous_hash' => $previous,
                'created_at' => now()->format('Y-m-d H:i:s.u'),
            ];
            $record['hash'] = self::hashFor($record);

            return AuditLog::query()->create($record);
        });
    }

    public static function hashFor(array $r): string
    {
        $payload = json_encode([
            $r['uuid'], $r['actor_id'], $r['actor_organization_id'], $r['action'], $r['auditable_type'],
            $r['auditable_id'], $r['outcome'], $r['context'], $r['previous_hash'],
        ], JSON_UNESCAPED_SLASHES);

        return hash('sha256', (string) $payload);
    }

    /** Verifies the hash chain; returns the id of the first broken record or null when intact. */
    public static function verifyChain(): ?int
    {
        $previous = null;
        foreach (AuditLog::query()->orderBy('id')->cursor() as $log) {
            $expected = self::hashFor([
                'uuid' => $log->uuid, 'actor_id' => $log->actor_id, 'actor_organization_id' => $log->actor_organization_id,
                'action' => $log->action, 'auditable_type' => $log->auditable_type, 'auditable_id' => $log->auditable_id,
                'outcome' => $log->outcome, 'context' => $log->context, 'previous_hash' => $log->previous_hash,
            ]);
            if ($log->previous_hash !== $previous || ! hash_equals($expected, $log->hash)) {
                return $log->id;
            }
            $previous = $log->hash;
        }

        return null;
    }

    private function redact(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = $this->redact($value);
            } elseif (is_string($key) && Str::contains(strtolower($key), self::REDACT)) {
                $context[$key] = '[redacted]';
            }
        }

        return $context;
    }
}
