<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Traits\ActivityManagementTrait;


class StudentController extends Controller
{
    use ActivityManagementTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    // 🎓 List Students in a Subject
    public function index(Request $request)
    {
        Gate::authorize('instructor');
    
        $academicPeriodId = session('active_academic_period_id');
    
        $subjects = Subject::where(function($query) use ($academicPeriodId) {
            $query->where('instructor_id', Auth::id())
                  ->orWhereHas('instructors', function($q) {
                      $q->where('instructor_id', Auth::id());
                  });
        })
        ->where('is_deleted', false)
        ->where('academic_period_id', $academicPeriodId)
        ->get();
    
        $courses = Course::where('department_id', Auth::user()->department_id)->get();
    
        $students = null;
        if ($request->has('subject_id')) {
            $subject = Subject::where('id', $request->subject_id)
                ->where(function($query) {
                    $query->where('instructor_id', Auth::id())
                          ->orWhereHas('instructors', function($q) {
                              $q->where('instructor_id', Auth::id());
                          });
                })
                ->firstOrFail();
    
            $students = $subject->students()
                ->where('students.is_deleted', 0)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

        }
    
        return view('instructor.manage-students', compact('subjects', 'courses', 'students'));
    }
    

    // ➕ Show Enrollment Form
    public function create()
    {
        Gate::authorize('instructor');
    
        $academicPeriodId = session('active_academic_period_id');
    
        $courses = Course::where('department_id', Auth::user()->department_id)->get();
    
                $subjects = Subject::where(function($q) {
                        $q->where('instructor_id', Auth::id())
                            ->orWhereHas('instructors', function($q2) { $q2->where('instructor_id', Auth::id()); });
                })
            ->where('academic_period_id', $academicPeriodId)
            ->get();
    
        return view('instructor.add-student', compact('subjects', 'courses'));
    }
    

    public function store(Request $request)
    {
        Gate::authorize('instructor');
    
        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'year_level' => 'required|integer|min:1|max:5',
            'subject_id' => 'required|exists:subjects,id',
            'course_id' => 'required|exists:courses,id',
        ]);
    
        $academicPeriodId = session('active_academic_period_id');
        $instructor = Auth::user();
    
        $subject = Subject::where('id', $request->subject_id)
            ->where('academic_period_id', $academicPeriodId)
            ->where(function($q) {
                $q->where('instructor_id', Auth::id())
                  ->orWhereHas('instructors', function($qr) { $qr->where('instructor_id', Auth::id()); });
            })
            ->firstOrFail();
    
        $student = Student::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'year_level' => $request->year_level,
            'department_id' => $instructor->department_id,
            'course_id' => $instructor->course_id,
            'academic_period_id' => $subject->academic_period_id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    
        StudentSubject::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);
    
        // ✅ Automatically insert default activities for all terms
        foreach (['prelim', 'midterm', 'prefinal', 'final'] as $term) {
            $this->getOrCreateDefaultActivities($subject->id, $term);
        }
    
        return redirect()->route('instructor.students.index')->with('success', 'Student enrolled successfully with default activities.');
    }    
    

    // 🗑 Drop Student from a Subject
    public function drop(Request $request, $studentId)
    {
        Gate::authorize('instructor');

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
        ]);

        StudentSubject::where('student_id', $studentId)
            ->where('subject_id', $request->subject_id)
            ->delete();

        return redirect()->back()->with('success', 'Student dropped from subject.');
    }

    // 📝 Update Student Details and Status
    public function update(Request $request, $studentId)
    {
        Gate::authorize('instructor');

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'year_level' => 'required|integer|min:1|max:5',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        // Update student details
        $student = Student::findOrFail($studentId);
        $student->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'year_level' => $request->year_level,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Student details updated successfully.');
    }
}
