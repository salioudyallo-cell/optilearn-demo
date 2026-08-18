<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;

/**
 * Securite d'acces au contenu (CLAUDE.md §8.4). Ces tests existent AVANT toute
 * interface : une Policy oubliee fait fuiter le contenu payant.
 */
function makeLesson(bool $preview = false): Lesson
{
    $course = Course::factory()->published()->create();
    $module = Module::factory()->create(['course_id' => $course->id, 'position' => 1]);

    return Lesson::factory()->create([
        'module_id' => $module->id,
        'position' => 1,
        'is_preview' => $preview,
    ]);
}

it('refuse un visiteur non authentifie sur une lecon non-preview', function () {
    $lesson = makeLesson(preview: false);

    $this->get(route('learn.lesson', $lesson))->assertForbidden();
});

it('autorise un visiteur non authentifie sur une lecon preview d\'un cours publie', function () {
    $lesson = makeLesson(preview: true);

    $this->get(route('learn.lesson', $lesson))->assertOk();
});

it('refuse un apprenant authentifie mais non inscrit sur une lecon non-preview', function () {
    $lesson = makeLesson(preview: false);
    $learner = User::factory()->learner()->create();

    $this->actingAs($learner)
        ->get(route('learn.lesson', $lesson))
        ->assertForbidden();
});

it('autorise un apprenant inscrit avec un acces actif', function () {
    $lesson = makeLesson(preview: false);
    $course = $lesson->module->course;
    $learner = User::factory()->learner()->create();

    Enrollment::factory()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
    ]);

    $this->actingAs($learner)
        ->get(route('learn.lesson', $lesson))
        ->assertOk();
});

it('refuse un apprenant dont l\'inscription est revoquee', function () {
    $lesson = makeLesson(preview: false);
    $course = $lesson->module->course;
    $learner = User::factory()->learner()->create();

    Enrollment::factory()->revoked()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
    ]);

    $this->actingAs($learner)
        ->get(route('learn.lesson', $lesson))
        ->assertForbidden();
});
