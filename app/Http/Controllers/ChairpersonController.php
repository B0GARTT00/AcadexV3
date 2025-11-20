<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Models\Department;
use App\Models\Course;
use App\Models\FinalGrade;
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
        
        // Base query for instructors
        $query = User::where('role', 0);

        // If the current user is a chairperson (role === 1), only list instructors within the
        // same department and course so Chairpersons cannot see instructors from other departments
        // (including those approved to teach GE). GE department instructors are excluded.
        if (Auth::user()->role === 1) {
            $query->where('department_id', Auth::user()->department_id)
                  ->where('course_id', Auth::user()->course_id)
                  ->where('department_id', '!=', $geDepartment->id);
        }
        
        $instructors = $query->orderBy('is_active', 'desc') // Show active instructors first
                           ->orderBy('last_name')
                           ->get();

        // Get GE requests for each instructor to check status
        $geRequests = \App\Models\GESubjectRequest::whereIn('instructor_id', $instructors->pluck('id'))
            ->get()
            ->keyBy('instructor_id');

        $pendingAccounts = UnverifiedUser::with('department', 'course')
            ->when(Auth::user()->role === 1, function($q) {
                $q->where('department_id', Auth::user()->department_id)
                  ->where('course_id', Auth::user()->course_id);
            })
            ->where('department_id', '!=', $geDepartment->id)
            ->whereNotNull('email_verified_at')
            ->get();
            
        return view('chairperson.manage-instructors', compact('instructors', 'pendingAccounts', 'geRequests'));
    }

    public function storeInstructor(Request $request)
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
            abort(403);
        }

        // Validate base email format first
        $request->validate([
            'first_name'    => 'required|string|max:255',
            'middle_name'   => 'nullable|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|string|regex:/^[^@]+$/|max:255',
            'password'      => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'department_id' => 'required|exists:departments,id',
            'course_id'     => 'required|exists:courses,id',
        ]);

        $fullEmail = strtolower(trim($request->email)) . '@brokenshire.edu.ph';

        // Check uniqueness of the full email in both unverified_users and users tables
        if (UnverifiedUser::where('email', $fullEmail)->exists()) {
            return back()->withErrors(['email' => 'This email is already registered and pending verification.'])->withInput();
        }

        if (User::where('email', $fullEmail)->exists()) {
            return back()->withErrors(['email' => 'This email is already registered.'])->withInput();
        }

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
            $query->where('department_id', Auth::user()->department_id)
                  ->where('course_id', Auth::user()->course_id);
        }
        $query->where('department_id', '!=', $geDepartment->id);
        $instructor = $query->firstOrFail();
        
        // When chairperson deactivates, remove both active status and GE teaching capability
        $instructor->update([
            'is_active' => false,
            'can_teach_ge' => false
        ]);
        
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
            $query->where('department_id', Auth::user()->department_id)
                  ->where('course_id', Auth::user()->course_id);
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
            ->where('course_id', Auth::user()->course_id) // Only subjects for user's course
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
            return redirect()->route('chairperson.assign-subjects')->with('error', 'Cannot unassign subject as it has enrolled students.');
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
            return redirect()->route('chairperson.assign-subjects')->with('success', 'Instructor assigned successfully.');
        } else {
            $subject->update([
                'instructor_id' => null,
                'updated_by' => Auth::id(),
            ]);
            return redirect()->route('chairperson.assign-subjects')->with('success', 'Instructor unassigned successfully.');
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
        
        // Fetch instructors depending on the user role (role: 0 = instructor)
        $geDepartment = Department::where('department_code', 'GE')->first();
        // When a Chairperson (role === 1) views this, only instructors in their
        // department and course should be visible (exclude GE instructors).
        // When a GE Coordinator (role === 4) views this, show GE department
        // instructors and those flagged as can_teach_ge.
        if (Auth::user()->role === 1) {
            $instructors = User::where('role', 0)
                ->where('is_active', true)
                ->where('department_id', $departmentId)
                ->where('course_id', $courseId)
                ->where('department_id', '!=', $geDepartment->id)
                ->orderBy('last_name')
                ->get();
        } else { // GE Coordinator or admin roles that are allowed
            $instructors = User::where('role', 0)
                ->where('is_active', true)
                ->where(function($query) use ($geDepartment) {
                    $query->where('department_id', $geDepartment->id)
                          ->orWhere('can_teach_ge', true);
                })
                ->orderBy('last_name')
                ->get();
        }
    
        // Ensure selected instructor is in the list of accessible instructors
        if ($selectedInstructorId && !$instructors->pluck('id')->contains((int)$selectedInstructorId)) {
            // Reset selection if user tries to view an instructor they're not allowed to see
            $selectedInstructorId = null;
            $selectedSubjectId = null;
        }

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
                ->with([
                    'termGrades' => function ($q) use ($selectedSubjectId) {
                        $q->where('subject_id', $selectedSubjectId);
                    },
                    'finalGrades' => function ($q) use ($selectedSubjectId) {
                        $q->where('subject_id', $selectedSubjectId);
                    }
                ])
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

    // ============================
    // Save Grade Notes
    // ============================

    public function saveGradeNotes(Request $request)
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 4)) {
            abort(403);
        }

        $request->validate([
            'final_grade_id' => 'required|exists:final_grades,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $finalGrade = FinalGrade::findOrFail($request->final_grade_id);
        
        // Update the notes
        $finalGrade->notes = $request->notes;
        $finalGrade->updated_by = Auth::id();
        $finalGrade->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Notes saved successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Notes saved successfully.');
    }

    // ============================
    // Structure Template Requests
    // ============================

    /**
     * Display a listing of structure template requests for the chairperson.
     */
    public function indexTemplateRequests()
    {
        if (Auth::user()->role !== 1) {
            abort(403);
        }

        $requests = \App\Models\StructureTemplateRequest::where('chairperson_id', Auth::id())
            ->with('reviewer')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->get();

        return view('chairperson.structure-template-requests', compact('requests'));
    }

    /**
     * Show the form for creating a new structure template request.
     */
    public function createTemplateRequest()
    {
        if (Auth::user()->role !== 1) {
            abort(403);
        }

        return view('chairperson.structure-template-create');
    }

    /**
     * Store a newly created structure template request.
     */
    public function storeTemplateRequest(\Illuminate\Http\Request $request)
    {
        if (Auth::user()->role !== 1) {
            abort(403);
        }

        $request->validate([
            'template_name' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'structure' => 'required|json',
        ]);

        try {
            $structureArray = json_decode($request->structure, true);
            
            \App\Models\StructureTemplateRequest::create([
                'chairperson_id' => Auth::id(),
                'label' => $request->label,
                'description' => $request->description,
                'structure_config' => [
                    'type' => 'custom',
                    'structure' => $structureArray,
                ],
                'status' => 'pending',
            ]);

            return redirect()
                ->route('chairperson.structureTemplates.index')
                ->with('success', 'Structure template request submitted successfully. It will be reviewed by an administrator.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to submit structure template request: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified structure template request.
     */
    public function showTemplateRequest(\App\Models\StructureTemplateRequest $request)
    {
        if (Auth::user()->role !== 1 || $request->chairperson_id !== Auth::id()) {
            abort(403);
        }

        $structureCatalog = \App\Support\Grades\FormulaStructure::getAllStructureDefinitions();

        return view('chairperson.structure-template-show', compact('request', 'structureCatalog'));
    }

    /**
     * Remove the specified structure template request (only if pending).
     */
    public function destroyTemplateRequest(\App\Models\StructureTemplateRequest $request)
    {
        if (Auth::user()->role !== 1 || $request->chairperson_id !== Auth::id()) {
            abort(403);
        }

        if ($request->status !== 'pending') {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Only pending requests can be deleted.']);
        }

        $request->delete();

        return redirect()
            ->route('chairperson.structureTemplates.index')
            ->with('success', 'Structure template request deleted successfully.');
    }
}
