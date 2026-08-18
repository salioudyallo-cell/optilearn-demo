<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CourseLevel;
use App\Models\Course;
use Illuminate\Contracts\View\View;

class CatalogController extends Controller
{
    /**
     * Catalogue public : uniquement les cours publies. Le filtre par niveau se fait
     * cote client en Alpine tant qu'on reste sous 50 cours (regle §6). On charge donc
     * l'ensemble des cours publies en une requete.
     */
    public function index(): View
    {
        $courses = Course::query()
            ->published()
            ->with('instructor')
            ->withCount('lessons')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('published_at')
            ->get();

        return view('public.catalog', [
            'courses' => $courses,
            'levels' => CourseLevel::cases(),
        ]);
    }

    /**
     * Fiche formation. La visibilite d'un cours non publie est portee par CoursePolicy.
     * Le programme (modules et lecons) est visible de tous : seule la lecture du contenu
     * verrouille est refusee, au niveau de la lecon (LessonPolicy).
     */
    public function show(Course $course): View
    {
        $this->authorize('view', $course);

        $course->load(['instructor', 'modules.lessons' => function ($query) {
            $query->orderBy('position');
        }]);

        return view('public.course', [
            'course' => $course,
        ]);
    }
}
