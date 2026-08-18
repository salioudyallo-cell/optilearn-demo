<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\User;
use App\Services\ProgressTracker;

it('affiche le bandeau Reprendre pour une formation en cours', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create(['title' => 'SEO avancé']);
    $module = Module::factory()->for($course)->create();
    $l1 = $module->lessons()->create(['position' => 1, 'title' => 'L1', 'type' => 'text', 'content' => 'a']);
    $module->lessons()->create(['position' => 2, 'title' => 'L2', 'type' => 'text', 'content' => 'b']);
    Enrollment::factory()->create([
        'user_id' => $user->getKey(),
        'course_id' => $course->getKey(),
        'status' => EnrollmentStatus::Active,
    ]);

    app(ProgressTracker::class)->markCompleted($user, $l1); // 1/2 -> 50 %

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Reprendre')
        ->assertSee('SEO avancé')
        ->assertSee('50 %');
});

it('n’affiche pas le bandeau si toutes les formations sont terminées', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create();
    $module = Module::factory()->for($course)->create();
    $lesson = $module->lessons()->create(['position' => 1, 'title' => 'L1', 'type' => 'text', 'content' => 'a']);
    Enrollment::factory()->create([
        'user_id' => $user->getKey(),
        'course_id' => $course->getKey(),
        'status' => EnrollmentStatus::Active,
    ]);

    app(ProgressTracker::class)->markCompleted($user, $lesson); // 1/1 -> terminé

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Continuer la leçon');
});
