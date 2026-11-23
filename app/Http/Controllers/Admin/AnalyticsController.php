<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAttendance;
use App\Models\CourseClass;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(): Response
    {
        // 1. KPI Stats
        $stats = [
            'total_courses' => Course::count(),
            'active_courses' => Course::where('status', 'published')->count(),
            'total_enrollments' => Enrollment::count(),
            'total_attendances' => CourseAttendance::count(),
        ];

        // 2. Top Popular Courses
        $popularCoursesRaw = Course::withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->take(6)
            ->get(['id', 'title', 'capacity']);

        $popularCourses = [
            'labels' => $popularCoursesRaw->pluck('title')->toArray(),
            'data' => $popularCoursesRaw->pluck('enrollments_count')->toArray(),
        ];

        // 3. Monthly Enrollments Trend (Last 6 Months)
        $monthlyGrowth = Enrollment::selectRaw("TO_CHAR(enrolled_at, 'YYYY-MM') as period, COUNT(id) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->take(6)
            ->get();

        $monthlyTrend = [
            'labels' => $monthlyGrowth->pluck('period')->toArray(),
            'data' => $monthlyGrowth->pluck('total')->toArray(),
        ];

        // If no enrollments yet, fallback to sample trend
        if (empty($monthlyTrend['labels'])) {
            $monthlyTrend = [
                'labels' => ['Nov 2026'],
                'data' => [$stats['total_enrollments']],
            ];
        }

        // 4. Attendance per course
        $courses = Course::take(6)->get(['id', 'title']);
        $attData = [];
        foreach ($courses as $c) {
            $attData[] = CourseAttendance::whereHas('class.module', function ($q) use ($c) {
                $q->where('course_id', $c->id);
            })->count();
        }

        $attendanceStats = [
            'labels' => $courses->pluck('title')->toArray(),
            'data' => $attData,
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'popularCourses' => $popularCourses,
            'monthlyTrend' => $monthlyTrend,
            'attendanceStats' => $attendanceStats,
        ]);
    }

    public function __invoke(): Response
    {
        return $this->index();
    }
}
