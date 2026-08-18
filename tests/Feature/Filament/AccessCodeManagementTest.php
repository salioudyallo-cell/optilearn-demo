<?php

declare(strict_types=1);

use App\Filament\Resources\AccessCodes\Pages\CreateAccessCode;
use App\Filament\Resources\AccessCodes\Pages\ListAccessCodes;
use App\Models\AccessCode;
use App\Models\Course;
use App\Models\User;
use Livewire\Livewire;

it('génère un code à 5 usages attribué au créateur', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->published()->create();

    $this->actingAs($admin);

    Livewire::test(CreateAccessCode::class)
        ->fillForm([
            'course_id' => $course->id,
            'label' => 'Cohorte SEO — Société X',
            'max_uses' => 5,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $code = AccessCode::where('label', 'Cohorte SEO — Société X')->first();

    expect($code)->not->toBeNull()
        ->and($code->max_uses)->toBe(5)
        ->and($code->used_count)->toBe(0)
        ->and($code->created_by)->toBe($admin->id)
        ->and(strlen($code->code))->toBe(10)
        ->and($code->course_id)->toBe($course->id);
})->group('filament');

it('désactive un code depuis l’action de la table', function () {
    $admin = User::factory()->admin()->create();
    $code = AccessCode::factory()->create(['is_active' => true]);

    $this->actingAs($admin);

    Livewire::test(ListAccessCodes::class)
        ->callTableAction('toggleActive', $code)
        ->assertHasNoErrors();

    expect($code->refresh()->is_active)->toBeFalse();
})->group('filament');
