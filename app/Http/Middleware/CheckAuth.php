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
        //check if logged in
        if (!Session::has('user_id')) {
            return redirect()->route('login')->withErrors(['error' => 'Please log in to continue.']);
        }

        $userRole = Session::get('user_role');

        if ($role && $userRole !== $role) {
            
            // if session somehow lost the role but kept user_id
            if (!$userRole) {
                Session::flush();
                return redirect()->route('login');
            }

            // redirect them to their role's dashboard
            return redirect()->route($userRole . '.dashboard')
                ->withErrors(['error' => 'Unauthorized access.']);
        }
        //on boarding middleware
        if ($actualRole === 'doctor') {
            $hasProfile = Session::has('profile_id');
            $isOnboardingRoute = $request->routeIs('doctor.onboarding') || $request->routeIs('doctor.onboarding.submit');

            //if no doctor profile yet, it means the doctor is not yet completed onboarding
            if (!$hasProfile && !$isOnboardingRoute) {
                return redirect()->route('doctor.onboarding')
                    ->withErrors(['error' => 'Please complete your profile first.']);
            }

            //if they alr have a profile and tried to visit onboarding
            if ($hasProfile && $isOnboardingRoute) {
                return redirect()->route('doctor.dashboard');
            }
        }
        //valid request
        return $next($request);
    }
}