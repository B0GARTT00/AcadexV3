<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnverifiedLogoutController extends Controller
{
    /**
     * Destroy an unverified user authentication session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('unverified')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
