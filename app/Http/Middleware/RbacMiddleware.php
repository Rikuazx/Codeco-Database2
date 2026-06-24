<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;
use App\Models\User;

class RbacMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissionSlug): Response
    {
        // 🧠 1. Prioritize authenticated user (Sanctum or session-based)
        $roleSlug = null;

        if (auth()->check()) {
            $user = auth()->user();
            $user->loadMissing('role');
            $roleSlug = $user->role ? $user->role->slug : null;
        }

        // 🧠 2. Fallback to simulated session role (for development/backward compatibility)
        if (!$roleSlug) {
            $roleSlug = session('simulated_role');
        }

        // 🧠 3. No role found — deny access
        if (!$roleSlug) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Unauthenticated. Please login first.'
                ], 401);
            }
            return redirect('/login')->with('error', 'Please login first.');
        }

        // 🧠 4. Fetch the Role with its Permissions
        $role = Role::with('permissions')->where('slug', $roleSlug)->first();

        if (!$role) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Access Denied: Role not found.'
                ], 403);
            }
            return redirect('/')->with('error', 'Access Denied: Role not found.');
        }

        // 🧠 5. Check if role has the requested permission
        $hasPermission = $role->permissions->contains('slug', $permissionSlug);

        if (!$hasPermission) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => "Access Denied: You do not have the required permission ({$permissionSlug})."
                ], 403);
            }
            return redirect('/')->with('error', "Access Denied: You do not have the required permission ({$permissionSlug}) to access this page.");
        }

        return $next($request);
    }
}
