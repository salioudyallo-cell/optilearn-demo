<?php

declare(strict_types=1);

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Filament\Resources\Enrollments\Pages\CreateEnrollment;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Livewire\Livewire;

it('inscrit manuellement un apprenant à une formation', function () {
    $admin = User::factory()->admin()->create();
    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();

    $this->actingAs($admin);

    Livewire::test(CreateEnrollment::class)
        ->fillForm([
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $enrollment = Enrollment::where('user_id', $learner->id)->where('course_id', $course->id)->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->source)->toBe(EnrollmentSource::Manual)
        ->and($enrollment->status)->toBe(EnrollmentStatus::Active);
})->group('filament');

it('révoque une inscription depuis l’action de la table', function () {
    $admin = User::factory()->admin()->create();
    $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::Active]);

    $this->actingAs($admin);

    Livewire::test(ListEnrollments::class)
        ->callTableAction('revoke', $enrollment)
        ->assertHasNoErrors();

    expect($enrollment->refresh()->status)->toBe(EnrollmentStatus::Revoked);
})->group('filament');
