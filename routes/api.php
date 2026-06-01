<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ClassSessionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\TeacherKpiController;

// Users
route::put('/users/{id}', [UserController::class, 'update']);
route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users', [UserController::class, 'index']);

// Teachers
Route::post('/teachers', [TeacherController::class, 'store']);
Route::get('/teachers', [TeacherController::class, 'index']);
Route::get('/teachers/stats', [TeacherController::class, 'stats']);
Route::put('/teachers/{id}', [TeacherController::class, 'update']);
Route::delete('/teachers/{id}', [TeacherController::class, 'destroy']);

// Teacher Availability
Route::post('/teacher-availability', [TeacherAvailabilityController::class, 'store']);
Route::get('/teacher-availability', [TeacherAvailabilityController::class, 'index']);
Route::delete('/teacher-availability/{id}', [TeacherAvailabilityController::class, 'destroy']);

// Teacher KPI
Route::post('/teacher-kpis/{teacher_id}/{month}/{year}',[TeacherKpiController::class, 'calculateMonthlyKpi']);
Route::get('/teacher-kpi', [TeacherKpiController::class, 'index']);

// Students
Route::put('/students/{id}', [StudentController::class, 'update']);
Route::post('/students', [StudentController::class, 'store']);
Route::get('/students', [StudentController::class, 'index']);
Route::get('/students/stats', [StudentController::class, 'stats']);
Route::delete('/students/{id}',[StudentController::class, 'destroy']);

// Classes
route::delete('/classes/{id}', [ClassController::class, 'destroy']);
Route::post('/classes', [ClassController::class, 'store']);
Route::get('/classes', [ClassController::class, 'index']);
Route::get('/classes/{id}', [ClassController::class, 'show']);
Route::put('/classes/{id}', [ClassController::class, 'update']);
// Class Sessions
Route::post('/generate-sessions/{class_id}', [ClassSessionController::class, 'generateSessions']);
Route::post('/sessions/{id}/complete', [ClassSessionController::class, 'complete']);
Route::post('/sessions/{id}/auto-assign', [ClassSessionController::class, 'autoAssignTeacher']);
Route::put('/sessions/{id}', [ClassSessionController::class, 'update']);
Route::post('/assign-teacher', [ClassSessionController::class, 'assignTeacher']);
Route::get('/sessions', [ClassSessionController::class, 'index']);
Route::delete('/sessions/{id}', [ClassSessionController::class, 'destroy']);

// Enrollments
Route::post('/enroll', [EnrollmentController::class, 'store']);

// Attendance
Route::post('/attendance', [AttendanceController::class, 'markAttendance']);

// Feedback
Route::post('/feedback', [FeedbackController::class, 'store']);
Route::get('/feedback', [FeedbackController::class, 'index']);

// Certificates
Route::get('/certificates', [CertificateController::class, 'index']);
Route::post('/certificates', [CertificateController::class, 'store']);
Route::get('/certificates/{id}', [CertificateController::class, 'show']);
Route::get('/certificates/{id}/download', [CertificateController::class, 'download']);
Route::get('/test-certificate', [CertificateController::class, 'test']);

// Auth
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
