<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Enums\PlatformMode;
use App\Facades\Platform;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;

beforeEach(fn () => Platform::setMode(PlatformMode::Commercial));

function enrolledLearner(Course $course): User
{
    $user = User::factory()->create();
    Enrollment::factory()->create([
        'user_id' => $user->getKey(),
        'course_id' => $course->getKey(),
        'status' => EnrollmentStatus::Active,
    ]);

    return $user;
}

it('permet à un apprenant inscrit de noter une formation', function (): void {
    $course = Course::factory()->published()->create();
    $user = enrolledLearner($course);

    $this->actingAs($user)
        ->post(route('reviews.store', $course), ['rating' => 5, 'comment' => 'Excellent'])
        ->assertRedirect();

    expect(Review::where('user_id', $user->getKey())->where('course_id', $course->getKey())->first())
        ->not->toBeNull()
        ->rating->toBe(5);
});

it('met à jour l’avis existant au lieu d’en créer un second', function (): void {
    $course = Course::factory()->published()->create();
    $user = enrolledLearner($course);

    $this->actingAs($user)->post(route('reviews.store', $course), ['rating' => 3]);
    $this->actingAs($user)->post(route('reviews.store', $course), ['rating' => 5]);

    expect(Review::where('user_id', $user->getKey())->count())->toBe(1)
        ->and(Review::where('user_id', $user->getKey())->first()->rating)->toBe(5);
});

it('refuse la note à un apprenant non inscrit', function (): void {
    $course = Course::factory()->published()->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->post(route('reviews.store', $course), ['rating' => 5])
        ->assertForbidden();
});

it('ferme la notation en mode entreprise (capacité absente)', function (): void {
    Platform::setMode(PlatformMode::Enterprise);
    $course = Course::factory()->published()->create();
    $user = enrolledLearner($course);

    $this->actingAs($user)
        ->post(route('reviews.store', $course), ['rating' => 5])
        ->assertNotFound();
});

it('affiche la note moyenne sur la fiche formation', function (): void {
    $course = Course::factory()->published()->create();
    Review::create(['user_id' => User::factory()->create()->id, 'course_id' => $course->id, 'rating' => 4]);
    Review::create(['user_id' => User::factory()->create()->id, 'course_id' => $course->id, 'rating' => 5]);

    $this->get(route('catalog.show', $course))
        ->assertOk()
        ->assertSee('Avis des apprenants')
        ->assertSee('4.5');
});
