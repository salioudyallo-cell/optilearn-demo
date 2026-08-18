<?php

declare(strict_types=1);

use App\Models\Course;

it('expose un sitemap XML incluant les formations publiées', function () {
    $published = Course::factory()->published()->create(['title' => 'Formation indexée']);
    $draft = Course::factory()->create(['title' => 'Brouillon caché']);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('content-type', 'application/xml')
        ->assertSee(route('catalog.show', $published), false)
        ->assertSee(route('home'), false)
        ->assertDontSee(route('catalog.show', $draft), false);
});

it('inclut le JSON-LD schema.org Course sur la fiche formation', function () {
    $course = Course::factory()->published()->create(['title' => 'Formation SEO structurée']);

    $this->get(route('catalog.show', $course))
        ->assertOk()
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type":"Course"', false);
});

it('pose une balise canonical et des métadonnées Open Graph', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('rel="canonical"', false)
        ->assertSee('property="og:title"', false);
});

it('affiche une page 404 à la charte pour une URL inconnue', function () {
    $this->get('/url-qui-nexiste-pas')
        ->assertNotFound()
        ->assertSee('Page introuvable');
});
