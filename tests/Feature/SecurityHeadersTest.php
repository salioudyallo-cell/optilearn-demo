<?php

declare(strict_types=1);

/**
 * En-têtes de sécurité présents sur les réponses web.
 */
it('pose les en-têtes de sécurité sur les pages publiques', function (): void {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
});

it('n’ajoute pas HSTS en clair (HTTP)', function (): void {
    $this->get(route('home'))->assertHeaderMissing('Strict-Transport-Security');
});

it('ajoute HSTS en HTTPS', function (): void {
    $response = $this->get('https://localhost/');

    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});
