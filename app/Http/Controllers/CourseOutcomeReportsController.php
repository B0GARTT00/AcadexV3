<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use App\Models\Subject;
use App\Services\CourseOutcomeReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseOutcomeReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'academic.period.set']);
    }

    // VPAA: Per-course CO summary - can view any course
    public function vpaaCourse(Request $request, CourseOutcomeReportingService $service)
    {
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;
        $courseId = (int)$request->input('course_id', 0);

        if (!$courseId) {
            // List all courses to choose from
            $courses = Course::where('is_deleted', false)
                ->orderBy('course_code')
                ->get(['id','course_code','course_description']);
            return view('vpaa.reports.co-course-chooser', [
                'courses' => $courses,
                'academicYear' => $period?->academic_year,
                'semester' => $period?->semester,
            ]);
        }

        $course = Course::where('id', $courseId)
            ->where('is_deleted', false)
            ->firstOrFail();
        
        // Get all subjects for this course in the active period
        $subjects = Subject::where('course_id', $courseId)
            ->where('is_deleted', false)
            ->when($periodId, fn($q) => $q->where('academic_period_id', $periodId))
            ->orderBy('subject_code')
            ->get();

        // Calculate CO for each subject
        $subjectCOs = [];
        foreach ($subjects as $subject) {
            $subjectCOs[$subject->id] = [
                'subject' => $subject,
                'co' => $service->aggregateSubject($subject->id),
            ];
        }

        return view('vpaa.reports.co-course', [
            'course' => $course,
            'subjectCOs' => $subjectCOs,
            'academicYear' => $period?->academic_year,
            'semester' => $period?->semester,
        ]);
    }

    // VPAA: Per-student CO report - can view any subject/student
    public function vpaaStudent(Request $request, CourseOutcomeReportingService $service)
    {
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;

        $subjectId = (int)$request->input('subject_id', 0);
        $studentId = (int)$request->input('student_id', 0);

        if (!$subjectId || !$studentId) {
            // Guided chooser: subjects then students
            $subjects = Subject::with('course')
                ->where('is_deleted', false)
                ->when($periodId, fn($q)=>$q->where('academic_period_id', $periodId))
                ->orderBy('subject_code')
                ->get();

            $students = collect();
            if ($subjectId) {
                $students = Student::whereHas('subjects', function($q) use ($subjectId){
                        $q->where('subject_id', $subjectId)->where('student_subjects.is_deleted', false);
                    })
                    ->where('students.is_deleted', false)
                    ->orderBy('last_name')
                    ->get();
            }

            return view('vpaa.reports.co-student-chooser', [
                'subjects' => $subjects,
                'students' => $students,
                'selectedSubjectId' => $subjectId ?: null,
                'academicYear' => $period?->academic_year,
                'semester' => $period?->semester,
            ]);
        }

        $subject = Subject::with(['course','academicPeriod'])
            ->where('id', $subjectId)
            ->where('is_deleted', false)
            ->firstOrFail();
            
        $student = Student::findOrFail($studentId);
        $data = $service->computeStudentSubject($studentId, $subjectId);

        return view('vpaa.reports.co-student', array_merge($data, [
            'selectedSubject' => $subject,
            'student' => $student,
        ]));
    }

    // GE Coordinator: Per-course CO summary - shows all courses, but only GE subjects
    public function geCoordinatorCourse(Request $request, CourseOutcomeReportingService $service)
    {
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;
        $courseId = (int)$request->input('course_id', 0);

        if (!$courseId) {
            // List all courses that have GE subjects
            $coursesWithGESubjects = Subject::where('department_id', 1) // GE subjects
                ->where('is_deleted', false)
                ->when($periodId, fn($q) => $q->where('academic_period_id', $periodId))
                ->distinct()
                ->pluck('course_id')
                ->filter()
                ->all();
            
            $courses = Course::whereIn('id', $coursesWithGESubjects)
                ->where('is_deleted', false)
                ->orderBy('course_code')
                ->get(['id','course_code','course_description']);
                
            return view('gecoordinator.reports.co-course-chooser', [
                'courses' => $courses,
                'academicYear' => $period?->academic_year,
                'semester' => $period?->semester,
            ]);
        }

        $course = Course::where('id', $courseId)
            ->where('is_deleted', false)
            ->firstOrFail();
        
        // Get only GE subjects for this course in the active period
        $subjects = Subject::where('course_id', $courseId)
            ->where('department_id', 1) // Only GE subjects
            ->where('is_deleted', false)
            ->when($periodId, fn($q) => $q->where('academic_period_id', $periodId))
            ->orderBy('subject_code')
            ->get();

        // Calculate CO for each GE subject
        $subjectCOs = [];
        foreach ($subjects as $subject) {
            $subjectCOs[$subject->id] = [
                'subject' => $subject,
                'co' => $service->aggregateSubject($subject->id),
            ];
        }

        return view('gecoordinator.reports.co-course', [
            'course' => $course,
            'subjectCOs' => $subjectCOs,
            'academicYear' => $period?->academic_year,
            'semester' => $period?->semester,
        ]);
    }

    // GE Coordinator: Per-student CO report - only GE subjects
    public function geCoordinatorStudent(Request $request, CourseOutcomeReportingService $service)
    {
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;

        $subjectId = (int)$request->input('subject_id', 0);
        $studentId = (int)$request->input('student_id', 0);

        if (!$subjectId || !$studentId) {
            // Guided chooser: GE subjects then students
            $subjects = Subject::with('course')
                ->where('department_id', 1) // GE subjects
                ->where('is_deleted', false)
                ->when($periodId, fn($q)=>$q->where('academic_period_id', $periodId))
                ->orderBy('subject_code')
                ->get();

            $students = collect();
            if ($subjectId) {
                $students = Student::whereHas('subjects', function($q) use ($subjectId){
                        $q->where('subject_id', $subjectId)->where('student_subjects.is_deleted', false);
                    })
                    ->where('students.is_deleted', false)
                    ->orderBy('last_name')
                    ->get();
            }

            return view('gecoordinator.reports.co-student-chooser', [
                'subjects' => $subjects,
                'students' => $students,
                'selectedSubjectId' => $subjectId ?: null,
                'academicYear' => $period?->academic_year,
                'semester' => $period?->semester,
            ]);
        }

        // Ensure the subject is a GE subject
        $subject = Subject::with(['course','academicPeriod'])
            ->where('department_id', 1) // GE subject
            ->where('id', $subjectId)
            ->where('is_deleted', false)
            ->firstOrFail();
            
        $student = Student::findOrFail($studentId);
        $data = $service->computeStudentSubject($studentId, $subjectId);

        return view('gecoordinator.reports.co-student', array_merge($data, [
            'selectedSubject' => $subject,
            'student' => $student,
        ]));
    }

    // Chair: Per-course (program/course_id) CO summary for active period
    public function chairCourse(Request $request, CourseOutcomeReportingService $service)
    {
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;
        $courseId = (int)$request->input('course_id', 0);

        if (!$courseId) {
            // List only the chairperson's assigned course
            $userCourseId = auth()->user()?->course_id;
            
            if (!$userCourseId) {
                abort(403, 'You are not assigned to any course.');
            }
            
            $courses = Course::where('id', $userCourseId)
                ->where('is_deleted', false)
                ->get(['id','course_code','course_description']);
            
            return view('chairperson.reports.co-course-chooser', [
                'courses' => $courses,
                'academicYear' => $period?->academic_year,
                'semester' => $period?->semester,
            ]);
        }

        // Ensure the course belongs to the chairperson's assigned course
        $userCourseId = auth()->user()?->course_id;
        $course = Course::where('id', $courseId)
            ->where('id', $userCourseId)
            ->where('is_deleted', false)
            ->firstOrFail();
        
        // Get all subjects for this course in the active period
        $subjects = Subject::where('course_id', $courseId)
            ->where('is_deleted', false)
            ->when($periodId, fn($q) => $q->where('academic_period_id', $periodId))
            ->orderBy('subject_code')
            ->get();

        // Calculate CO for each subject
        $subjectCOs = [];
        foreach ($subjects as $subject) {
            $subjectCOs[$subject->id] = [
                'subject' => $subject,
                'co' => $service->aggregateSubject($subject->id),
            ];
        }

        return view('chairperson.reports.co-course', [
            'course' => $course,
            'subjectCOs' => $subjectCOs,
            'academicYear' => $period?->academic_year,
            'semester' => $period?->semester,
        ]);
    }

    // Chair: Per-student CO report within a subject
    public function chairStudent(Request $request, CourseOutcomeReportingService $service)
    {
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;

        $subjectId = (int)$request->input('subject_id', 0);
        $studentId = (int)$request->input('student_id', 0);

        if (!$subjectId || !$studentId) {
            // Guided chooser: subjects then students
            $userCourseId = auth()->user()?->course_id;
            
            if (!$userCourseId) {
                abort(403, 'You are not assigned to any course.');
            }
            
            $subjects = Subject::with('course')
                ->where('course_id', $userCourseId)
                ->where('is_deleted', false)
                ->when($periodId, fn($q)=>$q->where('academic_period_id', $periodId))
                ->orderBy('subject_code')
                ->get();

            $students = collect();
            if ($subjectId) {
                $students = Student::whereHas('subjects', function($q) use ($subjectId){
                        $q->where('subject_id', $subjectId)->where('student_subjects.is_deleted', false);
                    })
                    ->where('students.is_deleted', false)
                    ->orderBy('last_name')
                    ->get();
            }

            return view('chairperson.reports.co-student-chooser', [
                'subjects' => $subjects,
                'students' => $students,
                'selectedSubjectId' => $subjectId ?: null,
                'academicYear' => $period?->academic_year,
                'semester' => $period?->semester,
            ]);
        }

        // Ensure the subject belongs to the chairperson's assigned course
        $userCourseId = auth()->user()?->course_id;
        $subject = Subject::with(['course','academicPeriod'])
            ->where('course_id', $userCourseId)
            ->where('id', $subjectId)
            ->where('is_deleted', false)
            ->firstOrFail();
            
        $student = Student::findOrFail($studentId);
        $data = $service->computeStudentSubject($studentId, $subjectId);

        return view('chairperson.reports.co-student', array_merge($data, [
            'selectedSubject' => $subject,
            'student' => $student,
        ]));
    }

    // Dean: Per-course CO summary - their department only
    public function deanCourse(Request $request, CourseOutcomeReportingService $service)
    {
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;
        $courseId = (int)$request->input('course_id', 0);
        $user = auth()->user();
        $departmentId = $user?->department_id;

        if (!$departmentId) {
            abort(403, 'You are not assigned to any department.');
        }

        if (!$courseId) {
            // List courses in dean's department
            $courses = Course::where('department_id', $departmentId)
                ->where('is_deleted', false)
                ->orderBy('course_code')
                ->get(['id','course_code','course_description']);
            return view('dean.reports.co-course-chooser', [
                'courses' => $courses,
                'academicYear' => $period?->academic_year,
                'semester' => $period?->semester,
            ]);
        }

        // Ensure the course belongs to dean's department
        $course = Course::where('id', $courseId)
            ->where('department_id', $departmentId)
            ->where('is_deleted', false)
            ->firstOrFail();
        
        // Get all subjects for this course in the active period
        $subjects = Subject::where('course_id', $courseId)
            ->where('is_deleted', false)
            ->when($periodId, fn($q) => $q->where('academic_period_id', $periodId))
            ->orderBy('subject_code')
            ->get();

        // Calculate CO for each subject
        $subjectCOs = [];
        foreach ($subjects as $subject) {
            $subjectCOs[$subject->id] = [
                'subject' => $subject,
                'co' => $service->aggregateSubject($subject->id),
            ];
        }

        return view('dean.reports.co-course', [
            'course' => $course,
            'subjectCOs' => $subjectCOs,
            'academicYear' => $period?->academic_year,
            'semester' => $period?->semester,
        ]);
    }

    // Dean: Per-student CO report - their department only
    public function deanStudent(Request $request, CourseOutcomeReportingService $service)
    {
        $periodId = session('active_academic_period_id');
        $period = $periodId ? AcademicPeriod::find($periodId) : null;

        $subjectId = (int)$request->input('subject_id', 0);
        $studentId = (int)$request->input('student_id', 0);
        $user = auth()->user();
        $departmentId = $user?->department_id;

        if (!$departmentId) {
            abort(403, 'You are not assigned to any department.');
        }

        if (!$subjectId || !$studentId) {
            // Guided chooser: subjects in dean's department then students
            $subjects = Subject::with('course')
                ->where('department_id', $departmentId)
                ->where('is_deleted', false)
                ->when($periodId, fn($q)=>$q->where('academic_period_id', $periodId))
                ->orderBy('subject_code')
                ->get();

            $students = collect();
            if ($subjectId) {
                $students = Student::whereHas('subjects', function($q) use ($subjectId){
                        $q->where('subject_id', $subjectId)->where('student_subjects.is_deleted', false);
                    })
                    ->where('students.is_deleted', false)
                    ->orderBy('last_name')
                    ->get();
            }

            return view('dean.reports.co-student-chooser', [
                'subjects' => $subjects,
                'students' => $students,
                'selectedSubjectId' => $subjectId ?: null,
                'academicYear' => $period?->academic_year,
                'semester' => $period?->semester,
            ]);
        }

        // Ensure the subject belongs to dean's department
        $subject = Subject::with(['course','academicPeriod'])
            ->where('department_id', $departmentId)
            ->where('id', $subjectId)
            ->where('is_deleted', false)
            ->firstOrFail();
            
        $student = Student::findOrFail($studentId);
        $data = $service->computeStudentSubject($studentId, $subjectId);

        return view('dean.reports.co-student', array_merge($data, [
            'selectedSubject' => $subject,
            'student' => $student,
        ]));
    }
}
