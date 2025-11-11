<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\UnverifiedUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form with department data.
     */
    public function create(): View
    {
        $departments = Department::all();
        $geDepartment = Department::where('department_code', 'GE')->first();
        return view('auth.register', compact('departments', 'geDepartment'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the base email format first
        $request->validate([
            'first_name'    => ['required', 'string', 'max:255'],
            'middle_name'   => ['nullable', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'regex:/^[^@]+$/', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'course_id'     => ['required', 'exists:courses,id'],
            'password'      => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
            ],
        ]);

        // Append domain to email
        $fullEmail = strtolower(trim($request->email)) . '@brokenshire.edu.ph';

        // Check uniqueness of the full email in both unverified_users and users tables
        if (UnverifiedUser::where('email', $fullEmail)->exists()) {
            return back()->withErrors(['email' => 'This email is already registered and pending verification.'])->withInput();
        }

        if (\App\Models\User::where('email', $fullEmail)->exists()) {
            return back()->withErrors(['email' => 'This email is already registered.'])->withInput();
        }

        // Store in unverified_users table
        $unverifiedUser = UnverifiedUser::create([
            'first_name'    => $request->first_name,
            'middle_name'   => $request->middle_name,
            'last_name'     => $request->last_name,
            'email'         => $fullEmail,
            'password'      => Hash::make($request->password),
            'department_id' => $request->department_id,
            'course_id'     => $request->course_id,
        ]);

        // Fire the Registered event to send verification email
        event(new Registered($unverifiedUser));

        // Log in the unverified user using the 'unverified' guard
        Auth::guard('unverified')->login($unverifiedUser);

        return redirect()->route('unverified.verification.notice');
    }
}
