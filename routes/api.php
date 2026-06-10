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
use App\Http\Controllers\ScheduleChangeRequestController;
use App\Http\Controllers\TeacherRequestController;

// Users
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users', [UserController::class, 'index']);

// Teachers
Route::post('/teachers', [TeacherController::class, 'store']);
Route::get('/teachers', [TeacherController::class, 'index']);

// Teacher Requests (Student → Teacher)
Route::post('/teacher-requests', [TeacherRequestController::class, 'store']);
Route::get('/teacher-requests', [TeacherRequestController::class, 'index']);
Route::put('/teacher-requests/{id}/respond', [TeacherRequestController::class, 'respond']);

// Students
Route::put('/students/{id}', [StudentController::class, 'update']);
Route::post('/students', [StudentController::class, 'store']);
Route::get('/students', [StudentController::class, 'index']);

// Classes
Route::put('/classes/{id}', [ClassController::class, 'update']);
Route::delete('/classes/{id}', [ClassController::class, 'destroy']);
Route::post('/classes', [ClassController::class, 'store']);
Route::get('/classes', [ClassController::class, 'index']);
Route::get('/classes/{id}', [ClassController::class, 'show']);

// Class Sessions
Route::post('/sessions', [ClassSessionController::class, 'store']);
Route::post('/generate-sessions/{class_id}', [ClassSessionController::class, 'generateSessions']);
Route::post('/sessions/{id}/complete', [ClassSessionController::class, 'complete']);
Route::post('/sessions/{id}/auto-assign', [ClassSessionController::class, 'autoAssignTeacher']);
Route::put('/sessions/{id}', [ClassSessionController::class, 'update']);
Route::post('/assign-teacher', [ClassSessionController::class, 'assignTeacher']);
Route::get('/sessions', [ClassSessionController::class, 'index']);
Route::get('/teachers/{teacher_id}/sessions', [ClassSessionController::class, 'byTeacher']);
Route::delete('/sessions/{id}', [ClassSessionController::class, 'destroy']);

// Enrollments
Route::get('/enrollments', [EnrollmentController::class, 'index']);
Route::post('/enroll', [EnrollmentController::class, 'store']);
Route::get('/students/{student_id}/enrollments', [EnrollmentController::class, 'byStudent']);
Route::delete('/enrollments/{id}', [EnrollmentController::class, 'destroy']);

// Attendance
Route::post('/attendance', [AttendanceController::class, 'markAttendance']);

// Teacher Availability
Route::post('/teacher-availability', [TeacherAvailabilityController::class, 'store']);
Route::get('/teacher-availability', [TeacherAvailabilityController::class, 'all']);
Route::get('/teacher-availability/{teacher_id}', [TeacherAvailabilityController::class, 'show']);

// Schedule Change Requests
Route::post('/schedule-change-requests', [ScheduleChangeRequestController::class, 'store']);
Route::post('/schedule-change-requests/{id}/approve', [ScheduleChangeRequestController::class, 'approve']);
Route::post('/schedule-change-requests/{id}/reject', [ScheduleChangeRequestController::class, 'reject']);

// Feedback
Route::post('/feedback', [FeedbackController::class, 'store']);
Route::get('/feedback', [FeedbackController::class, 'index']);
Route::get('/teachers/{teacher_id}/feedback', [FeedbackController::class, 'byTeacher']);
Route::get('/students/{student_id}/feedback', [FeedbackController::class, 'byStudent']);
Route::get('/sessions/{session_id}/enrolled-students', [FeedbackController::class, 'enrolledStudents']);
Route::get('/teachers/{teacher_id}/salary', [FeedbackController::class, 'salaryHistory']);

// Certificates
Route::get('/certificates', [CertificateController::class, 'index']);
Route::post('/certificates', [CertificateController::class, 'store']);
Route::get('/certificates/{id}', [CertificateController::class, 'show']);
Route::get('/certificates/{id}/download', [CertificateController::class, 'download']);
Route::get('/test-certificate', [CertificateController::class, 'test']);
Route::get('/students/{student_id}/certificates', [CertificateController::class, 'byStudent']);

// Auth
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
