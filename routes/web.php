<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// ============================================================
// 🔐 Authentication Routes (Guest only)
// ============================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', [AuthController::class, 'webLogin']);
});

Route::post('/logout', [AuthController::class, 'webLogout'])->middleware('auth')->name('logout');

// ============================================================
// 🏡 Home
// ============================================================
Route::get('/', function () {
    if (auth()->check()) {
        $role = auth()->user()->role ? auth()->user()->role->slug : null;
        return match ($role) {
            'admin'   => redirect('/admin/users'),
            'teacher' => redirect('/teacher/my-classes'),
            'student' => redirect('/student/my-classes'),
            default   => view('welcome'),
        };
    }
    return view('welcome');
})->name('home');

// Simulated login (backward compatibility — development only)
Route::post('/simulate-login', function (\Illuminate\Http\Request $request) {
    $role = $request->input('role');
    if (in_array($role, ['admin', 'teacher', 'student'])) {
        session(['simulated_role' => $role]);
        return redirect('/')->with('success', "Simulated login successfully as " . strtoupper($role));
    }
    return redirect('/')->with('error', 'Invalid role selected');
});

Route::post('/simulate-logout', function () {
    session()->forget('simulated_role');
    return redirect('/')->with('success', 'Logged out successfully');
});

// ============================================================
// 🛡️ Admin Views (auth + RBAC)
// ============================================================
Route::middleware(['auth', 'rbac:manage_users'])->prefix('admin')->group(function () {
    Route::get('/users', function () { return view('admin.users'); });
    Route::get('/students', function () { return view('admin.students'); });
    Route::get('/classes', function () { return view('admin.classes'); });
    Route::get('/enrollments', function () { return view('admin.enrollments'); });
    Route::get('/sessions', function () { return view('admin.sessions'); });
    Route::get('/certificates', function () { return view('admin.certificates'); });
    Route::get('/schedule', function () { return view('admin.schedule'); });
    Route::get('/kpi', function () { return view('admin.kpi'); });
});

// ============================================================
// 🛡️ Teacher Views (auth + RBAC)
// ============================================================
Route::middleware(['auth', 'rbac:submit_availability'])->prefix('teacher')->group(function () {
    Route::get('/my-classes',   function () { return view('teacher.my-classes'); });
    Route::get('/availability', function () { return view('teacher.availability'); });
    Route::get('/booking',      function () { return view('teacher.booking'); });
    Route::get('/requests',     function () { return view('teacher.requests'); });
    Route::get('/feedback',     function () { return view('teacher.feedback'); });
    Route::get('/salary',       function () { return view('teacher.salary'); });
    Route::get('/kpi',          function () { return view('teacher.kpi'); });
});

// ============================================================
// 🛡️ Student Views (auth + RBAC)
// ============================================================
Route::middleware(['auth', 'rbac:view_classes'])->prefix('student')->group(function () {
    Route::get('/my-classes',      function () { return view('student.my-classes'); });
    Route::get('/teachers',        function () { return view('student.teachers'); });
    Route::get('/my-certificates', function () { return view('student.my-certificates'); });
    Route::get('/my-feedback',     function () { return view('student.my-feedback'); });
});