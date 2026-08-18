<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\InvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

/**
 * Envoie à un apprenant un lien pour définir son mot de passe et activer son compte.
 * Réutilise le jeton de réinitialisation standard de Laravel : aucun mécanisme parallèle
 * à maintenir, et le lien mène à la page « réinitialiser le mot de passe » existante.
 */
class InvitationService
{
    public function send(User $user): void
    {
        $token = Password::broker()->createToken($user);

        $url = route('password.reset', ['token' => $token]).'?email='.urlencode($user->email);

        Mail::to($user->email)->send(new InvitationMail($user, $url));
    }
}
