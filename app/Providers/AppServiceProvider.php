<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Facades\Platform as PlatformFacade;
use App\Services\Payment\OfflinePaymentGateway;
use App\Services\Platform\PlatformManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Point de vérité unique du mode de plateforme, partagé sur toute la requête.
        $this->app->singleton(PlatformManager::class);

        // Passerelle de paiement active (mode Commercial). Interchangeable par config.
        $this->app->bind(PaymentGateway::class, function (): PaymentGateway {
            return match ((string) config('lms.payment.gateway')) {
                default => new OfflinePaymentGateway,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @feature('capacite') ... @endfeature : rend le bloc seulement si la capacité
        // est active dans le mode courant. L'application reste enforcée côté serveur
        // (routes, policies) ; cette directive ne fait que masquer l'affichage.
        Blade::if('feature', fn (string $capability): bool => PlatformFacade::allows($capability));
    }
}
