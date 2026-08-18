<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Lesson;
use App\Models\User;
use App\Services\ProgressTracker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Suivi de progression d'une lecon. Ecriture en base => Livewire justifie (§6).
 *
 * Contrat cote client (Alpine) :
 *  - savePosition appele au maximum toutes les 15 s (debounce), jamais en continu ;
 *  - marquage « termine » a la demande ou en fin de video ;
 *  - en cas de coupure reseau, la position est mise en file dans localStorage cote
 *    client et rejouee a la reconnexion (voir la vue).
 *
 * Chaque action re-verifie l'autorisation : on ne fait jamais confiance a l'id envoye
 * par le client (§7.4).
 */
class LessonPlayer extends Component
{
    public int $lessonId;

    public int $position = 0;

    public bool $completed = false;

    public function mount(Lesson $lesson): void
    {
        $this->authorizeLesson($lesson);

        $tracker = app(ProgressTracker::class);
        $user = $this->userOrFail();

        $this->lessonId = $lesson->getKey();
        $this->position = $tracker->positionFor($user, $lesson);
        $this->completed = $tracker->isCompleted($user, $lesson);
    }

    public function savePosition(int $seconds, ProgressTracker $tracker): void
    {
        $lesson = Lesson::findOrFail($this->lessonId);
        $this->authorizeLesson($lesson);

        $tracker->savePosition($this->userOrFail(), $lesson, $seconds);
        $this->position = max(0, $seconds);
    }

    public function markCompleted(ProgressTracker $tracker): void
    {
        $lesson = Lesson::findOrFail($this->lessonId);
        $this->authorizeLesson($lesson);

        $tracker->markCompleted($this->userOrFail(), $lesson);
        $this->completed = true;

        // Signale la fin de lecon (barre de progression du sommaire) sans recharger.
        $this->dispatch('lesson-completed', lessonId: $this->lessonId);
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
        $lesson = Lesson::findOrFail($this->lessonId);
        $this->authorizeLesson($lesson);

        return view('livewire.lesson-player', [
            'lesson' => $lesson,
        ]);
    }
}
