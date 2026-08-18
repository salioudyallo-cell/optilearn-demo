<?php

declare(strict_types=1);

use App\Livewire\LessonPlayer;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use Livewire\Livewire;

/**
 * @return array{course: Course, lesson: Lesson}
 */
function courseWithLesson(): array
{
    $course = Course::factory()->published()->create();
    $module = Module::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson = Lesson::factory()->create(['module_id' => $module->id, 'position' => 1, 'is_preview' => false, 'type' => 'video']);

    return ['course' => $course, 'lesson' => $lesson];
}

it('donne accès au lecteur à un apprenant inscrit', function () {
    ['course' => $course, 'lesson' => $lesson] = courseWithLesson();
    $learner = User::factory()->learner()->create();
    Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

    $this->actingAs($learner)
        ->get(route('learn.lesson', $lesson))
        ->assertOk()
        ->assertSee($lesson->title);
});

it('refuse le lecteur à un apprenant non inscrit', function () {
    ['lesson' => $lesson] = courseWithLesson();
    $learner = User::factory()->learner()->create();

    $this->actingAs($learner)
        ->get(route('learn.lesson', $lesson))
        ->assertForbidden();
});

it('sauvegarde la position vidéo et la restitue à la reprise', function () {
    ['course' => $course, 'lesson' => $lesson] = courseWithLesson();
    $learner = User::factory()->learner()->create();
    Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

    $this->actingAs($learner);

    Livewire::test(LessonPlayer::class, ['lesson' => $lesson])
        ->call('savePosition', 128)
        ->assertSet('position', 128);

    expect(LessonProgress::where('user_id', $learner->id)->where('lesson_id', $lesson->id)->first()->last_position_seconds)
        ->toBe(128);

    // Un nouveau montage reprend a la position enregistree.
    Livewire::test(LessonPlayer::class, ['lesson' => $lesson])
        ->assertSet('position', 128);
});

it('marque une leçon terminée', function () {
    ['course' => $course, 'lesson' => $lesson] = courseWithLesson();
    $learner = User::factory()->learner()->create();
    Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

    $this->actingAs($learner);

    Livewire::test(LessonPlayer::class, ['lesson' => $lesson])
        ->call('markCompleted')
        ->assertSet('completed', true);

    expect(LessonProgress::where('user_id', $learner->id)->where('lesson_id', $lesson->id)->first()->completed_at)
        ->not->toBeNull();
});

it('empêche un non-inscrit de sauvegarder une position via le composant', function () {
    ['lesson' => $lesson] = courseWithLesson();
    $learner = User::factory()->learner()->create();

    $this->actingAs($learner);

    // Le montage lui-meme doit etre refuse (autorisation dans mount()).
    Livewire::test(LessonPlayer::class, ['lesson' => $lesson])
        ->assertStatus(403);
});

it('protège le téléchargement du PDF d’une leçon', function () {
    $course = Course::factory()->published()->create();
    $module = Module::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $pdfLesson = Lesson::factory()->pdf()->create(['module_id' => $module->id, 'position' => 1, 'is_preview' => false]);

    $outsider = User::factory()->learner()->create();

    $this->actingAs($outsider)
        ->get(route('learn.pdf', $pdfLesson))
        ->assertForbidden();
});
