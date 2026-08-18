<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * robots.txt dynamique. Tant que le site n'est pas indexable, tout est bloque
     * (moteurs et robots IA). Une fois indexable, seules les zones privees sont
     * fermees et le sitemap est annonce.
     */
    public function robots(): Response
    {
        if (! config('lms.site.indexable')) {
            $body = "User-agent: *\nDisallow: /\n";
        } else {
            $disallow = ['/admin', '/mon-espace', '/apprendre', '/activer', '/certificats', '/profil'];
            $body = "User-agent: *\n";
            foreach ($disallow as $path) {
                $body .= "Disallow: {$path}\n";
            }
            $body .= "\nSitemap: ".route('sitemap').PHP_EOL;
        }

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Sitemap XML : pages publiques statiques + fiches des formations publiees.
     */
    public function index(): Response
    {
        $urls = [
            ['loc' => route('home'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('catalog.index'), 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => route('legal.terms'), 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => route('legal.notice'), 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => route('legal.privacy'), 'changefreq' => 'yearly', 'priority' => '0.3'],
        ];

        Course::query()
            ->published()
            ->orderByDesc('published_at')
            ->get()
            ->each(function (Course $course) use (&$urls): void {
                $urls[] = [
                    'loc' => route('catalog.show', $course),
                    'lastmod' => $course->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            });

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
