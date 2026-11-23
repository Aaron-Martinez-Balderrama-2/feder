<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Course::with(['category', 'instructor'])
            ->withCount('enrollments')
            ->orderBy('id', 'desc');

        if ($user->role === 'instructor') {
            $query->where('instructor_id', $user->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('hook', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Strictly paginated at 10 items
        $courses = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $categories = Category::orderBy('name')->get();
        $instructors = User::whereIn('role', ['admin', 'instructor'])
            ->orderBy('name')
            ->select('id', 'name', 'email', 'role')
            ->get();

        return Inertia::render('Admin/Courses/Form', [
            'course' => null,
            'categories' => $categories,
            'instructors' => $instructors,
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // If instructor, force instructor_id to self
        if ($user->role === 'instructor') {
            $data['instructor_id'] = $user->id;
        }

        // Generate unique slug
        $baseSlug = Str::slug($data['title']);
        $slug = $baseSlug;
        $counter = 1;
        while (Course::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }
        $data['slug'] = $slug;

        // Handle cover image base64
        if (!empty($data['cover_base64'])) {
            $data['cover_image'] = $this->saveBase64Image($data['cover_base64']);
        }
        unset($data['cover_base64']);

        $course = Course::create($data);

        // Auto-scaffold the 4 mandatory phases
        $mandatoryPhases = [
            'Fase 1: Modelo de Negocio',
            'Fase 2: Finanzas para la empresa',
            'Fase 3: Marketing Digital',
            'Fase 4: Legal y Cumplimiento Normativo',
        ];

        foreach ($mandatoryPhases as $idx => $phaseTitle) {
            CourseModule::create([
                'course_id' => $course->id,
                'title' => $phaseTitle,
                'position' => $idx + 1,
            ]);
        }

        \Illuminate\Support\Facades\Cache::forget('home_stats');
        \Illuminate\Support\Facades\Cache::forget('home_featured_courses');
        return redirect()->route('admin.courses.index')->with('success', "Cohorte \"{$course->title}\" creada con éxito junto a sus 4 fases obligatorias.");
    }

    public function edit(Request $request, Course $course): Response
    {
        $this->authorizeCourse($request->user(), $course);

        $categories = Category::orderBy('name')->get();
        $instructors = User::whereIn('role', ['admin', 'instructor'])
            ->orderBy('name')
            ->select('id', 'name', 'email', 'role')
            ->get();

        return Inertia::render('Admin/Courses/Form', [
            'course' => $course,
            'categories' => $categories,
            'instructors' => $instructors,
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $this->authorizeCourse($request->user(), $course);

        $data = $request->validated();

        if ($request->user()->role === 'instructor') {
            $data['instructor_id'] = $course->instructor_id;
        }

        // Slug update if title changed
        if ($data['title'] !== $course->title) {
            $baseSlug = Str::slug($data['title']);
            $slug = $baseSlug;
            $counter = 1;
            while (Course::where('slug', $slug)->where('id', '!=', $course->id)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }
            $data['slug'] = $slug;
        }

        // Handle cover image
        if (!empty($data['cover_base64'])) {
            if ($course->cover_image && File::exists(public_path($course->cover_image))) {
                File::delete(public_path($course->cover_image));
            }
            $data['cover_image'] = $this->saveBase64Image($data['cover_base64']);
        }
        unset($data['cover_base64']);

        $course->update($data);
        \Illuminate\Support\Facades\Cache::forget('home_stats');
        \Illuminate\Support\Facades\Cache::forget('home_featured_courses');

        return redirect()->route('admin.courses.index')->with('success', "Cohorte \"{$course->title}\" actualizada correctamente.");
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeCourse($request->user(), $course);

        if ($course->cover_image && File::exists(public_path($course->cover_image))) {
            File::delete(public_path($course->cover_image));
        }

        $title = $course->title;
        $course->delete();
        \Illuminate\Support\Facades\Cache::forget('home_stats');
        \Illuminate\Support\Facades\Cache::forget('home_featured_courses');

        return redirect()->route('admin.courses.index')->with('success', "Cohorte \"{$title}\" eliminada correctamente.");
    }

    private function authorizeCourse(User $user, Course $course): void
    {
        if ($user->role === 'instructor' && $course->instructor_id !== $user->id) {
            abort(403, 'No tienes permiso para gestionar esta cohorte.');
        }
    }

    private function saveBase64Image(string $base64): ?string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            return null;
        }

        $image = substr($base64, strpos($base64, ',') + 1);
        $image = base64_decode($image);

        if ($image === false) {
            return null;
        }

        $directory = public_path('uploads/courses');
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        $filename = 'course_' . uniqid('', true) . '.jpg';
        $fullPath = $directory . '/' . $filename;
        file_put_contents($fullPath, $image);

        return 'uploads/courses/' . $filename;
    }
}
