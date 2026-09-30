<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnlyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect('/login');
        }
        
        // Check if user has ADMIN role
        if (!$user->roles()->where('name', 'ADMIN')->exists()) {
            abort(403, 'This action requires Super Admin privileges');
        }
        
        return $next($request);
    }
}
