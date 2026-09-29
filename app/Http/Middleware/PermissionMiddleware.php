<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level permission enforcement.
 *
 * Usage: ->middleware('permission:sales.create')
 *        ->middleware('permission:sales.create,sales.edit')  (any of)
 *
 * This is the authoritative check. Buttons may be hidden in the UI, but a
 * request that reaches a protected route without the permission is rejected
 * here regardless of what the HTML looked like.
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasAnyPermission($permissions)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
