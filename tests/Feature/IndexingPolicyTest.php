<?php

declare(strict_types=1);

use App\Models\Course;

/**
 * Tant que le site n'est pas declare indexable, il doit rester hors des moteurs de
 * recherche et des robots d'IA : robots.txt bloquant, en-tete X-Robots-Tag, balise
 * meta noindex.
 */
it('robots.txt bloque tout quand le site n’est pas indexable', function (): void {
    config()->set('lms.site.indexable', false);

    $response = $this->get('/robots.txt');

    $response->assertOk();
    expect($response->getContent())
        ->toContain('User-agent: *')
        ->toContain('Disallow: /')
        ->not->toContain('Sitemap:');
});

it('ajoute l’en-tete X-Robots-Tag quand le site n’est pas indexable', function (): void {
    config()->set('lms.site.indexable', false);

    $this->get(route('home'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noai, noimageai');
});

it('pose la balise meta noindex sur les pages publiques quand le site n’est pas indexable', function (): void {
    config()->set('lms.site.indexable', false);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', escape: false);
});

it('ouvre l’indexation et annonce le sitemap une fois le site declare indexable', function (): void {
    config()->set('lms.site.indexable', true);

    $robots = $this->get('/robots.txt');
    $robots->assertOk();
    expect($robots->getContent())
        ->toContain('Sitemap:')
        ->toContain('Disallow: /admin');

    $this->get(route('home'))
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertDontSee('name="robots" content="noindex, nofollow"', escape: false);
});

it('n’expose jamais le contenu payant meme une fois indexable', function (): void {
    config()->set('lms.site.indexable', true);
    Course::factory()->published()->create();

    $robots = $this->get('/robots.txt');

    // Les espaces prives restent fermes.
    expect($robots->getContent())
        ->toContain('Disallow: /mon-espace')
        ->toContain('Disallow: /apprendre');
});
