<?php

declare(strict_types=1);

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\CertificateGenerator;
use Illuminate\Support\Facades\Storage;

it('génère un certificat avec un serial au bon format et un PDF stocké', function () {
    Storage::fake('private');
    config()->set('lms.certificates.disk', 'private');

    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();

    $certificate = app(CertificateGenerator::class)->issue($learner, $course);

    expect($certificate->serial)->toMatch('/^OPT-\d{4}-[A-F0-9]{6}$/')
        ->and($certificate->pdf_path)->toBe('certificates/'.$certificate->serial.'.pdf');

    Storage::disk('private')->assertExists($certificate->pdf_path);
});

it('permet au titulaire de télécharger son certificat', function () {
    Storage::fake('private');
    config()->set('lms.certificates.disk', 'private');

    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    $certificate = app(CertificateGenerator::class)->issue($learner, $course);

    $this->actingAs($learner)
        ->get(route('certificate.download', $certificate))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('refuse le téléchargement à un autre utilisateur', function () {
    Storage::fake('private');
    config()->set('lms.certificates.disk', 'private');

    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    $certificate = app(CertificateGenerator::class)->issue($learner, $course);

    $outsider = User::factory()->learner()->create();

    $this->actingAs($outsider)
        ->get(route('certificate.download', $certificate))
        ->assertForbidden();
});

it('confirme un certificat authentique sur la page de vérification publique', function () {
    $learner = User::factory()->learner()->create(['name' => 'Awa Diop']);
    $course = Course::factory()->published()->create(['title' => 'Formation SEO']);
    $certificate = Certificate::factory()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'serial' => 'OPT-2026-ABCDEF',
    ]);

    $this->get(route('certificate.verify', $certificate->serial))
        ->assertOk()
        ->assertSee('Certificat authentique')
        ->assertSee('Awa Diop')
        ->assertSee('Formation SEO');
});

it('indique un certificat introuvable pour un serial inconnu', function () {
    $this->get(route('certificate.verify', 'OPT-2026-000000'))
        ->assertOk()
        ->assertSee('Certificat introuvable');
});
