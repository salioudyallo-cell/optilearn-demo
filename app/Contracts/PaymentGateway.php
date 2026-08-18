<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Order;

/**
 * Passerelle de paiement. Interchangeable comme le pilote vidéo : brancher un prestataire
 * en ligne (Wave, Orange Money, carte) plus tard ne changera que l'implémentation et le
 * réglage PAYMENT_GATEWAY, sans toucher au reste du parcours d'achat.
 */
interface PaymentGateway
{
    /** Identifiant technique stocké sur la commande (provider). */
    public function name(): string;

    /** Le paiement est-il confirmé à la main (hors-ligne) ou automatiquement (webhook) ? */
    public function requiresManualConfirmation(): bool;

    /** Instructions affichées à l'acheteur après création de la commande. */
    public function instructions(Order $order): string;
}
