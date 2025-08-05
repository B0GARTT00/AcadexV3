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
        $query = User::where('role', 0);
        if (Auth::user()->role === 1) {
            $query->where('department_id', Auth::user()->department_id)
                  ->where('course_id', Auth::user()->course_id);
        }
        // GE Coordinator: show all instructors for GE subjects
        $instructors = $query->orderBy('last_name')->get();
        $pendingAccounts = UnverifiedUser::with('department', 'course')
            ->when(Auth::user()->role === 1, function($q) {
                $q->where('department_id', Auth::user()->department_id)
                  ->where('course_id', Auth::user()->course_id);
            })
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
        $query = User::where('id', $id)->where('role', 0);
        if (Auth::user()->role === 1) {
            $query->where('department_id', Auth::user()->department_id);
        }
        $instructor = $query->firstOrFail();
        $instructor->update(['is_active' => false]);
        return redirect()->back()->with('success', 'Instructor deactivated successfully.');
    }

    public function activateInstructor($id)
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
            abort(403);
        }
        $query = User::where('id', $id)->where('role', 0);
        if (Auth::user()->role === 1) {
            $query->where('department_id', Auth::user()->department_id);
        }
        $instructor = $query->firstOrFail();
        $instructor->update(['is_active' => true]);
        return redirect()->back()->with('success', 'Instructor activated successfully.');
    }

    // ============================
    // Subject Assignment
    // ============================

    public function assignSubjects()
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
            abort(403);
        }
        $academicPeriodId = session('active_academic_period_id');
        if (Auth::user()->role === 1) {
            $subjects = Subject::where('department_id', Auth::user()->department_id)
                ->where('course_id', Auth::user()->course_id)
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
        } else {
            // GE Coordinator: only GE subjects, all instructors
            $subjects = Subject::where('is_universal', true)
                ->where('is_deleted', false)
                ->where('academic_period_id', $academicPeriodId)
                ->orderBy('subject_code')
                ->get();
            $instructors = User::where('role', 0)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->get();
        }
        $yearLevels = $subjects->groupBy('year_level');
        return view('chairperson.assign-subjects', compact('yearLevels', 'instructors'));
    }
    

    public function storeAssignedSubject(Request $request)
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
            abort(403);
        }
        $academicPeriodId = session('active_academic_period_id');
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'instructor_id' => 'required|exists:users,id',
        ]);
        if (Auth::user()->role === 1) {
            $subject = Subject::where('id', $request->subject_id)
                ->where('department_id', Auth::user()->department_id)
                ->where('course_id', Auth::user()->course_id)
                ->where('academic_period_id', $academicPeriodId)
                ->firstOrFail();
            $instructor = User::where('id', $request->instructor_id)
                ->where('role', 0)
                ->where('department_id', Auth::user()->department_id)
                ->where('course_id', Auth::user()->course_id)
                ->where('is_active', true)
                ->firstOrFail();
        } else {
            // GE Coordinator: only GE subjects, any instructor
            $subject = Subject::where('id', $request->subject_id)
                ->where('is_universal', true)
                ->where('academic_period_id', $academicPeriodId)
                ->firstOrFail();
            $instructor = User::where('id', $request->instructor_id)
                ->where('role', 0)
                ->where('is_active', true)
                ->firstOrFail();
        }
        $subject->update([
            'instructor_id' => $instructor->id,
            'updated_by' => Auth::id(),
        ]);
        return redirect()->route('chairperson.assignSubjects')->with('success', 'Subject assigned successfully.');
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
        $instructors = User::where([
            ['role', 0],
            ['is_active', true],
        ])
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