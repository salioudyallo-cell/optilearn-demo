<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonType;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Services\Video\VideoManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Video auto-hebergee : le fichier ne doit jamais etre atteignable sans autorisation,
 * meme muni d'un lien signe valide.
 */
beforeEach(function (): void {
    config()->set('lms.video.driver', 'local');
    config()->set('lms.video.disk', 'videos');
    config()->set('lms.video.delivery', 'php');

    Storage::fake('videos');
    Storage::disk('videos')->put('cours/lecon-01.mp4', 'contenu-video-factice');

    // Publie : la policy n'ouvre l'apercu public que sur un cours publie.
    $this->course = Course::factory()->published()->create();
    $module = Module::factory()->for($this->course)->create();

    $this->lesson = Lesson::factory()->for($module)->create([
        'type' => LessonType::Video,
        'video_path' => 'cours/lecon-01.mp4',
        'is_preview' => false,
    ]);
});

it('transmet un lien signe de type fichier a un apprenant inscrit', function (): void {
    $learner = User::factory()->create();
    Enrollment::factory()->create([
        'user_id' => $learner->getKey(),
        'course_id' => $this->course->getKey(),
        'status' => EnrollmentStatus::Active,
    ]);

    $response = $this->actingAs($learner)
        ->getJson(route('learn.video-url', $this->lesson));

    $response->assertOk()->assertJsonPath('kind', 'file');

    // Le chemin du fichier ne doit jamais transiter vers le client.
    expect($response->json('url'))
        ->toContain('signature=')
        ->not->toContain('lecon-01.mp4');
});

it('sert le fichier a un apprenant inscrit muni du lien signe', function (): void {
    $learner = User::factory()->create();
    Enrollment::factory()->create([
        'user_id' => $learner->getKey(),
        'course_id' => $this->course->getKey(),
        'status' => EnrollmentStatus::Active,
    ]);

    $url = app(VideoManager::class)->sourceFor($this->lesson)->url;

    $this->actingAs($learner)->get($url)->assertOk();
});

it('refuse le fichier a un utilisateur non inscrit meme avec un lien signe valide', function (): void {
    $intruder = User::factory()->create();

    // Lien signe genere legitimement, puis partage : l'autorisation doit primer.
    $url = app(VideoManager::class)->sourceFor($this->lesson)->url;

    $this->actingAs($intruder)->get($url)->assertForbidden();
});

it('refuse un lien non signe', function (): void {
    $learner = User::factory()->create();
    Enrollment::factory()->create([
        'user_id' => $learner->getKey(),
        'course_id' => $this->course->getKey(),
        'status' => EnrollmentStatus::Active,
    ]);

    $this->actingAs($learner)
        ->get(route('learn.video-stream', $this->lesson))
        ->assertForbidden();
});

it('refuse un lien signe expire', function (): void {
    $learner = User::factory()->create();
    Enrollment::factory()->create([
        'user_id' => $learner->getKey(),
        'course_id' => $this->course->getKey(),
        'status' => EnrollmentStatus::Active,
    ]);

    $expired = URL::temporarySignedRoute(
        'learn.video-stream',
        now()->subMinute(),
        ['lesson' => $this->lesson->getKey()],
    );

    $this->actingAs($learner)->get($expired)->assertForbidden();
});

it('delegue au serveur web quand la livraison sendfile est active', function (): void {
    config()->set('lms.video.delivery', 'sendfile');
    config()->set('lms.video.sendfile_header', 'X-LiteSpeed-Location');

    $learner = User::factory()->create();
    Enrollment::factory()->create([
        'user_id' => $learner->getKey(),
        'course_id' => $this->course->getKey(),
        'status' => EnrollmentStatus::Active,
    ]);

    $url = app(VideoManager::class)->sourceFor($this->lesson)->url;

    $this->actingAs($learner)->get($url)
        ->assertOk()
        ->assertHeader('X-LiteSpeed-Location');
});

it('bascule vers Bunny sans toucher au contenu de la lecon', function (): void {
    config()->set('lms.video.driver', 'bunny');
    config()->set('lms.bunny.library_id', '12345');
    config()->set('lms.bunny.cdn_hostname', 'iframe.mediadelivery.net');
    config()->set('lms.bunny.token_security_key', 'cle-de-test');

    $this->lesson->update(['bunny_video_id' => 'abc-def']);

    $learner = User::factory()->create();
    Enrollment::factory()->create([
        'user_id' => $learner->getKey(),
        'course_id' => $this->course->getKey(),
        'status' => EnrollmentStatus::Active,
    ]);

    $response = $this->actingAs($learner)
        ->getJson(route('learn.video-url', $this->lesson));

    $response->assertOk()->assertJsonPath('kind', 'iframe');
    expect($response->json('url'))->toContain('iframe.mediadelivery.net');
});

/**
 * Apercu public : la lecon d'essai doit etre REELLEMENT lisible sans compte (levier de
 * conversion), tandis que le contenu paye reste ferme.
 */
it('laisse un visiteur non connecte lire une lecon d’essai', function (): void {
    $this->lesson->update(['is_preview' => true]);

    $response = $this->getJson(route('learn.video-url', $this->lesson));

    $response->assertOk()->assertJsonPath('kind', 'file');
    expect($response->json('url'))->toContain('signature=');
});

it('sert la video d’essai a un visiteur non connecte', function (): void {
    $this->lesson->update(['is_preview' => true]);

    $url = app(VideoManager::class)->sourceFor($this->lesson)->url;

    $this->get($url)->assertOk();
});

it('affiche le lecteur dans la page d’une lecon d’essai pour un visiteur', function (): void {
    $this->lesson->update(['is_preview' => true]);

    $this->get(route('learn.lesson', $this->lesson))
        ->assertOk()
        ->assertSee('guest: true', escape: false)
        ->assertSee('Aperçu gratuit', escape: false);
});

it('refuse une lecon non-essai a un visiteur non connecte', function (): void {
    $this->lesson->update(['is_preview' => false]);

    $this->getJson(route('learn.video-url', $this->lesson))->assertForbidden();

    $url = URL::temporarySignedRoute(
        'learn.video-stream',
        now()->addHour(),
        ['lesson' => $this->lesson->getKey()],
    );

    $this->get($url)->assertForbidden();
});

it('refuse une lecon d’essai si la formation n’est pas publiee', function (): void {
    $this->lesson->update(['is_preview' => true]);
    $this->course->update(['status' => CourseStatus::Draft]);

    $this->getJson(route('learn.video-url', $this->lesson))->assertForbidden();
});
