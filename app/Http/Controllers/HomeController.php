<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $stats = Cache::remember('home_stats', 60, function () {
            return [
                'students' => User::where('role', 'student')->count(),
                'courses' => Course::where('status', 'published')->count(),
                'classes' => CourseClass::where('is_completed', true)->count(),
            ];
        });

        $featuredCourses = Cache::remember('home_featured_courses', 60, function () {
            return Course::with(['category', 'instructor'])
                ->withCount('enrollments')
                ->where('status', 'published')
                ->orderBy('id', 'desc')
                ->take(6)
                ->get();
        });

        return Inertia::render('Home', [
            'stats' => $stats,
            'featuredCourses' => $featuredCourses,
            'courses' => $featuredCourses,
        ]);
    }

    public function __invoke(): Response
    {
        return $this->index();
    }
}
