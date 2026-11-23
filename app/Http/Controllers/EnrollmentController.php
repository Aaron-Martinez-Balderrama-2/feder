<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentService $enrollmentService
    ) {}

    public function store(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'student') {
            return back()->with('warning', 'Solo los estudiantes pueden inscribirse a cursos.');
        }

        $result = $this->enrollmentService->enroll($user->id, $course->id);

        if (!$result['ok']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('courses.show', $course->slug)
            ->with($result['code'] === 'already' ? 'info' : 'success', $result['message']);
    }
}
