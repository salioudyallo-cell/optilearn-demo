<?php

declare(strict_types=1);

use App\Models\Certificate;
use App\Models\User;

it('affiche les boutons de partage LinkedIn et WhatsApp sur mes certificats', function (): void {
    $user = User::factory()->create();
    $certificate = Certificate::factory()->for($user)->create();

    $response = $this->actingAs($user)->get(route('certificates.index'));

    $verifyUrl = route('certificate.verify', $certificate->serial);

    $response->assertOk()
        ->assertSee('linkedin.com/profile/add', escape: false)
        ->assertSee('wa.me', escape: false)
        // Le lien de vérification public est celui partagé.
        ->assertSee(urlencode($verifyUrl), escape: false);
});
