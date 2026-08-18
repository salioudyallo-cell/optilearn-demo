<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Seuls instructor et admin accedent au panel Filament (canAccessPanel()).
 */
it('refuse l\'acces au panel /admin a un apprenant', function () {
    $learner = User::factory()->learner()->create();

    $this->actingAs($learner)
        ->get('/admin')
        ->assertForbidden();
});

it('autorise l\'acces au panel /admin a un formateur', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->get('/admin')
        ->assertOk();
});

it('autorise l\'acces au panel /admin a un administrateur', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});
