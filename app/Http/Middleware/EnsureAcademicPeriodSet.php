<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAcademicPeriodSet
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $isInstructor = $user && $user->role === 0;
        $isGeCoordinator = $user && $user->role === 4; // Add check for GE Coordinator role (assuming 4 is the role ID for GE Coordinator)
        
        if (
            Auth::check() &&
            ($isInstructor || $isGeCoordinator) && // Check both Instructor and GE Coordinator roles
            !session()->has('active_academic_period_id') &&
            !$request->is('select-academic-period') &&
            !$request->is('set-academic-period')
        ) {
            return redirect()->route('select.academicPeriod');
        }

        return $next($request);
    }
}
