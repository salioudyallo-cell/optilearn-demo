<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Suspension de compte : alternative réversible à la suppression.
 */
it('empêche un compte suspendu de se connecter', function (): void {
    $user = User::factory()->create([
        'password' => 'password',
        'suspended_at' => now(),
    ]);

    $this->from(route('login'))
        ->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('laisse un compte réactivé se connecter à nouveau', function (): void {
    $user = User::factory()->create(['password' => 'password', 'suspended_at' => null]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
});

it('refuse l’accès au back-office à un admin suspendu', function (): void {
    $admin = User::factory()->admin()->create(['suspended_at' => now()]);

    // canAccessPanel renvoyant false, Filament refuse l'accès (403).
    $this->actingAs($admin)->get('/admin')->assertForbidden();
});

it('laisse un admin actif accéder au back-office', function (): void {
    $admin = User::factory()->admin()->create(['suspended_at' => null]);

    $this->actingAs($admin)->get('/admin')->assertOk();
});
