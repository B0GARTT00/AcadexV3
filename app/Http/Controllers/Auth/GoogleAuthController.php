<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth page.
     *
     * @return RedirectResponse
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     *
     * @return RedirectResponse
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            // Log the incoming request for debugging
            Log::info('Google OAuth Callback Received', [
                'has_state' => request()->has('state'),
                'has_code' => request()->has('code'),
                'session_id' => session()->getId(),
            ]);

            // Use stateless mode to avoid session state mismatch issues
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $email = $googleUser->getEmail();
            $googleId = $googleUser->getId();
            
            // Validate email is not null
            if (!$email) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Unable to retrieve email from Google account.']);
            }
            
            // Only allow @brokenshire.edu.ph domain emails
            if (!str_ends_with($email, '@brokenshire.edu.ph')) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Only @brokenshire.edu.ph email addresses are allowed.']);
            }

            // Find user by google_id or email (active users only)
            $user = User::where(function ($query) use ($googleId, $email) {
                    $query->where('google_id', $googleId)
                          ->orWhere('email', $email);
                })
                ->where('is_active', true)
                ->first();

            // User not found - prevent auto-registration
            if (!$user) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'No account found. Please contact your administrator.']);
            }

            // Update google_id if not set
            if (!$user->google_id) {
                $user->update(['google_id' => $googleId]);
            }

            // Check if user already has an active session on another device (skip for admins)
            if ($user->role !== 3) { // 3 = admin role
                $deviceFingerprint = request()->input('device_fingerprint');
                if ($this->hasActiveSession($user->id, $deviceFingerprint)) {
                    return redirect()->route('login')->withErrors([
                        'email' => 'This account is already logged in on another device. Please logout from the other device first or contact your administrator.',
                    ]);
                }
            }

            // Log the user in
            Auth::login($user, true);

            // Store device fingerprint in session data
            $deviceFingerprint = request()->input('device_fingerprint');
            if ($deviceFingerprint) {
                session()->put('device_fingerprint', $deviceFingerprint);
                
                // Also update the database immediately
                DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->where('id', session()->getId())
                    ->update(['device_fingerprint' => $deviceFingerprint]);
            }

            // Fire login event for user_logs tracking
            event(new \Illuminate\Auth\Events\Login('web', $user, true));

            // Redirect to dashboard
            return redirect()->intended(route('dashboard'));

        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::error('Google OAuth Invalid State: ' . $e->getMessage());
            
            return redirect()->route('login')
                ->withErrors(['email' => 'Invalid OAuth state. Please try again.']);
                
        } catch (\Exception $e) {
            Log::error('Google OAuth Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')
                ->withErrors(['email' => 'Unable to login with Google. Please try again.']);
        }
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
        $expirationTimestamp = now()->subMinutes($sessionLifetime)->timestamp;
        
        // First, clean up expired sessions for this user
        DB::table('sessions')
            ->where('user_id', $userId)
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
}
