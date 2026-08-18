<?php

declare(strict_types=1);

use App\Models\Lesson;
use App\Services\BunnySigner;

beforeEach(function () {
    config()->set('lms.bunny.library_id', '12345');
    config()->set('lms.bunny.cdn_hostname', 'vz-test.b-cdn.net');
    config()->set('lms.bunny.token_security_key', 'secret-key');
    config()->set('lms.bunny.signed_url_ttl', 14400);
});

it('se déclare configuré quand les clés Bunny sont présentes', function () {
    expect(app(BunnySigner::class)->isConfigured())->toBeTrue();

    config()->set('lms.bunny.token_security_key', null);
    expect(app(BunnySigner::class)->isConfigured())->toBeFalse();
});

it('produit une signature SHA-256 déterministe', function () {
    $signer = app(BunnySigner::class);

    $expires = 1_800_000_000;
    $path = '/embed/12345/abc-def';

    $expected = hash('sha256', 'secret-key'.$path.$expires);

    expect($signer->signature($path, $expires))->toBe($expected)
        ->and($signer->signature($path, $expires))->toHaveLength(64);
});

it('construit une URL de lecteur signée valable 4 heures', function () {
    $lesson = new Lesson(['bunny_video_id' => 'abc-def']);

    $url = app(BunnySigner::class)->playerUrl($lesson);

    expect($url)->toStartWith('https://vz-test.b-cdn.net/embed/12345/abc-def?token=')
        ->and($url)->toContain('expires=');
});

it('refuse de signer une leçon sans vidéo', function () {
    $lesson = new Lesson(['bunny_video_id' => null]);

    app(BunnySigner::class)->playerUrl($lesson);
})->throws(RuntimeException::class);
