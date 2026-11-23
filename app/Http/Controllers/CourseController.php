<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAttendance;
use App\Models\CourseClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function show(string $slug): Response
    {
        $course = Course::with([
            'category:id,name,slug,icon',
            'instructor:id,name,avatar',
            'modules' => fn ($q) => $q->orderBy('position')->with(['classes' => fn ($cq) => $cq->orderBy('id')]),
            'materials',
        ])
        ->withCount('enrollments')
        ->where('slug', $slug)
        ->firstOrFail();

        $user = auth()->user();
        $isEnrolled = false;
        $hasAttended = false;

        if ($user) {
            $isEnrolled = $course->enrollments()
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();

            if ($isEnrolled) {
                $classIds = $course->modules->flatMap(fn ($m) => $m->classes->pluck('id'))->all();
                if (!empty($classIds)) {
                    $hasAttended = CourseAttendance::where('user_id', $user->id)
                        ->whereIn('class_id', $classIds)
                        ->exists();
                }
            }
        }

        return Inertia::render('Course/Show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
            'hasAttended' => $hasAttended,
        ]);
    }

    public function markAttendance(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $classId = (int) $request->input('class_id');
        $cls = CourseClass::where('id', $classId)
            ->whereHas('module', fn ($q) => $q->where('course_id', $course->id))
            ->firstOrFail();

        // Check if attendance is active
        if (!$cls->attendance_expires_at || now()->gt($cls->attendance_expires_at)) {
            return back()->with('error', 'El registro de asistencia para esta sesión ya expiró o no se encuentra activo.');
        }

        CourseAttendance::firstOrCreate([
            'user_id' => $user->id,
            'class_id' => $cls->id,
        ]);

        return back()->with('success', '¡Asistencia registrada exitosamente!');
    }
}
