<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS with Bootstrap 5 Integration -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('css/datatables-custom.css') }}">
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- App CSS & JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>

    <style>
        :root {
            --primary-green: #0F4B36;
            --dark-green: #023336;
            --light-green: #EAF8E7;
            --active-green: rgba(255, 255, 255, 0.1);
            --hover-green: rgba(255, 255, 255, 0.08);
            --menu-text: rgba(255, 255, 255, 0.9);
            --section-text: rgba(255, 255, 255, 0.6);
            --border-color: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-green);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar-wrapper {
            background-color: var(--primary-green);
            border-right: 1px solid var(--border-color);
            height: 100vh;
            position: fixed;
            width: 16rem;
            display: flex;
            flex-direction: column;
        }

        .sidebar-content {
            height: calc(100vh - 140px); /* Adjusted to make room for version */
            overflow-y: auto;
            flex: 1;
        }

        .sidebar-section h6 {
            color: var(--section-text);
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 1.5rem;
        }

        .sidebar-link {
            padding: 0.625rem 1rem;
            margin: 0.125rem 0;
            border-radius: 0.375rem;
            transition: all 0.2s ease-in-out;
            color: var(--menu-text) !important;
            font-size: 0.9375rem;
        }

        .sidebar-link:hover {
            background-color: var(--hover-green);
            transform: translateX(3px);
            color: #ffffff !important;
        }

        .sidebar-link.active {
            background-color: var(--active-green) !important;
            color: #ffffff !important;
            font-weight: 500;
        }

        .sidebar-link i {
            opacity: 0.9;
            width: 20px;
            text-align: center;
        }

        .sidebar-link:hover i {
            opacity: 1;
        }

        /* Collapsible Menu Styles */
        .course-outcome-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .course-outcome-submenu.show {
            max-height: 200px;
            transition: max-height 0.3s ease-in;
        }

        .course-outcome-chevron {
            transition: transform 0.3s ease;
            font-size: 0.8rem;
        }

        .course-outcome-chevron.rotated {
            transform: rotate(180deg);
        }

        /* Submenu Styles */
        .submenu-link {
            padding: 0.5rem 1rem;
            margin: 0.125rem 0;
            border-radius: 0.375rem;
            transition: all 0.2s ease-in-out;
            color: var(--menu-text) !important;
            font-size: 0.875rem;
            background-color: transparent;
        }

        .submenu-link:hover {
            background-color: var(--hover-green);
            transform: translateX(3px);
            color: #ffffff !important;
        }

        .submenu-link.active {
            background-color: var(--active-green) !important;
            color: #ffffff !important;
            font-weight: 500;
        }

        .submenu-link i {
            opacity: 0.8;
            width: 18px;
            text-align: center;
            font-size: 0.85rem;
        }

        .submenu-link:hover i {
            opacity: 1;
        }

        /* Logo Section */
        .logo-section {
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-wrapper img {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
        }

        .logo-wrapper span {
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: #ffffff;
        }

        /* Navigation Bar */
        .top-nav {
            background-color: var(--dark-green);
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .academic-period {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #ffffff;
            font-size: 0.9375rem;
        }

        .academic-period i {
            opacity: 0.8;
        }

        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        /* Main Content Area */
        .main-content {
            background-color: var(--light-green);
            flex: 1;
            margin-left: 16rem;
            min-height: 100vh;
            width: calc(100% - 16rem);
        }

        /* Transitions */
        .transition-all {
            transition: all 0.2s ease-in-out;
        }

        .hover-lift {
            transition: transform 0.2s ease;
        }

        .hover-lift:hover {
            transform: translateY(-2px);
        }

        /* General UI Elements */
        .app-shadow {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        [x-cloak] {
            display: none !important;
        }

        /* Main Content Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-green);
            border-radius: 4px;
            opacity: 0.7;
        }

        ::-webkit-scrollbar-thumb:hover {
            opacity: 1;
        }

        /* Status Indicator */
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #22c55e;
            border: 2px solid #ffffff;
        }

        /* Version display at sidebar bottom */
        .version-display {
            padding: 1rem;
            font-size: 0.75rem;
            color: var(--menu-text);
            opacity: 0.7;
            border-top: 1px solid var(--border-color);
            text-align: center;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: var(--primary-green);
        }
    </style>

    <!-- Additional Page Styles -->
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar-wrapper">
        @include('layouts.sidebar')
        <div class="version-display">
            Acadex System v1.5.5
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        @include('layouts.navigation')

        <!-- Page Content -->
        <main class="p-4">
            <div class="container-fluid px-4">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Scripts Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Course Outcome Submenu Handler -->
    <script>
        function toggleCourseOutcomeMenu() {
            const submenu = document.getElementById('courseOutcomeSubmenu');
            const chevron = document.querySelector('.course-outcome-chevron');
            
            if (submenu && chevron) {
                if (submenu.classList.contains('show')) {
                    submenu.classList.remove('show');
                    chevron.classList.remove('rotated');
                } else {
                    submenu.classList.add('show');
                    chevron.classList.add('rotated');
                }
            }
        }
        
        // Auto-expand if on Course Outcome pages
        document.addEventListener('DOMContentLoaded', function() {
            const isCourseOutcomePage = window.location.pathname.includes('/course_outcomes') || 
                                      window.location.pathname.includes('/course-outcome-attainments');
            
            if (isCourseOutcomePage) {
                const submenu = document.getElementById('courseOutcomeSubmenu');
                const chevron = document.querySelector('.course-outcome-chevron');
                
                if (submenu && chevron) {
                    submenu.classList.add('show');
                    chevron.classList.add('rotated');
                }
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
