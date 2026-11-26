<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Categorías
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 90)->unique();
            $table->string('icon', 30)->default('spark');
            $table->timestamps();
        });

        // 2. Cursos
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->string('slug', 170)->unique();
            $table->string('hook', 255);
            $table->text('description');
            $table->string('outcome', 255);
            $table->string('level', 20)->default('inicial'); // inicial, intermedio, avanzado
            $table->unsignedSmallInteger('capacity')->default(30);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('progress_pct')->default(0);
            $table->string('color', 7)->default('#4338CA');
            $table->string('cover_image', 255)->nullable();
            $table->text('meet_url')->nullable();
            $table->text('whatsapp_url')->nullable();
            $table->string('status', 20)->default('draft'); // draft, published, archived
            $table->timestamps();

            $table->index('status');
        });

        // 3. Módulos del Curso (Las 4 fases obligatorias)
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title', 160);
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();

            $table->index(['course_id', 'position']);
        });

        // 4. Clases / Puntos de Avance
        Schema::create('course_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->cascadeOnDelete();
            $table->string('title', 150);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('attendance_expires_at')->nullable();
            $table->timestamps();
        });

        // 5. Materiales Didácticos
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('course_classes')->nullOnDelete();
            $table->string('title', 150);
            $table->text('file_path')->nullable();
            $table->timestamps();
        });

        // 6. Matrículas / Inscripciones
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('status', 20)->default('active'); // active, completed, cancelled
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index(['course_id', 'status']);
        });

        // 7. Registro de Asistencia Estudiantil
        Schema::create('course_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('course_classes')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'class_id']);
        });

        // 8. Ajustes Globales de Sede y Geolocalización
        Schema::create('settings', function (Blueprint $table) {
            $table->string('setting_key', 100)->primary();
            $table->text('setting_value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('course_attendance');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('course_materials');
        Schema::dropIfExists('course_classes');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('categories');
    }
};
