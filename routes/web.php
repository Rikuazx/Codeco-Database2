<?php

use Illuminate\Support\Facades\Route;

Route::get('/users', function () {
    return view('users');
});

Route::get('/classes', function () {
    return view('classes');
});

Route::get('/sessions', function () {
    return view('sessions');
});

Route::get('/feedback', function () {
    return view('feedback');
});
Route::get('/admin-student', function () {
    return view('admin_student');
});
Route::get('/admin-teacher', function () {
    return view('admin_teacher');
});
