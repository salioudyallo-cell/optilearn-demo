<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    /**
     * Formate un montant en FCFA avec un espace insecable comme separateur de milliers.
     * Les montants de la plateforme sont exclusivement en FCFA (entiers, sans decimale).
     */
    public static function fcfa(int $amount): string
    {
        return number_format($amount, 0, ',', "\u{00A0}")."\u{00A0}FCFA";
    }
}
