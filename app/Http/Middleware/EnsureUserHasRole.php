<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Allow Super Admin to bypass role checks and access everything
        if ($user && $user->hasRole('super_admin')) {
            return $next($request);
        }

        abort_unless($user && $user->hasRole(...$roles), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
