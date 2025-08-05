<?php

namespace App\Http\Controllers\GECoordinator;

use App\Http\Controllers\Controller;
use App\Models\UnverifiedUser;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountApprovalController extends Controller
{
    /**
     * Display a list of all pending GE instructor accounts for approval.
     */
    public function index(): View
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }

        // Get GE department
        $geDepartment = Department::where('department_code', 'GE')->first();
        
        // Eager-load related department and course for display, filtered by GE department
        $pendingAccounts = UnverifiedUser::with(['department', 'course'])
            ->where('department_id', $geDepartment->id)
            ->get();

        return view('gecoordinator.manage-instructors', compact('pendingAccounts'));
    }

    /**
     * Approve a pending GE instructor and migrate their data to the main users table.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function approve(int $id): RedirectResponse
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }

        // Get GE department
        $geDepartment = Department::where('department_code', 'GE')->first();

        $pending = UnverifiedUser::where('id', $id)
            ->where('department_id', $geDepartment->id)
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

        return back()->with('status', 'GE Instructor account has been approved successfully.');
    }

    /**
     * Reject and delete a pending GE instructor account request.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function reject(int $id): RedirectResponse
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }

        // Get GE department
        $geDepartment = Department::where('department_code', 'GE')->first();

        $pending = UnverifiedUser::where('id', $id)
            ->where('department_id', $geDepartment->id)
            ->firstOrFail();
            
        $pending->delete();

        return back()->with('status', 'GE Instructor account request has been rejected and removed.');
    }
} 