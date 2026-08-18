<?php

declare(strict_types=1);

use App\Enums\RedemptionOutcome;
use App\Models\AccessCode;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\AccessCodeRedeemer;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('access-code:');
});

/**
 * Atomicite du dernier usage (CLAUDE.md §8.4, test 4). Deux apprenants activent le meme
 * code alors qu'il reste un seul usage : exactement une inscription doit en resulter.
 * Le lockForUpdate dans la transaction serialise l'acces a la ligne du code.
 */
it('produit exactement une inscription quand deux apprenants epuisent le dernier usage', function () {
    $course = Course::factory()->published()->create();
    $code = AccessCode::factory()->create([
        'course_id' => $course->id,
        'max_uses' => 1,
        'used_count' => 0,
    ]);

    $first = User::factory()->learner()->create();
    $second = User::factory()->learner()->create();

    $redeemer = app(AccessCodeRedeemer::class);

    $firstResult = $redeemer->redeem($first, $code->code);
    $secondResult = $redeemer->redeem($second, $code->code);

    expect($firstResult->outcome)->toBe(RedemptionOutcome::Redeemed)
        ->and($secondResult->outcome)->toBe(RedemptionOutcome::Exhausted);

    expect(Enrollment::where('course_id', $course->id)->count())->toBe(1);

    $code->refresh();
    expect($code->used_count)->toBe(1);
});

it('accepte le format non ambigu et normalise la saisie', function () {
    $course = Course::factory()->published()->create();
    $code = AccessCode::factory()->create([
        'course_id' => $course->id,
        'code' => 'OPT7K4M9XQ',
        'max_uses' => 3,
    ]);

    $learner = User::factory()->learner()->create();

    // Minuscules, espaces et tiret de saisie : tout doit etre normalise.
    $result = app(AccessCodeRedeemer::class)->redeem($learner, ' opt7k4-m9xq ');

    expect($result->outcome)->toBe(RedemptionOutcome::Redeemed);
});

it('renvoie already_enrolled sans consommer d\'usage quand l\'apprenant a deja acces', function () {
    $course = Course::factory()->published()->create();
    $code = AccessCode::factory()->create([
        'course_id' => $course->id,
        'max_uses' => 5,
        'used_count' => 0,
    ]);

    $learner = User::factory()->learner()->create();
    $redeemer = app(AccessCodeRedeemer::class);

    $redeemer->redeem($learner, $code->code);
    $second = $redeemer->redeem($learner, $code->code);

    expect($second->outcome)->toBe(RedemptionOutcome::AlreadyEnrolled);

    $code->refresh();
    expect($code->used_count)->toBe(1)
        ->and(Enrollment::where('user_id', $learner->id)->where('course_id', $course->id)->count())->toBe(1);
});

it('bloque apres le nombre maximal de tentatives par apprenant', function () {
    config()->set('lms.access_codes.max_attempts', 3);
    $learner = User::factory()->learner()->create();
    $redeemer = app(AccessCodeRedeemer::class);

    // 3 tentatives sur un code inexistant : la 4e est etranglee.
    $redeemer->redeem($learner, 'WRONGCODE1');
    $redeemer->redeem($learner, 'WRONGCODE2');
    $redeemer->redeem($learner, 'WRONGCODE3');
    $throttled = $redeemer->redeem($learner, 'WRONGCODE4');

    expect($throttled->outcome)->toBe(RedemptionOutcome::Throttled)
        ->and($throttled->retryAfterMinutes)->toBeGreaterThan(0);
});
