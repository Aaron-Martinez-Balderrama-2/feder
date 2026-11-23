<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseClassController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CourseMaterialController;
use App\Http\Controllers\Admin\CourseStudentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Feder Plataforma Educativa
|--------------------------------------------------------------------------
*/

// Rutas Públicas
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/cursos', [CatalogController::class, 'index'])->name('courses.index');
Route::get('/cursos/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/mapa-del-sitio', [SitemapController::class, 'index'])->name('sitemap');

// Rutas de Invitados (Auth)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    // Recuperación de Contraseña
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

// Rutas de Usuarios Autenticados
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Panel del Estudiante & Perfil
    Route::get('/panel', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/perfil', [ProfileController::class, 'update'])->name('profile.update');

    // Acciones de Cursos para Estudiantes
    Route::post('/cursos/{course}/inscribir', [EnrollmentController::class, 'store'])->name('courses.enroll');
    Route::post('/cursos/{course}/asistencia', [CourseController::class, 'markAttendance'])->name('courses.attendance');
});

// Rutas de Gestión Docente y Administrativa (Admin + Instructor)
Route::middleware(['auth', 'role:admin,instructor'])
    ->prefix('gestion')
    ->name('admin.')
    ->group(function () {
        // Analíticas y Métricas
        Route::get('/', [AnalyticsController::class, 'index'])->name('dashboard');

        // Cohortes / Cursos
        Route::get('/cohortes', [AdminCourseController::class, 'index'])->name('courses.index');
        Route::get('/cohortes/crear', [AdminCourseController::class, 'create'])->name('courses.create');
        Route::post('/cohortes', [AdminCourseController::class, 'store'])->name('courses.store');
        Route::get('/cohortes/{course}/editar', [AdminCourseController::class, 'edit'])->name('courses.edit');
        Route::put('/cohortes/{course}', [AdminCourseController::class, 'update'])->name('courses.update');
        Route::delete('/cohortes/{course}', [AdminCourseController::class, 'destroy'])->name('courses.destroy');

        // Clases y Asistencia
        Route::get('/cohortes/{course}/clases', [CourseClassController::class, 'index'])->name('courses.classes.index');
        Route::post('/cohortes/{course}/clases', [CourseClassController::class, 'store'])->name('courses.classes.store');
        Route::post('/cohortes/{course}/clases/{class}/toggle', [CourseClassController::class, 'toggleComplete'])->name('courses.classes.toggle');
        Route::post('/cohortes/{course}/clases/{class}/asistencia', [CourseClassController::class, 'enableAttendance'])->name('courses.classes.attendance');
        Route::delete('/cohortes/{course}/clases/{class}', [CourseClassController::class, 'destroy'])->name('courses.classes.destroy');

        // Materiales Didácticos
        Route::get('/cohortes/{course}/materiales', [CourseMaterialController::class, 'index'])->name('courses.materials.index');
        Route::post('/cohortes/{course}/materiales', [CourseMaterialController::class, 'store'])->name('courses.materials.store');
        Route::delete('/cohortes/{course}/materiales/{material}', [CourseMaterialController::class, 'destroy'])->name('courses.materials.destroy');

        // Nómina de Estudiantes
        Route::get('/cohortes/{course}/estudiantes', [CourseStudentController::class, 'index'])->name('courses.students.index');
        Route::delete('/cohortes/{course}/matriculas/{enrollment}', [CourseStudentController::class, 'destroy'])->name('courses.students.destroy');
    });

// Rutas Exclusivas de Administrador (Admin)
Route::middleware(['auth', 'role:admin'])
    ->prefix('gestion')
    ->name('admin.')
    ->group(function () {
        // Categorías
        Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categorias', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categorias/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Usuarios
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Ajustes Institucionales de Sede
        Route::get('/ajustes', [SettingController::class, 'edit'])->name('settings.edit');
        Route::post('/ajustes', [SettingController::class, 'update'])->name('settings.update');
    });

// Entorno de Pruebas Aislado Local (BORRAR) - Paleta Naranja, Verde y Plomo
Route::get('/borrar', [\App\Http\Controllers\BorrarCatalogController::class, 'index'])->name('borrar.catalog');

