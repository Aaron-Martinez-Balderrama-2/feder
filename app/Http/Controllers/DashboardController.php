<?php

namespace App\Http\Controllers;

use App\Models\CourseAttendance;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Enrolled courses with strict 10 per page pagination
        $enrolledCourses = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->with(['course' => fn ($q) => $q->with(['category:id,name,icon', 'instructor:id,name'])])
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $attendancesCount = CourseAttendance::where('user_id', $user->id)->count();

        return Inertia::render('Dashboard', [
            'enrolledCourses' => $enrolledCourses,
            'attendancesCount' => $attendancesCount,
        ]);
    }

    public function __invoke(Request $request): Response
    {
        return $this->index($request);
    }
}
