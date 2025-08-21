<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Department;
use App\Models\Subject;
use App\Models\UserLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ============================
    // Departments
    // ============================

    public function departments()
    {
        Gate::authorize('admin');

        $departments = Department::where('is_deleted', false)
            ->orderBy('department_code')
            ->get();

        return view('admin.departments', compact('departments'));
    }

    public function createDepartment()
    {
        Gate::authorize('admin');
        return view('admin.create-department');
    }

    public function storeDepartment(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'department_code' => 'required|string|max:50',
            'department_description' => 'required|string|max:255',
        ]);

        Department::create([
            'department_code' => $request->department_code,
            'department_description' => $request->department_description,
            'is_deleted' => false,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.departments')->with('success', 'Department added successfully.');
    }

    // ============================
    // Courses
    // ============================

    public function courses()
    {
        Gate::authorize('admin');
    
        $courses = Course::where('is_deleted', false)
            ->orderBy('course_code')
            ->get();
    
        // Pass departments for the modal
        $departments = Department::where('is_deleted', false)
            ->orderBy('department_code')
            ->get();
    
        return view('admin.courses', compact('courses', 'departments'));
    }
    

    public function createCourse()
    {
        Gate::authorize('admin');

        $departments = Department::where('is_deleted', false)
            ->orderBy('department_code')
            ->get();

        return view('admin.create-course', compact('departments'));
    }

    public function storeCourse(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'course_code' => 'required|string|max:50',
            'course_description' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);

        Course::create([
            'course_code' => $request->course_code,
            'course_description' => $request->course_description,
            'department_id' => $request->department_id,
            'is_deleted' => false,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.courses')->with('success', 'Course added successfully.');
    }

    // ============================
    // Subjects
    // ============================

    public function subjects()
    {
        Gate::authorize('admin');

        $subjects = Subject::with(['department', 'course', 'academicPeriod'])
            ->where('is_deleted', false)
            ->orderBy('subject_code')
            ->get();

        $departments = Department::where('is_deleted', false)
            ->orderBy('department_code')
            ->get();

        $courses = Course::where('is_deleted', false)
            ->orderBy('course_code')
            ->get();

        $academicPeriods = AcademicPeriod::orderBy('academic_year', 'desc')
            ->orderBy('semester')
            ->get();

        return view('admin.subjects', compact('subjects', 'departments', 'courses', 'academicPeriods'));
    }

    public function createSubject()
    {
        Gate::authorize('admin');

        $departments = Department::where('is_deleted', false)->orderBy('department_code')->get();
        $courses = Course::where('is_deleted', false)->orderBy('course_code')->get();
        $academicPeriods = AcademicPeriod::orderBy('academic_year', 'desc')->orderBy('semester')->get();

        return view('admin.create-subject', compact('departments', 'courses', 'academicPeriods'));
    }

    public function storeSubject(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'subject_code' => 'required|string|max:255|unique:subjects,subject_code',
            'subject_description' => 'required|string|max:255',
            'units' => 'required|integer|min:1|max:6',
            'year_level' => 'required|integer|min:1|max:5',
            'academic_period_id' => 'required|exists:academic_periods,id',
            'department_id' => 'required|exists:departments,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        Subject::create([
            'subject_code' => $request->subject_code,
            'subject_description' => $request->subject_description,
            'units' => $request->units,
            'year_level' => $request->year_level,
            'academic_period_id' => $request->academic_period_id,
            'department_id' => $request->department_id,
            'course_id' => $request->course_id,
            'is_deleted' => false,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.subjects')->with('success', 'Subject added successfully.');
    }

    // ============================
    // Academic Periods (legacy fallback view)
    // ============================

    public function academicPeriods()
    {
        Gate::authorize('admin');

        $periods = AcademicPeriod::orderBy('academic_year', 'desc')->orderBy('semester')->get();
        return view('admin.academic-periods', compact('periods'));
    }

    public function viewUserLogs(Request $request)
    {
        Gate::authorize('admin');

        $dateToday = now()->timezone(config('app.timezone'))->format('Y-m-d');
        $selectedDate = $request->input('date', $dateToday);

        $userLogs = UserLog::whereDate('created_at', $selectedDate)->get();

        return view('admin.user-logs', compact('userLogs', 'dateToday', 'selectedDate'));
    }


    public function viewUsers()
    {
        Gate::authorize('admin');

        $users = User::whereIn('role', [1, 2, 3, 5])
            ->orderBy('role', 'asc')
            ->get();

        $departments = Department::all();
        $courses = Course::all();

        return view('admin.users', compact('users', 'departments', 'courses'));
    }

    public function adminConfirmUserCreationWithPassword(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'confirm_password' => 'required|string',
        ]);

        // Get the currently authenticated user
        $user = Auth::user();

        // Check if the entered password matches the stored password
        if (Hash::check($request->confirm_password, $user->password)) {
            // If password matches, proceed with the action (e.g., store the new user or perform other actions)
            // Return a success response for AJAX
            return response()->json(['success' => true, 'message' => 'Password confirmed successfully']);
        }

        // If password is incorrect, return an error message
        return response()->json(['success' => false, 'message' => 'The password you entered is incorrect.']);
    }

    
    public function storeUser(Request $request)
    {
        $validationRules = [
            'first_name'    => ['required', 'string', 'max:255'],
            'middle_name'   => ['nullable', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'regex:/^[^@]+$/', 'max:255', 'unique:users,email'],
            'role'          => ['required', 'in:1,2,3,5'],
            'password'      => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ];

        // Add department validation for non-admin and non-VPAA roles
        if ($request->role != 3 && $request->role != 5) {
            $validationRules['department_id'] = ['required', 'exists:departments,id'];
            
            // Course validation based on role
            if ($request->role == 1) { // Chairperson
                $validationRules['course_id'] = ['required', 'exists:courses,id'];
            } else if ($request->role == 2) { // Dean
                $validationRules['course_id'] = ['nullable', 'exists:courses,id'];
            }
        }

        $request->validate($validationRules);

        $fullEmail = $request->email . '@brokenshire.edu.ph';

        $userData = [
            'first_name'    => $request->first_name,
            'middle_name'   => $request->middle_name,
            'last_name'     => $request->last_name,
            'email'         => $fullEmail,
            'password'      => Hash::make($request->password),
            'role'          => $request->role,
            'is_active'     => true,
        ];

        // Add department for non-admin and non-VPAA roles
        if ($request->role != 3 && $request->role != 5) {
            $userData['department_id'] = $request->department_id;
            
            // Add course_id only if it's provided (for Dean) or required (for Chairperson)
            if ($request->role == 1 || ($request->role == 2 && $request->has('course_id'))) {
                $userData['course_id'] = $request->course_id;
            }
        }

        User::create($userData);

        return redirect()->route('admin.users')->with('success', 'User created successfully.');
    }
}
