<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Check if user already has an active session on another device (skip for admins)
        if ($user->role !== 3) { // 3 = admin role
            $deviceFingerprint = $request->input('device_fingerprint');
            if ($this->hasActiveSession($user->id, $deviceFingerprint)) {
                // Log out the current attempt
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirect back to login with error message
                return redirect()->route('login')->withErrors([
                    'email' => 'This account is already logged in on another device. Please logout from the other device first or contact your administrator.',
                ]);
            }
        }

        $this->sanitizeIntendedUrl($request, $user);

        // Regenerate the session to prevent session fixation
        $request->session()->regenerate();

        // Store device fingerprint in session data (will be saved with session)
        if ($request->has('device_fingerprint')) {
            $deviceFingerprint = $request->input('device_fingerprint');
            $request->session()->put('device_fingerprint', $deviceFingerprint);
            
            // Also update the database immediately
            DB::table('sessions')
                ->where('id', $request->session()->getId())
                ->update(['device_fingerprint' => $deviceFingerprint]);
        }

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
     * Check if user already has an active session.
     *
     * @param int $userId The user ID to check
     * @param string|null $currentFingerprint The device fingerprint from the current login attempt
     * @return bool True if user has an active session, false otherwise
     */
    private function hasActiveSession(int $userId, ?string $currentFingerprint = null): bool
    {
        // Calculate the expiration timestamp based on session lifetime
        $sessionLifetime = config('session.lifetime', 120); // in minutes
        $expirationTimestamp = now()->subMinutes($sessionLifetime)->getTimestamp();
        
        // First, clean up ALL expired sessions (not just for this user)
        DB::table('sessions')
            ->where('last_activity', '<', $expirationTimestamp)
            ->delete();
        
        // Check if there are any active sessions for this user
        $activeSessions = DB::table('sessions')
            ->where('user_id', $userId)
            ->where('last_activity', '>=', $expirationTimestamp)
            ->get();
        
        // If no active sessions, allow login
        if ($activeSessions->isEmpty()) {
            return false;
        }
        
        // If no fingerprint provided, fall back to old behavior
        if (!$currentFingerprint) {
            return !$activeSessions->isEmpty();
        }
        
        // Separate sessions into same-device and different-device
        $sameDeviceSessions = [];
        $differentDeviceSessions = [];
        
        foreach ($activeSessions as $session) {
            // Primary check: Use device fingerprint if available
            if ($session->device_fingerprint && $currentFingerprint) {
                $isSameDevice = ($session->device_fingerprint === $currentFingerprint);
            } else {
                // Fallback: Use browser fingerprint + IP if device fingerprint not available
                $agent = new \Jenssegers\Agent\Agent();
                $agent->setUserAgent(request()->userAgent());
                $currentBrowser = $agent->browser();
                $currentPlatform = $agent->platform();
                $currentDeviceType = $agent->isDesktop() ? 'Desktop' : ($agent->isTablet() ? 'Tablet' : 'Mobile');
                $currentIp = request()->ip();
                
                $fingerprintMatches = (
                    $session->browser === $currentBrowser &&
                    $session->platform === $currentPlatform &&
                    $session->device_type === $currentDeviceType
                );
                
                $ipMatches = ($session->ip_address === $currentIp);
                $isSameDevice = $fingerprintMatches && $ipMatches;
            }
            
            if ($isSameDevice) {
                $sameDeviceSessions[] = $session;
            } else {
                $differentDeviceSessions[] = $session;
            }
        }
        
        // If there are active sessions from different devices, block login
        if (!empty($differentDeviceSessions)) {
            return true;
        }
        
        // If only same-device sessions exist, delete them to allow re-login
        if (!empty($sameDeviceSessions)) {
            $sessionIds = array_map(fn($s) => $s->id, $sameDeviceSessions);
            DB::table('sessions')->whereIn('id', $sessionIds)->delete();
            return false;
        }
        
        return false;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Get the user ID and session ID before destroying
        $userId = Auth::id();
        $sessionId = $request->session()->getId();
        
        // Log the user out
        Auth::guard('web')->logout();

        // Delete ALL sessions for this user to ensure clean logout
        if ($userId) {
            DB::table('sessions')->where('user_id', $userId)->delete();
        } elseif ($sessionId) {
            // Fallback: delete just this session if user_id not available
            DB::table('sessions')->where('id', $sessionId)->delete();
        }

        // Invalidate the session and regenerate the CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to the homepage
        return redirect('/');
    }
}
