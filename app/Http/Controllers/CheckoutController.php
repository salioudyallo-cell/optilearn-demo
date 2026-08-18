<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Order;
use App\Services\Payment\OrderFulfiller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Parcours d'achat d'une formation (mode Commercial). L'accès est débloqué à la
 * confirmation du paiement (manuelle en mode hors-ligne, automatique via webhook demain).
 */
class CheckoutController extends Controller
{
    /**
     * Crée une commande pour une formation. Une formation gratuite (ou 100 % remisée)
     * débloque l'accès immédiatement.
     */
    public function store(Request $request, Course $course, PaymentGateway $gateway, OrderFulfiller $fulfiller): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);
        abort_unless($course->isPublished(), 404);

        if ($user->hasAccessToCourse($course)) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'coupon' => ['nullable', 'string', 'max:30'],
        ]);

        $amount = $course->price_fcfa;
        $coupon = null;

        if (! empty($validated['coupon'])) {
            $coupon = Coupon::query()->where('code', Str::upper($validated['coupon']))->first();

            if ($coupon === null || ! $coupon->isUsable()) {
                return back()->withErrors(['coupon' => __('Code promo invalide ou expiré.')]);
            }

            $amount = $coupon->apply($amount);
        }

        $order = Order::create([
            'user_id' => $user->getKey(),
            'course_id' => $course->getKey(),
            'coupon_id' => $coupon?->getKey(),
            'amount_fcfa' => $amount,
            'provider' => $gateway->name(),
            'provider_ref' => strtoupper(Str::random(12)),
            'status' => OrderStatus::Pending,
        ]);

        // Gratuit ou entièrement remisé : accès immédiat.
        if ($amount === 0) {
            $fulfiller->markPaid($order);

            return redirect()->route('dashboard')->with('status', __('Votre accès a été activé.'));
        }

        return redirect()->route('checkout.show', $order);
    }

    /**
     * Récapitulatif de commande : instructions de paiement, ou accès si déjà payée.
     */
    public function show(Order $order, PaymentGateway $gateway): View
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return view('public.checkout', [
            'order' => $order->load('course'),
            'instructions' => $order->isPaid() ? null : $gateway->instructions($order),
        ]);
    }
}
