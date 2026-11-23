<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\CourseMaterial;
use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExhaustiveCycleTwoTest extends TestCase
{
    public function test_cycle_2_complete_academic_hierarchy_crud(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        // ---------------- 1. Category CRUD ----------------
        $catName = 'Cat Test Ciclo 2 ' . Str::random(4);
        $catRes = $this->actingAs($admin)->post('/gestion/categorias', [
            'name' => $catName,
            'icon' => 'tag',
        ]);
        $catRes->assertSessionHas('success');

        $category = Category::where('name', $catName)->first();
        $this->assertNotNull($category);

        // Update Category
        $newName = $catName . ' (Modificado)';
        $catUpdateRes = $this->actingAs($admin)->put("/gestion/categorias/{$category->id}", [
            'name' => $newName,
            'icon' => 'star',
        ]);
        $catUpdateRes->assertSessionHas('success');
        $category->refresh();
        $this->assertEquals($newName, $category->name);
        $this->assertEquals('star', $category->icon);

        // ---------------- 2. Course Creation ----------------
        $courseTitle = 'Cohorte Ciclo 2 ' . Str::random(4);
        $courseRes = $this->actingAs($admin)->post('/gestion/cohortes', [
            'category_id' => $category->id,
            'title' => $courseTitle,
            'hook' => 'Aprende y pon a prueba todo el sistema con este cohorte.',
            'description' => 'Descripción completa y detallada de la cohorte.',
            'outcome' => 'Dominio total de las pruebas académicas.',
            'level' => 'intermedio',
            'capacity' => 20,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'color' => '#EA580C',
            'meet_url' => 'https://meet.google.com/test-room',
            'whatsapp_url' => 'https://chat.whatsapp.com/test-group',
            'status' => 'published',
        ]);
        $courseRes->assertRedirect('/gestion/cohortes');

        $course = Course::with('modules')->where('title', $courseTitle)->first();
        $this->assertNotNull($course);
        // Has 4 mandatory scaffolded modules
        $this->assertEquals(4, $course->modules->count());
        $this->assertEquals(0, $course->progress_pct);

        $firstModule = $course->modules->first();

        // ---------------- 3. Create Classes inside Module ----------------
        $class1Res = $this->actingAs($admin)->post("/gestion/cohortes/{$course->id}/clases", [
            'module_id' => $firstModule->id,
            'title' => 'Clase 1: Fundamentos Prácticos',
        ]);
        $class1Res->assertSessionHas('success');

        $class2Res = $this->actingAs($admin)->post("/gestion/cohortes/{$course->id}/clases", [
            'module_id' => $firstModule->id,
            'title' => 'Clase 2: Arquitectura y Entrega',
        ]);
        $class2Res->assertSessionHas('success');

        $classes = CourseClass::where('module_id', $firstModule->id)->get();
        $this->assertEquals(2, $classes->count());

        $firstClass = $classes->first();
        $secondClass = $classes->last();

        // ---------------- 4. Toggle Class Completion & Progress Recalculation ----------------
        // Toggle first class -> 1 of 2 completed = 50%
        $toggle1 = $this->actingAs($admin)->post("/gestion/cohortes/{$course->id}/clases/{$firstClass->id}/toggle");
        $toggle1->assertSessionHas('success');

        $course->refresh();
        $this->assertEquals(50, $course->progress_pct);

        // Toggle second class -> 2 of 2 completed = 100%
        $toggle2 = $this->actingAs($admin)->post("/gestion/cohortes/{$course->id}/clases/{$secondClass->id}/toggle");
        $toggle2->assertSessionHas('success');

        $course->refresh();
        $this->assertEquals(100, $course->progress_pct);

        // ---------------- 5. Class Attendance Activation ----------------
        $attendanceRes = $this->actingAs($admin)->post("/gestion/cohortes/{$course->id}/clases/{$firstClass->id}/asistencia", [
            'minutes' => 60,
        ]);
        $attendanceRes->assertSessionHas('success');
        $firstClass->refresh();
        $this->assertNotNull($firstClass->attendance_expires_at);

        // ---------------- 6. Course Materials Upload & Deletion ----------------
        $fakeFile = UploadedFile::fake()->create('guia_estudio.pdf', 200, 'application/pdf');
        $matRes = $this->actingAs($admin)->post("/gestion/cohortes/{$course->id}/materiales", [
            'title' => 'Guía de Estudio PDF Oficial',
            'class_id' => $firstClass->id,
            'file' => $fakeFile,
        ]);
        $matRes->assertSessionHas('success');

        $material = CourseMaterial::where('course_id', $course->id)->first();
        $this->assertNotNull($material);
        $this->assertEquals('Guía de Estudio PDF Oficial', $material->title);
        $this->assertFileExists(public_path($material->file_path));

        // Delete Material
        $filePath = public_path($material->file_path);
        $delMat = $this->actingAs($admin)->delete("/gestion/cohortes/{$course->id}/materiales/{$material->id}");
        $delMat->assertSessionHas('success');
        $this->assertDatabaseMissing('course_materials', ['id' => $material->id]);
        $this->assertFileDoesNotExist($filePath);

        // ---------------- 7. Clean up Created Course and Category ----------------
        $course->delete();
        $category->delete();
    }
}
