<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ClassSessionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\CertificateController;

Route::post('/generate-sessions/{class_id}', [ClassSessionController::class, 'generateSessions']);
Route::post('/enroll', [EnrollmentController::class, 'store']);
Route::post('/attendance', [AttendanceController::class, 'markAttendance']);
Route::post('/teacher-availability', [TeacherAvailabilityController::class, 'store']);
Route::post('/feedback', [FeedbackController::class, 'store']);
Route::post('/sessions/{id}/complete', [ClassSessionController::class, 'complete']);
Route::post('/sessions/{id}/auto-assign', [ClassSessionController::class, 'autoAssignTeacher']);
//certificate
Route::get('/certificates', [CertificateController::class, 'index']);
Route::post('/certificates', [CertificateController::class, 'store']);
Route::get('/certificates/{id}', [CertificateController::class, 'show']);
Route::get('/certificates/{id}/download', [CertificateController::class, 'download']);
Route::get('/test-certificate', [CertificateController::class, 'test']);


Route::post('/assign-teacher', [ClassSessionController::class, 'assignTeacher']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
