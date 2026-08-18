<?php

declare(strict_types=1);

use App\Filament\Resources\Groups\Pages\EditGroup;
use App\Filament\Resources\Groups\RelationManagers\MembersRelationManager;
use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use App\Services\CourseAssigner;

/**
 * Affectation de formations (mode Entreprise) : affecter = créer les inscriptions, de
 * façon idempotente, sans toucher au cœur d'accès.
 */
beforeEach(function (): void {
    $this->assigner = app(CourseAssigner::class);
    $this->group = Group::factory()->create();
    $this->course = Course::factory()->published()->create();
});

it('inscrit les membres d’un groupe quand une formation lui est affectée', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $this->group->members()->attach([$alice->getKey(), $bob->getKey()]);

    $created = $this->assigner->assignCourseToGroup($this->course, $this->group);

    expect($created)->toBe(2)
        ->and($alice->fresh()->hasAccessToCourse($this->course))->toBeTrue()
        ->and($bob->fresh()->hasAccessToCourse($this->course))->toBeTrue();
});

it('est idempotent : réaffecter ne crée aucun doublon', function (): void {
    $alice = User::factory()->create();
    $this->group->members()->attach($alice);

    $this->assigner->assignCourseToGroup($this->course, $this->group);
    $secondRun = $this->assigner->assignCourseToGroup($this->course, $this->group);

    expect($secondRun)->toBe(0)
        ->and($alice->enrollments()->where('course_id', $this->course->getKey())->count())->toBe(1);
});

it('inscrit un nouvel arrivant aux formations déjà affectées à ses groupes', function (): void {
    $this->group->courses()->attach($this->course, ['assigned_at' => now()]);

    $newcomer = User::factory()->create();
    $this->group->members()->attach($newcomer);
    $created = $this->assigner->reconcileUser($newcomer);

    expect($created)->toBe(1)
        ->and($newcomer->fresh()->hasAccessToCourse($this->course))->toBeTrue();
});

it('réconcilie un groupe entier (tous les membres × toutes les formations)', function (): void {
    $members = User::factory()->count(3)->create();
    $this->group->members()->attach($members->pluck('id')->all());
    $secondCourse = Course::factory()->published()->create();
    $this->group->courses()->attach([$this->course->getKey(), $secondCourse->getKey()], ['assigned_at' => now()]);

    $created = $this->assigner->reconcileGroup($this->group);

    // 3 membres × 2 formations = 6 inscriptions.
    expect($created)->toBe(6);
    $members->each(fn (User $m) => expect($m->fresh()->hasAccessToCourse($secondCourse))->toBeTrue());
});

it('n’accorde pas l’accès à un apprenant hors du groupe', function (): void {
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $this->group->members()->attach($member);

    $this->assigner->assignCourseToGroup($this->course, $this->group);

    expect($outsider->fresh()->hasAccessToCourse($this->course))->toBeFalse();
});

it('inscrit un membre via le relation manager Filament (attache réelle)', function (): void {
    $admin = User::factory()->admin()->create();
    $group = Group::factory()->create();
    $course = Course::factory()->published()->create();
    $group->courses()->attach($course, ['assigned_at' => now()]);
    $learner = User::factory()->create();

    Livewire\Livewire::actingAs($admin)
        ->test(MembersRelationManager::class, [
            'ownerRecord' => $group,
            'pageClass' => EditGroup::class,
        ])
        ->callTableAction('attach', data: ['recordId' => $learner->getKey()]);

    expect($learner->fresh()->hasAccessToCourse($course))->toBeTrue();
})->skip(fn () => ! class_exists(Livewire\Livewire::class), 'Livewire requis');
