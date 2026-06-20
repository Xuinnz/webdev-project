<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;

class CheckAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string|null  $role
     */
    public function handle(Request $request, Closure $next, $role = null): Response
    {
        // 1. Check if the user is logged in at all
        if (!Session::has('user_id')) {
            return redirect()->route('login')->withErrors(['error' => 'Please log in to continue.']);
        }

        // 2. If a specific role is required for this route, verify it
        if ($role && Session::get('user_role') !== $role) {
            
            // If they are logged in but in the wrong place, send them to their own dashboard
            $actualRole = Session::get('user_role');
            
            // Fallback just in case the role isn't set properly
            if (!$actualRole) {
                Session::flush();
                return redirect()->route('login');
            }

            return redirect()->route($actualRole . '.dashboard')
                ->withErrors(['error' => 'Unauthorized access.']);
        }

        // 3. All checks passed, proceed to the controller
        return $next($request);
    }
}