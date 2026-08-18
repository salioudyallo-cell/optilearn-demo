<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RedemptionOutcome;
use App\Models\Course;
use App\Models\Enrollment;

final readonly class RedemptionResult
{
    public function __construct(
        public RedemptionOutcome $outcome,
        public ?Course $course = null,
        public ?Enrollment $enrollment = null,
        public ?int $retryAfterMinutes = null,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->outcome->isSuccessful();
    }

    /**
     * Message destine a l'apprenant. Toutes les chaines vivent dans lang/fr.
     */
    public function message(): string
    {
        return match ($this->outcome) {
            RedemptionOutcome::Redeemed => __('access_codes.redeemed', [
                'course' => $this->course->title ?? '',
            ]),
            RedemptionOutcome::Throttled => __('access_codes.throttled', [
                'minutes' => $this->retryAfterMinutes ?? 0,
            ]),
            default => __('access_codes.'.$this->outcome->value),
        };
    }
}
