<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewAccessRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public User $applicant,
    ) {
    }

    public function envelope(): Envelope
    {
        $typeName = match ($this->organization->type->value) {
            'hospital' => 'Referring Hospital',
            'laboratory' => 'Testing Laboratory',
            'corporate' => 'Corporate Client',
            default => 'Institution',
        };

        return new Envelope(
            subject: "[NODIVA Operations] New Access Request: {$this->organization->name} ({$typeName})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-access-request',
            with: [
                'organization' => $this->organization,
                'applicant' => $this->applicant,
                'reviewUrl' => route('ops.partners.index', ['status' => 'pending']),
            ],
        );
    }
}
