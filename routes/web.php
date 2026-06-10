<?php

use Illuminate\Support\Facades\Route;

// 🏡 Simulated Authentication Home
Route::get('/', function () {
    return view('welcome');
})->name('home');

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

// 🛡️ Admin Views
Route::middleware(['rbac:manage_users'])->prefix('admin')->group(function () {
    Route::get('/users', function () { return view('admin.users'); });
    Route::get('/students', function () { return view('admin.students'); });
    Route::get('/classes', function () { return view('admin.classes'); });
    Route::get('/enrollments', function () { return view('admin.enrollments'); });
    Route::get('/sessions', function () { return view('admin.sessions'); });
    Route::get('/certificates', function () { return view('admin.certificates'); });
    Route::get('/schedule', function () { return view('admin.schedule'); });
});

// 🛡️ Teacher Views
Route::middleware(['rbac:submit_availability'])->prefix('teacher')->group(function () {
    Route::get('/my-classes',   function () { return view('teacher.my-classes'); });
    Route::get('/availability', function () { return view('teacher.availability'); });
    Route::get('/feedback',     function () { return view('teacher.feedback'); });
    Route::get('/salary',       function () { return view('teacher.salary'); });
});

// 🛡️ Student Views
Route::middleware(['rbac:view_classes'])->prefix('student')->group(function () {
    Route::get('/my-classes',      function () { return view('student.my-classes'); });
    Route::get('/teachers',        function () { return view('student.teachers'); });
    Route::get('/my-certificates', function () { return view('student.my-certificates'); });
    Route::get('/my-feedback',     function () { return view('student.my-feedback'); });
});