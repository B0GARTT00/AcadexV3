<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Department;
use App\Services\CourseOutcomeReportingService;
use Illuminate\Http\Request;

class ProgramReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'academic.period.set']);
    }

    /**
     * VPAA/Chair view: CO compliance per program (department) summarized per course for the active academic period.
     * GET /vpaa/reports/co-program?department_id=<id>
     */
    public function vpaaDepartment(Request $request, CourseOutcomeReportingService $service)
    {
        $departmentId = (int)$request->input('department_id', 0);
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;

        // If no department selected, show departments list to choose from
        if (!$departmentId) {
            $departments = \App\Models\Department::where('is_deleted', false)
                ->orderBy('department_description')
                ->get(['id', 'department_code', 'department_description']);

            return view('vpaa.reports.co-program-departments', [
                'departments' => $departments,
                'academicYear' => $period?->academic_year,
                'semester' => $period?->semester,
            ]);
        }

        $department = Department::findOrFail($departmentId);
        $byCourse = $service->aggregateDepartmentByCourse($departmentId, $periodId);

        return view('vpaa.reports.co-program', [
            'department' => $department,
            'byCourse' => $byCourse,
            'academicYear' => $period?->academic_year,
            'semester' => $period?->semester,
        ]);
    }

    /**
     * GE Coordinator view: CO compliance for GE subjects across all programs for the active academic period.
     * Shows all programs, but only counts CO attainment from GE subjects within each program.
     * GET /gecoordinator/reports/co-program
     */
    public function geCoordinatorProgram(Request $request, CourseOutcomeReportingService $service)
    {
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;
        
        $departmentId = 1; // GE department
        $department = Department::find($departmentId);
        
        // Get CO data for GE subjects across all programs
        $byCourse = $service->aggregateGESubjectsAcrossCourses($periodId);

        return view('gecoordinator.reports.co-program', [
            'department' => $department,
            'byCourse' => $byCourse,
            'academicYear' => $period?->academic_year,
            'semester' => $period?->semester,
        ]);
    }

    /**
     * Chairperson view: CO compliance for their own assigned course for the active academic period.
     * GET /chairperson/reports/co-program
     */
    public function chairProgram(Request $request, CourseOutcomeReportingService $service)
    {
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;
        $user = auth()->user();
        $courseId = $user?->course_id;

        if (!$courseId) {
            abort(403, 'You are not assigned to any course.');
        }

        $course = \App\Models\Course::find($courseId);
        
        // Get CO data for only this course
        $byCourse = [
            $course->id => [
                'course' => $course,
                'co' => $service->aggregateCourse($course->id, $periodId),
            ],
        ];

        return view('chairperson.reports.co-program', [
            'department' => $course?->department, // Still pass department for display context
            'byCourse' => $byCourse,
            'academicYear' => $period?->academic_year,
            'semester' => $period?->semester,
        ]);
    }

    /**
     * Dean view: CO compliance for their department for the active academic period.
     * GET /dean/reports/co-program
     */
    public function deanProgram(Request $request, CourseOutcomeReportingService $service)
    {
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;
        $user = auth()->user();
        $departmentId = $user?->department_id;

        if (!$departmentId) {
            abort(403, 'You are not assigned to any department.');
        }

        $department = Department::find($departmentId);
        $byCourse = $service->aggregateDepartmentByCourse($departmentId, $periodId);

        return view('dean.reports.co-program', [
            'department' => $department,
            'byCourse' => $byCourse,
            'academicYear' => $period?->academic_year,
            'semester' => $period?->semester,
        ]);
    }
}
