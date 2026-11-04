<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamMemberInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly OrganizationInvitation $invitation
    ) {
    }

    public function envelope(): Envelope
    {
        $orgName = $this->invitation->organization->name ?? 'NODIVA Operations Network';

        return new Envelope(
            subject: "Invitation: Join {$orgName} Console",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.team-invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => route('invitations.accept', ['token' => $this->invitation->token]),
                'inviter' => $this->invitation->inviter,
                'role' => $this->invitation->role,
            ],
        );
    }
}
