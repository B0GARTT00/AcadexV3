<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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

            // Log the user in
            Auth::login($user, true);

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
}
