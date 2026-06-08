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
        // 🧠 1. Get active role from simulated session or authenticated user
        $roleSlug = session('simulated_role');

        if (!$roleSlug && auth()->check()) {
            $user = auth()->user();
            if ($user->relationLoaded('role') && $user->role) {
                $roleSlug = $user->role->slug;
            } else {
                $roleSlug = $user->role ? $user->role->slug : $user->role;
            }
        }

        // Default to student if not logged in
        if (!$roleSlug) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Access Denied: Please simulate login first.'
                ], 401);
            }
            return redirect('/')->with('error', 'Please simulate login first.');
        }

        // 🧠 2. Fetch the Role with its Permissions
        $role = Role::with('permissions')->where('slug', $roleSlug)->first();

        if (!$role) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Access Denied: Role not found.'
                ], 403);
            }
            return redirect('/')->with('error', 'Access Denied: Role not found.');
        }

        // 🧠 3. Check if role has the requested permission
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
