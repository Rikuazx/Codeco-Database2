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

public function stats()
{
    return response()->json([
        'total' => \App\Models\Teacher::count(),
    ]);
}

public function update(Request $request, $id)
{
    $teacher = \App\Models\Teacher::with('user')
        ->findOrFail($id);

    $teacher->update([
        'specialization' => $request->specialization,
        'priority_score' => $request->priority_score,
    ]);

    $teacher->user->update([
        'name' => $request->name,
        'email' => $request->email,
    ]);

    return response()->json([
        'message' => 'Teacher updated'
    ]);
}

public function destroy($id)
{
    $teacher = \App\Models\Teacher::with('user')
        ->findOrFail($id);

    $teacher->user()->delete();
    $teacher->delete();

    return response()->json([
        'message' => 'Teacher deleted'
    ]);
}
public function index()
{
    $teachers = \App\Models\Teacher::with('user')->get();
    return response()->json($teachers);
}
}
