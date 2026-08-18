<?php

declare(strict_types=1);

use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;

/**
 * Non-régression : les ressources sans page « index » (Module, Quiz) doivent tout de
 * même rendre leur page d'édition sans lever « does not have an [index] page ».
 */
it('affiche la page d’édition d’un module sans erreur', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create(['instructor_id' => $admin->id]);
    $module = Module::factory()->create(['course_id' => $course->id, 'position' => 1]);

    $this->actingAs($admin)
        ->get(ModuleResource::getUrl('edit', ['record' => $module]))
        ->assertOk();
})->group('filament');

it('affiche la page d’édition d’un quiz sans erreur', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create(['instructor_id' => $admin->id]);
    $module = Module::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson = Lesson::factory()->quiz()->create(['module_id' => $module->id, 'position' => 1]);
    $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id]);

    $this->actingAs($admin)
        ->get(QuizResource::getUrl('edit', ['record' => $quiz]))
        ->assertOk();
})->group('filament');
