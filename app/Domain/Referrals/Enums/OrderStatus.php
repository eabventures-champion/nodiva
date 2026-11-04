<?php

declare(strict_types=1);

namespace App\Domain\Referrals\Enums;

/**
 * Referral order lifecycle. Transitions are explicit; only OrderWorkflow may change an order's status.
 */
enum OrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Matching = 'matching';
    case AwaitingLabAcceptance = 'awaiting_lab_acceptance';
    case Accepted = 'accepted';
    case CollectionScheduled = 'collection_scheduled';
    case SpecimenCollected = 'specimen_collected';
    case InTransit = 'in_transit';
    case SpecimenReceived = 'specimen_received';
    case SpecimenRejected = 'specimen_rejected';
    case Processing = 'processing';
    case AwaitingResultReview = 'awaiting_result_review';
    case ResultReleased = 'result_released';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Disputed = 'disputed';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted, self::Matching, self::AwaitingLabAcceptance, self::Cancelled],
            self::Submitted => [self::Matching, self::AwaitingLabAcceptance, self::Cancelled],
            self::Matching => [self::AwaitingLabAcceptance, self::Cancelled],
            self::AwaitingLabAcceptance => [self::Accepted, self::Matching, self::SpecimenCollected, self::InTransit, self::SpecimenReceived, self::Cancelled],
            self::Accepted => [self::CollectionScheduled, self::SpecimenCollected, self::InTransit, self::SpecimenReceived, self::Matching, self::Cancelled],
            self::CollectionScheduled => [self::SpecimenCollected, self::InTransit, self::SpecimenReceived, self::Cancelled],
            self::SpecimenCollected => [self::InTransit, self::SpecimenReceived, self::Cancelled],
            self::InTransit => [self::SpecimenReceived],
            self::SpecimenReceived => [self::Processing, self::SpecimenRejected],
            self::SpecimenRejected => [self::CollectionScheduled, self::Cancelled],
            self::Processing => [self::AwaitingResultReview],
            self::AwaitingResultReview => [self::ResultReleased, self::Processing],
            self::ResultReleased => [self::Completed, self::Disputed, self::AwaitingResultReview],
            self::Disputed => [self::ResultReleased, self::Completed],
            self::Completed => [self::Disputed],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Matching => 'Open broadcast (Awaiting acceptance)',
            self::AwaitingLabAcceptance => 'Awaiting lab acceptance',
            self::AwaitingResultReview => 'Awaiting result review',
            default => ucfirst(str_replace('_', ' ', $this->value)),
        };
    }

    /** Semantic tone used by the design system. */
    public function tone(): string
    {
        return match ($this) {
            self::Completed, self::ResultReleased => 'success',
            self::Cancelled, self::SpecimenRejected, self::Disputed => 'danger',
            self::Draft => 'neutral',
            self::Submitted, self::Matching, self::AwaitingLabAcceptance, self::AwaitingResultReview => 'warning',
            default => 'info',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }

    /** Statuses in which the order is "open" for SLA purposes. */
    public static function open(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => ! in_array($s, [self::Draft, self::Completed, self::Cancelled, self::ResultReleased], true)));
    }

    public static function cancellable(): array
    {
        return [self::Draft, self::Submitted, self::Matching, self::AwaitingLabAcceptance, self::Accepted, self::CollectionScheduled, self::SpecimenRejected];
    }
}
