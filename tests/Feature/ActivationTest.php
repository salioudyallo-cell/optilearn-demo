<?php

declare(strict_types=1);

use App\Livewire\ActivateCode;
use App\Mail\AccessGrantedMail;
use App\Models\AccessCode;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('active une formation avec un code valide et envoie l’email d’accès', function () {
    Mail::fake();

    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    $code = AccessCode::factory()->create(['course_id' => $course->id, 'max_uses' => 5]);

    $this->actingAs($learner);

    Livewire::test(ActivateCode::class)
        ->set('code', $code->code)
        ->call('redeem')
        ->assertSet('success', true);

    expect(Enrollment::where('user_id', $learner->id)->where('course_id', $course->id)->exists())->toBeTrue();
    expect($code->refresh()->used_count)->toBe(1);

    Mail::assertSent(AccessGrantedMail::class);
});

it('indique que l’apprenant a déjà accès sans consommer d’usage ni renvoyer d’email', function () {
    Mail::fake();

    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    $code = AccessCode::factory()->create(['course_id' => $course->id, 'max_uses' => 5]);

    // Premiere activation.
    $this->actingAs($learner);
    Livewire::test(ActivateCode::class)->set('code', $code->code)->call('redeem')->assertSet('success', true);

    // Seconde activation du meme code par le meme utilisateur.
    Livewire::test(ActivateCode::class)
        ->set('code', $code->code)
        ->call('redeem')
        ->assertSet('success', true);

    expect($code->refresh()->used_count)->toBe(1);
    Mail::assertSent(AccessGrantedMail::class, 1);
});

it('rejette un code inexistant', function () {
    $learner = User::factory()->learner()->create();
    $this->actingAs($learner);

    Livewire::test(ActivateCode::class)
        ->set('code', 'ZZZZZZZZZZ')
        ->call('redeem')
        ->assertSet('success', false);
});

it('exige une authentification pour accéder à la page d’activation', function () {
    $this->get(route('activate'))->assertRedirect(route('login'));
});
