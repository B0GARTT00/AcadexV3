<?php

namespace App\Http\Controllers;

use App\Imports\StudentReviewImport;
use App\Models\ReviewStudent;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Traits\ActivityManagementTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class StudentImportController extends Controller
{
    use ActivityManagementTrait;
    /**
     * Show the upload form and review pending imported students.
     */
    public function showUploadForm()
    {
        $academicPeriodId = session('active_academic_period_id');

        $subjects = Subject::with('course')
            ->where(function($q) { // include subjects where current user is primary instructor OR is attached via pivot table
                $q->where('instructor_id', Auth::id())
                  ->orWhereHas('instructors', function($q2) { $q2->where('instructor_id', Auth::id()); });
            })
            ->where('academic_period_id', $academicPeriodId) // ✅ Filter by active period
            ->where('is_deleted', false)
            ->get();

        $reviewStudents = ReviewStudent::with('course', 'subject')
            ->where('instructor_id', Auth::id())
            ->orderByDesc('created_at')
            ->orderBy('is_confirmed')  // Show unconfirmed first
            ->get();

        return view('instructor.excel.import-students', compact('subjects', 'reviewStudents'));
    }

/**
 * Handle Excel upload and store to review table.
 */
public function upload(Request $request)
{
    $request->validate([
        'file'       => 'required|file|mimes:xlsx,xls',
        'list_name'  => 'nullable|string|max:255',
    ]);

    $listName = $request->list_name ?? pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);

    // ✅ Check if list_name already exists
    $exists = ReviewStudent::where('list_name', $listName)
        ->where('instructor_id', Auth::id())
        ->exists();

    if ($exists) {
        return redirect()
            ->route('instructor.students.import')
            ->withErrors(['file' => "❌ A file with the name '{$listName}' already exists."]);
    }

    Excel::import(
        new StudentReviewImport(null, $listName),
        $request->file('file')
    );

    return redirect()
        ->route('instructor.students.import')
        ->with('status', '📥 Student list uploaded for review.');
}

    /**
     * Confirm reviewed students and move to main students table.
     */
    public function confirmImport(Request $request)
    {
        $request->validate([
            'list_name'  => 'required|string',
            'subject_id' => 'required|exists:subjects,id',
        ]);
    
        $academicPeriodId = session('active_academic_period_id');
        // Ensure the subject is accessible to this instructor and belongs to active academic period
        $subject = Subject::where('id', $request->subject_id)
            ->where('academic_period_id', $academicPeriodId)
            ->where(function($q) {
                $q->where('instructor_id', Auth::id())
                  ->orWhereHas('instructors', function($qr) { $qr->where('instructor_id', Auth::id()); });
            })
            ->firstOrFail();
    
        $listName = $request->list_name;
        $selectedIds = explode(',', $request->input('selected_student_ids'));
    
        $reviewStudents = ReviewStudent::where('instructor_id', Auth::id())
            ->where('list_name', $listName)
            ->whereIn('id', $selectedIds)
            ->get();
    
        foreach ($reviewStudents as $review) {
            // Check if a matching student already exists
            $existingStudent = Student::where('first_name', $review->first_name)
                ->where('last_name', $review->last_name)
                ->where('middle_name', $review->middle_name)
                ->where('year_level', $review->year_level)
                ->where('course_id', $review->course_id)
                ->where('academic_period_id', $subject->academic_period_id)
                ->first();
    
            if ($existingStudent) {
                // ✅ Check if already linked to this subject
                $alreadyEnrolled = StudentSubject::where('student_id', $existingStudent->id)
                    ->where('subject_id', $subject->id)
                    ->exists();
    
                if (!$alreadyEnrolled) {
                    StudentSubject::create([
                        'student_id' => $existingStudent->id,
                        'subject_id' => $subject->id,
                    ]);
                }
    
                continue; // Skip creating new Student
            }
    
            // Create new student
            $student = Student::create([
                'first_name'         => $review->first_name,
                'middle_name'        => $review->middle_name,
                'last_name'          => $review->last_name,
                'year_level'         => $review->year_level,
                'course_id'          => $review->course_id,
                'department_id'      => Auth::user()->department_id,
                'academic_period_id' => $subject->academic_period_id,
                'created_by'         => Auth::id(),
                'updated_by'         => Auth::id(),
            ]);
    
            StudentSubject::create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
            ]);
        }
    
        // ✅ Check and create activities for all terms if missing
        $terms = ['prelim', 'midterm', 'prefinal', 'final'];
        foreach ($terms as $term) {
            $this->getOrCreateDefaultActivities($subject->id, $term);
        }
    
                // Mark review students as confirmed instead of deleting them
        ReviewStudent::where('instructor_id', Auth::id())
            ->whereIn('id', $selectedIds)
            ->update(['is_confirmed' => true]);

        return redirect()->route('instructor.students.import')->with('status', '✅ Selected students successfully imported to the selected subject.');
    }
    

    /**
     * API: Get students enrolled in a specific subject (AJAX).
     */
    public function getSubjectStudents($subjectId)
    {
        $subject = Subject::with(['students' => function ($query) {
            $query->where('students.is_deleted', 0)->with('course');
        }])
        ->where('id', $subjectId)
        ->where(function($q) { // allow either primary instructor or pivot instructor
            $q->where('instructor_id', Auth::id())
              ->orWhereHas('instructors', function($q2) { $q2->where('instructor_id', Auth::id()); });
        })
        ->firstOrFail();

        $students = $subject->students->map(function ($student) {
            return [
                'full_name'   => $student->full_name,
                'course_code' => $student->course->course_code ?? 'N/A',
                'year_level'  => $student->formatted_year_level,
            ];
        });

        return response()->json($students);
    }
}


