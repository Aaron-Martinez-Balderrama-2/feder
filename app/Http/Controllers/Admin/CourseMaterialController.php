<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\CourseMaterial;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class CourseMaterialController extends Controller
{
    public function index(Request $request, Course $course): Response
    {
        $this->authorizeCourse($request->user(), $course);

        // Fetch classes belonging to this course for the selection dropdown
        $classes = CourseClass::whereHas('module', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })->select('id', 'title', 'module_id')->get();

        // Paginate materials strictly by 10
        $materials = CourseMaterial::where('course_id', $course->id)
            ->with('class')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Courses/Materials', [
            'course' => $course,
            'classes' => $classes,
            'materials' => $materials,
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeCourse($request->user(), $course);

        $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'class_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($course) {
                    if ($value) {
                        $valid = CourseClass::where('id', $value)
                            ->whereHas('module', function ($q) use ($course) {
                                $q->where('course_id', $course->id);
                            })->exists();
                        if (!$valid) {
                            $fail('La clase seleccionada no pertenece a este curso.');
                        }
                    }
                },
            ],
            'file' => [
                'required',
                'file',
                'max:20480', // 20 MB max
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,mp3,mp4,png,jpg,jpeg,txt,csv',
            ],
        ]);

        $file = $request->file('file');
        $directory = public_path('uploads/materials');
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        $extension = $file->getClientOriginalExtension();
        $filename = 'mat_' . uniqid('', true) . '.' . $extension;
        $file->move($directory, $filename);
        $filePath = 'uploads/materials/' . $filename;

        CourseMaterial::create([
            'course_id' => $course->id,
            'class_id' => $request->input('class_id') ?: null,
            'title' => $request->input('title'),
            'file_path' => $filePath,
        ]);

        return back()->with('success', 'Recurso didáctico adjuntado con éxito.');
    }

    public function destroy(Request $request, Course $course, CourseMaterial $material): RedirectResponse
    {
        $this->authorizeCourse($request->user(), $course);

        if ($material->course_id !== $course->id) {
            abort(404, 'El recurso no pertenece a este curso.');
        }

        if (File::exists(public_path($material->file_path))) {
            File::delete(public_path($material->file_path));
        }

        $title = $material->title;
        $material->delete();

        return back()->with('success', "Recurso \"{$title}\" eliminado con éxito.");
    }

    private function authorizeCourse(User $user, Course $course): void
    {
        if ($user->role === 'instructor' && $course->instructor_id !== $user->id) {
            abort(403, 'No tienes permiso para gestionar recursos de esta cohorte.');
        }
    }
}
