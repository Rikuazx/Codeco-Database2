<?php

use Illuminate\Support\Facades\Route;

Route::get('/users', function () {
    return view('users');
});

Route::get('/enrollments', function () {
    return view('enrollments');
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

Route::get('/attendance', function () {
    return view('attendance');
});

Route::get('/students', function () {
    return view('students');
});

Route::get('/certificates', function () {
    return view('certificates');
});

Route::get('/my-certificates', function () {
    return view('my-certificates');
});