<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Enums\RedemptionOutcome;
use App\Facades\Track;
use App\Models\AccessCode;
use App\Models\AccessCodeRedemption;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Tracking\EventName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Le code d'acces remplace le paiement : c'est la seule porte d'entree vers le contenu
 * payant. Toute la regle metier vit ici, jamais dans un controleur ni un composant
 * Livewire (regle de portabilite n°1).
 */
final class AccessCodeRedeemer
{
    public function redeem(User $user, string $rawCode): RedemptionResult
    {
        $code = $this->normalize($rawCode);
        $throttleKey = $this->throttleKey($user);

        // Sans limitation, un code de 10 caracteres se force par essais successifs.
        if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts())) {
            return new RedemptionResult(
                outcome: RedemptionOutcome::Throttled,
                retryAfterMinutes: (int) ceil(RateLimiter::availableIn($throttleKey) / 60),
            );
        }

        RateLimiter::hit($throttleKey, $this->decayMinutes() * 60);

        $result = DB::transaction(fn (): RedemptionResult => $this->attempt($user, $code));

        // Une activation reussie ne doit pas penaliser l'apprenant qui s'est trompe avant.
        if ($result->isSuccessful()) {
            RateLimiter::clear($throttleKey);
            Track::event(EventName::CodeActivated, ['course_id' => $result->course?->getKey()], $user);
        }

        return $result;
    }

    /**
     * Execute dans une transaction. Le verrou sur la ligne du code serialise les
     * activations concurrentes : deux requetes simultanees sur le dernier usage
     * ne peuvent pas produire deux inscriptions.
     */
    private function attempt(User $user, string $code): RedemptionResult
    {
        $accessCode = AccessCode::query()
            ->where('code', $code)
            ->lockForUpdate()
            ->first();

        if ($accessCode === null) {
            return new RedemptionResult(RedemptionOutcome::NotFound);
        }

        $course = $accessCode->course;

        if ($course === null || ! $course->isPublished()) {
            return new RedemptionResult(RedemptionOutcome::CourseUnavailable);
        }

        $existingEnrollment = Enrollment::query()
            ->where('user_id', $user->getKey())
            ->where('course_id', $course->getKey())
            ->first();

        // Reutiliser son propre code ne doit pas ressembler a une erreur, et ne
        // consomme pas d'usage supplementaire.
        if ($existingEnrollment !== null && $existingEnrollment->grantsAccess()) {
            return new RedemptionResult(
                outcome: RedemptionOutcome::AlreadyEnrolled,
                course: $course,
                enrollment: $existingEnrollment,
            );
        }

        if (! $accessCode->is_active) {
            return new RedemptionResult(RedemptionOutcome::Inactive);
        }

        if ($accessCode->hasExpired()) {
            return new RedemptionResult(RedemptionOutcome::Expired);
        }

        if ($accessCode->remainingUses() <= 0) {
            return new RedemptionResult(RedemptionOutcome::Exhausted);
        }

        $alreadyRedeemed = AccessCodeRedemption::query()
            ->where('access_code_id', $accessCode->getKey())
            ->where('user_id', $user->getKey())
            ->exists();

        if (! $alreadyRedeemed) {
            AccessCodeRedemption::query()->create([
                'access_code_id' => $accessCode->getKey(),
                'user_id' => $user->getKey(),
                'redeemed_at' => now(),
            ]);

            $accessCode->increment('used_count');
        }

        $enrollment = Enrollment::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'course_id' => $course->getKey(),
            ],
            [
                'status' => EnrollmentStatus::Active,
                'source' => EnrollmentSource::AccessCode,
                'access_code_id' => $accessCode->getKey(),
                'enrolled_at' => now(),
                'expires_at' => null,
            ],
        );

        return new RedemptionResult(
            outcome: RedemptionOutcome::Redeemed,
            course: $course,
            enrollment: $enrollment,
        );
    }

    private function normalize(string $rawCode): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $rawCode) ?? '');
    }

    private function throttleKey(User $user): string
    {
        return 'access-code:'.$user->getKey();
    }

    private function maxAttempts(): int
    {
        return (int) config('lms.access_codes.max_attempts');
    }

    private function decayMinutes(): int
    {
        return (int) config('lms.access_codes.decay_minutes');
    }
}
