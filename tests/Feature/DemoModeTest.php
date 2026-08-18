<?php

declare(strict_types=1);

it('affiche le bandeau de démonstration quand DEMO_MODE est actif', function (): void {
    config()->set('lms.demo.enabled', true);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Environnement de démonstration');
});

it('masque le bandeau hors instance de démonstration', function (): void {
    config()->set('lms.demo.enabled', false);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Environnement de démonstration');
});

it('refuse la réinitialisation hors instance de démonstration', function (): void {
    config()->set('lms.demo.enabled', false);

    $this->artisan('app:demo-reset')
        ->expectsOutputToContain('Refusé')
        ->assertFailed();
});
