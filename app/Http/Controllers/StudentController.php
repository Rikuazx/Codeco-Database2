<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentController extends Controller
{
   public function store(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'status' => 'required',
        'type' => 'required',
    ]);

    $student = \App\Models\Student::create([
        'user_id' => $request->user_id,
        'status' => $request->status,
        'type' => $request->type,
        'registration_date' => now(),
    ]);

    return response()->json($student);
}
}
