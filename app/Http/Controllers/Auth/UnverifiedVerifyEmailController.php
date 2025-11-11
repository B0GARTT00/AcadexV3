<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\UnverifiedUser;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnverifiedVerifyEmailController extends Controller
{
    /**
     * Mark the unverified user's email address as verified.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // Find the user by ID from the route
        $user = UnverifiedUser::findOrFail($request->route('id'));

        // Verify the hash matches
        if (! hash_equals(
            (string) $request->route('hash'),
            sha1($user->getEmailForVerification())
        )) {
            abort(403, 'Invalid verification link.');
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            // Check if the selected department is GE
            $isGEDepartment = Department::where('id', $user->department_id)
                ->where('department_code', 'GE')
                ->exists();

            $approvalMessage = $isGEDepartment 
                ? 'Your email is already verified! Your account request is pending GE Coordinator approval.'
                : 'Your email is already verified! Your account request is pending Department Chairperson approval.';

            return redirect()->route('login')->with('status', $approvalMessage);
        }

        // Mark email as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Check if the selected department is GE
        $isGEDepartment = Department::where('id', $user->department_id)
            ->where('department_code', 'GE')
            ->exists();

        $approvalMessage = $isGEDepartment 
            ? 'Your email has been verified successfully! Your account request is now pending GE Coordinator approval.'
            : 'Your email has been verified successfully! Your account request is now pending Department Chairperson approval.';

        return redirect()->route('login')->with('status', $approvalMessage);
    }
}
