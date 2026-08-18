<?php

declare(strict_types=1);

namespace App\Support;

use App\Mail\AdminExceptionMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Notifie l'administrateur par email lors d'une exception non gérée, avec un anti-flood :
 * au plus un email par « type » d'exception sur la fenêtre configurée. L'activation passe
 * par la configuration (jamais par app()->environment() dans la logique — règle §5).
 */
final class ExceptionReporter
{
    public function report(Throwable $e): void
    {
        if (! config('lms.error_notifications.enabled')) {
            return;
        }

        $recipient = config('lms.error_notifications.email');
        if (blank($recipient)) {
            return;
        }

        // Une signature par type + emplacement : on ne renotifie pas la meme erreur en boucle.
        $signature = 'error-notified:'.md5($e::class.'|'.$e->getFile().'|'.$e->getLine());
        $throttle = (int) config('lms.error_notifications.throttle_minutes');

        if (! Cache::add($signature, true, now()->addMinutes($throttle))) {
            return;
        }

        try {
            $context = app()->runningInConsole() ? 'CLI' : request()->fullUrl();

            Mail::to($recipient)->send(new AdminExceptionMail(
                exceptionClass: $e::class,
                exceptionMessage: $e->getMessage(),
                location: $e->getFile().':'.$e->getLine(),
                context: $context,
                trace: $e->getTraceAsString(),
            ));
        } catch (Throwable $mailFailure) {
            // Ne jamais laisser l'envoi d'alerte casser le traitement de l'erreur initiale.
            Log::warning('Échec de la notification d’erreur admin : '.$mailFailure->getMessage());
        }
    }
}
