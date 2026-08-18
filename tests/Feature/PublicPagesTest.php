<?php

declare(strict_types=1);

use App\Enums\PlatformMode;
use App\Facades\Platform;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;

it('affiche la page d\'accueil', function () {
    $this->get('/')->assertOk()->assertSee('OptiLeads');
});

it('affiche le catalogue avec les cours publies uniquement', function () {
    $published = Course::factory()->published()->create(['title' => 'Formation SEO publiée']);
    $draft = Course::factory()->create(['title' => 'Brouillon confidentiel']);

    $this->get(route('catalog.index'))
        ->assertOk()
        ->assertSee('Formation SEO publiée')
        ->assertDontSee('Brouillon confidentiel');
});

it('affiche la fiche d\'un cours publie avec son programme et son prix', function () {
    // Le prix ne s'affiche qu'en mode commercial (capacite « tarification »).
    Platform::setMode(PlatformMode::Commercial);

    $course = Course::factory()->published()->create([
        'title' => 'Formation Google Ads',
        'price_fcfa' => 120000,
    ]);
    $module = Module::factory()->create(['course_id' => $course->id, 'position' => 1, 'title' => 'Module de découverte']);
    Lesson::factory()->preview()->create(['module_id' => $module->id, 'position' => 1, 'title' => 'Leçon offerte']);

    $this->get(route('catalog.show', $course))
        ->assertOk()
        ->assertSee('Formation Google Ads')
        ->assertSee('Module de découverte')
        ->assertSee('120')
        ->assertSee('FCFA');
});

it('renvoie 404 sur la fiche d\'un cours en brouillon pour un visiteur', function () {
    $course = Course::factory()->create(); // draft par defaut

    // La route utilise le slug ; un brouillon n'est pas visible et la Policy refuse.
    $this->get(route('catalog.show', $course))->assertForbidden();
});

it('affiche les pages legales', function () {
    $this->get(route('legal.terms'))->assertOk();
    $this->get(route('legal.notice'))->assertOk();
    $this->get(route('legal.privacy'))->assertOk();
});

it('permet a un visiteur de creer un compte avec ses informations de profil', function () {
    $response = $this->post('/register', [
        'name' => 'Fatou Ndiaye',
        'email' => 'fatou@example.com',
        'phone' => '+221 77 123 45 67',
        'country' => 'SN',
        'company' => 'Sonatel',
        'password' => 'MotDePasse2026!',
        'password_confirmation' => 'MotDePasse2026!',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'fatou@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->phone)->toBe('+221 77 123 45 67')
        ->and($user->country)->toBe('SN')
        ->and($user->role->value)->toBe('learner');
});

it('refuse une inscription sans pays valide', function () {
    $this->post('/register', [
        'name' => 'Test',
        'email' => 'test@example.com',
        'phone' => '+221 77 000 00 00',
        'country' => 'FR',
        'password' => 'MotDePasse2026!',
        'password_confirmation' => 'MotDePasse2026!',
    ])->assertSessionHasErrors('country');

    $this->assertGuest();
});

it('bloque l\'acces direct a une lecon verrouillee meme en forcant l\'URL', function () {
    $course = Course::factory()->published()->create();
    $module = Module::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson = Lesson::factory()->create(['module_id' => $module->id, 'position' => 1, 'is_preview' => false]);

    $this->get(route('learn.lesson', $lesson))->assertForbidden();
});
