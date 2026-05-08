<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TeacherController extends Controller
{
   public function store(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    $teacher = \App\Models\Teacher::create([
        'user_id' => $request->user_id,
    ]);

    return response()->json($teacher);
}

public function index()
{
    return \App\Models\Teacher::with('user')->get();
}
}
