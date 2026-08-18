<?php

declare(strict_types=1);

use App\Enums\PlatformMode;
use App\Facades\Platform;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;

/**
 * Ressources d'entreprise (organisations, groupes) : accessibles à l'admin en mode
 * Entreprise, entièrement fermées en mode Commercial.
 */
it('permet à un admin d’accéder aux organisations en mode entreprise', function (): void {
    Platform::setMode(PlatformMode::Enterprise);
    Organization::factory()->create(['name' => 'Ministère de la Santé']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/organizations')
        ->assertOk()
        ->assertSee('Ministère de la Santé');
});

it('permet à un admin d’accéder aux groupes en mode entreprise', function (): void {
    Platform::setMode(PlatformMode::Enterprise);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/groups')->assertOk();
});

it('ferme les organisations en mode commercial', function (): void {
    Platform::setMode(PlatformMode::Commercial);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/organizations')->assertForbidden();
});

it('ferme les groupes en mode commercial', function (): void {
    Platform::setMode(PlatformMode::Commercial);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/groups')->assertForbidden();
});

it('refuse les organisations à un formateur même en mode entreprise', function (): void {
    Platform::setMode(PlatformMode::Enterprise);
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->get('/admin/organizations')->assertForbidden();
});

it('rend la fiche d’un groupe avec ses actions import/export en mode entreprise', function (): void {
    Platform::setMode(PlatformMode::Enterprise);
    $group = Group::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get("/admin/groups/{$group->getKey()}/edit")
        ->assertOk()
        ->assertSee('Importer des apprenants (CSV)')
        ->assertSee('Exporter la progression (CSV)');
});
