<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user (API).
     *
     * POST /api/register
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|in:student,teacher,admin',
        ]);

        DB::beginTransaction();

        try {
            $role = Role::where('slug', $request->role)->first();

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role_id'  => $role ? $role->id : null,
            ]);

            // Auto-create student profile
            if ($request->role === 'student') {
                Student::create([
                    'user_id'           => $user->id,
                    'status'            => 'active',
                    'type'              => 'regular',
                    'registration_date' => now(),
                ]);
            }

            // Auto-create teacher profile
            if ($request->role === 'teacher') {
                Teacher::create([
                    'user_id'        => $user->id,
                    'priority_score' => 100,
                    'specialization' => '',
                ]);
            }

            DB::commit();

            // Generate Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            $user->load('role.permissions');

            return response()->json([
                'message'     => 'Registration successful',
                'user'        => $user->makeHidden(['password']),
                'permissions' => $user->role ? $user->role->permissions->pluck('slug') : [],
                'token'       => $token,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error'   => 'Registration failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login with email & password (API).
     *
     * POST /api/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with('role.permissions')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke all previous tokens (single-device login)
        $user->tokens()->delete();

        // Create new Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'     => 'Login successful',
            'user'        => $user->makeHidden(['password']),
            'permissions' => $user->role ? $user->role->permissions->pluck('slug') : [],
            'token'       => $token,
        ]);
    }

    /**
     * Logout — revoke current token (API).
     *
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user profile with role & permissions (API).
     *
     * GET /api/me
     */
    public function me(Request $request)
    {
        $user = $request->user()->load(['role.permissions', 'student', 'teacher']);

        return response()->json([
            'user'        => $user->makeHidden(['password']),
            'permissions' => $user->role ? $user->role->permissions->pluck('slug') : [],
        ]);
    }

    /**
     * Web login — session-based authentication.
     *
     * POST /login
     */
    public function webLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $role = $user->role ? $user->role->slug : null;

            // Set simulated_role for backward compatibility with existing views
            if ($role) {
                session(['simulated_role' => $role]);
            }

            // Redirect based on role
            return match ($role) {
                'admin'   => redirect('/admin/users')->with('success', 'Welcome back, ' . $user->name . '!'),
                'teacher' => redirect('/teacher/my-classes')->with('success', 'Welcome back, ' . $user->name . '!'),
                'student' => redirect('/student/my-classes')->with('success', 'Welcome back, ' . $user->name . '!'),
                default   => redirect('/')->with('success', 'Login successful!'),
            };
        }

        return back()->withErrors([
            'email' => 'The provided credentials are incorrect.',
        ])->onlyInput('email');
    }

    /**
     * Web register — create account via web form.
     *
     * POST /register
     */
    public function webRegister(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|in:student,teacher',
        ]);

        DB::beginTransaction();

        try {
            $role = Role::where('slug', $request->role)->first();

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role_id'  => $role ? $role->id : null,
            ]);

            if ($request->role === 'student') {
                Student::create([
                    'user_id'           => $user->id,
                    'status'            => 'active',
                    'type'              => 'regular',
                    'registration_date' => now(),
                ]);
            }

            if ($request->role === 'teacher') {
                Teacher::create([
                    'user_id'        => $user->id,
                    'priority_score' => 100,
                    'specialization' => '',
                ]);
            }

            DB::commit();

            Auth::login($user);
            $request->session()->regenerate();

            if ($role) {
                session(['simulated_role' => $role->slug]);
            }

            return match ($request->role) {
                'teacher' => redirect('/teacher/my-classes')->with('success', 'Account created! Welcome, ' . $user->name . '!'),
                'student' => redirect('/student/my-classes')->with('success', 'Account created! Welcome, ' . $user->name . '!'),
                default   => redirect('/')->with('success', 'Account created successfully!'),
            };

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Web logout — destroy session.
     *
     * POST /logout
     */
    public function webLogout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->forget('simulated_role');

        return redirect('/login')->with('success', 'You have been logged out.');
    }
}
