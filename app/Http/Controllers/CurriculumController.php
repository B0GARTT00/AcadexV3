<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Department;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CurriculumController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        Gate::authorize('admin-chair');

        $query = Curriculum::with('course')->orderByDesc('created_at');
        
        // If user is chairperson, only show curriculums for their assigned course
        if (Gate::allows('chairperson')) {
            $query->whereHas('course', function($q) {
                $q->where('id', Auth::user()->course_id);
            });
        }

        $curriculums = $query->get();

        return view('curriculum.index', compact('curriculums'));
    }

    public function show(Curriculum $curriculum)
    {
        Gate::authorize('admin-chair');

        // If user is chairperson, verify they can access this curriculum
        if (Gate::allows('chairperson') && $curriculum->course_id !== Auth::user()->course_id) {
            abort(403, 'Unauthorized access to this curriculum.');
        }

        $subjects = $curriculum->subjects()->orderBy('year_level')->orderBy('semester')->get();

        return view('curriculum.show', compact('curriculum', 'subjects'));
    }

    public function create()
    {
        Gate::authorize('admin-chair');

        $query = Course::where('is_deleted', false);
        
        // If user is chairperson, only show their assigned course
        if (Gate::allows('chairperson')) {
            $query->where('id', Auth::user()->course_id);
        }

        $courses = $query->orderBy('course_code')->get();

        return view('curriculum.create', compact('courses'));
    }

    public function store(Request $request)
    {
        Gate::authorize('admin-chair');

        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255|unique:curriculums,name',
        ]);

        // If user is chairperson, verify they can create curriculum for this course
        if (Gate::allows('chairperson') && $request->course_id != Auth::user()->course_id) {
            abort(403, 'Unauthorized to create curriculum for this course.');
        }

        Curriculum::create([
            'course_id' => $request->course_id,
            'name' => $request->name,
            'is_active' => true,
        ]);

        return redirect()->route('curriculum.index')->with('success', 'Curriculum created successfully.');
    }

    public function destroy(Curriculum $curriculum)
    {
        Gate::authorize('admin-chair');

        // If user is chairperson, verify they can delete this curriculum
        if (Gate::allows('chairperson') && $curriculum->course_id !== Auth::user()->course_id) {
            abort(403, 'Unauthorized to delete this curriculum.');
        }

        $curriculum->delete();

        return redirect()->route('curriculum.index')->with('success', 'Curriculum deleted.');
    }

    public function addSubject(Request $request, Curriculum $curriculum)
    {
        Gate::authorize('admin-chair');

        // If user is chairperson, verify they can add subjects to this curriculum
        if (Gate::allows('chairperson') && $curriculum->course_id !== Auth::user()->course_id) {
            abort(403, 'Unauthorized to add subjects to this curriculum.');
        }

        $request->validate([
            'subject_code' => 'required|string|max:255',
            'subject_description' => 'required|string|max:255',
            'year_level' => 'required|integer',
            'semester' => 'required|string',
        ]);

        $curriculum->subjects()->create([
            'subject_code' => $request->subject_code,
            'subject_description' => $request->subject_description,
            'year_level' => $request->year_level,
            'semester' => $request->semester,
        ]);

        return redirect()->route('curriculum.show', $curriculum)->with('success', 'Subject added to curriculum.');
    }

    public function removeSubject(CurriculumSubject $subject)
    {
        Gate::authorize('admin-chair');

        // If user is chairperson, verify they can remove subjects from this curriculum
        if (Gate::allows('chairperson') && $subject->curriculum->course_id !== Auth::user()->course_id) {
            abort(403, 'Unauthorized to remove subjects from this curriculum.');
        }

        $subject->delete();

        return back()->with('success', 'Subject removed from curriculum.');
    }

    public function selectSubjects()
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 2 || Auth::user()->role === 4)) {
            abort(403);
        }

        // For Chairperson: show curriculums for their assigned course
        if (Auth::user()->role === 1) {
            $curriculums = Curriculum::with('course')
                ->whereHas('course', function($q) {
                    $q->where('id', Auth::user()->course_id);
                })
                ->get();
            return view('chairperson.select-curriculum-subjects', compact('curriculums'));
        }

        // For GE Coordinator: show all curriculums (they can select any, but will filter to GE subjects)
        if (Auth::user()->role === 4) {
            $curriculums = Curriculum::with('course')->get();
            return view('chairperson.select-curriculum-subjects', compact('curriculums'));
        }

        // Default: show all curriculums (for admin/dean)
        $curriculums = Curriculum::with('course')->get();
        return view('chairperson.select-curriculum-subjects', compact('curriculums'));
    }

    public function fetchSubjects(Curriculum $curriculum)
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 2 || Auth::user()->role === 4)) {
            abort(403);
        }

        // If user is chairperson, verify they can fetch subjects from this curriculum
        if (Auth::user()->role === 1 && $curriculum->course_id !== Auth::user()->course_id) {
            abort(403, 'Unauthorized to fetch subjects from this curriculum.');
        }

        $curriculumSubjects = $curriculum->subjects()
            ->orderBy('year_level')
            ->orderBy('semester')
            ->get();

        $subjects = $curriculumSubjects->map(function($cs) {
            // Mark as GE if subject code starts with 'GE', 'NSTP', 'PD', 'PE', 'RS' or contains 'General Education'
            $isGE = stripos($cs->subject_code, 'GE') === 0 || 
                    stripos($cs->subject_code, 'NSTP') === 0 ||
                    stripos($cs->subject_code, 'PD') === 0 ||
                    stripos($cs->subject_code, 'PE') === 0 ||
                    stripos($cs->subject_code, 'RS') === 0 ||
                    stripos($cs->subject_description, 'General Education') !== false ||
                    stripos($cs->subject_description, 'Understanding the Self') !== false ||
                    stripos($cs->subject_description, 'Philippine History') !== false ||
                    stripos($cs->subject_description, 'Mathematics in the Modern World') !== false;
            
            // For Chairperson: exclude GE, PD, PE, RS, NSTP subjects
            $isRestricted = false;
            if (Auth::user()->role === 1) {
                $isRestricted = $isGE;
            }
            
            return [
                'id' => $cs->id,
                'subject_code' => $cs->subject_code,
                'subject_description' => $cs->subject_description,
                'year_level' => $cs->year_level,
                'semester' => $cs->semester,
                'is_universal' => $isGE,
                'is_restricted' => $isRestricted,
            ];
        });

        return response()->json($subjects);
    }

    public function confirmSubjects(Request $request)
    {
        if (!(Auth::user()->role === 1 || Auth::user()->role === 2 || Auth::user()->role === 4)) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'curriculum_id' => 'required|exists:curriculums,id',
            'subject_ids' => 'required|array',
        ]);

        $curriculum = Curriculum::findOrFail($request->curriculum_id);

        // If user is chairperson, verify they can confirm subjects for this curriculum
        if (Auth::user()->role === 1 && $curriculum->course_id !== Auth::user()->course_id) {
            abort(403, 'Unauthorized to confirm subjects for this curriculum.');
        }

        $subjects = CurriculumSubject::where('curriculum_id', $request->curriculum_id)
            ->whereIn('id', $request->subject_ids)
            ->get();

        foreach ($subjects as $curriculumSubject) {
            // For GE subjects, set the department to GE
            $isGE = stripos($curriculumSubject->subject_code, 'GE') === 0 || 
                   stripos($curriculumSubject->subject_code, 'NSTP') === 0 ||
                   stripos($curriculumSubject->subject_code, 'PD') === 0 ||
                   stripos($curriculumSubject->subject_code, 'PE') === 0 ||
                   stripos($curriculumSubject->subject_code, 'RS') === 0 ||
                   stripos($curriculumSubject->subject_description, 'General Education') !== false ||
                   stripos($curriculumSubject->subject_description, 'Understanding the Self') !== false ||
                   stripos($curriculumSubject->subject_description, 'Philippine History') !== false ||
                   stripos($curriculumSubject->subject_description, 'Mathematics in the Modern World') !== false;
            
            $departmentId = $isGE ? 
                Department::where('department_code', 'GE')->first()->id : 
                Auth::user()->department_id;

            Subject::firstOrCreate([
                'subject_code' => $curriculumSubject->subject_code
            ], [
                'subject_description' => $curriculumSubject->subject_description,
                'year_level' => $curriculumSubject->year_level,
                'department_id' => $departmentId,
                'course_id' => $curriculumSubject->curriculum->course_id,
                'academic_period_id' => session('active_academic_period_id'),
                'is_universal' => $isGE,
                'is_deleted' => false,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        return back()->with('success', 'Subjects confirmed and added to the subject list.');
    }
}
