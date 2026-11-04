<?php

declare(strict_types=1);

namespace App\Domain\Specimens\Enums;

/**
 * Chain-of-custody event types. Each event maps to a required prior specimen status
 * and a resulting specimen status. Rules are enforced by SpecimenCustody.
 */
enum CustodyEvent: string
{
    case Collected = 'collected';
    case HandedOver = 'handed_over';
    case Dispatched = 'dispatched';
    case Received = 'received';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Disposed = 'disposed';

    /**
     * @param  bool  $courierRequired  whether the workflow requires a dispatch before receipt
     * @return list<SpecimenStatus>
     */
    public function requiredFrom(bool $courierRequired): array
    {
        return match ($this) {
            self::Collected => [SpecimenStatus::PendingCollection],
            self::HandedOver => [SpecimenStatus::Collected, SpecimenStatus::InTransit],
            self::Dispatched => [SpecimenStatus::Collected],
            self::Received => $courierRequired ? [SpecimenStatus::InTransit] : [SpecimenStatus::Collected, SpecimenStatus::InTransit],
            self::Accepted, self::Rejected => [SpecimenStatus::Received],
            self::Disposed => [SpecimenStatus::Accepted, SpecimenStatus::Rejected],
        };
    }

    public function resultingStatus(SpecimenStatus $current): SpecimenStatus
    {
        return match ($this) {
            self::Collected => SpecimenStatus::Collected,
            self::HandedOver => $current,
            self::Dispatched => SpecimenStatus::InTransit,
            self::Received => SpecimenStatus::Received,
            self::Accepted => SpecimenStatus::Accepted,
            self::Rejected => SpecimenStatus::Rejected,
            self::Disposed => SpecimenStatus::Disposed,
        };
    }

    /** Which side of the network is allowed to record this event. */
    public function recordedBy(): string
    {
        return match ($this) {
            self::Collected, self::HandedOver, self::Dispatched => 'sender',
            default => 'laboratory',
        };
    }

    public function requiresReason(): bool
    {
        return in_array($this, [self::Rejected, self::Disposed], true);
    }

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
