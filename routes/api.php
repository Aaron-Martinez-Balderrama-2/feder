<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ClassroomApiController;
use App\Http\Controllers\Api\CurriculumApiController;
use App\Http\Controllers\Api\EvaluationCertificateApiController;
use App\Http\Controllers\Api\PaymentAnalyticsApiController;
use App\Http\Controllers\Api\PortalApiController;
use App\Http\Controllers\Api\SocialLinkageApiController;
use App\Http\Controllers\Api\UserRbacApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API RESTful - Arquitectura de 3 Capas FEDER (Laravel 12 + React + PostgreSQL)
|--------------------------------------------------------------------------
*/

// 1. Endpoints Públicos y con Sesión Opcional
Route::middleware(['api.optional'])->group(function () {
    Route::post('/bootstrap', [PortalApiController::class, 'bootstrap']);
    Route::post('/visit', [PortalApiController::class, 'visit']);
    Route::get('/search', [PortalApiController::class, 'globalSearch']);

    Route::get('/home', [CurriculumApiController::class, 'homeOverview']);
    Route::get('/courses', [CurriculumApiController::class, 'indexCatalog']);
    Route::get('/courses/{slug}', [CurriculumApiController::class, 'showCourse']);

    // Inspección Pública de Certificados mediante Código QR (CU6)
    Route::get('/certificates/verify/{code}', [EvaluationCertificateApiController::class, 'verifyPublicQr']);
});

// 2. Autenticación y Recuperación de Cuenta
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthApiController::class, 'login']);
    Route::post('/register', [AuthApiController::class, 'register']);
    Route::post('/forgot-password', [AuthApiController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthApiController::class, 'resetPassword']);
});

// 3. Endpoints Protegidos para Usuarios Autenticados
Route::middleware(['api.auth'])->group(function () {
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);
    Route::post('/auth/profile', [AuthApiController::class, 'updateProfile']);
    Route::post('/auth/preferences', [AuthApiController::class, 'updatePreferences']);

    // Pulso de control real de tiempo de uso en plataforma (CU4)
    Route::post('/usage/heartbeat', [PortalApiController::class, 'heartbeat']);

    // Espacio personal del Participante / Docente
    Route::get('/workspace', [ClassroomApiController::class, 'myWorkspace']);

    // Aula Virtual, Foro y Asistencia de Participante (CU4)
    Route::get('/classroom/{course}', [ClassroomApiController::class, 'showClassroom']);
    Route::post('/classroom/{course}/forum', [ClassroomApiController::class, 'storeThread']);
    Route::post('/forum/threads/{thread}/replies', [ClassroomApiController::class, 'storeReply']);
    Route::delete('/forum/threads/{thread}', [ClassroomApiController::class, 'destroyThread']);
    Route::post('/classes/{class}/mark-attendance', [ClassroomApiController::class, 'markStudentAttendance']);

    // Evaluaciones Automáticas y Postulación a Feria (CU5)
    Route::post('/evaluations/{evaluation}/attempt', [EvaluationCertificateApiController::class, 'submitAttempt']);
    Route::post('/classroom/{course}/fair-register', [EvaluationCertificateApiController::class, 'saveFairParticipation']);

    // Vinculación Social Propia (CU2)
    Route::get('/social/my-profile', [SocialLinkageApiController::class, 'myProfile']);
    Route::post('/social/my-profile', [SocialLinkageApiController::class, 'saveMyProfile']);

    // Módulo de Pagos Electrónicos y Planes de Cuotas (INF513 Req 10)
    Route::get('/payments/my', [PaymentAnalyticsApiController::class, 'myPayments']);
    Route::post('/payments/methods', [PaymentAnalyticsApiController::class, 'storePaymentMethod']);
    Route::delete('/payments/methods/{method}', [PaymentAnalyticsApiController::class, 'destroyPaymentMethod']);
    Route::post('/courses/{course}/checkout', [PaymentAnalyticsApiController::class, 'enrollCheckout']);
    Route::post('/payments/plans/{plan}/pay', [PaymentAnalyticsApiController::class, 'payInstallment']);

    // =====================================================================
    // OPERACIÓN DOCENTE, DIRECCIÓN EJECUTIVA Y ADMINISTRACIÓN (Matriz RBAC)
    // =====================================================================

    // Operación de Aula, Fases, Subfases, Materiales y Asistencia (CU3 + CU4)
    Route::middleware(['permission:classroom.operate,curriculum.manage'])->group(function () {
        Route::get('/manage/courses', [CurriculumApiController::class, 'manageCourses']);
        Route::post('/manage/courses', [CurriculumApiController::class, 'storeCourse']);
        Route::put('/manage/courses/{course}', [CurriculumApiController::class, 'updateCourse']);
        Route::delete('/manage/courses/{course}', [CurriculumApiController::class, 'destroyCourse']);

        // Fases editables
        Route::post('/manage/courses/{course}/modules', [CurriculumApiController::class, 'storeModule']);
        Route::put('/manage/modules/{module}', [CurriculumApiController::class, 'updateModule']);
        Route::delete('/manage/modules/{module}', [CurriculumApiController::class, 'destroyModule']);

        // Subfases / clases editables
        Route::post('/manage/modules/{module}/classes', [CurriculumApiController::class, 'storeClass']);
        Route::put('/manage/classes/{class}', [CurriculumApiController::class, 'updateClass']);
        Route::post('/manage/classes/{class}/toggle-complete', [CurriculumApiController::class, 'toggleClassComplete']);
        Route::delete('/manage/classes/{class}', [CurriculumApiController::class, 'destroyClass']);

        // Materiales por curso, fase y subfase
        Route::post('/manage/courses/{course}/materials', [ClassroomApiController::class, 'storeMaterial']);
        Route::delete('/manage/materials/{material}', [ClassroomApiController::class, 'destroyMaterial']);

        // Moderación de foro y asistencias
        Route::post('/manage/forum/{thread}/pin', [ClassroomApiController::class, 'toggleThreadPin']);
        Route::post('/manage/classes/{class}/schedule-attendance', [ClassroomApiController::class, 'scheduleAttendance']);
        Route::post('/manage/classes/{class}/manual-attendance', [ClassroomApiController::class, 'recordManualAttendance']);
    });

    // Evaluaciones de Módulos y Feria Empresarial (CU5)
    Route::middleware(['permission:evaluations.manage,classroom.operate'])->group(function () {
        Route::post('/manage/courses/{course}/evaluations', [EvaluationCertificateApiController::class, 'storeEvaluation']);
        Route::delete('/manage/evaluations/{evaluation}', [EvaluationCertificateApiController::class, 'destroyEvaluation']);
        Route::post('/manage/courses/{course}/fair-evaluate', [EvaluationCertificateApiController::class, 'evaluateFairParticipation']);
    });

    // Emisión y Subida de Certificados Imprimibles con QR (CU6)
    Route::middleware(['permission:certificates.manage,classroom.operate'])->group(function () {
        Route::post('/manage/courses/{course}/certificates', [EvaluationCertificateApiController::class, 'issueOrUploadCertificate']);
        Route::post('/manage/certificates/{certificate}/toggle', [EvaluationCertificateApiController::class, 'revokeCertificate']);
    });

    // Categorías y Periodos Académicos Editables (CU3)
    Route::middleware(['permission:curriculum.manage'])->group(function () {
        Route::get('/manage/categories', [CurriculumApiController::class, 'indexCategories']);
        Route::post('/manage/categories', [CurriculumApiController::class, 'storeCategory']);
        Route::put('/manage/categories/{category}', [CurriculumApiController::class, 'updateCategory']);
        Route::delete('/manage/categories/{category}', [CurriculumApiController::class, 'destroyCategory']);

        Route::get('/manage/periods', [CurriculumApiController::class, 'indexPeriods']);
        Route::post('/manage/periods', [CurriculumApiController::class, 'storePeriod']);
        Route::put('/manage/periods/{period}', [CurriculumApiController::class, 'updatePeriod']);
        Route::delete('/manage/periods/{period}', [CurriculumApiController::class, 'destroyPeriod']);
    });

    // Gestión Institucional de Vinculación Social y Convenios (CU2)
    Route::middleware(['permission:social.manage'])->group(function () {
        Route::get('/manage/social/organizations', [SocialLinkageApiController::class, 'indexOrganizations']);
        Route::post('/manage/social/organizations', [SocialLinkageApiController::class, 'storeOrganization']);
        Route::put('/manage/social/organizations/{organization}', [SocialLinkageApiController::class, 'updateOrganization']);
        Route::delete('/manage/social/organizations/{organization}', [SocialLinkageApiController::class, 'destroyOrganization']);

        Route::get('/manage/social/profiles', [SocialLinkageApiController::class, 'indexProfiles']);
        Route::post('/manage/social/profiles/{profile}/verify', [SocialLinkageApiController::class, 'verifyProfile']);
    });

    // Métricas LMS, Demografía, Impacto Social y Exportación de Reportes Reales (CU7 + CU8)
    Route::middleware(['permission:metrics.view,reports.export'])->group(function () {
        Route::get('/analytics/dashboard', [PaymentAnalyticsApiController::class, 'dashboardMetrics']);
        Route::get('/analytics/export-csv', [PaymentAnalyticsApiController::class, 'exportReportCsv']);
    });

    // Gestión de Usuarios y Fotos (CU1)
    Route::middleware(['permission:users.manage'])->group(function () {
        Route::get('/manage/users', [UserRbacApiController::class, 'indexUsers']);
        Route::post('/manage/users', [UserRbacApiController::class, 'storeUser']);
        Route::put('/manage/users/{user}', [UserRbacApiController::class, 'updateUser']);
        Route::delete('/manage/users/{user}', [UserRbacApiController::class, 'destroyUser']);
    });

    // Matriz RBAC, Menú Dinámico, Sede y Bitácora de Auditoría (INF513 Req 2 y 4)
    Route::middleware(['permission:rbac.manage,audit.view'])->group(function () {
        Route::get('/manage/rbac', [UserRbacApiController::class, 'rbacMatrix']);
        Route::put('/manage/rbac/roles/{role}', [UserRbacApiController::class, 'updateRolePermissions']);
        Route::post('/manage/menus', [UserRbacApiController::class, 'storeMenu']);
        Route::put('/manage/menus/{menuItem}', [UserRbacApiController::class, 'updateMenu']);
        Route::delete('/manage/menus/{menuItem}', [UserRbacApiController::class, 'destroyMenu']);
        Route::post('/manage/settings', [UserRbacApiController::class, 'updateSettings']);
        Route::get('/manage/audit-logs', [UserRbacApiController::class, 'auditLogs']);
    });
});
