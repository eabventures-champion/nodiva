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

class AccessRequestConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public User $applicant,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[NODIVA] Institutional Access Request Received: {$this->organization->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.access-request-confirmation',
            with: [
                'organization' => $this->organization,
                'applicant' => $this->applicant,
            ],
        );
    }
}
