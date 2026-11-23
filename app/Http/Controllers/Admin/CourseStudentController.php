<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseStudentController extends Controller
{
    public function index(Request $request, Course $course): Response
    {
        $this->authorizeCourse($request->user(), $course);

        // Paginate enrolled students strictly by 10 items
        $enrollments = Enrollment::where('course_id', $course->id)
            ->with(['user' => function ($q) use ($course) {
                $q->withCount(['attendances as attendance_count' => function ($aq) use ($course) {
                    $aq->whereHas('class.module', function ($mq) use ($course) {
                        $mq->where('course_id', $course->id);
                    });
                }]);
            }])
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Courses/Students', [
            'course' => $course,
            'enrollments' => $enrollments,
        ]);
    }

    public function destroy(Request $request, Course $course, Enrollment $enrollment): RedirectResponse
    {
        $this->authorizeCourse($request->user(), $course);

        if ($enrollment->course_id !== $course->id) {
            abort(404, 'La matrícula no pertenece a este curso.');
        }

        $studentName = $enrollment->user->name ?? 'Estudiante';
        $enrollment->delete();

        return back()->with('success', "Matrícula de {$studentName} dada de baja.");
    }

    private function authorizeCourse(User $user, Course $course): void
    {
        if ($user->role === 'instructor' && $course->instructor_id !== $user->id) {
            abort(403, 'No tienes permiso para ver la nómina de esta cohorte.');
        }
    }
}
