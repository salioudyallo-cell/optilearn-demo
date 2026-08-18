<?php

declare(strict_types=1);

namespace App\Services;

use App\Facades\Track;
use App\Mail\CertificateObtainedMail;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\Tracking\EventName;
use Illuminate\Support\Facades\Mail;

/**
 * Detecte l'achevement d'une formation et delivre le certificat. Appele apres chaque
 * evenement de progression susceptible de completer le cours (fin de lecon, reussite
 * d'un quiz). Idempotent : ne cree jamais deux certificats pour le meme couple
 * (utilisateur, formation).
 */
final class CourseCompletion
{
    public function __construct(
        private LearningNavigator $navigator,
        private CertificateGenerator $certificates,
    ) {}

    public function isComplete(User $user, Course $course): bool
    {
        $total = $this->navigator->orderedLessons($course)->count();

        if ($total === 0) {
            return false;
        }

        return $this->navigator->completedLessonIds($user, $course)->count() === $total;
    }

    /**
     * Delivre le certificat si la formation est achevee et qu'aucun n'existe encore.
     */
    public function handle(User $user, Course $course): ?Certificate
    {
        if (! $this->isComplete($user, $course)) {
            return null;
        }

        $existing = Certificate::query()
            ->where('user_id', $user->getKey())
            ->where('course_id', $course->getKey())
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $certificate = $this->certificates->issue($user, $course);

        Mail::to($user->email)->send(new CertificateObtainedMail($user, $course, $certificate));

        Track::event(EventName::CourseCompleted, ['course_id' => $course->getKey()], $user);
        Track::event(EventName::CertificateObtained, ['course_id' => $course->getKey(), 'serial' => $certificate->serial], $user);

        return $certificate;
    }
}
