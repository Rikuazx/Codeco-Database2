<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
  public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6',
        'role' => 'required|in:student,teacher,admin'
    ]);

    DB::beginTransaction();

    try {
        //  Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role ?? 'student',
        ]);

        //  Auto create student
        if ($request->role === 'student') {
            Student::create([
                'user_id' => $user->id,
                'status' => 'active',
                'type' => 'regular',
                'registration_date' => now(),
            ]);
        }

        //  Auto create teacher
        if ($request->role === 'teacher') {
            Teacher::create([
                'user_id' => $user->id,
                'priority_score' => 100,
                'specialization' => '',
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $user->update([
        'name' => $request->name,
        'email' => $request->email,
        'role' => $request->role,
    ]);

    return response()->json(['message' => 'Updated']);
}

public function destroy($id)
{
    User::findOrFail($id)->delete();

    return response()->json(['message' => 'Deleted']);
}

public function index()
{
    $users = \App\Models\User::with(['student', 'teacher'])->get();

    return response()->json($users);
}
}   
