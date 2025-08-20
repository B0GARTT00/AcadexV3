<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Subject;
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
        // Get filter parameters
        $selectedDepartmentId = $request->input('department_id');
        $selectedCourseId = $request->input('course_id');
        
        // Get all departments for the filter dropdown
        $departments = Department::where('is_deleted', false)
            ->select('id', 'department_code', 'department_description')
            ->orderBy('department_description')
            ->get();
            
        // Get courses based on selected department
        $courses = collect();
        if ($selectedDepartmentId) {
            $courses = Course::where('department_id', $selectedDepartmentId)
                ->where('is_deleted', false)
                ->orderBy('course_code')
                ->get();
        }
        
        // Get base query for course outcome attainment data
        $query = CourseOutcomeAttainment::select([
                'course_outcome_attainments.*',
                'students.id as student_id',
                'students.first_name',
                'students.last_name',
                'students.course_id',
                'courses.id as course_id',
                'courses.course_code',
                'courses.course_description',
                'departments.department_code',
                'departments.department_description',
                'course_outcomes.id as co_id',
                'course_outcomes.co_code',
                'course_outcomes.description as co_description'
            ])
            ->leftJoin('students', 'course_outcome_attainments.student_id', '=', 'students.id')
            ->leftJoin('courses', 'students.course_id', '=', 'courses.id')
            ->leftJoin('departments', 'courses.department_id', '=', 'departments.id')
            ->leftJoin('course_outcomes', 'course_outcome_attainments.co_id', '=', 'course_outcomes.id');
            
        // Apply filters
        if ($selectedDepartmentId) {
            $query->where('courses.department_id', $selectedDepartmentId);
        }
        
        if ($selectedCourseId) {
            $query->where('students.course_id', $selectedCourseId);
        }
        
        // Get the data
        $attainmentData = $query->get();
        
        // Organize data by course, then student, then outcomes
        $organizedData = [];
        $courseOutcomes = [];
        
        // First pass: collect all unique course outcomes across all courses
        foreach ($attainmentData as $item) {
            $courseKey = $item->course_code ?? 'Unknown';
            $outcomeKey = $item->co_id;
            
            if (!isset($courseOutcomes[$courseKey][$outcomeKey])) {
                $courseOutcomes[$courseKey][$outcomeKey] = (object)[
                    'id' => $item->co_id,
                    'co_code' => $item->co_code,
                    'description' => $item->co_description
                ];
            }
        }
        
        // Second pass: organize data by course and student
        foreach ($attainmentData as $item) {
            $courseKey = $item->course_code ?? 'Unknown';
            $studentId = $item->student_id;
            
            if (!isset($organizedData[$courseKey])) {
                $organizedData[$courseKey] = [
                    'course_code' => $item->course_code,
                    'course_description' => $item->course_description,
                    'department_code' => $item->department_code,
                    'students' => [],
                    'outcomes' => $courseOutcomes[$courseKey] ?? []
                ];
            }
            
            if (!isset($organizedData[$courseKey]['students'][$studentId])) {
                $organizedData[$courseKey]['students'][$studentId] = [
                    'first_name' => $item->first_name,
                    'last_name' => $item->last_name,
                    'outcomes' => []
                ];
            }
            
            // Add the outcome data for this student
            $organizedData[$courseKey]['students'][$studentId]['outcomes'][$item->co_id] = (object)[
                'id' => $item->id,
                'score' => $item->score,
                'max' => $item->max,
                'co_code' => $item->co_code,
                'co_description' => $item->co_description
            ];
        }
        
        // Sort outcomes by co_code for consistent display
        foreach ($organizedData as &$courseData) {
            if (isset($courseData['outcomes'])) {
                // Convert to array, sort by co_code, then convert back to object
                $outcomesArray = (array)$courseData['outcomes'];
                usort($outcomesArray, function($a, $b) {
                    return strcmp($a->co_code, $b->co_code);
                });
                $courseData['outcomes'] = $outcomesArray;
            }
        }
        
        return view('vpaa.course-outcome-attainment', [
            'departments' => $departments,
            'courses' => $courses,
            'selectedDepartmentId' => $selectedDepartmentId,
            'selectedCourseId' => $selectedCourseId,
            'attainmentData' => $organizedData,
            'hasData' => !empty($organizedData)
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
        // Get all non-deleted departments
        $departments = Department::where('is_deleted', false)
            ->select('id', 'department_code', 'department_description')
            ->orderBy('department_description')
            ->get();

        // Manually count instructors and students for each department
        $departments->each(function ($department) {
            $department->instructor_count = User::where('department_id', $department->id)
                ->where('role', 0) // Instructor role
                ->where('is_active', true)
                ->count();
                
            $department->student_count = $department->students()
                ->where('is_deleted', false)
                ->count();
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

    public function viewInstructors($departmentId = null)
    {
        $query = User::where('role', 0) // Instructor role
            ->where('is_active', true)
            ->with(['department' => function($query) {
                $query->select('id', 'department_code', 'department_description');
            }])
            ->orderBy('last_name');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $instructors = $query->paginate(15); // Paginate with 15 items per page
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
        
        return redirect()->route('vpaa.instructors')
            ->with('success', 'Instructor updated successfully.');
    }

    // ============================
    // View Students by Department
    // ============================

    public function viewStudents(Request $request)
    {
        $selectedDepartmentId = $request->input('department_id');
        $selectedCourseId = $request->input('course_id');
        
        $query = Student::with(['course', 'department'])
            ->where('is_deleted', false)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($selectedDepartmentId) {
            $query->where('department_id', $selectedDepartmentId);
        }

        if ($selectedCourseId) {
            $query->where('course_id', $selectedCourseId);
        }

        $students = $query->get();
        
        $departments = Department::where('is_deleted', false)
            ->select('id', 'department_code', 'department_description')
            ->orderBy('department_description')
            ->get();
            
        $courses = $selectedDepartmentId 
            ? Course::where('department_id', $selectedDepartmentId)
                ->where('is_deleted', false)
                ->orderBy('course_code')
                ->get()
            : collect();

        // Get the selected department if an ID is provided
        $selectedDepartment = $selectedDepartmentId 
            ? Department::find($selectedDepartmentId)
            : null;

        return view('vpaa.students', compact(
            'students', 
            'departments', 
            'courses', 
            'selectedDepartmentId',
            'selectedCourseId',
            'selectedDepartment'
        ));
    }

    // ============================
    // View Final Grades by Department/Course
    // ============================

    public function viewGrades(Request $request)
    {
    
        $departmentId = $request->input('department_id');
        $courseId = $request->input('course_id');
        $academicPeriodId = session('active_academic_period_id');
    
        // Get departments for the filter
        $departments = Department::where('is_deleted', false)
            ->select('id', 'department_code', 'department_description')
            ->orderBy('department_description')
            ->get();
            
        // Get courses based on selected department
        $courses = $departmentId 
            ? Course::where('department_id', $departmentId)
                ->where('is_deleted', false)
                ->orderBy('course_code')
                ->get()
            : collect();
    
        // Initialize collections
        $students = collect();
        $finalGrades = collect();
        $instructors = collect();
        $subjects = collect();
    
        if ($courseId) {
            // Get instructors for the selected course
            $instructors = User::where('role', 0) // role 0 = instructor
                ->whereHas('subjects', function ($query) use ($courseId, $academicPeriodId) {
                    $query->where('course_id', $courseId)
                        ->where('academic_period_id', $academicPeriodId);
                })
                ->where('is_active', true)
                ->orderBy('last_name')
                ->get();
    
            // Get subjects for the selected course
            $subjects = Subject::where('course_id', $courseId)
                ->where('academic_period_id', $academicPeriodId)
                ->where('is_deleted', false)
                ->orderBy('subject_code')
                ->get();
    
            // Get students for the selected course
            $students = Student::where('course_id', $courseId)
                ->where('is_deleted', false)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
    
            // Get final grades for the selected course
            if ($students->isNotEmpty() && $subjects->isNotEmpty()) {
                $studentIds = $students->pluck('id');
                $subjectIds = $subjects->pluck('id');
    
                $finalGrades = FinalGrade::whereIn('student_id', $studentIds)
                    ->whereIn('subject_id', $subjectIds)
                    ->with(['student', 'subject'])
                    ->get()
                    ->groupBy(['student_id', 'subject_id']);
            }
        }
    
        return view('vpaa.grades', compact(
            'departments',
            'courses',
            'instructors',
            'students',
            'subjects',
            'finalGrades',
            'departmentId',
            'courseId'
        ));
    }
}
