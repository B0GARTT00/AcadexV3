<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">

    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- DNS Prefetch & Preconnect for CDN resources -->
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Critical CSS to prevent FOUC -->
    <style>
        /* Force light mode - disable Tailwind dark mode */
        html {
            color-scheme: light !important;
        }
        
        /* Override all dark mode Tailwind classes */
        @media (prefers-color-scheme: dark) {
            * {
                color-scheme: light !important;
            }
            
            /* Force all backgrounds to be light */
            .dark\:bg-gray-800,
            [class*="dark:bg-gray"] {
                background-color: #ffffff !important;
            }
            
            /* Force all text to be dark */
            .dark\:text-gray-200,
            .dark\:text-gray-400,
            [class*="dark:text-gray"] {
                color: #000000 !important;
            }
            
            /* Force all borders to be light gray */
            .dark\:border-gray-700,
            [class*="dark:border-gray"] {
                border-color: #e5e7eb !important;
            }
        }
        
        /* Immediate layout structure before external CSS loads */
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow-x: hidden;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #EAF8E7;
        }
        
        /* Loading Overlay */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.3s ease-out, visibility 0.3s ease-out;
        }
        
        body.loaded .page-loader {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        
        .loader-spinner {
            width: 48px;
            height: 48px;
            border: 3px solid #e5e7eb;
            border-top-color: #0F4B36;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 1.25rem;
        }
        
        .loader-text {
            color: #4b5563;
            font-size: 0.875rem;
            font-weight: 400;
            letter-spacing: 0.025em;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .sidebar-wrapper {
            position: fixed;
            left: 0;
            top: 0;
            width: 16rem;
            height: 100vh;
            background-color: #0F4B36;
            z-index: 1040;
        }
        .main-content {
            margin-left: 16rem;
            min-height: 100vh;
            background-color: #EAF8E7;
            position: relative;
            z-index: 1;
        }
        header {
            height: 70px;
            background-color: #023336;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        header .dropdown {
            z-index: 2000 !important;
        }
        header .dropdown-menu {
            z-index: 2010 !important;
            position: absolute !important;
        }
        header h1 {
            font-size: 1rem !important;
            font-weight: 600 !important;
            line-height: 1 !important;
        }
        header .badge {
            transform: translateZ(0);
            backface-visibility: hidden;
            font-size: 0.8125rem !important;
            line-height: 1 !important;
        }
        /* Prevent Alpine flashing */
        [x-cloak] { 
            display: none !important; 
        }
        /* Prevent icon font flickering */
        .bi, i[class*="bi-"] {
            font-style: normal;
            font-weight: normal !important;
            font-variant: normal;
            text-transform: none;
            line-height: 1;
            vertical-align: -.125em;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons - Preload and use CDN for better caching -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- DataTables CSS with Bootstrap 5 Integration -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('css/datatables-custom.css') }}">
    
    <!-- Google Fonts - Inter (with display=swap to prevent FOIT) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- App CSS & JS (with cache busting) -->
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--light-green);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
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
            overflow: hidden;
            z-index: 1040;
            contain: layout style paint;
        }

        .sidebar-content {
            height: calc(100vh - 140px); /* Adjusted to make room for version */
            overflow-y: auto;
            overflow-x: hidden;
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
            transition: background-color 0.2s ease-in-out;
            color: var(--menu-text) !important;
            font-size: 0.9375rem;
            will-change: background-color;
        }

        .sidebar-link:hover {
            background-color: var(--hover-green);
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
            display: inline-block;
            flex-shrink: 0;
            line-height: 1;
            transform: translateZ(0);
            backface-visibility: hidden;
            font-style: normal;
        }

        .sidebar-link:hover i {
            opacity: 1;
        }
        
        /* Icon stability - prevent flickering */
        i, .bi, [class*="bi-"] {
            display: inline-block;
            line-height: 1;
            vertical-align: middle;
            transform: translateZ(0);
            backface-visibility: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Collapsible Menu Styles */
        .course-outcome-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.2s ease-out;
            will-change: max-height;
        }

        .course-outcome-submenu.show {
            max-height: 200px;
            transition: max-height 0.2s ease-in;
        }

        .course-outcome-chevron {
            transition: transform 0.2s ease;
            font-size: 0.8rem;
            display: inline-block;
            width: 16px;
            text-align: center;
        }

        .course-outcome-chevron.rotated {
            transform: rotate(180deg);
        }

        /* Grades Submenu Styles */
        .grades-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.2s ease-out;
            will-change: max-height;
        }

        .grades-submenu.show {
            max-height: 200px;
            transition: max-height 0.2s ease-in;
        }

        .grades-chevron {
            transition: transform 0.2s ease;
            font-size: 0.8rem;
            display: inline-block;
            width: 16px;
            text-align: center;
        }

        .grades-chevron.rotated {
            transform: rotate(180deg);
        }
        
        /* Students submenu styles (same behavior as Course Outcome submenu) */
        .students-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.2s ease-out;
            will-change: max-height;
        }
        .students-submenu.show {
            max-height: 200px;
            transition: max-height 0.2s ease-in;
        }

        .students-chevron {
            transition: transform 0.2s ease;
            font-size: 0.8rem;
            display: inline-block;
            width: 16px;
            text-align: center;
        }
        .students-chevron.rotated { transform: rotate(180deg); }
        
        /* Academic Records submenu styles */
        .academic-records-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            will-change: max-height;
        }
        .academic-records-submenu.show {
            max-height: 300px;
            transition: max-height 0.3s ease-in;
        }

        .academic-records-chevron {
            transition: transform 0.2s ease;
            font-size: 0.8rem;
            display: inline-block;
            width: 16px;
            text-align: center;
        }
        .academic-records-chevron.rotated { transform: rotate(180deg); }
        
        /* Chairperson Reports submenu styles */
        .chairperson-reports-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            will-change: max-height;
        }
        .chairperson-reports-submenu.show {
            max-height: 400px;
            transition: max-height 0.3s ease-in;
        }

        .chairperson-reports-chevron {
            transition: transform 0.2s ease;
            font-size: 0.8rem;
            display: inline-block;
            width: 16px;
            text-align: center;
        }
        .chairperson-reports-chevron.rotated { transform: rotate(180deg); }

        /* Manage CO (GE Coordinator) submenu styles */
        .manage-co-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            will-change: max-height;
        }
        .manage-co-submenu.show {
            max-height: 300px;
            transition: max-height 0.3s ease-in;
        }

        .manage-co-chevron {
            transition: transform 0.2s ease;
            font-size: 0.8rem;
            display: inline-block;
            width: 16px;
            text-align: center;
        }
        .manage-co-chevron.rotated { transform: rotate(180deg); }
        
        /* Prevent sidebar from affecting header */
        .sidebar-wrapper {
            contain: layout style paint;
        }

        /* Submenu Styles */
        .submenu-link {
            padding: 0.5rem 1rem;
            margin: 0.125rem 0;
            border-radius: 0.375rem;
            transition: background-color 0.2s ease-in-out;
            color: var(--menu-text) !important;
            font-size: 0.875rem;
            background-color: transparent;
            will-change: background-color;
        }

        .submenu-link:hover {
            background-color: var(--hover-green);
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
            display: inline-block;
            flex-shrink: 0;
            line-height: 1;
        }

        .submenu-link:hover i {
            opacity: 1;
        }

        /* Logo Section */
        .logo-section {
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            transform: translateZ(0);
            backface-visibility: hidden;
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
            display: block;
        }

        .logo-wrapper span {
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: #ffffff;
            line-height: 1;
        }

        /* Navigation Bar */
        .top-nav {
            background-color: var(--dark-green);
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            min-height: 70px;
            contain: layout style paint;
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
            margin-left: 16rem;
            min-height: 100vh;
            width: calc(100% - 16rem);
            position: relative;
        }

        /* Prevent layout shifts */
        * {
            box-sizing: border-box;
        }

        .hover-lift {
            transition: opacity 0.2s ease;
        }

        .hover-lift:hover {
            opacity: 0.9;
        }

        /* General UI Elements */
        .app-shadow {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        [x-cloak] {
            display: none !important;
        }

        /* Prevent Cumulative Layout Shift */
        img {
            display: block;
        }

        /* Reserve space for badges */
        .badge.rounded-pill {
            min-width: 20px;
            display: inline-block;
        }

        /* Stabilize all icons */
        .bi, [class^="bi-"], [class*=" bi-"] {
            display: inline-block;
            line-height: 1;
            vertical-align: middle;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Header stability */
        .header-stable {
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
        }

        /* Prevent icon flickering */
        i {
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
        }

        /* Smooth rendering */
        body {
            overflow-y: scroll;
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

    <!-- Preload critical resources -->
    <link rel="preload" as="image" href="{{ asset('logo.jpg') }}">
    <link rel="preload" as="script" href="{{ asset('js/page-transition.js') }}">
    
    <!-- Page transition handler (load early) -->
    <script src="{{ asset('js/page-transition.js') }}" defer></script>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="page-loader">
        <div class="loader-spinner"></div>
        <div class="loader-text">Loading...</div>
    </div>
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

    <!-- Remove loading class when page is ready -->
    <script>
        // Show content when page is fully loaded
        window.addEventListener('load', function() {
            // Small delay for smoother transition
            setTimeout(function() {
                document.body.classList.add('loaded');
            }, 150);
        });

        // Fallback if load event already fired
        if (document.readyState === 'complete') {
            setTimeout(function() {
                document.body.classList.add('loaded');
            }, 150);
        } else if (document.readyState === 'interactive') {
            // If DOM is ready but resources are still loading
            setTimeout(function() {
                document.body.classList.add('loaded');
            }, 200);
        }

        // Smooth page transitions - show loader on navigation
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            
            if (link && 
                link.href && 
                link.href.indexOf(window.location.origin) === 0 &&
                !link.hasAttribute('target') &&
                !link.hasAttribute('download') &&
                !link.classList.contains('dropdown-toggle') &&
                !link.getAttribute('href').startsWith('#') &&
                !link.closest('.dropdown-menu')) {
                
                // Show loading screen for internal navigation
                document.body.classList.remove('loaded');
            }
        });

        // Handle browser back/forward
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                setTimeout(function() {
                    document.body.classList.add('loaded');
                }, 100);
            }
        });
    </script>

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
        
        // Students Submenu Handler
        function toggleStudentsMenu() {
            const submenu = document.getElementById('studentsSubmenu');
            const chevron = document.querySelector('.students-chevron');
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
        
        // Manage Course Outcome Submenu Handler
        function toggleManageCOMenu() {
            const submenu = document.getElementById('manageCOSubmenu');
            const chevron = document.querySelector('.manage-co-chevron');
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
        
        // Manage Academic Records Submenu Handler
        function toggleAcademicRecordsMenu() {
            const submenu = document.getElementById('academicRecordsSubmenu');
            const chevron = document.querySelector('.academic-records-chevron');
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
        
        // Auto-expand if on Course Outcome pages (GE Coordinator)
        document.addEventListener('DOMContentLoaded', function() {
            const isCOPage = window.location.pathname.includes('/gecoordinator/reports/co-');
            if (isCOPage) {
                const submenu = document.getElementById('manageCOSubmenu');
                const chevron = document.querySelector('.manage-co-chevron');
                if (submenu && chevron) {
                    submenu.classList.add('show');
                    chevron.classList.add('rotated');
                }
            }
        });
        
        // Auto-expand if on Students pages
        document.addEventListener('DOMContentLoaded', function() {
            const isStudentsPage = window.location.pathname.includes('/instructor/students');
            if (isStudentsPage) {
                const submenu = document.getElementById('studentsSubmenu');
                const chevron = document.querySelector('.students-chevron');
                if (submenu && chevron) {
                    submenu.classList.add('show');
                    chevron.classList.add('rotated');
                }
            }
        });
        
        // Chairperson Reports Submenu Handler
        function toggleChairpersonReportsMenu() {
            const submenu = document.getElementById('chairpersonReportsSubmenu');
            const chevron = document.querySelector('.chairperson-reports-chevron');
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
        // Auto-expand if on Chairperson Reports or Course Outcome pages
        document.addEventListener('DOMContentLoaded', function() {
            const isChairpersonReports = window.location.pathname.includes('/chairperson/reports') || window.location.pathname.includes('/chairperson/course_outcomes');
            if (isChairpersonReports) {
                const submenu = document.getElementById('chairpersonReportsSubmenu');
                const chevron = document.querySelector('.chairperson-reports-chevron');
                if (submenu && chevron) {
                    submenu.classList.add('show');
                    chevron.classList.add('rotated');
                }
            }
        });
    </script>

    <!-- Grades Submenu Handler -->
    <script>
        function toggleGradesMenu() {
            const submenu = document.getElementById('gradesSubmenu');
            const chevron = document.querySelector('.grades-chevron');
            
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
        
        // Auto-expand if on Grades pages
        document.addEventListener('DOMContentLoaded', function() {
            const isGradesPage = window.location.pathname.includes('/grades') || 
                                window.location.pathname.includes('/final-grades');
            
            if (isGradesPage) {
                const submenu = document.getElementById('gradesSubmenu');
                const chevron = document.querySelector('.grades-chevron');
                
                if (submenu && chevron) {
                    submenu.classList.add('show');
                    chevron.classList.add('rotated');
                }
            }
        });
    </script>

    {{-- Sign Out Confirmation Modal - At body level for proper z-index stacking --}}
    <div class="modal fade" id="signOutModal" tabindex="-1" aria-labelledby="signOutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow bg-white">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title text-danger fw-semibold" id="signOutModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Confirm Sign Out
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-dark">
                    Are you sure you want to sign out?
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('logout') }}" id="logoutForm" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            Yes, Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Global Modal Backdrop Fix --}}
    <script>
        // Fix all modals to have no backdrop - must run before any modal initialization
        (function() {
            // Wait for Bootstrap to be available
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                // Store original methods
                const originalShow = bootstrap.Modal.prototype.show;
                const originalGetOrCreateInstance = bootstrap.Modal.getOrCreateInstance;
                
                // Override show method
                bootstrap.Modal.prototype.show = function() {
                    if (this._config && this._config.backdrop !== false) {
                        this._config.backdrop = false;
                    }
                    originalShow.call(this);
                };
                
                // Override getOrCreateInstance to always use backdrop: false
                bootstrap.Modal.getOrCreateInstance = function(element, config) {
                    config = config || {};
                    config.backdrop = false;
                    return originalGetOrCreateInstance.call(this, element, config);
                };
                
                // Override constructor to set backdrop: false by default
                const OriginalModal = bootstrap.Modal;
                bootstrap.Modal = function(element, config) {
                    config = config || {};
                    if (config.backdrop !== false) {
                        config.backdrop = false;
                    }
                    return new OriginalModal(element, config);
                };
                // Copy static methods
                Object.setPrototypeOf(bootstrap.Modal, OriginalModal);
                bootstrap.Modal.prototype = OriginalModal.prototype;
                bootstrap.Modal.getOrCreateInstance = function(element, config) {
                    config = config || {};
                    config.backdrop = false;
                    return OriginalModal.getOrCreateInstance(element, config);
                };
                bootstrap.Modal.getInstance = OriginalModal.getInstance;
                bootstrap.Modal.VERSION = OriginalModal.VERSION;
                bootstrap.Modal.Default = OriginalModal.Default;
            }
        })();
    </script>

    @stack('scripts')
</body>
</html>
