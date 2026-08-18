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
 * Invitation d'un apprenant à rejoindre son espace de formation (mode Entreprise).
 * Contient un lien pour définir son mot de passe, réutilisant le jeton de
 * réinitialisation standard de Laravel.
 */
class InvitationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $setPasswordUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre accès à la plateforme de formation',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.invitation',
        );
    }
}
