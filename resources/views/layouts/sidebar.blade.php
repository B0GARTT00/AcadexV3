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
                    <li class="nav-item">
                        <a href="{{ route('instructor.course_outcomes.index') }}" 
                           class="nav-link d-flex align-items-center sidebar-link">
                            <i class="bi bi-book me-3 "></i>
                            <span>Manage Course Outcome</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" 
                           class="nav-link d-flex align-items-center sidebar-link">
                            <i class="bi bi-award me-3"></i>
                            <span>Course Outcome</span>
                        </a>
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
        @endif

        {{-- Admin --}}
        @if ($role === 3)
            <div class="sidebar-section">
                <h6 class="px-3 mb-2">SYSTEM ADMINISTRATION</h6>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="{{ route('admin.userLogs') }}" 
                           class="nav-link {{ request()->routeIs('admin.userLogs') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-journal-text me-3"></i>
                            <span>User Logs</span>
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
                        <a href="{{ route('vpaa.instructors') }}" 
                           class="nav-link {{ request()->routeIs('vpaa.instructors*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-people me-3"></i>
                            <span>Manage Instructors</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('vpaa.students') }}" 
                           class="nav-link {{ request()->routeIs('vpaa.students*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-mortarboard me-3"></i>
                            <span>Manage Students</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('vpaa.grades') }}" 
                           class="nav-link {{ request()->routeIs('vpaa.grades*') ? 'active' : '' }} d-flex align-items-center sidebar-link">
                            <i class="bi bi-card-checklist me-3"></i>
                            <span>View Grades</span>
                        </a>
                    </li>
                </ul>
            </div>
        @endif
    </div>
</div>
