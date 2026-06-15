<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Role;
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
            // 🧠 Lookup role_id from RBAC roles table
            $role = Role::where('slug', $request->role)->first();

            //  Create user with role_id (no more legacy role string)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role_id' => $role ? $role->id : null,
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
                'data' => $user->load('role')
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

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        // 🧠 Support both role slug and role_id
        if ($request->has('role') && !$request->has('role_id')) {
            $role = Role::where('slug', $request->role)->first();
            $updateData['role_id'] = $role ? $role->id : $user->role_id;
        } elseif ($request->has('role_id')) {
            $updateData['role_id'] = $request->role_id;
        }

        $user->update($updateData);

        return response()->json(['message' => 'Updated', 'data' => $user->load('role')]);
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function index()
    {
        $users = User::with(['student', 'teacher', 'role'])->get();

        return response()->json($users);
    }
}
