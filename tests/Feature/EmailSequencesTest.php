<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Mail\InactivityReminderMail;
use App\Mail\WelcomeMail;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use App\Services\ProgressTracker;
use Illuminate\Support\Facades\Mail;

it('envoie un e-mail de bienvenue à l’inscription', function (): void {
    Mail::fake();

    $this->post(route('register'), [
        'name' => 'Awa Diop',
        'email' => 'awa@example.com',
        'phone' => '221771234567',
        'company' => 'Sonatel',
        'country' => 'SN',
        'password' => 'motdepasse123',
        'password_confirmation' => 'motdepasse123',
    ])->assertRedirect(route('dashboard', absolute: false));

    Mail::assertQueued(WelcomeMail::class, fn (WelcomeMail $m) => $m->hasTo('awa@example.com'));
});

/**
 * Prépare un apprenant ayant commencé une formation (1 leçon sur 2) puis inactif depuis
 * un certain nombre de jours.
 */
function inactiveLearner(int $daysInactive): User
{
    $user = User::factory()->create();
    $course = Course::factory()->published()->create(['title' => 'SEO B2B']);
    $module = Module::factory()->for($course)->create();
    $l1 = $module->lessons()->create(['position' => 1, 'title' => 'L1', 'type' => 'text', 'content' => 'a']);
    $module->lessons()->create(['position' => 2, 'title' => 'L2', 'type' => 'text', 'content' => 'b']);
    Enrollment::factory()->create([
        'user_id' => $user->getKey(),
        'course_id' => $course->getKey(),
        'status' => EnrollmentStatus::Active,
    ]);

    app(ProgressTracker::class)->markCompleted($user, $l1);
    // Recule la dernière activité sans toucher aux autres colonnes.
    LessonProgress::query()->where('user_id', $user->getKey())
        ->update(['updated_at' => now()->subDays($daysInactive)]);

    return $user;
}

it('relance un apprenant inactif depuis 10 jours', function (): void {
    Mail::fake();
    $user = inactiveLearner(10);

    $this->artisan('app:remind-inactive')->assertSuccessful();

    Mail::assertQueued(InactivityReminderMail::class, fn (InactivityReminderMail $m) => $m->hasTo($user->email));
    expect($user->fresh()->inactivity_reminded_at)->not->toBeNull();
});

it('ne relance pas un apprenant actif récemment', function (): void {
    Mail::fake();
    inactiveLearner(2);

    $this->artisan('app:remind-inactive')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('ne relance pas deux fois dans la même quinzaine', function (): void {
    Mail::fake();
    $user = inactiveLearner(10);
    $user->forceFill(['inactivity_reminded_at' => now()->subDays(3)])->save();

    $this->artisan('app:remind-inactive')->assertSuccessful();

    Mail::assertNothingQueued();
});
