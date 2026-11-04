<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Organization;
use App\Models\ReferralOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferralRequestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ReferralOrder $order,
        public Organization $laboratory,
        public bool $isBroadcast = true,
    ) {
    }

    public function envelope(): Envelope
    {
        $priorityPrefix = strtoupper($this->order->priority) === 'URGENT' ? '[URGENT / STAT] ' : '';
        $modeText = $this->isBroadcast ? 'Open Broadcast' : 'Direct Referral';

        return new Envelope(
            subject: "{$priorityPrefix}[NODIVA] New {$modeText}: {$this->order->reference} from {$this->order->requestingOrganization?->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.referral-request-notification',
            with: [
                'order' => $this->order,
                'laboratory' => $this->laboratory,
                'isBroadcast' => $this->isBroadcast,
                'actionUrl' => route('lab.reception.index'),
            ],
        );
    }
}
