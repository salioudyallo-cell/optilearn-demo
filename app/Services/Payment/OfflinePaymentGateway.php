<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Support\Money;

/**
 * Paiement hors-ligne : l'acheteur règle par Wave, Orange Money, virement ou espèces,
 * puis un administrateur confirme la commande, ce qui débloque l'accès. Le mode par
 * défaut, adapté au marché où le paiement en ligne n'est pas encore intégré.
 */
final class OfflinePaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'offline';
    }

    public function requiresManualConfirmation(): bool
    {
        return true;
    }

    public function instructions(Order $order): string
    {
        $whatsapp = config('lms.contact.whatsapp_number');
        $contact = $whatsapp
            ? 'WhatsApp : +'.$whatsapp
            : 'contact@opti-leads.com';

        return "Pour finaliser votre commande n° {$order->provider_ref}, réglez "
            .Money::fcfa($order->amount_fcfa)
            .' par Wave, Orange Money, virement ou espèces, puis envoyez votre preuve de '
            .'paiement à '.config('brand.short_name')." ({$contact}). Votre accès sera activé dès confirmation.";
    }
}
