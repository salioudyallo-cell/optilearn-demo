<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Facades\Track;
use App\Mail\AccessGrantedMail;
use App\Models\Enrollment;
use App\Models\Order;
use App\Services\Tracking\EventName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Confirme le paiement d'une commande et débloque l'accès : marque la commande payée,
 * crée l'inscription (source Purchase), consomme le code promo éventuel, envoie l'e-mail
 * d'accès et enregistre l'événement. Idempotent : rappeler sur une commande déjà payée
 * ne fait rien.
 *
 * Utilisé aujourd'hui par la confirmation manuelle (admin) ; utilisable demain par un
 * webhook de prestataire en ligne, sans changement.
 */
class OrderFulfiller
{
    public function markPaid(Order $order): void
    {
        if ($order->isPaid()) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $order->forceFill([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
            ])->save();

            Enrollment::updateOrCreate(
                ['user_id' => $order->user_id, 'course_id' => $order->course_id],
                [
                    'status' => EnrollmentStatus::Active,
                    'source' => EnrollmentSource::Purchase,
                    'order_id' => $order->getKey(),
                    'enrolled_at' => now(),
                ],
            );

            if ($order->coupon_id !== null && $order->coupon !== null) {
                $order->coupon->increment('used_count');
            }
        });

        $order->loadMissing(['user', 'course']);

        if ($order->user !== null && $order->course !== null) {
            Mail::to($order->user->email)->send(new AccessGrantedMail($order->user, $order->course));
            Track::event(EventName::CodeActivated, [
                'course_id' => $order->course_id,
                'via' => 'purchase',
            ], $order->user);
        }
    }
}
