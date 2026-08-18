<?php

declare(strict_types=1);

use App\Enums\PlatformMode;
use App\Facades\Platform;
use App\Models\Course;
use App\Models\User;
use App\Services\Platform\PlatformManager;
use Illuminate\Support\Facades\Route;

/**
 * Le mode de plateforme pilote les capacités. Le code ne teste jamais le mode
 * directement : il interroge une capacité. Ces tests verrouillent le préréglage de
 * chaque mode et l'application côté serveur (route + vue).
 */
it('applique par défaut le mode entreprise', function (): void {
    expect(Platform::mode())->toBe(PlatformMode::Enterprise)
        ->and(Platform::allows('organizations'))->toBeTrue()
        ->and(Platform::allows('assignments'))->toBeTrue()
        ->and(Platform::allows('pricing'))->toBeFalse()
        ->and(Platform::allows('online_payment'))->toBeFalse();
});

it('bascule vers le mode commercial et ouvre les capacités marchandes', function (): void {
    Platform::setMode(PlatformMode::Commercial);

    expect(Platform::isCommercial())->toBeTrue()
        ->and(Platform::allows('pricing'))->toBeTrue()
        ->and(Platform::allows('cart'))->toBeTrue()
        ->and(Platform::allows('online_payment'))->toBeTrue()
        ->and(Platform::allows('organizations'))->toBeFalse();
});

it('persiste le mode entre deux résolutions', function (): void {
    Platform::setMode(PlatformMode::Commercial);

    // Nouvelle instance : le mode doit être relu depuis le stockage.
    app()->forgetInstance(PlatformManager::class);

    expect(Platform::mode())->toBe(PlatformMode::Commercial);
});

it('affiche le prix sur la fiche formation en mode commercial', function (): void {
    Platform::setMode(PlatformMode::Commercial);
    $course = Course::factory()->published()->create(['price_fcfa' => 150000]);

    $this->get(route('catalog.show', $course))
        ->assertOk()
        ->assertSee('150', escape: false)
        ->assertSee('FCFA', escape: false);
});

it('masque le prix sur la fiche formation en mode entreprise', function (): void {
    Platform::setMode(PlatformMode::Enterprise);
    $course = Course::factory()->published()->create(['price_fcfa' => 150000]);

    $this->get(route('catalog.show', $course))
        ->assertOk()
        ->assertDontSee('FCFA')
        ->assertSee('Formation interne');
});

it('bloque une route protégée par une capacité absente du mode', function (): void {
    Route::middleware(['web', 'feature:online_payment'])
        ->get('/_test/checkout', fn () => 'ok');

    // Mode entreprise : paiement absent -> 404.
    Platform::setMode(PlatformMode::Enterprise);
    $this->get('/_test/checkout')->assertNotFound();

    // Mode commercial : paiement présent -> accessible.
    Platform::setMode(PlatformMode::Commercial);
    $this->get('/_test/checkout')->assertOk()->assertSee('ok');
});

it('rend la page de configuration Filament pour un administrateur', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/platform-settings')
        ->assertOk()
        ->assertSee('Configuration de la plateforme')
        ->assertSee('Mode actuel');
});

it('refuse la page de configuration à un apprenant', function (): void {
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->get('/admin/platform-settings')
        ->assertForbidden();
});
