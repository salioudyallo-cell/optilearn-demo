<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Modèle économique de l'instance. Le mode n'est qu'un préréglage nommé de capacités
 * (voir config/platform.php) : le code ne teste jamais le mode directement, il teste une
 * capacité via Platform::allows(). Ajouter un modèle futur = ajouter un cas ici et son
 * préréglage, sans réécrire le reste.
 */
enum PlatformMode: string
{
    /** Catalogue public payant, type Udemy/Coursera. */
    case Commercial = 'commercial';

    /** Espace de formation privé d'une organisation : aucun prix, aucun paiement. */
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Commercial => __('platform.mode.commercial'),
            self::Enterprise => __('platform.mode.enterprise'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Commercial => __('platform.mode.commercial_hint'),
            self::Enterprise => __('platform.mode.enterprise_hint'),
        };
    }
}
