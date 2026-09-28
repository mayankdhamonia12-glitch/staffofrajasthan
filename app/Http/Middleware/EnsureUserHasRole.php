<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict access to routes based on the authenticated user's role.
 *
 * Usage in routes:
 *   ->middleware('role:candidate')
 *   ->middleware('role:employer')
 *   ->middleware('role:admin,owner')
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Only route-authored role values should reach this middleware. Treat an
        // invalid value as a configuration error rather than silently widening
        // access.
        $allowedRoles = array_map(function (string $role): UserRole {
            return UserRole::tryFrom($role)
                ?? throw new \LogicException("Unknown user role [{$role}].");
        }, $roles);

        if (! $user->hasAnyRole(...$allowedRoles)) {
            abort(403, 'You are not authorized to access this page.');
        }

        return $next($request);
    }
}
