<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Facades\Platform;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque une route si la capacité requise n'est pas active dans le mode courant.
 *
 * Application côté serveur : une route commerciale (panier, paiement) renvoie 404 en
 * mode Entreprise, même si l'utilisateur en connaît l'URL. Masquer dans l'UI ne suffit
 * jamais.
 *
 *     Route::post('/checkout', ...)->middleware('feature:online_payment');
 */
class EnsureFeature
{
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        abort_unless(Platform::allows($capability), 404);

        return $next($request);
    }
}
