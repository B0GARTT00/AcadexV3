<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Subject;
use App\Models\CourseOutcomes;
use App\Models\FinalGrade;
use App\Models\Student;
use App\Models\User;
use App\Models\Department;
use App\Models\CourseOutcomeAttainment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class VPAAController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
        // Check if user is VPAA (role 5)
        $this->middleware(function ($request, $next) {
            if (auth()->check() && auth()->user()->role === 5) {
                return $next($request);
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'You are not authorized to access this page.');
        });
    }
    
    /**
     * Show course outcome attainment reports
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function viewCourseOutcomeAttainment(Request $request)
    {
        // Department-first flow: if no department selected, show department cards; otherwise show subjects for that department
        $departmentId = $request->input('department_id');

        $academicPeriodId = session('active_academic_period_id');
        $period = $academicPeriodId ? \App\Models\AcademicPeriod::find($academicPeriodId) : null;

        if (!$departmentId) {
            // Show department wildcards with chairperson and GE coordinator
            $departments = Department::where('is_deleted', false)
                ->select('id', 'department_code', 'department_description')
                ->orderBy('department_description')
                ->get();

            // Attach chairperson and GE coordinator to each department
            foreach ($departments as $dept) {
                $dept->chairperson = $dept->users()->where('role', 1)->first();
                $dept->gecoordinator = $dept->users()->where('role', 4)->first();
            }

            return view('vpaa.scores.course-outcome-departments', [
                'departments' => $departments,
                'academicYear' => $period->academic_year ?? null,
                'semester' => $period->semester ?? null,
            ]);
        }

        // Show subjects filtered by selected department (and optionally academic period)
        $subjects = Subject::query()
            ->join('courses', 'courses.id', '=', 'subjects.course_id')
            ->where('subjects.is_deleted', false)
            ->where('courses.department_id', $departmentId)
            ->when($academicPeriodId, function ($q) use ($academicPeriodId) {
                $q->where('subjects.academic_period_id', $academicPeriodId);
            })
            ->select('subjects.*')
            ->orderBy('subjects.subject_code')
            ->get();

        $selectedDepartment = Department::find($departmentId);

        return view('vpaa.scores.course-outcome-results-wildcards', [
            'subjects' => $subjects,
            'academicYear' => $period->academic_year ?? null,
            'semester' => $period->semester ?? null,
            'selectedDepartment' => $selectedDepartment,
        ]);
    }

    /**
     * VPAA view for a specific subject's course outcome results (read-only UI mirroring instructor).
     */
    public function subject($subjectId)
    {
    // Subject + context (no academic period required for VPAA)
    $selectedSubject = \App\Models\Subject::with(['course', 'academicPeriod'])->findOrFail($subjectId);

        // Students enrolled in the subject
        $students = \App\Models\Student::whereHas('subjects', function($q) use ($subjectId) {
            $q->where('subject_id', $subjectId);
        })->get();

        // Terms
        $terms = ['prelim', 'midterm', 'prefinal', 'final'];

        // Activities grouped by term with COs
        $activitiesByTerm = [];
        $coColumnsByTerm = [];
        foreach ($terms as $term) {
            $activities = \App\Models\Activity::where('subject_id', $subjectId)
                ->where('term', $term)
                ->where('is_deleted', false)
                ->whereNotNull('course_outcome_id')
                ->get();
            $activitiesByTerm[$term] = $activities;

            $coIds = $activities->pluck('course_outcome_id')->unique()->toArray();
            if (!empty($coIds)) {
                $sortedCos = \App\Models\CourseOutcomes::whereIn('id', $coIds)
                    ->orderBy('co_code')
                    ->pluck('id')
                    ->toArray();
                $coColumnsByTerm[$term] = $sortedCos;
            } else {
                $coColumnsByTerm[$term] = [];
            }
        }

        // Map activity->CO per term
        $activityCoMap = [];
        foreach ($activitiesByTerm as $term => $activities) {
            foreach ($activities as $activity) {
                $activityCoMap[$term][$activity->id] = $activity->course_outcome_id;
            }
        }

        // Gather student scores per activity
        $studentScores = [];
        foreach ($students as $student) {
            foreach ($activitiesByTerm as $term => $activities) {
                foreach ($activities as $activity) {
                    $score = \App\Models\Score::where('student_id', $student->id)
                        ->where('activity_id', $activity->id)
                        ->first();
                    $studentScores[$student->id][$term][$activity->id] = [
                        'score' => $score ? $score->score : 0,
                        'max' => $activity->number_of_items,
                    ];
                }
            }
        }

        // Compute CO attainment per student using existing trait
        $coResults = [];
        $calculator = new \App\Http\Controllers\CourseOutcomeAttainmentController();
        foreach ($students as $student) {
            $coResults[$student->id] = $calculator->computeCoAttainment($studentScores[$student->id] ?? [], $activityCoMap);
        }

        // CO details and final ordered list
        $flat = array_merge(...array_values($coColumnsByTerm ?: [[]]));
        $finalCOs = array_unique($flat);
        $coDetails = \App\Models\CourseOutcomes::whereIn('id', $finalCOs)->get()->keyBy('id');
        usort($finalCOs, function($a, $b) use ($coDetails) {
            $codeA = $coDetails[$a]->co_code ?? '';
            $codeB = $coDetails[$b]->co_code ?? '';
            $numA = (int)preg_replace('/[^0-9]/', '', $codeA);
            $numB = (int)preg_replace('/[^0-9]/', '', $codeB);
            return $numA <=> $numB;
        });
        $finalCOs = array_values($finalCOs);

        return view('vpaa.scores.course-outcome-results', [
            'students' => $students,
            'coResults' => $coResults,
            'coColumnsByTerm' => $coColumnsByTerm,
            'coDetails' => $coDetails,
            'finalCOs' => $finalCOs,
            'terms' => $terms,
            'subjectId' => $subjectId,
            'selectedSubject' => $selectedSubject,
        ]);
    }
    
    /**
     * Display the VPAA dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $departmentsCount = Department::where('is_deleted', false)->count();
        $instructorsCount = User::where('role', 0) // Instructor role
            ->where('is_active', true)
            ->count();
        $studentsCount = Student::where('is_deleted', false)->count();

        return view('vpaa.dashboard', [
            'departmentsCount' => $departmentsCount,
            'instructorsCount' => $instructorsCount,
            'studentsCount' => $studentsCount
        ]);
    }

    // ============================
    // View All Departments
    // ============================

    public function viewDepartments()
    {
        // Get all non-deleted departments except GE (General Education)
        $departments = Department::where('is_deleted', false)
            ->where('department_code', '!=', 'GE') // Exclude GE department
            ->select('id', 'department_code', 'department_description')
            ->orderBy('department_description')
            ->get();

        // Manually count instructors and students for each department, and attach chairperson and GE coordinator
        $departments->each(function ($department) {
            $department->instructor_count = User::where('department_id', $department->id)
                ->where('role', 0) // Instructor role
                ->where('is_active', true)
                ->count();

            $department->student_count = $department->students()
                ->where('is_deleted', false)
                ->count();

            $department->chairperson = User::where('department_id', $department->id)
                ->where('role', 1)
                ->first();
            $department->gecoordinator = User::where('department_id', $department->id)
                ->where('role', 4)
                ->first();
        });

        return view('vpaa.departments', compact('departments'));
    }
    
    /**
     * Store a newly created department in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'department_code' => 'required|string|max:20|unique:departments,department_code',
            'department_description' => 'required|string|max:255',
        ]);

        try {
            Department::create($validated);
            return redirect()->route('vpaa.departments')
                ->with('status', 'Department created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating department: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified department in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateDepartment(Request $request, $id)
    {
        $validated = $request->validate([
            'department_code' => 'required|string|max:20|unique:departments,department_code,' . $id,
            'department_description' => 'required|string|max:255',
        ]);

        try {
            $department = Department::findOrFail($id);
            $department->update($validated);
            
            return redirect()->route('vpaa.departments')
                ->with('status', 'Department updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating department: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete the specified department.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyDepartment($id)
    {
        try {
            $department = Department::findOrFail($id);
            
            // Check if department has any users or students
            $hasUsers = User::where('department_id', $id)->exists();
            $hasStudents = $department->students()->exists();
            
            if ($hasUsers || $hasStudents) {
                return redirect()->back()
                    ->with('error', 'Cannot delete department with associated users or students.');
            }
            
            $department->update(['is_deleted' => true]);
            
            return redirect()->route('vpaa.departments')
                ->with('status', 'Department deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting department: ' . $e->getMessage());
        }
    }

    // ============================
    // View Instructors by Department
    // ============================

    public function viewInstructors(Request $request, $departmentId = null)
    {
        // Check if department_id is passed as a URL parameter or as a request parameter
        $departmentId = $departmentId ?: $request->input('department_id');
        
        // Only show users if a department is selected
        $instructors = collect(); // Empty collection by default
        
        if ($departmentId) {
            $instructors = User::where('is_active', true)
                ->where('department_id', $departmentId) // Filter by department
                ->where('role', '!=', 3) // Exclude admin users (role 3)
                ->with(['department' => function($query) {
                    $query->select('id', 'department_code', 'department_description');
                }])
                ->orderBy('last_name')
                ->paginate(15);
        }

        $departments = Department::where('is_deleted', false)
            ->select('id', 'department_code', 'department_description')
            ->orderBy('department_description')
            ->get();
        $selectedDepartment = $departmentId ? Department::find($departmentId) : null;

        return view('vpaa.instructors', compact('instructors', 'departments', 'selectedDepartment'));
    }
    
    /**
     * Show the form for editing the specified instructor.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function editInstructor($id)
    {
        $instructor = User::findOrFail($id);
        $departments = Department::where('is_deleted', false)
            ->select('id', 'department_code', 'department_description')
            ->orderBy('department_description')
            ->get();
            
        return view('vpaa.edit-instructor', compact('instructor', 'departments'));
    }
    
    /**
     * Update the specified instructor in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateInstructor(Request $request, $id)
    {
        $instructor = User::findOrFail($id);
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'department_id' => 'required|exists:departments,id',
            'is_active' => 'boolean'
        ]);
        
        // Update the instructor
        $instructor->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'department_id' => $validated['department_id'],
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);
        
        return redirect()->route('vpaa.instructors', ['department_id' => request('department_id')])
            ->with('success', 'Instructor updated successfully.');
    }

    // ============================
    // View Students by Department
    // ============================


    // ============================
    // View Final Grades by Department/Course
    // ============================

    public function viewStudents(Request $request)
    {
        $departments = Department::orderBy('department_description')->get();
        $departmentId = $request->input('department_id');

        if ($departmentId) {
            $students = Student::where('department_id', $departmentId)
                ->orderBy('last_name')
                ->get();
            $department = Department::find($departmentId);
            return view('vpaa.students', compact('students', 'department', 'departments'));
        }

        // Show department wildcards first
        return view('vpaa.students-departments', compact('departments'));
    }
// (Stray code removed)
}
