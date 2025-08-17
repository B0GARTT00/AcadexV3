@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center">
            <i class="bi bi-speedometer2 text-primary me-3"></i>
            VPAA Dashboard
        </h1>
        <div class="text-sm text-gray-500">
            <i class="bi bi-calendar3 me-1"></i>
            {{ now()->format('F j, Y') }}
        </div>
    </div>

    @if (session('status'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
            <p class="font-bold">Success</p>
            <p>{{ session('status') }}</p>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Departments Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="bi bi-building text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 truncate">Departments</p>
                        <div class="flex items-baseline">
                            <p class="text-2xl font-semibold text-gray-900">{{ $departmentsCount ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3">
                <a href="{{ route('vpaa.departments') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center">
                    View all departments
                    <i class="bi bi-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Instructors Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="bi bi-people-fill text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 truncate">Instructors</p>
                        <div class="flex items-baseline">
                            <p class="text-2xl font-semibold text-gray-900">{{ $instructorsCount ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3">
                <a href="{{ route('vpaa.instructors') }}" class="text-sm font-medium text-green-600 hover:text-green-800 flex items-center">
                    View all instructors
                    <i class="bi bi-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Students Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-cyan-100 text-cyan-600 mr-4">
                        <i class="bi bi-mortarboard-fill text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 truncate">Students</p>
                        <div class="flex items-baseline">
                            <p class="text-2xl font-semibold text-gray-900">{{ $studentsCount ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3">
                <a href="{{ route('vpaa.students') }}" class="text-sm font-medium text-cyan-600 hover:text-cyan-800 flex items-center">
                    View all students
                    <i class="bi bi-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="bi bi-lightning-charge-fill text-yellow-500 me-2"></i>
                Quick Actions
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('vpaa.departments') }}" class="group p-4 border border-gray-200 rounded-lg hover:bg-blue-50 transition-colors">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-blue-100 text-blue-600 mr-4 group-hover:bg-blue-200 transition-colors">
                            <i class="bi bi-building text-xl"></i>
                        </div>
                        <span class="font-medium text-gray-700 group-hover:text-blue-700">Manage Departments</span>
                    </div>
                </a>
                
                <a href="{{ route('vpaa.instructors') }}" class="group p-4 border border-gray-200 rounded-lg hover:bg-green-50 transition-colors">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-green-100 text-green-600 mr-4 group-hover:bg-green-200 transition-colors">
                            <i class="bi bi-people-fill text-xl"></i>
                        </div>
                        <span class="font-medium text-gray-700 group-hover:text-green-700">View All Instructors</span>
                    </div>
                </a>
                
                <a href="{{ route('vpaa.students') }}" class="group p-4 border border-gray-200 rounded-lg hover:bg-cyan-50 transition-colors">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-cyan-100 text-cyan-600 mr-4 group-hover:bg-cyan-200 transition-colors">
                            <i class="bi bi-mortarboard-fill text-xl"></i>
                        </div>
                        <span class="font-medium text-gray-700 group-hover:text-cyan-700">View All Students</span>
                    </div>
                </a>
                
                <a href="{{ route('vpaa.grades') }}" class="group p-4 border border-gray-200 rounded-lg hover:bg-purple-50 transition-colors">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-purple-100 text-purple-600 mr-4 group-hover:bg-purple-200 transition-colors">
                            <i class="bi bi-graph-up text-xl"></i>
                        </div>
                        <span class="font-medium text-gray-700 group-hover:text-purple-700">View Grade Reports</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
