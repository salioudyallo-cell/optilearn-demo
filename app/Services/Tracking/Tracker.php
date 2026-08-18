<?php

declare(strict_types=1);

namespace App\Services\Tracking;

use App\Models\TrackedEvent;
use App\Models\User;
use Throwable;

/**
 * Enregistre un événement produit. Volontairement tolérant : le suivi ne doit JAMAIS
 * casser un parcours utilisateur, donc toute erreur d'écriture est avalée.
 *
 * Noms d'événements standardisés dans EventName.
 */
class Tracker
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function event(string $name, array $properties = [], ?User $user = null): void
    {
        try {
            TrackedEvent::create([
                'name' => $name,
                'user_id' => $user?->getKey() ?? auth()->id(),
                'properties' => $properties === [] ? null : $properties,
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Le suivi est best-effort : on n'interrompt jamais l'utilisateur.
        }
    }
}
