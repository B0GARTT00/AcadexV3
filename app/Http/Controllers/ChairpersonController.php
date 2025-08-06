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

class ChairpersonController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ============================
    // Instructor Management
    // ============================

    public function manageInstructors()
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
            abort(403);
        }
        
        // Get GE department to exclude GE department instructors from chairperson management
        $geDepartment = Department::where('department_code', 'GE')->first();
        
        $query = User::where('role', 0);
        
        if (Auth::user()->role === 1) {
            $query->where(function($q) use ($geDepartment) {
                // Include instructors from the chairperson's department
                $q->where('department_id', Auth::user()->department_id)
                  ->where('course_id', Auth::user()->course_id);
            })->orWhere(function($q) use ($geDepartment) {
                // Also include instructors from other departments who are approved to teach GE subjects
                $q->where('can_teach_ge', true)
                  ->where('department_id', '!=', $geDepartment->id);
            });
        }
        
        $instructors = $query->orderBy('last_name')->get();
        
        $pendingAccounts = UnverifiedUser::with('department', 'course')
            ->when(Auth::user()->role === 1, function($q) {
                $q->where('department_id', Auth::user()->department_id)
                  ->where('course_id', Auth::user()->course_id);
            })
            ->where('department_id', '!=', $geDepartment->id)
            ->get();
            
        return view('chairperson.manage-instructors', compact('instructors', 'pendingAccounts'));
    }

    public function storeInstructor(Request $request)
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
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
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
            abort(403);
        }
        
        // Exclude GE department instructors
        $geDepartment = Department::where('department_code', 'GE')->first();
        
        $query = User::where('id', $id)->where('role', 0);
        if (Auth::user()->role === 1) {
            $query->where('department_id', Auth::user()->department_id);
        }
        $query->where('department_id', '!=', $geDepartment->id);
        $instructor = $query->firstOrFail();
        $instructor->update(['is_active' => false]);
        return redirect()->back()->with('success', 'Instructor deactivated successfully.');
    }

    public function activateInstructor($id)
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
            abort(403);
        }
        
        // Exclude GE department instructors
        $geDepartment = Department::where('department_code', 'GE')->first();
        
        $query = User::where('id', $id)->where('role', 0);
        if (Auth::user()->role === 1) {
            $query->where('department_id', Auth::user()->department_id);
        }
        $query->where('department_id', '!=', $geDepartment->id);
        $instructor = $query->firstOrFail();
        $instructor->update(['is_active' => true]);
        return redirect()->back()->with('success', 'Instructor activated successfully.');
    }

    public function requestGEAssignment($id)
    {
        if (!Auth::user()->isChairperson()) {
            abort(403);
        }
        
        // Find the instructor (must be from chairperson's department)
        $instructor = User::where('id', $id)
            ->where('role', 0)
            ->where('department_id', Auth::user()->department_id)
            ->where('is_active', true)
            ->firstOrFail();
        
        // Check if there's already a pending request for this instructor
        $existingRequest = \App\Models\GESubjectRequest::where('instructor_id', $id)
            ->where('status', 'pending')
            ->first();
            
        if ($existingRequest) {
            return redirect()->back()->with('error', 'There is already a pending GE assignment request for this instructor.');
        }
        
        // Create the GE assignment request
        \App\Models\GESubjectRequest::create([
            'instructor_id' => $id,
            'requested_by' => Auth::id(),
            'status' => 'pending',
        ]);
        
        return redirect()->back()->with('success', 'GE assignment request submitted successfully. The GE Coordinator will review your request.');
    }

    // ============================
    // Subject Assignment
    // ============================

    public function assignSubjects()
    {
        if (!Auth::user()->isChairperson()) {
            abort(403);
        }
        
        $academicPeriodId = session('active_academic_period_id');
        
        // Chairperson: manages subjects with course_id != 1 (department subjects)
        $subjects = Subject::where('department_id', Auth::user()->department_id)
            ->where('course_id', '!=', 1) // Exclude General Education subjects
            ->where('is_deleted', false)
            ->where('academic_period_id', $academicPeriodId)
            ->orderBy('subject_code')
            ->get();
            
        $instructors = User::where('role', 0)
            ->where('department_id', Auth::user()->department_id)
            ->where('course_id', Auth::user()->course_id)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get();
        
        $yearLevels = $subjects->groupBy('year_level');
        return view('chairperson.assign-subjects', compact('yearLevels', 'instructors'));
    }
    

    public function storeAssignedSubject(Request $request)
    {
        if (!Auth::user()->isChairperson()) {
            abort(403);
        }
        
        $academicPeriodId = session('active_academic_period_id');
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'instructor_id' => 'required|exists:users,id',
        ]);
        
        // Chairperson: manages subjects with course_id != 1 (department subjects)
        $subject = Subject::where('id', $request->subject_id)
            ->where('department_id', Auth::user()->department_id)
            ->where('course_id', '!=', 1) // Exclude General Education subjects
            ->where('academic_period_id', $academicPeriodId)
            ->firstOrFail();
            
        $instructor = User::where('id', $request->instructor_id)
            ->where('role', 0)
            ->where('department_id', Auth::user()->department_id)
            ->where('course_id', Auth::user()->course_id)
            ->where('is_active', true)
            ->firstOrFail();
            
        $subject->update([
            'instructor_id' => $instructor->id,
            'updated_by' => Auth::id(),
        ]);
        return redirect()->route('chairperson.assign-subjects')->with('success', 'Subject assigned successfully.');
    }
    public function toggleAssignedSubject(Request $request)
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
            abort(403);
        }
        $academicPeriodId = session('active_academic_period_id');
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'instructor_id' => 'nullable|exists:users,id',
        ]);
        if (Auth::user()->role === 1) {
            $subject = Subject::where('id', $request->subject_id)
                ->where('department_id', Auth::user()->department_id)
                ->where('course_id', Auth::user()->course_id)
                ->where('academic_period_id', $academicPeriodId)
                ->firstOrFail();
        } else {
            $subject = Subject::where('id', $request->subject_id)
                ->where('is_universal', true)
                ->where('academic_period_id', $academicPeriodId)
                ->firstOrFail();
        }
        $enrolledStudents = $subject->students()->count();
        if ($enrolledStudents > 0 && !$request->instructor_id) {
            return redirect()->route('chairperson.assignSubjects')->with('error', 'Cannot unassign subject as it has enrolled students.');
        }
        if ($request->instructor_id) {
            if (Auth::user()->role === 1) {
                $instructor = User::where('id', $request->instructor_id)
                    ->where('role', 0)
                    ->where('department_id', Auth::user()->department_id)
                    ->where('course_id', Auth::user()->course_id)
                    ->where('is_active', true)
                    ->firstOrFail();
            } else {
                $instructor = User::where('id', $request->instructor_id)
                    ->where('role', 0)
                    ->where('is_active', true)
                    ->firstOrFail();
            }
            $subject->update([
                'instructor_id' => $instructor->id,
                'updated_by' => Auth::id(),
            ]);
            return redirect()->route('chairperson.assignSubjects')->with('success', 'Instructor assigned successfully.');
        } else {
            $subject->update([
                'instructor_id' => null,
                'updated_by' => Auth::id(),
            ]);
            return redirect()->route('chairperson.assignSubjects')->with('success', 'Instructor unassigned successfully.');
        }
    }
        
    
    // ============================
    // View Grades
    // ============================

    public function viewGrades(Request $request)
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
            abort(403);
        }
        
        $selectedInstructorId = $request->input('instructor_id');
        $selectedSubjectId = $request->input('subject_id');
        
        $academicPeriodId = session('active_academic_period_id');
        $departmentId = Auth::user()->department_id;
        $courseId = Auth::user()->course_id;
        
        // Fetch instructors in department and course (role: 0 = instructor)
        $geDepartment = Department::where('department_code', 'GE')->first();
        $instructors = User::where([
            ['role', 0],
            ['is_active', true],
        ])
        ->where('department_id', '!=', $geDepartment->id)
        ->orderBy('last_name')
        ->get();
    
        // Subjects are loaded only when an instructor is selected
        $subjects = [];
        if ($selectedInstructorId) {
            $subjectQuery = Subject::where([
                ['instructor_id', $selectedInstructorId],
                ['academic_period_id', $academicPeriodId],
                ['is_deleted', false],
            ]);
            if (Auth::user()->role === 1) {
                $subjectQuery->where('department_id', $departmentId)
                            ->where('course_id', $courseId);
            } else if (Auth::user()->role === 4) {
                $subjectQuery->where('is_universal', true);
            }
            $subjects = $subjectQuery->orderBy('subject_code')->get();
        }
    
        // Students and grades are only loaded when a subject is selected
        $students = [];
        if ($selectedSubjectId) {
            $subjectQuery = Subject::where([
                ['id', $selectedSubjectId],
            ]);
            if (Auth::user()->role === 1) {
                $subjectQuery->where('department_id', $departmentId)
                            ->where('course_id', $courseId);
            } else if (Auth::user()->role === 4) {
                $subjectQuery->where('is_universal', true);
            }
            $subject = $subjectQuery->firstOrFail();
    
            $students = $subject->students()
                ->with(['termGrades' => function ($q) use ($selectedSubjectId) {
                    $q->where('subject_id', $selectedSubjectId);
                }])
                ->get();
        }
    
        return view('chairperson.view-grades', [
            'instructors' => $instructors,
            'subjects' => $subjects,
            'students' => $students,
            'selectedInstructorId' => $selectedInstructorId,
            'selectedSubjectId' => $selectedSubjectId,
        ]);
    }
    
      

    // ============================
    // Students by Year Level
    // ============================

    public function viewStudentsPerYear()
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
            abort(403);
        }
        if (Auth::user()->role === 1) {
            $students = Student::where('department_id', Auth::user()->department_id)
                ->where('course_id', Auth::user()->course_id)
                ->where('is_deleted', false)
                ->with('course')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        } else {
            // GE Coordinator: students enrolled in GE subjects
            $geSubjectIds = Subject::where('is_universal', true)->pluck('id');
            $students = Student::whereHas('subjects', function($q) use ($geSubjectIds) {
                    $q->whereIn('subjects.id', $geSubjectIds);
                })
                ->where('is_deleted', false)
                ->with('course')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }
        return view('chairperson.students-by-year', compact('students'));
    }
}