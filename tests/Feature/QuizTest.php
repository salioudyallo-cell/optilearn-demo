<?php

declare(strict_types=1);

use App\Livewire\QuizPlayer;
use App\Mail\CertificateObtainedMail;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * Construit un cours avec une seule leçon quiz de 2 questions, et renvoie les éléments.
 *
 * @return array{course: Course, lesson: Lesson, quiz: Quiz, correct: array<int,int>, wrong: array<int,int>}
 */
function courseWithQuiz(int $passScore = 70): array
{
    $course = Course::factory()->published()->create();
    $module = Module::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson = Lesson::factory()->quiz()->create(['module_id' => $module->id, 'position' => 1, 'is_preview' => false]);
    $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id, 'pass_score_pct' => $passScore, 'max_attempts' => 3]);

    $correct = [];
    $wrong = [];
    foreach ([1, 2] as $pos) {
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => $pos]);
        $good = QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
        $bad = QuizOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false]);
        $correct[$question->id] = $good->id;
        $wrong[$question->id] = $bad->id;
    }

    return compact('course', 'lesson', 'quiz', 'correct', 'wrong');
}

it('calcule un score de 100 % et fait réussir avec les bonnes réponses', function () {
    ['course' => $course, 'lesson' => $lesson, 'quiz' => $quiz, 'correct' => $correct] = courseWithQuiz();
    $learner = User::factory()->learner()->create();
    Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

    $this->actingAs($learner);

    Livewire::test(QuizPlayer::class, ['lesson' => $lesson])
        ->call('submit', $correct)
        ->assertSet('passed', true)
        ->assertSet('scorePercent', 100);

    expect(QuizAttempt::where('user_id', $learner->id)->where('quiz_id', $quiz->id)->first()->passed)->toBeTrue();
});

it('échoue avec les mauvaises réponses et n’atteint pas le score de passage', function () {
    ['course' => $course, 'lesson' => $lesson, 'wrong' => $wrong] = courseWithQuiz();
    $learner = User::factory()->learner()->create();
    Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

    $this->actingAs($learner);

    Livewire::test(QuizPlayer::class, ['lesson' => $lesson])
        ->call('submit', $wrong)
        ->assertSet('passed', false)
        ->assertSet('scorePercent', 0);
});

it('marque la leçon quiz comme terminée après réussite', function () {
    ['course' => $course, 'lesson' => $lesson, 'correct' => $correct] = courseWithQuiz();
    $learner = User::factory()->learner()->create();
    Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

    $this->actingAs($learner);

    Livewire::test(QuizPlayer::class, ['lesson' => $lesson])->call('submit', $correct);

    expect(LessonProgress::where('user_id', $learner->id)->where('lesson_id', $lesson->id)->first()->completed_at)
        ->not->toBeNull();
});

it('limite le nombre de tentatives', function () {
    ['course' => $course, 'lesson' => $lesson, 'quiz' => $quiz, 'wrong' => $wrong] = courseWithQuiz();
    $quiz->update(['max_attempts' => 2]);
    $learner = User::factory()->learner()->create();
    Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

    $this->actingAs($learner);

    // Deux tentatives ratees consomment le quota.
    Livewire::test(QuizPlayer::class, ['lesson' => $lesson])->call('submit', $wrong)->call('retry')->call('submit', $wrong);

    // La troisieme doit etre refusee : plus de tentative restante.
    $component = Livewire::test(QuizPlayer::class, ['lesson' => $lesson]);
    expect($component->get('remainingAttempts'))->toBe(0);

    expect(QuizAttempt::where('user_id', $learner->id)->where('quiz_id', $quiz->id)->count())->toBe(2);
});

it('délivre un certificat et envoie l’email quand le quiz complète la formation', function () {
    Mail::fake();

    // Cours a une seule lecon : reussir le quiz complete le cours.
    ['course' => $course, 'lesson' => $lesson, 'correct' => $correct] = courseWithQuiz();
    $learner = User::factory()->learner()->create();
    Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

    $this->actingAs($learner);

    Livewire::test(QuizPlayer::class, ['lesson' => $lesson])->call('submit', $correct);

    $certificate = Certificate::where('user_id', $learner->id)->where('course_id', $course->id)->first();
    expect($certificate)->not->toBeNull()
        ->and($certificate->serial)->toStartWith('OPT-')
        ->and($certificate->pdf_path)->not->toBeNull();

    Mail::assertSent(CertificateObtainedMail::class);
});
