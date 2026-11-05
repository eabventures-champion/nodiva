<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Support\ReferenceGenerator;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CommunicationThread extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'reference',
        'subject',
        'category',
        'priority',
        'recipient_type',
        'sender_organization_id',
        'sender_user_id',
        'recipient_organization_id',
        'status',
        'last_message_at',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CommunicationThread $thread): void {
            if (empty($thread->reference)) {
                $thread->reference = ReferenceGenerator::make('COM', 'communication_threads', 'reference', 6);
            }
            if (empty($thread->last_message_at)) {
                $thread->last_message_at = now();
            }
        });
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommunicationMessage::class, 'thread_id')->oldest();
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(CommunicationMessage::class, 'thread_id')->latestOfMany();
    }

    public function senderOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'sender_organization_id');
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function recipientOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'recipient_organization_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(CommunicationThreadRead::class, 'thread_id');
    }

    public function isBroadcast(): bool
    {
        return in_array($this->recipient_type, ['all_hospitals', 'all_laboratories', 'all_corporates', 'all_partners'], true);
    }

    public function counterpartFor(Organization $currentOrg): ?Organization
    {
        if ($this->sender_organization_id === $currentOrg->id) {
            return $this->recipientOrganization;
        }

        return $this->senderOrganization;
    }

    public function recipientLabel(): string
    {
        return match ($this->recipient_type) {
            'all_hospitals' => 'All Hospital Administrators',
            'all_laboratories' => 'All Laboratory Administrators',
            'all_corporates' => 'All Corporate Administrators',
            'all_partners' => 'All Institutional Partners',
            default => $this->recipientOrganization?->name ?? 'Organization',
        };
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'operational' => 'Operational Notice',
            'clinical' => 'Clinical Advisory',
            'billing' => 'Billing & Settlement',
            'compliance' => 'Accreditation & Compliance',
            'urgent_notice' => 'Urgent Network Notice',
            default => 'General Platform Notice',
        };
    }

    public function priorityBadgeClass(): string
    {
        return match ($this->priority) {
            'urgent' => 'bg-rose-50 text-rose-800 border border-rose-200/80',
            'high' => 'bg-amber-50 text-amber-800 border border-amber-200/80',
            default => 'bg-[#167C78]/10 text-[#167C78] border border-[#167C78]/25',
        };
    }

    public function isAccessibleBy(?Organization $org): bool
    {
        if (! $org) {
            return false;
        }

        if ($org->isPlatform()) {
            return true;
        }

        if ($this->sender_organization_id === $org->id || $this->recipient_organization_id === $org->id) {
            return true;
        }

        if ($this->recipient_type === 'all_partners') {
            return true;
        }

        if ($this->recipient_type === 'all_hospitals' && $org->isHospital()) {
            return true;
        }

        if ($this->recipient_type === 'all_laboratories' && $org->isLaboratory()) {
            return true;
        }

        if ($this->recipient_type === 'all_corporates' && $org->isCorporate()) {
            return true;
        }

        return false;
    }

    public function canBeManagedBy(?Organization $org): bool
    {
        if (! $org) {
            return false;
        }

        // Platform Operations has authority over all communications across the network
        if ($org->isPlatform()) {
            return true;
        }

        // System/Platform broadcast notices can only be closed/resolved by Platform Operations
        if ($this->isBroadcast()) {
            return false;
        }

        // In direct peer-to-peer communications between hospitals, laboratories, and corporates,
        // both the initiator (sender) and recipient organization have the right to resolve or reopen
        return $this->sender_organization_id === $org->id || $this->recipient_organization_id === $org->id;
    }

    public function closerOrganizationName(): ?string
    {
        if (! $this->closer) {
            return null;
        }

        return $this->closer->currentMembership?->organization?->name
            ?? $this->closer->activeMemberships()->first()?->organization?->name;
    }

    public function isUnreadFor(User $user): bool
    {
        $lastMessage = $this->latestMessage ?? $this->messages()->latest()->first();
        if (! $lastMessage) {
            return false;
        }

        if ($lastMessage->user_id === $user->id) {
            return false;
        }

        $read = $this->reads()->where('user_id', $user->id)->first();
        if (! $read) {
            return true;
        }

        return $read->last_read_message_id < $lastMessage->id;
    }

    public function markAsReadFor(User $user): void
    {
        $lastMessage = $this->latestMessage ?? $this->messages()->latest()->first();
        if (! $lastMessage) {
            return;
        }

        $this->reads()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'last_read_message_id' => $lastMessage->id,
                'read_at' => now(),
            ]
        );
    }
}
