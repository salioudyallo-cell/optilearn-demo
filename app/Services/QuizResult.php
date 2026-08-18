<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\QuizAttempt;

final readonly class QuizResult
{
    /**
     * @param  array<int, int>  $correctOptionIdByQuestion  question_id => option_id correcte
     */
    public function __construct(
        public QuizAttempt $attempt,
        public int $scorePercent,
        public bool $passed,
        public array $correctOptionIdByQuestion,
    ) {}
}
