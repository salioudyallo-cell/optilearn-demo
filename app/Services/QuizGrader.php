<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use RuntimeException;

/**
 * Correction d'un quiz. Toute la logique metier vit ici, jamais dans le composant
 * Livewire (regle §1). Le composant se contente de collecter les reponses et d'afficher
 * le resultat renvoye.
 */
final class QuizGrader
{
    public function __construct(
        private ProgressTracker $progress,
    ) {}

    /**
     * Nombre de tentatives deja effectuees par l'utilisateur sur ce quiz.
     */
    public function attemptCount(User $user, Quiz $quiz): int
    {
        return QuizAttempt::query()
            ->where('user_id', $user->getKey())
            ->where('quiz_id', $quiz->getKey())
            ->count();
    }

    public function remainingAttempts(User $user, Quiz $quiz): int
    {
        return max(0, $quiz->max_attempts - $this->attemptCount($user, $quiz));
    }

    public function hasPassed(User $user, Quiz $quiz): bool
    {
        return QuizAttempt::query()
            ->where('user_id', $user->getKey())
            ->where('quiz_id', $quiz->getKey())
            ->where('passed', true)
            ->exists();
    }

    /**
     * Corrige une soumission et enregistre la tentative.
     *
     * @param  array<int, int>  $answers  question_id => option_id choisie
     */
    public function grade(User $user, Quiz $quiz, array $answers): QuizResult
    {
        if ($this->remainingAttempts($user, $quiz) <= 0) {
            throw new RuntimeException('Nombre maximal de tentatives atteint.');
        }

        $quiz->loadMissing('questions.options');
        $questions = $quiz->questions;
        $total = $questions->count();

        if ($total === 0) {
            throw new RuntimeException('Ce quiz ne comporte aucune question.');
        }

        $correctByQuestion = [];
        $correctCount = 0;

        foreach ($questions as $question) {
            $correctOption = $question->options->firstWhere('is_correct', true);
            $correctByQuestion[$question->id] = $correctOption !== null ? $correctOption->id : 0;

            $chosen = $answers[$question->id] ?? null;
            if ($chosen !== null && $correctOption !== null && (int) $chosen === $correctOption->id) {
                $correctCount++;
            }
        }

        $scorePercent = (int) round($correctCount / $total * 100);
        $passed = $scorePercent >= $quiz->pass_score_pct;

        $attempt = QuizAttempt::query()->create([
            'user_id' => $user->getKey(),
            'quiz_id' => $quiz->getKey(),
            'score_pct' => $scorePercent,
            'passed' => $passed,
            // Stocke via le cast array : jamais interroge en SQL (§4 regle 2).
            'answers' => $this->normalizeAnswers($answers),
            'attempted_at' => now(),
        ]);

        // Une reussite marque la lecon du quiz comme terminee.
        if ($passed && $quiz->lesson !== null) {
            $this->progress->markCompleted($user, $quiz->lesson);
        }

        return new QuizResult(
            attempt: $attempt,
            scorePercent: $scorePercent,
            passed: $passed,
            correctOptionIdByQuestion: $correctByQuestion,
        );
    }

    /**
     * @param  array<int, int>  $answers
     * @return array<int, int>
     */
    private function normalizeAnswers(array $answers): array
    {
        $clean = [];
        foreach ($answers as $questionId => $optionId) {
            $clean[(int) $questionId] = (int) $optionId;
        }

        return $clean;
    }
}
