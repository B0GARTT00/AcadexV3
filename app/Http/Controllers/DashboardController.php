<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\
{
    Student, 
    Subject, 
    TermGrade, 
    FinalGrade,
    User,
    UnverifiedUser,
    UserLog,
    Course,
    Department,
};

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role === 0) {
            return $this->instructorDashboard();
        }

        if ($user->isChairperson()) {
            return $this->chairpersonDashboard();
        }

        if ($user->isGECoordinator()) {
            return $this->geCoordinatorDashboard();
        }

        if ($user->isAdmin()) {
            return $this->adminDashboard($request);
        }

        if ($user->role === 2) { // Dean
            return $this->deanDashboard();
        }

        abort(403, 'Unauthorized access.');
    }

    private function instructorDashboard()
    {
        if (!session()->has('active_academic_period_id')) {
            return redirect()->route('select.academicPeriod');
        }

        $academicPeriodId = session('active_academic_period_id');
        $instructorId = Auth::id();

        $subjects = $this->getInstructorSubjects($instructorId, $academicPeriodId);
        $dashboardData = $this->getInstructorDashboardData($subjects, $academicPeriodId);
        $subjectCharts = $this->generateSubjectCharts($subjects);

        return view('dashboard.instructor', $dashboardData + ['subjectCharts' => $subjectCharts]);
    }

    private function getInstructorSubjects($instructorId, $academicPeriodId)
    {
        return Subject::where('instructor_id', $instructorId)
            ->where('academic_period_id', $academicPeriodId)
            ->with('students')
            ->get();
    }

    private function getInstructorDashboardData($subjects, $academicPeriodId)
    {
        $instructorStudents = $subjects->flatMap->students
            ->where('is_deleted', false)
            ->unique('id')
            ->count();

        $enrolledSubjectsCount = $subjects->count();

        $subjectIds = $subjects->pluck('id');
        $finalGrades = FinalGrade::whereIn('subject_id', $subjectIds)
            ->where('academic_period_id', $academicPeriodId)
            ->get();

        $totalPassedStudents = $finalGrades->where('remarks', 'Passed')->count();
        $totalFailedStudents = $finalGrades->where('remarks', 'Failed')->count();

        $termCompletions = $this->calculateTermCompletions($subjects);

        return compact(
            'instructorStudents',
            'enrolledSubjectsCount',
            'totalPassedStudents',
            'totalFailedStudents',
            'termCompletions'
        );
    }

    private function calculateTermCompletions($subjects)
    {
        $terms = ['prelim', 'midterm', 'prefinal', 'final'];
        $termCompletions = [];

        foreach ($terms as $term) {
            $termId = $this->getTermId($term);
            $total = 0;
            $graded = 0;

            foreach ($subjects as $subject) {
                $studentCount = $subject->students->where('is_deleted', false)->count();
                $gradedCount = TermGrade::where('subject_id', $subject->id)
                    ->where('term_id', $termId)
                    ->distinct('student_id')
                    ->count('student_id');

                $total += $studentCount;
                $graded += $gradedCount;
            }

            $termCompletions[$term] = [
                'graded' => $graded,
                'total' => $total,
            ];
        }

        return $termCompletions;
    }

    private function generateSubjectCharts($subjects)
    {
        $terms = ['prelim', 'midterm', 'prefinal', 'final'];
        $subjectCharts = [];

        foreach ($subjects as $subject) {
            $termsData = [];
            $termPercentages = [];

            foreach ($terms as $term) {
                $termId = $this->getTermId($term);
                $studentCount = $subject->students->where('is_deleted', false)->count();
                $gradedCount = TermGrade::where('subject_id', $subject->id)
                    ->where('term_id', $termId)
                    ->distinct('student_id')
                    ->count('student_id');

                $percentage = $studentCount > 0 ? round(($gradedCount / $studentCount) * 100, 2) : 0;

                $termsData[$term] = [
                    'graded' => $gradedCount,
                    'total' => $studentCount,
                    'percentage' => $percentage,
                ];

                $termPercentages[] = $percentage;
            }

            $subjectCharts[] = [
                'code' => $subject->subject_code,
                'description' => $subject->subject_description,
                'terms' => $termsData,
                'termPercentages' => $termPercentages,
            ];
        }

        return $subjectCharts;
    }

    private function chairpersonDashboard()
    {
        if (!session()->has('active_academic_period_id')) {
            return redirect()->route('select.academicPeriod');
        }

        $departmentId = Auth::user()->department_id;
        $academicPeriodId = session('active_academic_period_id');

        $data = [
            "countInstructors" => User::where("role", 0)
                ->where("department_id", $departmentId)
                ->where("is_active", true)
                ->count(),
            "countStudents" => Student::where("department_id", $departmentId)
                ->where("is_deleted", false)
                ->whereHas('subjects', function($query) use ($academicPeriodId) {
                    $query->where('academic_period_id', $academicPeriodId);
                })
                ->count(),
            "countCourses" => Subject::where('department_id', $departmentId)
                ->where('academic_period_id', $academicPeriodId)
                ->where('is_deleted', false)
                ->distinct('course_id')
                ->count('course_id'),
            "countActiveInstructors" => User::where("is_active", 1)
                ->where("role", 0)
                ->where("department_id", $departmentId)
                ->whereHas('subjects', function($query) use ($academicPeriodId) {
                    $query->where('academic_period_id', $academicPeriodId);
                })
                ->count(),
            "countInactiveInstructors" => User::where("is_active", 0)
                ->where("role", 0)
                ->where("department_id", $departmentId)
                ->count(),
            "countUnverifiedInstructors" => UnverifiedUser::where("department_id", $departmentId)
                ->count(),
        ];

        return view('dashboard.chairperson', $data);
    }

    private function adminDashboard(Request $request)
    {
        $selectedDate = $request->query('date', Carbon::today()->toDateString());
        $selectedYear = $request->query('year', now()->year);
        $yearRange = range(now()->year, now()->year - 10);

        $loginStats = $this->getLoginStats($selectedDate);
        $monthlyStats = $this->getMonthlyLoginStats($selectedYear);

        return view('dashboard.admin', array_merge([
            'totalUsers' => User::count(),
            'selectedDate' => $selectedDate,
            'selectedYear' => $selectedYear,
            'yearRange' => $yearRange,
        ], $loginStats, $monthlyStats));
    }

    private function getLoginStats($selectedDate)
    {
        $hours = range(0, 23);
        
        $successfulLogins = UserLog::selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->where('event_type', 'login')
            ->whereDate('created_at', $selectedDate)
            ->groupByRaw('HOUR(created_at)')
            ->pluck('total', 'hour');

        $failedLogins = UserLog::selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->where('event_type', 'failed_login')
            ->whereDate('created_at', $selectedDate)
            ->groupByRaw('HOUR(created_at)')
            ->pluck('total', 'hour');

        $successfulData = array_map(fn($hour) => $successfulLogins[$hour] ?? 0, $hours);
        $failedData = array_map(fn($hour) => $failedLogins[$hour] ?? 0, $hours);

        return [
            'loginCount' => array_sum($successfulData),
            'failedLoginCount' => array_sum($failedData),
            'successfulData' => $successfulData,
            'failedData' => $failedData,
        ];
    }

    private function getMonthlyLoginStats($selectedYear)
    {
        $monthlySuccessfulLogins = UserLog::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->where('event_type', 'login')
            ->whereYear('created_at', $selectedYear)
            ->groupByRaw('MONTH(created_at)')
            ->pluck('total', 'month');

        $monthlyFailedLogins = UserLog::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->where('event_type', 'failed_login')
            ->whereYear('created_at', $selectedYear)
            ->groupByRaw('MONTH(created_at)')
            ->pluck('total', 'month');

        $monthlySuccessfulData = array_map(fn($month) => $monthlySuccessfulLogins[$month] ?? 0, range(1, 12));
        $monthlyFailedData = array_map(fn($month) => $monthlyFailedLogins[$month] ?? 0, range(1, 12));

        return [
            'monthlySuccessfulData' => $monthlySuccessfulData,
            'monthlyFailedData' => $monthlyFailedData,
        ];
    }

    private function deanDashboard()
    {
        $studentsPerDepartment = Student::join('departments', 'students.department_id', '=', 'departments.id')
            ->select('departments.department_description as department_name', DB::raw('count(*) as total'))
            ->groupBy('students.department_id', 'departments.department_description')
            ->pluck('total', 'department_name');

        $studentsPerCourse = Student::join('courses', 'students.course_id', '=', 'courses.id')
            ->select('courses.course_code', 'courses.course_description', DB::raw('count(*) as total'))
            ->groupBy('students.course_id', 'courses.course_code', 'courses.course_description')
            ->pluck('total', 'courses.course_code');

        return view('dashboard.dean', [
            'studentsPerDepartment' => $studentsPerDepartment,
            'totalInstructors' => User::where('role', 'instructor')->count(),
            'studentsPerCourse' => $studentsPerCourse
        ]);
    }

    private function geCoordinatorDashboard()
    {
        if (!session()->has('active_academic_period_id')) {
            return redirect()->route('select.academicPeriod');
        }

        $academicPeriodId = session('active_academic_period_id');
        
        // Get GE department
        $geDepartment = Department::where('department_code', 'GE')->first();
        
        if (!$geDepartment) {
            return back()->with('error', 'GE department not found. Please contact administrator.');
        }

        // Count all instructors in GE department or approved to teach GE subjects
        $countInstructors = User::where("role", 0)
            ->where(function($query) use ($geDepartment) {
                $query->where("department_id", $geDepartment->id)
                      ->orWhere("can_teach_ge", true);
            })
            ->count();
            
        $countActiveInstructors = User::where("is_active", 1)
            ->where("role", 0)
            ->where(function($query) use ($geDepartment) {
                $query->where("department_id", $geDepartment->id)
                      ->orWhere("can_teach_ge", true);
            })
            ->count();
            
        $countInactiveInstructors = User::where("is_active", 0)
            ->where("role", 0)
            ->where(function($query) use ($geDepartment) {
                $query->where("department_id", $geDepartment->id)
                      ->orWhere("can_teach_ge", true);
            })
            ->count();
            
        $countPendingInstructors = UnverifiedUser::where("department_id", $geDepartment->id)
            ->count();

        $data = [
            "countInstructors" => $countInstructors,
            "countStudents" => Student::whereHas('subjects', function($query) use ($geDepartment, $academicPeriodId) {
                    $query->where('department_id', $geDepartment->id)
                          ->where('academic_period_id', $academicPeriodId);
                })
                ->where("is_deleted", false)
                ->distinct()
                ->count("students.id"),
            "countCourses" => Subject::where("department_id", $geDepartment->id)
                ->where("is_deleted", false)
                ->where('academic_period_id', $academicPeriodId)
                ->distinct('subject_code')
                ->count(),
            "countActiveInstructors" => $countActiveInstructors,
            "countInactiveInstructors" => $countInactiveInstructors,
            "countPendingInstructors" => $countPendingInstructors,
            "activePercentage" => $countInstructors > 0 ? round(($countActiveInstructors / $countInstructors) * 100, 1) : 0,
            "inactivePercentage" => $countInstructors > 0 ? round(($countInactiveInstructors / $countInstructors) * 100, 1) : 0,
            "pendingPercentage" => ($countInstructors + $countPendingInstructors) > 0 ? round(($countPendingInstructors / ($countInstructors + $countPendingInstructors)) * 100, 1) : 0,
        ];

        return view('dashboard.gecoordinator', $data);
    }

    private function getTermId($term)
    {
        return [
            'prelim' => 1,
            'midterm' => 2,
            'prefinal' => 3,
            'final' => 4,
        ][$term] ?? null;
    }
}
