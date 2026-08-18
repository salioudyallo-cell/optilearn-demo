<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Enregistre (ou met à jour) l'avis d'un apprenant sur une formation à laquelle il a
     * accès. Une note par apprenant et par formation.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        // Seul un apprenant ayant accès à la formation peut la noter.
        abort_unless($user->hasAccessToCourse($course), 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        Review::updateOrCreate(
            ['user_id' => $user->getKey(), 'course_id' => $course->getKey()],
            ['rating' => $validated['rating'], 'comment' => $validated['comment'] ?? null],
        );

        return back()->with('status', __('Merci, votre avis a été enregistré.'));
    }
}
