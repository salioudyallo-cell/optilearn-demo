<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\RedemptionOutcome;
use App\Mail\AccessGrantedMail;
use App\Services\AccessCodeRedeemer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Activation d'un code d'acces. Ecriture en base => Livewire justifie (regle §6).
 * Toute la logique metier (validation, atomicite, rate limiting) vit dans
 * AccessCodeRedeemer : ce composant ne fait que collecter la saisie et afficher le
 * resultat.
 */
class ActivateCode extends Component
{
    #[Validate('required|string|min:6|max:20')]
    public string $code = '';

    public ?string $message = null;

    public bool $success = false;

    public ?string $courseSlug = null;

    public function redeem(AccessCodeRedeemer $redeemer): void
    {
        $this->validate();

        $user = auth()->user();
        abort_if($user === null, 403);

        $result = $redeemer->redeem($user, $this->code);

        $this->success = $result->isSuccessful();
        $this->message = $result->message();
        $this->courseSlug = $result->course?->slug;

        // L'email « acces accorde » n'est envoye que sur une vraie nouvelle activation,
        // pas quand l'apprenant ressaisit un code qu'il a deja utilise.
        if ($result->outcome === RedemptionOutcome::Redeemed && $result->course !== null) {
            Mail::to($user->email)->send(new AccessGrantedMail($user, $result->course));
        }

        if ($this->success) {
            $this->reset('code');
        }
    }

    #[Layout('components.layouts.public')]
    public function render(): View
    {
        return view('livewire.activate-code');
    }
}
