<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité HTTP appliqués à toutes les réponses web.
 *
 * Sous-ensemble volontairement « sûr » : ces en-têtes n'exigent aucun ajustement du
 * contenu (contrairement à une CSP stricte, qui casserait les scripts en ligne de
 * Livewire/Filament et devrait être calibrée à part). Ils protègent contre le sniffing
 * de type MIME, le clickjacking et la fuite de référent.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Politique de permissions : ne pas écraser celle que Filament pose sur l'admin.
        if (! $response->headers->has('Permissions-Policy')) {
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        }

        // HSTS uniquement en HTTPS : force le navigateur à rester en TLS pendant un an.
        // Jamais en clair (développement local), pour ne pas verrouiller un domaine http.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
