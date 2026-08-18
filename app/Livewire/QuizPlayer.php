<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use App\Services\CourseCompletion;
use App\Services\QuizGrader;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use RuntimeException;

/**
 * Quiz QCM. Les questions sont chargees en une seule fois (aucun aller-retour par
 * question, §6) ; la selection des reponses se fait en Alpine cote client. Seule la
 * soumission finale — une ecriture en base — passe par Livewire.
 *
 * L'autorisation est re-verifiee cote serveur a la soumission : on ne fait jamais
 * confiance a l'id de lecon envoye par le client (§7.4).
 */
class QuizPlayer extends Component
{
    public int $lessonId;

    /** @var array<int, int> question_id => option_id choisie (rempli par Alpine a la soumission) */
    public array $answers = [];

    public bool $submitted = false;

    public ?int $scorePercent = null;

    public bool $passed = false;

    /** @var array<int, int> question_id => option_id correcte, revele apres correction */
    public array $correctByQuestion = [];

    public int $remainingAttempts = 0;

    public ?string $error = null;

    public function mount(Lesson $lesson): void
    {
        $this->authorizeLesson($lesson);
        $this->lessonId = $lesson->getKey();

        $quiz = $this->quiz();
        $grader = app(QuizGrader::class);
        $user = $this->userOrFail();

        $this->remainingAttempts = $grader->remainingAttempts($user, $quiz);
        $this->passed = $grader->hasPassed($user, $quiz);
    }

    /**
     * @param  array<int, int>  $answers
     */
    public function submit(array $answers, QuizGrader $grader, CourseCompletion $completion): void
    {
        $lesson = Lesson::findOrFail($this->lessonId);
        $this->authorizeLesson($lesson);

        $quiz = $this->quiz();
        $user = $this->userOrFail();

        try {
            $result = $grader->grade($user, $quiz, $answers);
        } catch (RuntimeException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->answers = $result->attempt->answers;
        $this->submitted = true;
        $this->scorePercent = $result->scorePercent;
        $this->passed = $result->passed;
        $this->correctByQuestion = $result->correctOptionIdByQuestion;
        $this->remainingAttempts = $grader->remainingAttempts($user, $quiz);

        // Une reussite peut achever la formation et declencher le certificat.
        if ($result->passed && $lesson->module?->course !== null) {
            $completion->handle($user, $lesson->module->course);
        }
    }

    public function retry(): void
    {
        $this->reset(['submitted', 'scorePercent', 'correctByQuestion', 'answers', 'error']);
    }

    private function quiz(): Quiz
    {
        return Quiz::query()
            ->where('lesson_id', $this->lessonId)
            ->with('questions.options')
            ->firstOrFail();
    }

    private function authorizeLesson(Lesson $lesson): void
    {
        abort_unless(Gate::allows('view', $lesson), 403);
    }

    private function userOrFail(): User
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        return $user;
    }

    public function render(): View
    {
        return view('livewire.quiz-player', [
            'quiz' => $this->quiz(),
        ]);
    }
}
