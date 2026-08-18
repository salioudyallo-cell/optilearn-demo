<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featuredCourses = Course::query()
            ->published()
            ->with('instructor')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('public.home', [
            'featuredCourses' => $featuredCourses,
        ]);
    }
}
