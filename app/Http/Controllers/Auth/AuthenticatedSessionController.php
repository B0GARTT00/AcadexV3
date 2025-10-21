<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Authenticate the user
        $request->authenticate();

        // Get the authenticated user
    /** @var User $user */
    $user = Auth::user();

        // Check if the user's account is active
        if ($user->is_active == 0) {
            // Log the user out if inactive
            Auth::logout();
            $request->session()->invalidate();  // Invalidate the session
            $request->session()->regenerateToken();  // Regenerate the CSRF token to prevent session fixation
            
            // Redirect to login with an error message
            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact the admin or your chairperson.',
            ]);
        }

        $this->sanitizeIntendedUrl($request, $user);

        // Regenerate the session to prevent session fixation
        $request->session()->regenerate();

        // Always clear any previous session academic period
        Session::forget('active_academic_period_id');

        // Require academic period selection for instructor or chairperson
        if (in_array($user->role, [0, 2])) {
            return redirect()->route('select.academicPeriod');
        }

        // Redirect VPAA to their dashboard
        if ($user->isVPAA()) {
            return redirect()->intended(route('vpaa.dashboard'));
        }

        // Redirect to the intended route (dashboard or other)
        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function sanitizeIntendedUrl(Request $request, User $user): void
    {
        $intended = $request->session()->get('url.intended');

        if (!$intended) {
            return;
        }

        if ($this->pointsToAdminArea($intended) && !Gate::forUser($user)->allows('admin')) {
            $request->session()->forget('url.intended');
        }
    }

    private function pointsToAdminArea(string $url): bool
    {
        $path = ltrim(parse_url($url, PHP_URL_PATH) ?? '', '/');

        if ($path === '') {
            return false;
        }

        return Str::startsWith($path, 'admin');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Log the user out
        Auth::guard('web')->logout();

        // Invalidate the session and regenerate the CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to the homepage
        return redirect('/');
    }
}
