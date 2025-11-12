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
                    <li class="nav-item">
                        <a href="{{ route('instructor.students.index') }}"
                           class="nav-link {{ request()->routeIs('instructor.students.index') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-mortarboard me-3"></i> 
                            <span>Manage Students</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('instructor.grades.index') }}" 
                           class="nav-link {{ request()->routeIs('instructor.grades.*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-card-checklist me-3"></i>
                            <span>Manage Grades</span>
                        </a>
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
                            <i class="bi bi-mortarboard me-3"></i>
                            <span>Course Outcome</span>
                            <i class="bi bi-chevron-down ms-auto course-outcome-chevron {{ $isCourseOutcomeActive ? 'rotated' : '' }}"></i>
                        </a>
                        <div class="course-outcome-submenu {{ $isCourseOutcomeActive ? 'show' : '' }}" id="courseOutcomeSubmenu">
                            <ul class="nav nav-pills flex-column ms-3">
                                <li class="nav-item">
                                    <a href="{{ route('instructor.course_outcomes.index') }}" 
                                       class="nav-link {{ request()->routeIs('instructor.course_outcomes.*') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-book me-3"></i>
                                        <span>Manage Course Outcome</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('instructor.course-outcome-attainments.index') }}"
                                       class="nav-link {{ request()->routeIs('instructor.course-outcome-attainments.*') ? 'active' : '' }} d-flex align-items-center sidebar-link submenu-link">
                                        <i class="bi bi-award me-3"></i>
                                        <span>Course Outcome Attainment</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    <li class="nav-item">
                        <a href="{{ route('instructor.final-grades.index') }}" 
                           class="nav-link {{ request()->routeIs('instructor.final-grades.*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-graph-up me-3"></i>
                            <span>Final Grades</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">DATA MANAGEMENT</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('instructor.students.import') }}" 
                           class="nav-link {{ request()->routeIs('instructor.students.import') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-file-earmark-arrow-up me-3"></i>
                            <span>Import Students</span>
                        </a>
                    </li>
                </ul>
            </div>
        @endif

        {{-- Chairperson --}}
        @if ($role === 1)
            <div class="sidebar-section">
                <h6 class="px-3 mb-2">QUICK ACCESS</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('notifications.index') }}" 
                           class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-bell me-3"></i>
                            <span>Notifications</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">DEPARTMENT MANAGEMENT</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('chairperson.instructors') }}" 
                           class="nav-link {{ request()->routeIs('chairperson.instructors') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-people me-3"></i>
                            <span>Manage Instructors</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('chairperson.assign-subjects') }}" 
                           class="nav-link {{ request()->routeIs('chairperson.assign-subjects') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-journal-plus me-3"></i>
                            <span>Assign Subjects</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('chairperson.studentsByYear') }}" 
                           class="nav-link {{ request()->routeIs('chairperson.studentsByYear') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-person-lines-fill me-3"></i>
                            <span>Students List</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('chairperson.viewGrades') }}"     
                           class="nav-link {{ request()->routeIs('chairperson.viewGrades') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-clipboard-data me-3"></i>
                            <span>View Grades</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('chairperson.course_outcomes.index') }}" 
                           class="nav-link {{ request()->routeIs('chairperson.course_outcomes.*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-book me-3"></i>
                            <span>Manage Course Outcome</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        @php
                            $myTemplateRequests = \App\Models\StructureTemplateRequest::where('chairperson_id', Auth::id())->count();
                            $myPendingRequests = \App\Models\StructureTemplateRequest::where('chairperson_id', Auth::id())->pending()->count();
                        @endphp
                        <a href="{{ route('chairperson.structureTemplates.index') }}" 
                           class="nav-link {{ request()->routeIs('chairperson.structureTemplates.*') ? 'active' : '' }} d-flex align-items-center justify-content-between sidebar-link">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-diagram-3 me-3"></i>
                                <span>Template Requests</span>
                            </div>
                            @if ($myPendingRequests > 0)
                                <span class="badge bg-warning text-dark rounded-pill">{{ $myPendingRequests }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">REPORTS</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('chairperson.reports.co-program') }}" 
                           class="nav-link {{ request()->routeIs('chairperson.reports.co-program') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-diagram-3 me-3"></i>
                            <span>Program CO Summary</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('chairperson.reports.co-course') }}" 
                           class="nav-link {{ request()->routeIs('chairperson.reports.co-course') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-book me-3"></i>
                            <span>Course CO Summary</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('chairperson.reports.co-student') }}" 
                           class="nav-link {{ request()->routeIs('chairperson.reports.co-student') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-person-lines-fill me-3"></i>
                            <span>Student CO Report</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">DATA MANAGEMENT</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('curriculum.selectSubjects') }}" 
                           class="nav-link {{ request()->routeIs('curriculum.selectSubjects') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-file-earmark-arrow-up me-3"></i>
                            <span>Import Subjects</span>
                        </a>
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
                            <span>Courses</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.subjects') }}" 
                           class="nav-link {{ request()->routeIs('admin.subjects') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-journal-bookmark me-3"></i>
                            <span>Subjects</span>
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
                            <div class="d-flex align-items-center">
                                <i class="bi bi-clipboard-check me-3"></i>
                                <span>Template Requests</span>
                            </div>
                            @if ($pendingTemplateRequests > 0)
                                <span class="badge bg-warning text-dark rounded-pill">{{ $pendingTemplateRequests }}</span>
                            @endif
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
                        <a href="{{ route('admin.sessions') }}" 
                           class="nav-link {{ request()->routeIs('admin.sessions*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-shield-lock me-3"></i>
                            <span>Session & Activity Monitor</span>
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
                <h6 class="px-3 mb-2">QUICK ACCESS</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('notifications.index') }}" 
                           class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-bell me-3"></i>
                            <span>Notifications</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">GE COORDINATION</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('gecoordinator.instructors') }}"
                           class="nav-link {{ request()->routeIs('gecoordinator.instructors') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-people me-3"></i>
                            <span>Manage GE Instructors</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('gecoordinator.assign-subjects') }}"
                           class="nav-link {{ request()->routeIs('gecoordinator.assign-subjects') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-journal-plus me-3"></i>
                            <span>Assign GE Subjects</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('gecoordinator.studentsByYear') }}"
                           class="nav-link {{ request()->routeIs('gecoordinator.studentsByYear') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-person-lines-fill me-3"></i>
                            <span>GE Students List</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('gecoordinator.viewGrades') }}"
                           class="nav-link {{ request()->routeIs('gecoordinator.viewGrades') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-clipboard-data me-3"></i>
                            <span>View GE Grades</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('gecoordinator.course_outcomes.index') }}" 
                           class="nav-link {{ request()->routeIs('gecoordinator.course_outcomes.*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
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
                        <a href="{{ route('gecoordinator.reports.co-program') }}" 
                           class="nav-link {{ request()->routeIs('gecoordinator.reports.co-program') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-diagram-3 me-3"></i>
                            <span>Program CO Summary</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('gecoordinator.reports.co-course') }}" 
                           class="nav-link {{ request()->routeIs('gecoordinator.reports.co-course') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-book me-3"></i>
                            <span>Course CO Summary</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('gecoordinator.reports.co-student') }}" 
                           class="nav-link {{ request()->routeIs('gecoordinator.reports.co-student') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-person-lines-fill me-3"></i>
                            <span>Student CO Report</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h6 class="px-3 mb-2">DATA MANAGEMENT</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('curriculum.selectSubjects') }}"
                           class="nav-link {{ request()->routeIs('curriculum.selectSubjects') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-file-earmark-arrow-up me-3"></i>
                            <span>Import GE Subjects</span>
                        </a>
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
