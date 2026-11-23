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
use Illuminate\Support\Str;
use Tests\TestCase;

class ExhaustiveCycleThreeTest extends TestCase
{
    public function test_cycle_3_acid_enrollment_and_attendance_rules(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        // 1. Create a category & isolated test course with capacity 1
        $category = Category::first();
        $course = Course::create([
            'category_id' => $category->id,
            'title' => 'Curso Capacidad Limitada ACID',
            'slug' => 'curso-acid-' . Str::lower(Str::random(6)),
            'hook' => 'Comprobación de concurrencia y reglas de inscripción.',
            'description' => 'Descripción completa del curso de prueba ACID.',
            'outcome' => 'Verificación garantizada.',
            'level' => 'inicial',
            'capacity' => 1,
            'progress_pct' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'status' => 'published',
        ]);

        // Add a module and a class
        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Fase Única',
            'position' => 1,
        ]);
        $class = CourseClass::create([
            'module_id' => $module->id,
            'title' => 'Clase con Asistencia',
            'attendance_expires_at' => now()->addHours(2),
        ]);

        // 2. Create Student 1 and Student 2
        $student1 = User::create([
            'name' => 'Estudiante Uno ACID',
            'email' => 'student1_' . Str::random(6) . '@feder.edu.bo',
            'password' => 'password',
            'role' => 'student',
            'is_active' => true,
        ]);
        $student2 = User::create([
            'name' => 'Estudiante Dos ACID',
            'email' => 'student2_' . Str::random(6) . '@feder.edu.bo',
            'password' => 'password',
            'role' => 'student',
            'is_active' => true,
        ]);

        // 3. Student 1 enrolls -> Should SUCCEED
        $enrollRes1 = $this->actingAs($student1)->post("/cursos/{$course->id}/inscribir");
        $enrollRes1->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student1->id,
            'course_id' => $course->id,
        ]);

        // 4. Student 1 attempts duplicate enrollment -> Should redirect with info/error, not crash
        $enrollResDup = $this->actingAs($student1)->post("/cursos/{$course->id}/inscribir");
        $enrollResDup->assertSessionHas('info');

        // 5. Student 2 attempts to enroll -> Capacity of 1 is FULL -> Should FAIL
        $enrollRes2 = $this->actingAs($student2)->post("/cursos/{$course->id}/inscribir");
        $enrollRes2->assertSessionHas('error');
        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $student2->id,
            'course_id' => $course->id,
        ]);

        // 6. Test 50% Progress Cutoff: Expand capacity to 10, but advance progress to 50%
        $course->capacity = 10;
        $course->progress_pct = 50;
        $course->save();

        $enrollResCutoff = $this->actingAs($student2)->post("/cursos/{$course->id}/inscribir");
        $enrollResCutoff->assertSessionHas('error');
        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $student2->id,
            'course_id' => $course->id,
        ]);

        // 7. Student 1 marks attendance in active class -> Should SUCCEED
        $attRes = $this->actingAs($student1)->post("/cursos/{$course->id}/asistencia", [
            'class_id' => $class->id,
        ]);
        $attRes->assertSessionHas('success');
        $this->assertDatabaseHas('course_attendance', [
            'user_id' => $student1->id,
            'class_id' => $class->id,
        ]);

        // 8. Student 1 attempts to mark attendance again -> Should return success (idempotent firstOrCreate)
        $attResDup = $this->actingAs($student1)->post("/cursos/{$course->id}/asistencia", [
            'class_id' => $class->id,
        ]);
        $attResDup->assertSessionHas('success');

        // 9. Clean up created course, module, classes, users
        $student1->delete();
        $student2->delete();
        $course->delete();
    }

    public function test_cycle_3_settings_update_and_sitemap_reflection(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        // 1. Admin updates campus settings
        $settingsRes = $this->actingAs($admin)->post('/gestion/ajustes', [
            'office_address' => 'Av. Bush, Ciudad Universitaria UAGRM, Modulo 236',
            'office_lat' => -17.776100,
            'office_lng' => -63.195200,
            'contact_phone' => '+591 70000000',
            'contact_email' => 'contacto@feder.edu.bo',
            'schedule_hours' => 'Lunes a Viernes 08:00 - 18:00',
        ]);
        $settingsRes->assertSessionHas('success');

        // 2. Sitemap page renders updated settings
        $sitemapRes = $this->get('/mapa-del-sitio');
        $sitemapRes->assertStatus(200);

        $this->assertEquals('contacto@feder.edu.bo', Setting::getVal('contact_email'));
    }
}
