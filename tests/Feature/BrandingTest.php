<?php

declare(strict_types=1);

use App\Support\BrandPalette;

it('affiche la marque par défaut (OptiLeads) sans configuration', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('OptiLeads');
});

it('n’injecte pas de re-theme quand aucune couleur personnalisée n’est définie', function (): void {
    config()->set('brand.colors.custom', false);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee(':root{', escape: false);
});

it('injecte la palette générée quand une couleur principale est définie', function (): void {
    config()->set('brand.colors.custom', true);
    config()->set('brand.colors.primary', '#0f6e5c');
    config()->set('brand.colors.accent', '#e0851e');
    config()->set('brand.colors.highlight', '#f5b841');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(':root{', escape: false)
        ->assertSee('--color-brand-blue-600', escape: false);
});

it('affiche le nom en toutes lettres quand aucun logo image n’est fourni', function (): void {
    config()->set('brand.logo_image', '');
    config()->set('app.name', 'Nimba Académie');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Nimba Académie');
});

it('génère une échelle de couleurs complète et valide', function (): void {
    $vars = BrandPalette::build('#0f6e5c', '#e0851e', '#f5b841');

    // Paliers principaux (50..900) + accent (50..700) + alias.
    expect($vars)->toHaveKey('--color-brand-blue-600');
    expect($vars)->toHaveKey('--color-brand-orange-500');
    expect($vars)->toHaveKey('--color-brand-navy');
    expect($vars)->toHaveKey('--color-brand-cream');

    // Le palier 600 reprend la couleur principale exacte.
    expect($vars['--color-brand-blue-600'])->toBe('#0f6e5c');

    // Toutes les valeurs sont des hex valides.
    foreach ($vars as $value) {
        expect($value)->toMatch('/^#[0-9a-f]{6}$/');
    }
});

it('éclaircit vers le blanc et assombrit vers le noir', function (): void {
    $vars = BrandPalette::build('#808080', '#808080', '#808080');

    // 50 (mélange vers blanc) est plus clair que 900 (mélange vers noir).
    $light = hexdec(ltrim($vars['--color-brand-blue-50'], '#'));
    $dark = hexdec(ltrim($vars['--color-brand-blue-900'], '#'));

    expect($light)->toBeGreaterThan($dark);
});
