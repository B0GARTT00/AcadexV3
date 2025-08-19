<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\UnverifiedUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $request->validate([
            'first_name'    => ['required', 'string', 'max:255'],
            'middle_name'   => ['nullable', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'regex:/^[^@]+$/', 'max:255', 'unique:unverified_users,email'],
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

        // Store in unverified_users table
        UnverifiedUser::create([
            'first_name'    => $request->first_name,
            'middle_name'   => $request->middle_name,
            'last_name'     => $request->last_name,
            'email'         => $fullEmail,
            'password'      => Hash::make($request->password),
            'department_id' => $request->department_id,
            'course_id'     => $request->course_id,
        ]);

        // Check if the selected department is GE
        $isGEDepartment = Department::where('id', $request->department_id)
            ->where('department_code', 'GE')
            ->exists();

        $approvalMessage = $isGEDepartment 
            ? 'Your account request has been submitted and is pending GE Coordinator approval.'
            : 'Your account request has been submitted and is pending Department Chairperson approval.';

        return redirect()->route('login')->with('status', $approvalMessage);
    }
}
