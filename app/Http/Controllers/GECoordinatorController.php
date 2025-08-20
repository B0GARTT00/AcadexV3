<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Models\Department;
use App\Models\Course;
use App\Models\UnverifiedUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class GECoordinatorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Get all available instructors who can teach GE subjects
     */
    public function getAvailableInstructors()
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }
        
        $geDepartment = Department::where('department_code', 'GE')->firstOrFail();
        
        $instructors = User::where('role', 0)
            ->where('is_active', true)
            ->where(function($query) use ($geDepartment) {
                $query->where('department_id', $geDepartment->id)
                      ->orWhere('can_teach_ge', true);
            })
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name'])
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->first_name . ' ' . ($user->middle_name ? $user->middle_name . ' ' : '') . $user->last_name)
                ];
            });
            
        return response()->json($instructors);
    }
    
    /**
     * Get all instructors assigned to a subject
     */
    public function getSubjectInstructors(Subject $subject)
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }
        
        // Ensure the subject is a GE subject
        if ($subject->course_id != 2) {
            return response()->json([], 403);
        }
        
        $instructors = $subject->instructors->map(function($instructor) {
            return [
                'id' => $instructor->id,
                'name' => $instructor->name
            ];
        });
        
        return response()->json($instructors);
    }

    // ============================
    // Instructor Management
    // ============================

    public function manageInstructors()
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }
        
        // GE Coordinator: show instructors from GE department AND those approved to teach GE subjects
        $geDepartment = Department::where('department_code', 'GE')->first();
        
        $instructors = User::where('role', 0)
            ->where(function($query) use ($geDepartment) {
                $query->where('department_id', $geDepartment->id)
                      ->orWhere('can_teach_ge', true);
            })
            ->orderBy('last_name')
            ->get();
            
        $pendingAccounts = UnverifiedUser::with('department', 'course')
            ->where('department_id', $geDepartment->id)
            ->get();
        
        return view('gecoordinator.manage-instructors', compact('instructors', 'pendingAccounts'));
    }

    public function storeInstructor(Request $request)
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }

        $request->validate([
            'first_name'    => 'required|string|max:255',
            'middle_name'   => 'nullable|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|string|regex:/^[^@]+$/|unique:unverified_users,email|max:255',
            'password'      => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'department_id' => 'required|exists:departments,id',
            'course_id'     => 'required|exists:courses,id',
        ]);

        $fullEmail = strtolower(trim($request->email)) . '@brokenshire.edu.ph';

        UnverifiedUser::create([
            'first_name'    => $request->first_name,
            'middle_name'   => $request->middle_name,
            'last_name'     => $request->last_name,
            'email'         => $fullEmail,
            'password'      => Hash::make($request->password),
            'department_id' => $request->department_id,
            'course_id'     => $request->course_id,
        ]);

        return redirect()->back()->with('status', 'Instructor account submitted for approval.');
    }

    public function deactivateInstructor($id)
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }
        
        $geDepartment = Department::where('department_code', 'GE')->first();
        $instructor = User::where('id', $id)
            ->where('role', 0)
            ->where('department_id', $geDepartment->id)
            ->firstOrFail();
        $instructor->update(['is_active' => false]);
        return redirect()->back()->with('success', 'Instructor deactivated successfully.');
    }

    public function activateInstructor($id)
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }
        
        $geDepartment = Department::where('department_code', 'GE')->first();
        $instructor = User::where('id', $id)
            ->where('role', 0)
            ->where('department_id', $geDepartment->id)
            ->firstOrFail();
        $instructor->update(['is_active' => true]);
        return redirect()->back()->with('success', 'Instructor activated successfully.');
    }

    // ============================
    // Subject Assignment
    // ============================

    public function assignSubjects()
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403, 'Unauthorized action.');
        }
        
        $academicPeriodId = session('active_academic_period_id');
        if (!$academicPeriodId) {
            $academicPeriodId = 1; // Default to 1 if not set
        }

        // Get subjects (GE, PD, NSTP, RS, PE, and universal subjects) for the current academic period
        $subjects = Subject::with('instructors')
            ->where(function($query) {
                $query->where('subject_code', 'LIKE', 'GE%')
                      ->orWhere('subject_code', 'LIKE', 'PD%')
                      ->orWhere('subject_code', 'LIKE', 'NSTP%')
                      ->orWhere('subject_code', 'LIKE', 'RS%')
                      ->orWhere('subject_code', 'LIKE', 'PE%')
                      ->orWhere('is_universal', true);
            })
            ->where('academic_period_id', $academicPeriodId) // Filter by current academic period
            ->where('is_deleted', false)
            ->orderBy('subject_code')
            ->get();
            
        // Group subjects by year level for the view
        $yearLevels = [];
        for ($i = 1; $i <= 4; $i++) {
            $yearLevels[$i] = $subjects->where('year_level', $i)->values();
        }
            
        // Get all GE instructors
        $geDepartment = Department::where('department_code', 'GE')->firstOrFail();
        
        // Get available instructors
        $instructors = User::where('role', 0)
            ->where(function($query) use ($geDepartment) {
                $query->where('department_id', $geDepartment->id)
                      ->orWhere('can_teach_ge', true);
            })
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get();

        // Add debug info to view
        $debugInfo = [
            'ge_department' => $geDepartment->toArray(),
            'subjects_count' => $subjects->count(),
            'year_levels' => array_map('count', $yearLevels),
            'instructors_count' => $instructors->count(),
            'academic_period_id' => $academicPeriodId
        ];

        return view('gecoordinator.assign-subjects', [
            'yearLevels' => $yearLevels, 
            'instructors' => $instructors,
            'debug' => $debugInfo  // Add debug info to view
        ]);
    }

    public function storeAssignedSubject(Request $request)
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'instructor_id' => 'required|exists:users,id',
        ]);

        $subject = Subject::where('id', $request->subject_id)
            ->where('course_id', 2) // Only General Education subjects for GE Coordinator
            ->firstOrFail();

        // Ensure the subject is managed by GE Coordinator (course_id = 2)
        if ($subject->course_id != 2) {
            return redirect()->back()->with('error', 'Only General Education subjects can be assigned by GE Coordinator.');
        }

        // Attach the instructor to the subject (many-to-many)
        $subject->instructors()->syncWithoutDetaching([$request->instructor_id]);

        return redirect()->back()->with('success', 'Instructor assigned to subject successfully.');
    }

    public function manageSchedule()
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }

        // Get active academic period from session
        $academicPeriodId = session('active_academic_period_id');

        // Get GE subjects for the current academic period
        $subjects = Subject::where('academic_period_id', $academicPeriodId)
            ->where('course_id', 2) // Assuming 2 is GE course_id
            ->with(['instructors', 'students'])
            ->orderBy('subject_code')
            ->get();

        return view('gecoordinator.manage-schedule', compact('subjects'));
    }

    public function reports()
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }

        // Get active academic period from session
        $academicPeriodId = session('active_academic_period_id');

        // Get GE subjects statistics
        $totalSubjects = Subject::where('academic_period_id', $academicPeriodId)
            ->where('course_id', 2)
            ->count();

        $assignedSubjects = Subject::where('academic_period_id', $academicPeriodId)
            ->where('course_id', 2)
            ->whereHas('instructors')
            ->count();

        $unassignedSubjects = $totalSubjects - $assignedSubjects;

        // Get instructor statistics
        $geDepartment = Department::where('department_code', 'GE')->first();
        $totalInstructors = User::where('role', 0)
            ->where(function($query) use ($geDepartment) {
                $query->where('department_id', $geDepartment->id ?? 0)
                      ->orWhere('can_teach_ge', true);
            })
            ->where('is_active', true)
            ->count();

        // Get student enrollment statistics
        $totalEnrollments = \DB::table('student_subjects')
            ->join('subjects', 'student_subjects.subject_id', '=', 'subjects.id')
            ->where('subjects.academic_period_id', $academicPeriodId)
            ->where('subjects.course_id', 2)
            ->where('student_subjects.is_deleted', false)
            ->count();

        // Get subjects by year level
        $subjectsByYear = Subject::where('academic_period_id', $academicPeriodId)
            ->where('course_id', 2)
            ->select('year_level', \DB::raw('count(*) as count'))
            ->groupBy('year_level')
            ->get()
            ->pluck('count', 'year_level')
            ->toArray();

        $reportData = [
            'total_subjects' => $totalSubjects,
            'assigned_subjects' => $assignedSubjects,
            'unassigned_subjects' => $unassignedSubjects,
            'total_instructors' => $totalInstructors,
            'total_enrollments' => $totalEnrollments,
            'subjects_by_year' => $subjectsByYear
        ];

        return view('gecoordinator.reports', compact('reportData'));
    }

    public function toggleAssignedSubject(Request $request)
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'instructor_id' => 'required|exists:users,id',
        ]);

        $subject = Subject::where('id', $request->subject_id)
            ->where('course_id', 2) // Only General Education subjects for GE Coordinator
            ->firstOrFail();
        
        // Ensure the subject is managed by GE Coordinator (course_id = 2)
        if ($subject->course_id != 2) {
            return response()->json(['error' => 'Only General Education subjects can be managed by GE Coordinator.'], 403);
        }

        // Check if we're assigning or unassigning
        if ($request->isMethod('delete')) {
            // Unassign the instructor
            $subject->instructors()->detach($request->instructor_id);
            return response()->json([
                'success' => true, 
                'message' => 'Instructor unassigned successfully.',
                'action' => 'unassigned'
            ]);
        } else {
            // Assign the instructor (if not already assigned)
            $subject->instructors()->syncWithoutDetaching([$request->instructor_id]);
            $instructor = User::find($request->instructor_id);
            
            return response()->json([
                'success' => true, 
                'message' => 'Instructor assigned successfully.',
                'action' => 'assigned',
                'instructor_name' => $instructor->name
            ]);
        }
    }

    // ============================
    // Grades Management
    // ============================

    public function viewGrades(Request $request)
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }
        
        $selectedInstructorId = $request->input('instructor_id');
        $selectedSubjectId = $request->input('subject_id');
        
        $academicPeriodId = session('active_academic_period_id');
        
        // If no academic period is set, use academic period 1 as default
        if (!$academicPeriodId) {
            $academicPeriodId = 1;
        }
        
        // Fetch all active instructors (regular instructors might teach GE subjects)
        $instructors = User::where([
            ['role', 0],
            ['is_active', true],
        ])
        ->orderBy('last_name')
        ->get();
    
        // Subjects are loaded only when an instructor is selected
        $subjects = [];
        if ($selectedInstructorId) {
            $subjects = Subject::where([
                ['instructor_id', $selectedInstructorId],
                ['academic_period_id', $academicPeriodId],
                ['is_deleted', false],
                ['course_id', 1], // Only General Education subjects for GE Coordinator
            ])
            ->orderBy('subject_code')
            ->get();
        }
    
        // Students and grades are only loaded when a subject is selected
        $students = [];
        if ($selectedSubjectId) {
            $subject = Subject::where([
                ['id', $selectedSubjectId],
                ['course_id', 1], // Only General Education subjects for GE Coordinator
            ])->firstOrFail();
    
            $students = $subject->students()
                ->with(['termGrades' => function ($q) use ($selectedSubjectId) {
                    $q->where('subject_id', $selectedSubjectId);
                }])
                ->get();
        }
    
        return view('gecoordinator.view-grades', [
            'instructors' => $instructors,
            'subjects' => $subjects,
            'students' => $students,
            'selectedInstructorId' => $selectedInstructorId,
            'selectedSubjectId' => $selectedSubjectId,
        ]);
    }

    // ============================
    // Student Management
    // ============================

    public function viewStudentsPerYear()
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }

        // GE Coordinator: students enrolled in General Education subjects
        $students = Student::with(['course', 'department'])
            ->whereHas('subjects', function($query) {
                $query->where('course_id', 1);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('gecoordinator.students-by-year', compact('students'));
    }

    // ============================
    // GE Assignment Request Management
    // ============================

    public function approveGERequest($id)
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }

        $request = \App\Models\GESubjectRequest::where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        // Update the request status
        $request->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // Get the instructor without changing their department
        $instructor = User::find($request->instructor_id);
        
        if ($instructor) {
            // Instead of changing the department, we can add a flag or role
            // to indicate they can teach GE subjects
            $instructor->can_teach_ge = true;
            $instructor->save();
        }

        return redirect()->back()->with('status', 'GE assignment request approved successfully. The instructor can now teach GE subjects.');
    }

    public function rejectGERequest($id)
    {
        if (!Auth::user()->isGECoordinator()) {
            abort(403);
        }

        $request = \App\Models\GESubjectRequest::where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        // Update the request status
        $request->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->back()->with('status', 'GE assignment request rejected successfully.');
    }
}