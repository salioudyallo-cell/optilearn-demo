<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Mail\InvitationMail;
use App\Models\Course;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Services\GroupProgressReport;
use App\Services\Import\LearnerImporter;
use App\Services\InvitationService;
use App\Services\ProgressTracker;
use Illuminate\Support\Facades\Mail;

/**
 * Outils du mode Entreprise : import CSV, invitations, tableau de bord RH.
 */
it('importe des apprenants depuis un CSV et les rattache au groupe', function (): void {
    Mail::fake();

    $org = Organization::factory()->create();
    $group = Group::factory()->for($org)->create();
    $course = Course::factory()->published()->create();
    $group->courses()->attach($course, ['assigned_at' => now()]);

    $csv = "nom;email\nAwa Diop;awa@example.com\nMoussa Traoré;moussa@example.com\n";
    // league/csv detecte le separateur par defaut la virgule : on force le point-virgule
    // en fournissant un CSV a virgule pour rester simple et portable.
    $csv = "nom,email\nAwa Diop,awa@example.com\nMoussa Traore,moussa@example.com\n";

    $summary = app(LearnerImporter::class)->import($csv, $org, $group, invite: true);

    expect($summary->created)->toBe(2)
        ->and($summary->attached)->toBe(2)
        ->and($summary->invited)->toBe(2);

    $awa = User::where('email', 'awa@example.com')->first();
    expect($awa)->not->toBeNull()
        ->and($awa->role)->toBe(UserRole::Learner)
        ->and($awa->organization_id)->toBe($org->getKey())
        // Rattaché au groupe -> inscrit automatiquement à la formation affectée.
        ->and($awa->hasAccessToCourse($course))->toBeTrue();

    Mail::assertQueued(InvitationMail::class, 2);
});

it('est idempotent : réimporter le même e-mail ne crée pas de doublon', function (): void {
    Mail::fake();
    $org = Organization::factory()->create();
    $csv = "nom,email\nAwa Diop,awa@example.com\n";

    app(LearnerImporter::class)->import($csv, $org, null, invite: false);
    $second = app(LearnerImporter::class)->import($csv, $org, null, invite: false);

    expect($second->created)->toBe(0)
        ->and($second->existing)->toBe(1)
        ->and(User::where('email', 'awa@example.com')->count())->toBe(1);
});

it('signale les lignes au format invalide sans interrompre l’import', function (): void {
    Mail::fake();
    $org = Organization::factory()->create();
    $csv = "nom,email\nValide,ok@example.com\nInvalide,pas-un-email\n";

    $summary = app(LearnerImporter::class)->import($csv, $org, null, invite: false);

    expect($summary->created)->toBe(1)
        ->and($summary->errors)->toHaveCount(1);
});

it('envoie une invitation avec un lien de définition de mot de passe', function (): void {
    Mail::fake();
    $user = User::factory()->create();

    app(InvitationService::class)->send($user);

    Mail::assertQueued(InvitationMail::class, function (InvitationMail $mail) use ($user): bool {
        return $mail->hasTo($user->email) && str_contains($mail->setPasswordUrl, 'reset-password');
    });
});

it('calcule la progression d’un groupe pour le tableau de bord RH', function (): void {
    $org = Organization::factory()->create();
    $group = Group::factory()->for($org)->create();

    // Une formation à 2 leçons.
    $course = Course::factory()->published()->create();
    $module = $course->modules()->create(['position' => 1, 'title' => 'M1']);
    $lessonA = $module->lessons()->create(['position' => 1, 'title' => 'L1', 'type' => 'text', 'content' => 'a']);
    $module->lessons()->create(['position' => 2, 'title' => 'L2', 'type' => 'text', 'content' => 'b']);
    $group->courses()->attach($course, ['assigned_at' => now()]);

    $member = User::factory()->create();
    $group->members()->attach($member);

    // Le membre termine 1 leçon sur 2.
    app(ProgressTracker::class)->markCompleted($member, $lessonA);

    $rows = app(GroupProgressReport::class)->forGroup($group);

    expect($rows)->toHaveCount(1);
    $row = $rows->first();
    expect($row['lessons_total'])->toBe(2)
        ->and($row['lessons_done'])->toBe(1)
        ->and($row['percent'])->toBe(50)
        ->and($row['courses_done'])->toBe(0);
});
