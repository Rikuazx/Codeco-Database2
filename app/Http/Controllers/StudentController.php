<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
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
public function update(Request $request, $id)
{
    $student = \App\Models\Student::with('user')
        ->findOrFail($id);

    $student->update([
        'status' => $request->status,
        'type' => $request->type,
    ]);

    $student->user->update([
        'name' => $request->name,
        'email' => $request->email,
    ]);

    if ($request->filled('password')) {

        $student->user->update([
            'password' => bcrypt($request->password)
        ]);
    }

    return response()->json([
        'message' => 'Student updated successfully'
    ]);
}


public function stats()
{
    return response()->json([
        // Total
        'total_students' => \App\Models\Student::count(),

        // Status
        'active_students' => \App\Models\Student::where('status', 'active')->count(),
        'stopped_students' => \App\Models\Student::where('status', 'stopped')->count(),
        'hibernating_students' => \App\Models\Student::where('status', 'hibernating')->count(),

        // Type
        'regular_students' => \App\Models\Student::where('type', 'regular')->count(),
        'weekend_students' => \App\Models\Student::where('type', 'weekend')->count(),
    ]);
    {
    return response()->json([
        'total_students' => Student::count(),

        'status' => [
            'active' => Student::where('status', 'active')->count(),
            'stopped' => Student::where('status', 'stopped')->count(),
            'hibernating' => Student::where('status', 'hibernating')->count(),
        ],

        'type' => [
            'regular' => Student::where('type', 'regular')->count(),
            'weekend' => Student::where('type', 'weekend')->count(),
        ]
    ]);
}
}

public function destroy($id)
{
    $student = \App\Models\Student::with('user')
        ->findOrFail($id);

    $student->user()->delete();

    $student->delete();

    return response()->json([
        'message' => 'Student deleted successfully'
    ]);
}

public function index()
{
    $students = \App\Models\Student::with('user')->get();
    return response()->json($students);
}
}
