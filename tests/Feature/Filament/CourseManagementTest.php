<?php

declare(strict_types=1);

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Models\Course;
use App\Models\User;
use Livewire\Livewire;

it('liste uniquement les formations du formateur connecté', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();

    $own = Course::factory()->create(['instructor_id' => $instructor->id, 'title' => 'Ma formation']);
    $foreign = Course::factory()->create(['instructor_id' => $other->id, 'title' => 'Formation d’un autre']);

    $this->actingAs($instructor);

    Livewire::test(ListCourses::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
})->group('filament');

it('permet à l’administrateur de voir toutes les formations', function () {
    $admin = User::factory()->admin()->create();
    $a = Course::factory()->create();
    $b = Course::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListCourses::class)
        ->assertCanSeeTableRecords([$a, $b]);
})->group('filament');

it('crée une formation en attribuant le formateur connecté', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor);

    Livewire::test(CreateCourse::class)
        ->fillForm([
            'title' => 'Publicité Meta pour PME',
            'slug' => 'publicite-meta-pme',
            'level' => 'debutant',
            'price_fcfa' => 90000,
            'duration_minutes' => 180,
            'status' => 'draft',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $course = Course::where('slug', 'publicite-meta-pme')->first();

    expect($course)->not->toBeNull()
        ->and($course->instructor_id)->toBe($instructor->id);
})->group('filament');

it('empêche un formateur d’atteindre la formation d’un autre', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();
    $foreign = Course::factory()->create(['instructor_id' => $other->id]);

    // La requete de la ressource exclut deja les formations d'autrui : introuvable => 404.
    $this->actingAs($instructor)
        ->get(CourseResource::getUrl('edit', ['record' => $foreign]))
        ->assertNotFound();
})->group('filament');
