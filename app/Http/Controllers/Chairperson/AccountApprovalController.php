<?php

namespace App\Http\Controllers\Chairperson;

use App\Http\Controllers\Controller;
use App\Models\UnverifiedUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountApprovalController extends Controller
{
    /**
     * Display a list of all pending instructor accounts for approval.
     */
    public function index(): View
    {
        Gate::authorize('chairperson');

        // Get GE department to exclude it
        $geDepartment = \App\Models\Department::where('department_code', 'GE')->first();

        // Eager-load related department and course for display, filtered by chairperson's department and course
        // Exclude GE department instructors
        $pendingAccounts = UnverifiedUser::with(['department', 'course'])
            ->where('department_id', Auth::user()->department_id)
            ->where('course_id', Auth::user()->course_id)
            ->where('department_id', '!=', $geDepartment->id)
            ->get();

        return view('chairperson.manage-instructors', compact('pendingAccounts'));
    }

    /**
     * Approve a pending instructor and migrate their data to the main users table.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function approve(int $id): RedirectResponse
    {
        Gate::authorize('chairperson');

        // Get GE department to exclude it
        $geDepartment = \App\Models\Department::where('department_code', 'GE')->first();

        $pending = UnverifiedUser::where('id', $id)
            ->where('department_id', Auth::user()->department_id)
            ->where('course_id', Auth::user()->course_id)
            ->where('department_id', '!=', $geDepartment->id)
            ->firstOrFail();

        // Transfer to the main users table
        User::create([
            'first_name'    => $pending->first_name,
            'middle_name'   => $pending->middle_name,
            'last_name'     => $pending->last_name,
            'email'         => $pending->email,
            'password'      => $pending->password, // Already hashed
            'department_id' => $pending->department_id,
            'course_id'     => $pending->course_id,
            'role'          => 0, // Instructor role
            'is_active'     => true,
        ]);

        // Remove from unverified list
        $pending->delete();

        return back()->with('status', 'Instructor account has been approved successfully.');
    }

    /**
     * Reject and delete a pending instructor account request.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function reject(int $id): RedirectResponse
    {
        Gate::authorize('chairperson');

        // Get GE department to exclude it
        $geDepartment = \App\Models\Department::where('department_code', 'GE')->first();

        $pending = UnverifiedUser::where('id', $id)
            ->where('department_id', Auth::user()->department_id)
            ->where('course_id', Auth::user()->course_id)
            ->where('department_id', '!=', $geDepartment->id)
            ->firstOrFail();
            
        $pending->delete();

        return back()->with('status', 'Instructor account request has been rejected and removed.');
    }
}
