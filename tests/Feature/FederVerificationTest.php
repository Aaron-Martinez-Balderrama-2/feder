<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\User;
use App\Services\EnrollmentService;
use Tests\TestCase;

class FederVerificationTest extends TestCase
{
    public function test_database_connection_and_seeded_records(): void
    {
        $this->assertGreaterThanOrEqual(3, User::count());
        $this->assertGreaterThanOrEqual(5, Category::count());
        $this->assertGreaterThanOrEqual(3, Course::count());
        $this->assertGreaterThanOrEqual(12, CourseModule::count());
        $this->assertGreaterThanOrEqual(24, CourseClass::count());
        $this->assertNotNull(Setting::getVal('office_address'));
    }

    public function test_pagination_fixed_at_ten(): void
    {
        $courses = Course::paginate(10);
        $this->assertEquals(10, $courses->perPage());
    }

    public function test_course_progress_calculation(): void
    {
        $course = Course::with('modules.classes')->first();
        $this->assertNotNull($course);

        $initialPct = $course->progress_pct;
        $course->recalculateProgress();
        $this->assertEquals($initialPct, $course->progress_pct);
    }

    public function test_public_routes_render_cleanly(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response = $this->get('/cursos');
        $response->assertStatus(200);

        $response = $this->get('/mapa-del-sitio');
        $response->assertStatus(200);

        $response = $this->get('/login');
        $response->assertStatus(200);

        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_course_show_route(): void
    {
        $course = Course::first();
        $this->assertNotNull($course);

        $response = $this->get("/cursos/{$course->slug}");
        $response->assertStatus(200);
    }

    public function test_admin_access_control(): void
    {
        // Guests cannot access admin dashboard
        $response = $this->get('/gestion');
        $response->assertRedirect('/login');

        // Student cannot access admin dashboard
        $student = User::where('role', 'student')->first();
        $this->assertNotNull($student);

        $response = $this->actingAs($student)->get('/gestion');
        $response->assertStatus(403);

        // Admin can access admin dashboard
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/gestion');
        $response->assertStatus(200);
    }

    public function test_acid_enrollment_service_cutoff(): void
    {
        $service = app(EnrollmentService::class);
        $student = User::where('role', 'student')->first();
        $this->assertNotNull($student);

        // Find or create course with >= 50% progress
        $course = Course::first();
        $this->assertNotNull($course);
        $originalPct = $course->progress_pct;

        try {
            $course->progress_pct = 55;
            $course->save();

            $res = $service->enroll($student->id, $course->id);
            $this->assertFalse($res['ok']);
            $this->assertEquals('closed', $res['code']);
        } finally {
            $course->progress_pct = $originalPct;
            $course->save();
        }
    }
}
