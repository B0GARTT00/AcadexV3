<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Subject;
use App\Models\FinalGrade;
use App\Models\Student;
use App\Models\User;
use App\Models\Department;
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

        $instructors = $query->get();
        $departments = Department::where('is_deleted', false)->orderBy('department_description')->get();
        $selectedDepartment = $departmentId ? Department::find($departmentId) : null;

        return view('vpaa.instructors', compact('instructors', 'departments', 'selectedDepartment'));
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
