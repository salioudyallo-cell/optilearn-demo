<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tant que le site n'est pas déclaré indexable (config lms.site.indexable), ajoute un
 * en-tête X-Robots-Tag interdisant l'indexation par les moteurs et l'usage par les
 * robots d'IA. L'en-tête HTTP couvre aussi les réponses non-HTML (PDF, flux), là où la
 * balise meta ne s'applique pas.
 */
class EnforceIndexingPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('lms.site.indexable')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noai, noimageai');
        }

        return $response;
    }
}
