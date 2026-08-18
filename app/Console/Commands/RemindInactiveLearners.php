<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Mail\InactivityReminderMail;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CourseCompletion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Relance les apprenants inactifs : ceux qui ont commencé une formation, ne l'ont pas
 * terminée, et n'ont plus progressé depuis 7 à 30 jours. Un même apprenant n'est pas
 * relancé plus d'une fois toutes les deux semaines.
 *
 * Exécutée chaque jour par le scheduler (voir routes/console.php).
 */
class RemindInactiveLearners extends Command
{
    protected $signature = 'app:remind-inactive';

    protected $description = 'Relance par e-mail les apprenants inactifs depuis 7 à 30 jours.';

    public function handle(CourseCompletion $completion): int
    {
        $now = Carbon::now();
        $inactiveSince = $now->copy()->subDays(7);
        $tooOld = $now->copy()->subDays(30);

        $learners = User::query()
            ->where('role', UserRole::Learner->value)
            ->whereNull('suspended_at')
            ->whereHas('enrollments', function ($q): void {
                // Condition d'accès inlinée (le scope Enrollment n'est pas résolu sur un
                // builder générique dans whereHas).
                $q->where('status', EnrollmentStatus::Active->value)
                    ->where(function ($w): void {
                        $w->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            })
            ->where(function ($q) use ($now): void {
                $q->whereNull('inactivity_reminded_at')
                    ->orWhere('inactivity_reminded_at', '<', $now->copy()->subDays(14));
            })
            ->get();

        $sent = 0;

        foreach ($learners as $learner) {
            $lastActivity = LessonProgress::query()
                ->where('user_id', $learner->getKey())
                ->max('updated_at');

            // Jamais commencé : l'e-mail de bienvenue couvre déjà ce cas.
            if ($lastActivity === null) {
                continue;
            }

            $last = Carbon::parse($lastActivity);
            if ($last->greaterThan($inactiveSince) || $last->lessThan($tooOld)) {
                continue;
            }

            $course = $learner->enrollments()
                ->grantingAccess()
                ->with('course')
                ->get()
                ->pluck('course')
                ->first(fn ($course) => ! $completion->isComplete($learner, $course));

            if ($course === null) {
                continue;
            }

            Mail::to($learner->email)->send(new InactivityReminderMail($learner, $course->title));
            $learner->forceFill(['inactivity_reminded_at' => $now])->save();
            $sent++;
        }

        $this->info("{$sent} relance(s) envoyée(s).");

        return self::SUCCESS;
    }
}
