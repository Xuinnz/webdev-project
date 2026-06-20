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
        // check if user is logged in
        if (!Session::has('user_id')) {
            return redirect()->route('login')->withErrors(['error' => 'Please log in to continue.']);
        }

        // if one role tried to access other rol's function
        if ($role && Session::get('user_role') !== $role) {
            
            $actualRole = Session::get('user_role');
            
            // if no role, make them login again
            if (!$actualRole) {
                Session::flush();
                return redirect()->route('login');
            }

            //redirect to their role's dashboard
            return redirect()->route($actualRole . '.dashboard')
                ->withErrors(['error' => 'Unauthorized access.']);
        }
        //valid request.
        return $next($request);
    }
}