<?php

declare(strict_types=1);

namespace App\Domain\Results;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Domain\Referrals\Enums\OrderStatus;
use App\Domain\Referrals\OrderWorkflow;
use App\Domain\Results\Enums\ResultStatus;
use App\Domain\Support\DomainException;
use App\Models\CriticalResultAcknowledgement;
use App\Models\DiagnosticResult;
use App\Models\DiagnosticResultItem;
use App\Models\ReferralOrder;
use App\Models\ResultDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Diagnostic Result entry, authorization, release, amendment and critical acknowledgment workflow.
 */
final class ResultWorkflow
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OrderWorkflow $workflow,
    ) {
    }

    /**
     * Enter draft result or create new version.
     */
    public function enterResult(ReferralOrder $order, array $data, Actor $actor): DiagnosticResult
    {
        $actor->authorize('results.enter');

        if ($order->laboratory_id !== $actor->organizationId()) {
            throw new DomainException('Only the fulfilling laboratory can enter results.');
        }

        return DB::transaction(function () use ($order, $data, $actor) {
            $existingDraft = $order->results()->where('status', ResultStatus::Draft)->latest('id')->first();

            if ($existingDraft) {
                $result = $existingDraft;
                $result->update([
                    'interpretation' => $data['interpretation'] ?? null,
                    'is_critical' => (bool) ($data['is_critical'] ?? false),
                    'amendment_reason' => $data['amendment_reason'] ?? null,
                    'entered_by' => $actor->user->id,
                ]);
                $result->items()->delete();
                $version = $result->version;
            } else {
                $latest = $order->results()->latest('version')->first();
                $version = $latest ? $latest->version + 1 : 1;

                $result = DiagnosticResult::query()->create([
                    'referral_order_id' => $order->id,
                    'version' => $version,
                    'supersedes_id' => $latest?->id,
                    'status' => ResultStatus::Draft,
                    'interpretation' => $data['interpretation'] ?? null,
                    'is_critical' => (bool) ($data['is_critical'] ?? false),
                    'amendment_reason' => $data['amendment_reason'] ?? null,
                    'entered_by' => $actor->user->id,
                ]);
            }

            // Save items
            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    DiagnosticResultItem::query()->create([
                        'diagnostic_result_id' => $result->id,
                        'referral_order_item_id' => $item['referral_order_item_id'] ?? null,
                        'analyte' => $item['analyte'],
                        'loinc_code' => $item['loinc_code'] ?? null,
                        'value' => (string) $item['value'],
                        'unit' => $item['unit'] ?? null,
                        'reference_range' => $item['reference_range'] ?? null,
                        'flag' => $item['flag'] ?? 'normal',
                    ]);
                }
            }

            $this->audit->log('result.entered', $result, ['version' => $version], 'success', $actor);

            return $result;
        });
    }

    /**
     * Upload supporting document (e.g., signed clinical PDF report, electropherogram, histopathology slide photo).
     * Saved to private disk only — NEVER publicly accessible.
     */
    public function attachDocument(DiagnosticResult $result, UploadedFile $file, Actor $actor): ResultDocument
    {
        $actor->authorize('results.enter');

        $disk = config('filesystems.clinical_disk', 'local');
        $sha256 = hash_file('sha256', $file->getRealPath());
        $path = $file->store("clinical_results/{$result->order->uuid}", $disk);

        return ResultDocument::query()->create([
            'diagnostic_result_id' => $result->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'sha256' => $sha256,
            'uploaded_by' => $actor->user->id,
        ]);
    }

    /**
     * Submit draft result for review / authorising release.
     */
    public function submitForReview(DiagnosticResult $result, Actor $actor): DiagnosticResult
    {
        $actor->authorize('results.enter');

        if ($result->status !== ResultStatus::Draft) {
            throw new DomainException('Result is not in draft status.');
        }

        $result->status = ResultStatus::PendingReview;
        $result->submitted_by = $actor->user->id;
        $result->submitted_at = now();
        $result->save();

        $order = $result->order;
        $this->workflow->transition($order, OrderStatus::AwaitingResultReview, $actor, 'Diagnostic result submitted for pathologist/lead review');
        $order->save();

        return $result;
    }

    /**
     * Pathologist / authorised laboratory personnel reviews and officially releases the result.
     * Enforces separation of duties if configured.
     */
    public function releaseResult(DiagnosticResult $result, Actor $actor, ?string $reviewNotes = null): DiagnosticResult
    {
        $actor->authorize('results.release');

        if ($result->status !== ResultStatus::PendingReview && $result->status !== ResultStatus::Draft) {
            throw new DomainException('Result is not ready for release.');
        }

        if (config('nodiva.result_separation_of_duties', true)) {
            if ($result->entered_by === $actor->user->id && ! $actor->organization()->setting('allow_self_release', false)) {
                throw new DomainException('Separation of duties policy requires result release by a different authorized reviewer.');
            }
        }

        return DB::transaction(function () use ($result, $actor, $reviewNotes) {
            if ($result->supersedes_id) {
                DiagnosticResult::where('id', $result->supersedes_id)->update(['status' => ResultStatus::Superseded]);
            }

            $result->status = ResultStatus::Released;
            $result->released_by = $actor->user->id;
            $result->released_at = now();
            $result->review_notes = $reviewNotes;
            $result->save();

            $order = $result->order;
            $order->result_released_at = now();
            $this->workflow->transition($order, OrderStatus::ResultReleased, $actor, 'Result verified, authorised and released to referring institution');

            // If no dispute/hold, complete order
            $order->completed_at = now();
            $this->workflow->transition($order, OrderStatus::Completed, $actor, 'Referral order completed');
            $order->save();

            $this->audit->log('result.released', $result, [
                'is_critical' => $result->is_critical,
                'version' => $result->version,
            ], 'success', $actor);

            return $result;
        });
    }

    /**
     * Referring clinician acknowledges critical result.
     */
    public function acknowledgeCritical(DiagnosticResult $result, Actor $actor, ?string $notes = null): CriticalResultAcknowledgement
    {
        $actor->authorize('results.acknowledge_critical');

        if (! $result->is_critical) {
            throw new DomainException('Result does not have a critical flag.');
        }

        return DB::transaction(function () use ($result, $actor, $notes) {
            $ack = CriticalResultAcknowledgement::query()->updateOrCreate(
                [
                    'diagnostic_result_id' => $result->id,
                    'organization_id' => $actor->organizationId(),
                ],
                [
                    'user_id' => $actor->user->id,
                    'notes' => $notes,
                    'acknowledged_at' => now(),
                ]
            );

            $this->audit->log('result.critical_acknowledged', $result, [], 'success', $actor);

            return $ack;
        });
    }
}
