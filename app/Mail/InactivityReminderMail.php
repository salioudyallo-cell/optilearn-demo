<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Relance d'un apprenant inactif : l'invite à reprendre une formation en cours.
 */
class InactivityReminderMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $courseTitle,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reprenez votre formation quand vous voulez');
    }

    public function content(): Content
    {
        return new Content(text: 'emails.inactivity-reminder', with: [
            'dashboardUrl' => route('dashboard'),
        ]);
    }
}
