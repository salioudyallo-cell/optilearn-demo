<?php

declare(strict_types=1);

use App\Livewire\ActivateCode;
use App\Livewire\LessonPlayer;
use App\Livewire\QuizPlayer;
use App\Models\AccessCode;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('parcours 1 — un visiteur découvre le catalogue puis crée un compte', function () {
    $course = Course::factory()->published()->create(['title' => 'Formation vitrine']);

    // Découverte publique.
    $this->get(route('home'))->assertOk();
    $this->get(route('catalog.index'))->assertOk()->assertSee('Formation vitrine');
    $this->get(route('catalog.show', $course))->assertOk();

    // Inscription.
    $this->post('/register', [
        'name' => 'Nouvel Apprenant',
        'email' => 'nouvel@example.com',
        'phone' => '+221 77 000 00 00',
        'country' => 'SN',
        'password' => 'MotDePasse2026!',
        'password_confirmation' => 'MotDePasse2026!',
    ]);

    $this->assertAuthenticated();
    expect(User::where('email', 'nouvel@example.com')->first()->role->value)->toBe('learner');
});

it('parcours 2 — un apprenant active un code puis suit une leçon', function () {
    $course = Course::factory()->published()->create();
    $module = Module::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson = Lesson::factory()->create(['module_id' => $module->id, 'position' => 1, 'is_preview' => false, 'type' => 'video']);
    $code = AccessCode::factory()->create(['course_id' => $course->id, 'max_uses' => 5]);

    $learner = User::factory()->learner()->create();
    $this->actingAs($learner);

    // Avant activation : accès refusé.
    $this->get(route('learn.lesson', $lesson))->assertForbidden();

    // Activation du code.
    Livewire::test(ActivateCode::class)->set('code', $code->code)->call('redeem')->assertSet('success', true);

    // Après activation : accès accordé et progression enregistrée.
    $this->get(route('learn.lesson', $lesson))->assertOk();
    Livewire::test(LessonPlayer::class, ['lesson' => $lesson])
        ->call('savePosition', 42)
        ->call('markCompleted')
        ->assertSet('completed', true);
});

it('parcours 3 — un apprenant réussit le quiz et obtient un certificat vérifiable', function () {
    Storage::fake('private');
    config()->set('lms.certificates.disk', 'private');

    $course = Course::factory()->published()->create();
    $module = Module::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $quizLesson = Lesson::factory()->quiz()->create(['module_id' => $module->id, 'position' => 1, 'is_preview' => false]);
    $quiz = Quiz::factory()->create(['lesson_id' => $quizLesson->id, 'pass_score_pct' => 70]);

    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => 1]);
    $good = QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    QuizOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false]);

    $learner = User::factory()->learner()->create();
    Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);
    $this->actingAs($learner);

    // Réussite du quiz → complète le cours (unique leçon) → certificat délivré.
    Livewire::test(QuizPlayer::class, ['lesson' => $quizLesson])
        ->call('submit', [$question->id => $good->id])
        ->assertSet('passed', true);

    $certificate = Certificate::where('user_id', $learner->id)->where('course_id', $course->id)->first();
    expect($certificate)->not->toBeNull();

    // Téléchargement autorisé pour le titulaire.
    $this->get(route('certificate.download', $certificate))->assertOk();

    // Vérification publique authentique (sans authentification).
    auth()->logout();
    $this->get(route('certificate.verify', $certificate->serial))
        ->assertOk()
        ->assertSee('Certificat authentique');
});
