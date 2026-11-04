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

class OrganizationApprovedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public User $recipient,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[NODIVA] Institutional Access Confirmed & Activated: {$this->organization->name}",
        );
    }

    public function content(): Content
    {
        $dashboardRoute = match ($this->organization->type->value) {
            'hospital' => route('hospital.dashboard'),
            'laboratory' => route('lab.dashboard'),
            'corporate' => route('corporate.dashboard'),
            default => route('login'),
        };

        return new Content(
            view: 'emails.organization-approved',
            with: [
                'organization' => $this->organization,
                'recipient' => $this->recipient,
                'dashboardUrl' => $dashboardRoute,
            ],
        );
    }
}
