<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChairpersonController;
use App\Http\Controllers\Chairperson\AccountApprovalController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\DeanController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AcademicPeriodController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\FinalGradeController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\CourseOutcomesController;
use App\Http\Middleware\EnsureAcademicPeriodSet;
use App\Http\Controllers\CourseOutcomeAttainmentController;
use App\Http\Controllers\CourseOutcomeReportsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\GoogleAuthController;

// Welcome Page
use Illuminate\Support\Facades\Auth;

// Google OAuth routes
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('auth.login');
});

// Profile Management
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Academic Period Selection
Route::middleware('auth')->group(function () {
    Route::get('/select-academic-period', function () {
        $periods = \App\Models\AcademicPeriod::where('is_deleted', false)
            ->orderByDesc('academic_year')
            ->orderByRaw("FIELD(semester, '1st', '2nd', 'Summer')")
            ->get();

        return view('instructor.select-academic-period', compact('periods'));
    })->name('select.academicPeriod');

    Route::post('/set-academic-period', function (Request $request) {
        $request->validate([
            'academic_period_id' => 'required|exists:academic_periods,id',
        ]);
        session(['active_academic_period_id' => $request->academic_period_id]);
        return redirect()->intended('/dashboard');
    })->name('set.academicPeriod');
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Notifications (for chairperson and GE coordinator)
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/unread', [NotificationController::class, 'getUnread'])->name('notifications.unread');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// Chairperson Routes
Route::prefix('chairperson')
    ->middleware(['auth', 'academic.period.set'])
    ->name('chairperson.')
    ->group(function () {
        Route::get('/instructors', [ChairpersonController::class, 'manageInstructors'])->name('instructors');
        Route::get('/instructors/create', [ChairpersonController::class, 'createInstructor'])->name('createInstructor');
        Route::post('/instructors/store', [ChairpersonController::class, 'storeInstructor'])->name('storeInstructor');
        Route::post('/instructors/{id}/deactivate', [ChairpersonController::class, 'deactivateInstructor'])->name('deactivateInstructor');
        Route::post('/instructors/{id}/activate', [ChairpersonController::class, 'activateInstructor'])->name('activateInstructor');
        Route::post('/instructors/{id}/request-ge-assignment', [ChairpersonController::class, 'requestGEAssignment'])->name('requestGEAssignment');
        
        Route::get('/assign-subjects', [ChairpersonController::class, 'assignSubjects'])->name('assign-subjects');
        Route::post('/assign-subjects/store', [ChairpersonController::class, 'storeAssignedSubject'])->name('storeAssignedSubject');
        
        // Add this route for toggling assigned subjects
        Route::post('/assign-subjects/toggle', [ChairpersonController::class, 'toggleAssignedSubject'])->name('toggleAssignedSubject');

    Route::get('/grades', [ChairpersonController::class, 'viewGrades'])->name('viewGrades');
    Route::post('/grades/save-notes', [ChairpersonController::class, 'saveGradeNotes'])->name('saveGradeNotes');

    // Reports - Program-level CO compliance for chairperson's department
    Route::get('/reports/co-program', [\App\Http\Controllers\ProgramReportsController::class, 'chairProgram'])->name('reports.co-program');
        // Reports - Per-course and Per-student CO compliance
        Route::get('/reports/co-course', [CourseOutcomeReportsController::class, 'chairCourse'])->name('reports.co-course');
        Route::get('/reports/co-student', [CourseOutcomeReportsController::class, 'chairStudent'])->name('reports.co-student');
        Route::get('/students-by-year', [ChairpersonController::class, 'viewStudentsPerYear'])->name('studentsByYear');

        // Course Outcomes - only Manage Course Outcome access for Chairperson
        Route::resource('course_outcomes', CourseOutcomesController::class);
        Route::patch('/course_outcomes/{courseOutcome}/description', [CourseOutcomesController::class, 'updateDescription'])
            ->name('course_outcomes.update_description');
        Route::post('/course_outcomes/generate', [CourseOutcomesController::class, 'generateCourseOutcomes'])
            ->name('course_outcomes.generate');
        Route::post('/course_outcomes/validate_password', [CourseOutcomesController::class, 'validatePassword'])
            ->name('course_outcomes.validate_password');
        // AJAX endpoint for course outcomes by subject and term (use GradeController)
        Route::get('/course-outcomes', [GradeController::class, 'ajaxCourseOutcomes'])->name('course-outcomes.ajax');

        Route::get('/approvals', [AccountApprovalController::class, 'index'])->name('accounts.index');
        Route::post('/approvals/{id}/approve', [AccountApprovalController::class, 'approve'])->name('accounts.approve');
        Route::post('/approvals/{id}/reject', [AccountApprovalController::class, 'reject'])->name('accounts.reject');
        
        // Structure Template Requests
        Route::get('/structure-templates', [ChairpersonController::class, 'indexTemplateRequests'])->name('structureTemplates.index');
        Route::get('/structure-templates/create', [ChairpersonController::class, 'createTemplateRequest'])->name('structureTemplates.create');
        Route::post('/structure-templates', [ChairpersonController::class, 'storeTemplateRequest'])->name('structureTemplates.store');
        Route::get('/structure-templates/{request}', [ChairpersonController::class, 'showTemplateRequest'])->name('structureTemplates.show');
        Route::delete('/structure-templates/{request}', [ChairpersonController::class, 'destroyTemplateRequest'])->name('structureTemplates.destroy');
    });

// GE Coordinator Routes
Route::prefix('gecoordinator')
    ->middleware(['auth', 'academic.period.set'])
    ->name('gecoordinator.')
    ->group(function () {
        Route::get('/instructors', [\App\Http\Controllers\GECoordinatorController::class, 'manageInstructors'])->name('instructors');
        Route::get('/available-instructors', [\App\Http\Controllers\GECoordinatorController::class, 'getAvailableInstructors'])->name('available-instructors');
        Route::post('/instructors', [\App\Http\Controllers\GECoordinatorController::class, 'storeInstructor'])->name('storeInstructor');
        Route::post('/instructors/{id}/deactivate', [\App\Http\Controllers\GECoordinatorController::class, 'deactivateInstructor'])->name('deactivateInstructor');
        Route::post('/instructors/{id}/activate', [\App\Http\Controllers\GECoordinatorController::class, 'activateInstructor'])->name('activateInstructor');
        
        // Subject Management Routes
        Route::get('/assign-subjects', [\App\Http\Controllers\GECoordinatorController::class, 'assignSubjects'])->name('assign-subjects');
        
        // Handle assigning instructors (POST for new assignments, DELETE for unassigning)
        Route::post('/subjects/assign', [\App\Http\Controllers\GECoordinatorController::class, 'toggleAssignedSubject'])->name('assignInstructor');
        Route::delete('/subjects/unassign', [\App\Http\Controllers\GECoordinatorController::class, 'toggleAssignedSubject'])->name('unassignInstructor');
        
        // Get instructors for a subject
        Route::get('/subjects/{subject}/instructors', [\App\Http\Controllers\GECoordinatorController::class, 'getSubjectInstructors'])
            ->name('getSubjectInstructors');
        
        Route::get('/students-by-year', [\App\Http\Controllers\GECoordinatorController::class, 'viewStudentsPerYear'])->name('studentsByYear');
        Route::get('/grades', [\App\Http\Controllers\GECoordinatorController::class, 'viewGrades'])->name('viewGrades');
        
        // Course Outcomes - GE Coordinator has same access as Chairperson
        Route::resource('course_outcomes', CourseOutcomesController::class);
        Route::patch('/course_outcomes/{courseOutcome}/description', [CourseOutcomesController::class, 'updateDescription'])
            ->name('course_outcomes.update_description');
        Route::post('/course_outcomes/generate', [CourseOutcomesController::class, 'generateCourseOutcomes'])
            ->name('course_outcomes.generate');
        Route::post('/course_outcomes/validate_password', [CourseOutcomesController::class, 'validatePassword'])
            ->name('course_outcomes.validate_password');
        // AJAX endpoint for course outcomes by subject and term (use GradeController)
        Route::get('/course-outcomes', [GradeController::class, 'ajaxCourseOutcomes'])->name('course-outcomes.ajax');
        
        // Account Approval Routes
        Route::get('/approvals', [\App\Http\Controllers\GECoordinator\AccountApprovalController::class, 'index'])->name('accounts.index');
        Route::post('/approvals/{id}/approve', [\App\Http\Controllers\GECoordinator\AccountApprovalController::class, 'approve'])->name('accounts.approve');
        Route::post('/approvals/{id}/reject', [\App\Http\Controllers\GECoordinator\AccountApprovalController::class, 'reject'])->name('accounts.reject');
        
        // GE Assignment Request Routes
        Route::post('/ge-requests/{id}/approve', [\App\Http\Controllers\GECoordinatorController::class, 'approveGERequest'])->name('geRequests.approve');
        Route::post('/ge-requests/{id}/reject', [\App\Http\Controllers\GECoordinatorController::class, 'rejectGERequest'])->name('geRequests.reject');
        
        Route::get('/manage-schedule', [\App\Http\Controllers\GECoordinatorController::class, 'manageSchedule'])->name('manage-schedule');

        // Reports Route
        Route::get('/reports', [\App\Http\Controllers\GECoordinatorController::class, 'reports'])->name('reports');
        
        // CO Reports
        Route::get('/reports/co-student', [\App\Http\Controllers\CourseOutcomeReportsController::class, 'geCoordinatorStudent'])
            ->name('reports.co-student');
        Route::get('/reports/co-course', [\App\Http\Controllers\CourseOutcomeReportsController::class, 'geCoordinatorCourse'])
            ->name('reports.co-course');
        Route::get('/reports/co-program', [\App\Http\Controllers\ProgramReportsController::class, 'geCoordinatorProgram'])
            ->name('reports.co-program');
    });

// Curriculum Routes
Route::middleware(['auth', 'academic.period.set'])->group(function () {
    Route::get('/curriculum/select-subjects', [CurriculumController::class, 'selectSubjects'])->name('curriculum.selectSubjects');
    Route::post('/curriculum/confirm-subjects', [CurriculumController::class, 'confirmSubjects'])->name('curriculum.confirmSubjects');
    Route::get('/curriculum/{curriculum}/fetch-subjects', [CurriculumController::class, 'fetchSubjects'])->name('curriculum.fetchSubjects');
});

// Instructor Routes
Route::prefix('instructor')
    ->middleware(['auth', EnsureAcademicPeriodSet::class])
    ->name('instructor.')
    ->group(function () {
        Route::get('/dashboard', [InstructorController::class, 'dashboard'])->name('dashboard');

        // Student Management
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/enroll', [StudentController::class, 'create'])->name('students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::put('/students/{student}/update', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}/drop', [StudentController::class, 'drop'])->name('students.drop');

        // ✅ Student Import Routes
        Route::get('/students/import', [StudentImportController::class, 'showUploadForm'])->name('students.import');
        Route::post('/students/import', [StudentImportController::class, 'upload'])->name('students.import.upload');
        Route::post('/students/import/confirm', [StudentImportController::class, 'confirmImport'])->name('students.import.confirm');

        // Grades
        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
        Route::get('/grades/partial', [GradeController::class, 'partial'])->name('grades.partial');
        Route::post('/grades/save', [GradeController::class, 'store'])->name('grades.store');
        Route::post('/grades/ajax-save-score', [GradeController::class, 'ajaxSaveScore'])->name('grades.ajaxSaveScore');

        // Final Grades
        Route::get('/final-grades', [FinalGradeController::class, 'index'])->name('final-grades.index');
        Route::post('/final-grades/generate', [FinalGradeController::class, 'generate'])->name('final-grades.generate');

        // Activities
        Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
        Route::get('/activities/create', [ActivityController::class, 'create'])->name('activities.create');
        Route::post('/activities/store', [ActivityController::class, 'store'])->name('activities.store');
        Route::post('/activities/realign', [ActivityController::class, 'realign'])->name('activities.realign');
        Route::put('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities/{id}', [ActivityController::class, 'delete'])->name('activities.delete');

        // Course Outcomes
        Route::resource('course_outcomes', CourseOutcomesController::class);
        Route::patch('/course_outcomes/{courseOutcome}/description', [CourseOutcomesController::class, 'updateDescription'])
            ->name('course_outcomes.update_description');
        // AJAX endpoint for course outcomes by subject and term (use GradeController)
        Route::get('/course-outcomes', [GradeController::class, 'ajaxCourseOutcomes'])  ->name('course-outcomes.ajax');

        // Course Outcome Attainments
        Route::get('/course-outcome-attainments', [CourseOutcomeAttainmentController::class,    'index'])->name('course-outcome-attainments.index');
        Route::get('/course-outcome-attainments/subject/{subject}', [CourseOutcomeAttainmentController::class, 'subject'])->name('course-outcome-attainments.subject');
        Route::post('/course-outcome-attainments', [CourseOutcomeAttainmentController::class,   'store'])->name('course-outcome-attainments.store');
        Route::get('/course-outcome-attainments/{id}',  [CourseOutcomeAttainmentController::class, 'show'])->name('course-outcome-attainments.show');
        Route::put('/course-outcome-attainments/{id}',  [CourseOutcomeAttainmentController::class, 'update'])->name('course-outcome-attainments. update');
        Route::delete('/course-outcome-attainments/{id}', [CourseOutcomeAttainmentController::class, 'destroy'])->name('course-outcome-attainments.destroy');
    });

// Dean Routes
Route::prefix('dean')->middleware(['auth', 'academic.period.set'])->name('dean.')->group(function () {
    Route::get('/instructors', [DeanController::class, 'viewInstructors'])->name('instructors');
    Route::get('/students', [DeanController::class, 'viewStudents'])->name('students');
    Route::get('/grades', [DeanController::class, 'viewGrades'])->name('grades');
    Route::get('/instructor/grades/partial', [GradeController::class, 'partial'])->name('instructor.grades.partial');
    Route::get('/dean/students', [DeanController::class, 'viewStudents'])->name('dean.students');
    
    // CO Reports - Dean sees their department's data
    Route::get('/reports/co-program', [\App\Http\Controllers\ProgramReportsController::class, 'deanProgram'])->name('reports.co-program');
    Route::get('/reports/co-course', [\App\Http\Controllers\CourseOutcomeReportsController::class, 'deanCourse'])->name('reports.co-course');
    Route::get('/reports/co-student', [\App\Http\Controllers\CourseOutcomeReportsController::class, 'deanStudent'])->name('reports.co-student');
});

// Admin Routes
Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {
    Route::get('/departments', [AdminController::class, 'departments'])->name('departments');
    Route::get('/departments/create', [AdminController::class, 'createDepartment'])->name('createDepartment');
    Route::post('/departments/store', [AdminController::class, 'storeDepartment'])->name('storeDepartment');

    Route::get('/courses', [AdminController::class, 'courses'])->name('courses');
    Route::get('/courses/create', [AdminController::class, 'createCourse'])->name('createCourse');
    Route::post('/courses/store', [AdminController::class, 'storeCourse'])->name('storeCourse');

    Route::get('/subjects', [AdminController::class, 'subjects'])->name('subjects');
    Route::get('/subjects/create', [AdminController::class, 'createSubject'])->name('createSubject');
    Route::post('/subjects/store', [AdminController::class, 'storeSubject'])->name('storeSubject');

    Route::get('/academic-periods', [AcademicPeriodController::class, 'index'])->name('academicPeriods');
    Route::post('/academic-periods/generate', [AcademicPeriodController::class, 'generate'])->name('academicPeriods.generate');
    Route::get('/user-logs', [AdminController::class, 'viewUserLogs'])->name('userLogs');
    Route::get('/admin/user-logs/filter', [AdminController::class, 'filterUserLogs'])->name('user_logs.filter');

    Route::get('/grades-formula', [AdminController::class, 'gradesFormula'])->name('gradesFormula');
    Route::get('/grades-formula/default', [AdminController::class, 'gradesFormulaDefault'])->name('gradesFormula.default');
    Route::get('/grades-formula/department/{department}', [AdminController::class, 'gradesFormulaDepartment'])->name('gradesFormula.department');
    Route::get('/grades-formula/department/{department}/edit', [AdminController::class, 'gradesFormulaEditDepartment'])->name('gradesFormula.edit.department');
    Route::get('/grades-formula/department/{department}/formulas/create', [AdminController::class, 'createDepartmentFormula'])->name('gradesFormula.department.formulas.create');
    Route::get('/grades-formula/department/{department}/formulas/{formula}/edit', [AdminController::class, 'editDepartmentFormulaEntry'])->name('gradesFormula.department.formulas.edit');
    Route::delete('/grades-formula/department/{department}/formulas/{formula}', [AdminController::class, 'destroyDepartmentFormula'])->name('gradesFormula.department.formulas.destroy');
    Route::post('/grades-formula/department/bulk-apply', [AdminController::class, 'bulkApplyDepartmentFormula'])->name('gradesFormula.department.bulkApply');
    Route::post('/grades-formula/department/{department}/apply-template', [AdminController::class, 'applyDepartmentTemplate'])->name('gradesFormula.department.applyTemplate');
    Route::get('/grades-formula/department/{department}/course/{course}', [AdminController::class, 'gradesFormulaCourse'])->name('gradesFormula.course');
    Route::get('/grades-formula/department/{department}/course/{course}/edit', [AdminController::class, 'gradesFormulaEditCourse'])->name('gradesFormula.edit.course');
    Route::get('/grades-formula/subject/{subject}', [AdminController::class, 'gradesFormulaSubject'])->name('gradesFormula.subject');
    Route::get('/grades-formula/subject/{subject}/edit', [AdminController::class, 'gradesFormulaEditSubject'])->name('gradesFormula.edit.subject');
    Route::post('/grades-formula/subject/{subject}/apply', [AdminController::class, 'applySubjectFormula'])->name('gradesFormula.subject.apply');
    Route::delete('/grades-formula/subject/{subject}/custom', [AdminController::class, 'removeSubjectFormula'])->name('gradesFormula.subject.remove');
    Route::post('/grades-formula/store', [AdminController::class, 'storeGradesFormula'])->name('gradesFormula.store');
    Route::put('/grades-formula/{formula}', [AdminController::class, 'updateGradesFormula'])->name('gradesFormula.update');
    Route::delete('/grades-formula/{formula}', [AdminController::class, 'destroyGlobalFormula'])->name('gradesFormula.destroy');
    Route::get('/grades-formula/{formula}/edit', [AdminController::class, 'editGlobalFormula'])->name('gradesFormula.edit');
    Route::post('/grades-formula/structure-template/store', [AdminController::class, 'storeStructureTemplate'])->name('gradesFormula.structureTemplate.store');
    Route::put('/grades-formula/structure-template/{template}', [AdminController::class, 'updateStructureTemplate'])->name('gradesFormula.structureTemplate.update');
    Route::delete('/grades-formula/structure-template/{template}', [AdminController::class, 'destroyStructureTemplate'])->name('gradesFormula.structureTemplate.destroy');

    // Structure Template Requests (from chairpersons)
    Route::get('/structure-template-requests', [AdminController::class, 'indexStructureTemplateRequests'])->name('structureTemplateRequests.index');
    Route::get('/structure-template-requests/{request}', [AdminController::class, 'showStructureTemplateRequest'])->name('structureTemplateRequests.show');
    Route::post('/structure-template-requests/{request}/approve', [AdminController::class, 'approveStructureTemplateRequest'])->name('structureTemplateRequests.approve');
    Route::post('/structure-template-requests/{request}/reject', [AdminController::class, 'rejectStructureTemplateRequest'])->name('structureTemplateRequests.reject');

    Route::get('/users', [AdminController::class, 'viewUsers'])->name('users');
    Route::post('/users/confirm-password', [AdminController::class, 'adminConfirmUserCreationWithPassword'])->name('confirmUserCreationWithPassword');
    Route::post('/users/store-verified-user', [AdminController::class, 'storeUser'])->name('storeVerifiedUser');
    
    // Session Management - Force Logout
    Route::post('/users/{user}/force-logout', [AdminController::class, 'forceLogoutUser'])->name('users.forceLogout');
    Route::get('/users/{user}/session-count', [AdminController::class, 'getUserSessionCount'])->name('users.sessionCount');
});

// VPAA Routes
use App\Http\Controllers\VPAAController as VPAAController;
use App\Http\Controllers\ProgramReportsController;

Route::prefix('vpaa')
    ->middleware(['auth', 'academic.period.set'])
    ->name('vpaa.')
    ->group(function () {
        // Course Outcome Attainment
        Route::get('/course-outcome-attainment', [VPAAController::class, 'viewCourseOutcomeAttainment'])
            ->name('course-outcome-attainment');
        Route::get('/course-outcome-attainment/subject/{subject}', [VPAAController::class, 'subject'])
            ->name('course-outcome-attainment.subject');
        // CO Reports
        Route::get('/reports/co-student', [CourseOutcomeReportsController::class, 'vpaaStudent'])
            ->name('reports.co-student');
        Route::get('/reports/co-course', [CourseOutcomeReportsController::class, 'vpaaCourse'])
            ->name('reports.co-course');
        Route::get('/reports/co-program', [ProgramReportsController::class, 'vpaaDepartment'])
            ->name('reports.co-program');
        // Dashboard
        Route::get('/dashboard', [VPAAController::class, 'index'])->name('dashboard');
        
        // Departments
        Route::get('/departments', [VPAAController::class, 'viewDepartments'])->name('departments');
        Route::post('/departments', [VPAAController::class, 'storeDepartment'])->name('departments.store');
        Route::put('/departments/{department}', [VPAAController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{department}', [VPAAController::class, 'destroyDepartment'])->name('departments.destroy');
        
        // Instructors
        Route::get('/instructors', [VPAAController::class, 'viewInstructors'])->name('instructors');
        Route::get('/instructors/{instructor}/edit', [VPAAController::class, 'editInstructor'])->name('instructors.edit');
        Route::put('/instructors/{instructor}', [VPAAController::class, 'updateInstructor'])->name('instructors.update');
        // Department-specific instructor view - this should come after the edit routes to avoid conflicts
        Route::get('/instructors/department/{departmentId}', [VPAAController::class, 'viewInstructors'])->name('instructors.department');
        
        // Students
        Route::get('/students', [VPAAController::class, 'viewStudents'])->name('students');
        
        // Grades
        Route::get('/grades', [VPAAController::class, 'viewGrades'])->name('grades');
    });

// Add a fallback redirect for VPAA dashboard
Route::get('/vpaa', function () {
    return redirect()->route('vpaa.dashboard');
})->middleware(['auth', 'academic.period.set']);

// Auth Routes
require __DIR__.'/auth.php';
