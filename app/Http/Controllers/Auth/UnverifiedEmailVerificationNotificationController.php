<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnverifiedEmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification for unverified users.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::guard('unverified')->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('verification.notice', absolute: false));
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
