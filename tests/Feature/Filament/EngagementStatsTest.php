<?php

declare(strict_types=1);

use App\Filament\Widgets\EngagementStats;
use App\Models\User;
use Livewire\Livewire;

it('affiche les KPI d’engagement pour un administrateur', function (): void {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(EngagementStats::class)
        ->assertOk()
        ->assertSee('Apprenants actifs (7 j)')
        ->assertSee('Formations terminées');
});

it('masque les KPI d’engagement aux non-admins', function (): void {
    expect(EngagementStats::canView())->toBeFalse();

    $this->actingAs(User::factory()->admin()->create());
    expect(EngagementStats::canView())->toBeTrue();
});
