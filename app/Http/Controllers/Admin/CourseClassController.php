<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseClassController extends Controller
{
    public function index(Request $request, Course $course): Response
    {
        $this->authorizeCourse($request->user(), $course);

        $course->load([
            'modules' => function ($q) {
                $q->orderBy('position')->with(['classes' => function ($c) {
                    $c->orderBy('id');
                }]);
            },
        ]);

        return Inertia::render('Admin/Courses/Classes', [
            'course' => $course,
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeCourse($request->user(), $course);

        $data = $request->validate([
            'module_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($course) {
                    $valid = CourseModule::where('id', $value)
                        ->where('course_id', $course->id)
                        ->exists();
                    if (!$valid) {
                        $fail('El módulo seleccionado no pertenece a este curso.');
                    }
                },
            ],
            'title' => ['required', 'string', 'max:150'],
        ]);

        CourseClass::create([
            'module_id' => $data['module_id'],
            'title' => $data['title'],
            'is_completed' => false,
        ]);

        $course->recalculateProgress();

        return back()->with('success', 'Sesión agregada correctamente.');
    }

    public function toggleComplete(Request $request, Course $course, CourseClass $class): RedirectResponse
    {
        $this->authorizeCourse($request->user(), $course);
        $this->validateClassBelongsToCourse($course, $class);

        $class->is_completed = !$class->is_completed;
        $class->completed_at = $class->is_completed ? now() : null;
        $class->save();

        $course->recalculateProgress();

        $statusText = $class->is_completed ? 'completada' : 'pendiente';
        return back()->with('success', "Sesión marcada como {$statusText}.");
    }

    public function enableAttendance(Request $request, Course $course, CourseClass $class): RedirectResponse
    {
        $this->authorizeCourse($request->user(), $course);
        $this->validateClassBelongsToCourse($course, $class);

        $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:480'],
        ]);

        $minutes = $request->integer('minutes');
        $class->attendance_expires_at = now()->addMinutes($minutes);
        $class->save();

        return back()->with('success', "Registro de asistencia activado durante {$minutes} minutos.");
    }

    public function destroy(Request $request, Course $course, CourseClass $class): RedirectResponse
    {
        $this->authorizeCourse($request->user(), $course);
        $this->validateClassBelongsToCourse($course, $class);

        $title = $class->title;
        $class->delete();

        $course->recalculateProgress();

        return back()->with('success', "Sesión \"{$title}\" eliminada correctamente.");
    }

    private function authorizeCourse(User $user, Course $course): void
    {
        if ($user->role === 'instructor' && $course->instructor_id !== $user->id) {
            abort(403, 'No tienes permiso para gestionar las clases de esta cohorte.');
        }
    }

    private function validateClassBelongsToCourse(Course $course, CourseClass $class): void
    {
        if ($class->module->course_id !== $course->id) {
            abort(404, 'La sesión no pertenece al curso indicado.');
        }
    }
}
