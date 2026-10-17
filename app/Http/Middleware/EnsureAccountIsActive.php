<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /** @param Closure(Request): (Response) $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSuspended() || $request->routeIs('account.suspended', 'logout')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'This account is currently suspended.'], 403);
        }

        return redirect()->route('account.suspended');
    }
}
