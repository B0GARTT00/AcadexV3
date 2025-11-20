<div class="d-flex flex-column flex-shrink-0 p-3 text-white h-100 sidebar-wrapper">
    <!-- Logo Section -->
    <div class="logo-section">
        <a href="{{ route('dashboard') }}" class="logo-wrapper text-white text-decoration-none">
            <img src="{{ asset('logo.jpg') }}" alt="Logo" class="rounded">
            <span>ACADEX</span>
        </a>
    </div>

    <div class="sidebar-content flex-grow-1 overflow-auto custom-scrollbar">
        <!-- Dashboard Section -->
        <div class="sidebar-section">
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" 
                       class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                        <i class="bi bi-house-door me-3"></i> 
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        @php $role = Auth::user()->role; @endphp

        {{-- Instructor --}}
        @if ($role === 0)
            <div class="sidebar-section">
                <h6 class="px-3 mb-2">INSTRUCTOR PORTAL</h6>
                <ul class="nav nav-pills flex-column">
                    @php
                        $isStudentsActive = request()->routeIs('instructor.students.*');
                    @endphp
                    <li class="nav-item">
                                <a class="nav-link d-flex align-items-center sidebar-link students-toggle {{ $isStudentsActive ? 'active' : '' }}" 
                           onclick="toggleStudentsMenu()"
                           style="cursor: pointer;">
                            <i class="bi bi-people me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                            <span style="flex: 1;">Manage Students</span>
                            <i class="bi bi-chevron-down ms-auto students-chevron {{ $isStudentsActive ? 'rotated' : '' }}" style="flex-shrink: 0;"></i>
                        </a>
                        <div class="students-submenu {{ $isStudentsActive ? 'show' : '' }}" id="studentsSubmenu">
                            <ul class="nav nav-pills flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('instructor.students.index') }}" 
                                       class="nav-link {{ request()->routeIs('instructor.students.index') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-mortarboard me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                                        <span>Student Records</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('instructor.students.import') }}"
                                       class="nav-link {{ request()->routeIs('instructor.students.import') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-file-earmark-arrow-up me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                                        <span>Import Students</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    {{-- Grades Collapsible Menu --}}
                    @php
                        $isGradesActive = request()->routeIs('instructor.grades.*') || request()->routeIs('instructor.final-grades.*');
                    @endphp
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center sidebar-link grades-toggle {{ $isGradesActive ? 'active' : '' }}" 
                           onclick="toggleGradesMenu()"
                           style="cursor: pointer;">
                            <i class="bi bi-card-checklist me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                            <span style="flex: 1;">Grades</span>
                            <i class="bi bi-chevron-down ms-auto grades-chevron {{ $isGradesActive ? 'rotated' : '' }}" style="flex-shrink: 0;"></i>
                        </a>
                        <div class="grades-submenu {{ $isGradesActive ? 'show' : '' }}" id="gradesSubmenu">
                            <ul class="nav nav-pills flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('instructor.grades.index') }}" 
                                       class="nav-link {{ request()->routeIs('instructor.grades.*') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-pencil-square me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                                        <span>Manage Grades</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('instructor.final-grades.index') }}" 
                                       class="nav-link {{ request()->routeIs('instructor.final-grades.*') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-graph-up me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                                        <span>Final Grades</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    <li class="nav-item">
                        <a href="{{ route('instructor.activities.index') }}" 
                           class="nav-link {{ request()->routeIs('instructor.activities.*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-journal-text me-3"></i>
                            <span>Manage Activities</span>
                        </a>
                    </li>
                    
                    {{-- Course Outcome Collapsible Menu --}}
                    @php
                        $isCourseOutcomeActive = request()->routeIs('instructor.course_outcomes.*') || request()->routeIs('instructor.course-outcome-attainments.*');
                    @endphp
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center sidebar-link course-outcome-toggle" 
                           onclick="toggleCourseOutcomeMenu()"
                           style="cursor: pointer;">
                            <i class="bi bi-mortarboard me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                            <span style="flex: 1;">Course Outcome</span>
                            <i class="bi bi-chevron-down ms-auto course-outcome-chevron {{ $isCourseOutcomeActive ? 'rotated' : '' }}" style="flex-shrink: 0;"></i>
                        </a>
                        <div class="course-outcome-submenu {{ $isCourseOutcomeActive ? 'show' : '' }}" id="courseOutcomeSubmenu">
                            <ul class="nav nav-pills flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('instructor.course_outcomes.index') }}" 
                                       class="nav-link {{ request()->routeIs('instructor.course_outcomes.*') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-book me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                                        <span>View Course Outcome</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('instructor.course-outcome-attainments.index') }}"
                                       class="nav-link {{ request()->routeIs('instructor.course-outcome-attainments.*') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-award me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                                        <span>Course Outcome Attainment</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>

            {{-- Data Management section removed for Instructor (Import Students moved to Students submenu) --}}
        @endif

        {{-- Chairperson --}}
        @if ($role === 1)
            @php
                $myTemplateRequests = \App\Models\StructureTemplateRequest::where('chairperson_id', Auth::id())->count();
                $myPendingRequests = \App\Models\StructureTemplateRequest::where('chairperson_id', Auth::id())->pending()->count();
                $isAcademicActive = request()->routeIs('chairperson.instructors') || request()->routeIs('chairperson.assign-subjects') || request()->routeIs('curriculum.selectSubjects') || request()->routeIs('chairperson.studentsByYear') || request()->routeIs('chairperson.viewGrades');
                $isReportsActive = request()->routeIs('chairperson.reports.*') || request()->routeIs('chairperson.course_outcomes.*');
            @endphp

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">DEPARTMENT MANAGEMENT</h6>
                <ul class="nav nav-pills flex-column">
                    {{-- Academic Records dropdown --}}
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center sidebar-link academic-records-toggle {{ $isAcademicActive ? 'active' : '' }}" 
                           onclick="toggleAcademicRecordsMenu()"
                           style="cursor: pointer;">
                            <i class="bi bi-journal-bookmark me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                            <span style="flex: 1;">Academic Records</span>
                            <i class="bi bi-chevron-down ms-auto academic-records-chevron {{ $isAcademicActive ? 'rotated' : '' }}" style="flex-shrink: 0;"></i>
                        </a>
                        <div class="academic-records-submenu {{ $isAcademicActive ? 'show' : '' }}" id="academicRecordsSubmenu">
                            <ul class="nav nav-pills flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('chairperson.instructors') }}" 
                                       class="nav-link {{ request()->routeIs('chairperson.instructors') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-people me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                                        <span>Manage Instructors</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('chairperson.assign-subjects') }}" 
                                       class="nav-link {{ request()->routeIs('chairperson.assign-subjects') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-journal-plus me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                                        <span>Manage Courses</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('curriculum.selectSubjects') }}"
                                       class="nav-link {{ request()->routeIs('curriculum.selectSubjects') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-file-earmark-arrow-up me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                                        <span>Import Courses</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('chairperson.studentsByYear') }}" 
                                       class="nav-link {{ request()->routeIs('chairperson.studentsByYear') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-person-lines-fill me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                                        <span>Manage Students</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('chairperson.viewGrades') }}"     
                                       class="nav-link {{ request()->routeIs('chairperson.viewGrades') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-clipboard-data me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                                        <span>View Grades</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">DATA MANAGEMENT</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('chairperson.structureTemplates.index') }}" 
                           class="nav-link {{ request()->routeIs('chairperson.structureTemplates.*') ? 'active' : '' }} d-flex align-items-center justify-content-between sidebar-link">
                            <div class="d-flex align-items-center" style="flex: 1; min-width: 0;">
                                <i class="bi bi-diagram-3 me-3"></i>
                                <span>Formula Requests</span>
                            </div>
                            <div style="min-width: 30px; text-align: right;">
                                @if ($myPendingRequests > 0)
                                    <span class="badge bg-warning text-dark rounded-pill">{{ $myPendingRequests }}</span>
                                @endif
                            </div>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('chairperson.course_outcomes.index') }}" 
                           class="nav-link {{ request()->routeIs('chairperson.course_outcomes.*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-book me-3"></i>
                            <span>Manage Course Outcome</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">REPORTS</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center sidebar-link chairperson-reports-toggle {{ $isReportsActive ? 'active' : '' }}" 
                           onclick="toggleChairpersonReportsMenu()"
                           style="cursor: pointer;">
                            <i class="bi bi-bar-chart-line me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                            <span style="flex: 1;">Reports</span>
                            <i class="bi bi-chevron-down ms-auto chairperson-reports-chevron {{ $isReportsActive ? 'rotated' : '' }}" style="flex-shrink: 0;"></i>
                        </a>
                        <div class="chairperson-reports-submenu {{ $isReportsActive ? 'show' : '' }}" id="chairpersonReportsSubmenu">
                            <ul class="nav nav-pills flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('chairperson.course_outcomes.index') }}" 
                                       class="nav-link {{ request()->routeIs('chairperson.course_outcomes.*') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-book me-3"></i>
                                        <span>Manage Course Outcome</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('chairperson.reports.co-program') }}" 
                                       class="nav-link {{ request()->routeIs('chairperson.reports.co-program') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-diagram-3 me-3"></i>
                                        <span>Program Outcomes Summary</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('chairperson.reports.co-course') }}" 
                                       class="nav-link {{ request()->routeIs('chairperson.reports.co-course') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-book me-3"></i>
                                        <span>Course Outcomes Summary</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('chairperson.reports.co-student') }}" 
                                       class="nav-link {{ request()->routeIs('chairperson.reports.co-student') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-person-lines-fill me-3"></i>
                                        <span>Student Outcomes Summary</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        @endif

        {{-- Dean --}}
        @if ($role === 2)
            <div class="sidebar-section">
                <h6 class="px-3 mb-2">ACADEMIC OVERVIEW</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('dean.instructors') }}" 
                           class="nav-link {{ request()->routeIs('dean.instructors') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-people me-3"></i>
                            <span>View Instructors</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('dean.students') }}" 
                           class="nav-link {{ request()->routeIs('dean.students') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-mortarboard me-3"></i>
                            <span>View Students</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('dean.grades') }}" 
                           class="nav-link {{ request()->routeIs('dean.grades') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-clipboard-data me-3"></i>
                            <span>View Grades</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">REPORTS</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('dean.reports.co-program') }}" 
                           class="nav-link {{ request()->routeIs('dean.reports.co-program') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-diagram-3 me-3"></i>
                            <span>Program CO Summary</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('dean.reports.co-course') }}" 
                           class="nav-link {{ request()->routeIs('dean.reports.co-course') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-book me-3"></i>
                            <span>Course CO Summary</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('dean.reports.co-student') }}" 
                           class="nav-link {{ request()->routeIs('dean.reports.co-student') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-person-lines-fill me-3"></i>
                            <span>Student CO Report</span>
                        </a>
                    </li>
                </ul>
            </div>
        @endif

        {{-- Admin --}}
        @if ($role === 3)
            <div class="sidebar-section">
                <h6 class="px-3 mb-2">SYSTEM ADMINISTRATION</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('admin.sessions') }}" 
                           class="nav-link {{ request()->routeIs('admin.sessions*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-shield-lock me-3"></i>
                            <span>Session & Activity Monitor</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.departments') }}" 
                           class="nav-link {{ request()->routeIs('admin.departments') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-building me-3"></i>
                            <span>Departments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.courses') }}" 
                           class="nav-link {{ request()->routeIs('admin.courses') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-book me-3"></i>
                            <span>Programs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.subjects') }}" 
                           class="nav-link {{ request()->routeIs('admin.subjects') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-journal-bookmark me-3"></i>
                            <span>Courses</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.gradesFormula') }}" 
                           class="nav-link {{ request()->routeIs('admin.gradesFormula') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-sliders me-3"></i>
                            <span>Grades Formula</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        @php
                            $pendingTemplateRequests = \App\Models\StructureTemplateRequest::pending()->count();
                        @endphp
                        <a href="{{ route('admin.structureTemplateRequests.index') }}" 
                           class="nav-link {{ request()->routeIs('admin.structureTemplateRequests.*') ? 'active' : '' }} d-flex align-items-center justify-content-between sidebar-link">
                            <div class="d-flex align-items-center" style="flex: 1; min-width: 0;">
                                <i class="bi bi-clipboard-check me-3"></i>
                                <span>Formula Requests</span>
                            </div>
                            <div style="min-width: 30px; text-align: right;">
                                @if ($pendingTemplateRequests > 0)
                                    <span class="badge bg-warning text-dark rounded-pill">{{ $pendingTemplateRequests }}</span>
                                @endif
                            </div>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.users') }}" 
                           class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-people me-3"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.academicPeriods') }}" 
                           class="nav-link {{ request()->routeIs('admin.academicPeriods') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-calendar3 me-3"></i>
                            <span>Academic Period</span>
                        </a>
                    </li>
                </ul>
            </div>
        @endif

        {{-- GE Coordinator --}}
        @if ($role === 4)
            <div class="sidebar-section">
                <h6 class="px-3 mb-2">GE COORDINATION</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('gecoordinator.instructors') }}"
                           class="nav-link {{ request()->routeIs('gecoordinator.instructors') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-people me-3"></i>
                            <span>Manage Instructors</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">ACADEMIC RECORDS</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('gecoordinator.assign-subjects') }}"
                           class="nav-link {{ request()->routeIs('gecoordinator.assign-subjects') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-journal-plus me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                            <span>Manage Courses</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('curriculum.selectSubjects') }}"
                           class="nav-link {{ request()->routeIs('curriculum.selectSubjects') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-file-earmark-arrow-up me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                            <span>Import Courses</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('gecoordinator.viewGrades') }}"
                           class="nav-link {{ request()->routeIs('gecoordinator.viewGrades') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-clipboard-data me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                            <span>View Grades</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('gecoordinator.studentsByYear') }}"
                           class="nav-link {{ request()->routeIs('gecoordinator.studentsByYear') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-person-lines-fill me-3" style="width: 20px; text-align: center; display: inline-block; flex-shrink: 0;"></i>
                            <span>View Students</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">REPORTS</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center sidebar-link manage-co-toggle"
                           onclick="toggleManageCOMenu()"
                           style="cursor: pointer;">
                            <i class="bi bi-book me-3"></i>
                            <span style="flex: 1;">Manage Course Outcome</span>
                            <i class="bi bi-chevron-down ms-auto manage-co-chevron" style="flex-shrink: 0;"></i>
                        </a>
                        <div class="manage-co-submenu ms-3 mt-2" id="manageCOSubmenu" style="display: none;">
                            <ul class="nav nav-pills flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('gecoordinator.reports.co-program') }}" 
                                       class="nav-link {{ request()->routeIs('gecoordinator.reports.co-program') ? 'active' : '' }} d-flex align-items-center sidebar-link py-2">
                                        <i class="bi bi-diagram-3 me-3"></i>
                                        <span>Program Outcomes Summary</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('gecoordinator.reports.co-course') }}" 
                                       class="nav-link {{ request()->routeIs('gecoordinator.reports.co-course') ? 'active' : '' }} d-flex align-items-center sidebar-link py-2">
                                        <i class="bi bi-book me-3"></i>
                                        <span>Course Outcomes Summary</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('gecoordinator.reports.co-student') }}" 
                                       class="nav-link {{ request()->routeIs('gecoordinator.reports.co-student') ? 'active' : '' }} d-flex align-items-center sidebar-link py-2">
                                        <i class="bi bi-person-lines-fill me-3"></i>
                                        <span>Student Outcomes Summary</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        @endif

        {{-- VPAA --}}
        @if ($role === 5)
            <div class="sidebar-section">
                <h6 class="px-3 mb-2">VPAA DASHBOARD</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('vpaa.departments') }}" 
                           class="nav-link {{ request()->routeIs('vpaa.departments') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-building me-3"></i>
                            <span>Departments Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('vpaa.students') }}" 
                           class="nav-link {{ request()->routeIs('vpaa.students') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-people-fill me-3"></i>
                            <span>Students Overview</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">REPORTS</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('vpaa.reports.co-program') }}" 
                           class="nav-link {{ request()->routeIs('vpaa.reports.co-program') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-diagram-3 me-3"></i>
                            <span>Program CO Summary</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('vpaa.reports.co-course') }}" 
                           class="nav-link {{ request()->routeIs('vpaa.reports.co-course') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-book me-3"></i>
                            <span>Course CO Summary</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('vpaa.reports.co-student') }}" 
                           class="nav-link {{ request()->routeIs('vpaa.reports.co-student') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-person-lines-fill me-3"></i>
                            <span>Student CO Report</span>
                        </a>
                    </li>
                </ul>
            </div>
        @endif
    </div>
</div>
