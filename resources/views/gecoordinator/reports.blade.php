@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-2xl font-bold text-gray-900">GE Coordinator Reports</h2>
        </div>

        <div class="p-6">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Total Subjects Card -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-600 rounded-md flex items-center justify-center">
                                <i class="fas fa-book text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-blue-600">Total GE Subjects</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $reportData['total_subjects'] }}</div>
                        </div>
                    </div>
                </div>

                <!-- Assigned Subjects Card -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-600 rounded-md flex items-center justify-center">
                                <i class="fas fa-user-check text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-green-600">Assigned Subjects</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $reportData['assigned_subjects'] }}</div>
                        </div>
                    </div>
                </div>

                <!-- Unassigned Subjects Card -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-600 rounded-md flex items-center justify-center">
                                <i class="fas fa-user-times text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-red-600">Unassigned Subjects</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $reportData['unassigned_subjects'] }}</div>
                        </div>
                    </div>
                </div>

                <!-- Total Instructors Card -->
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-600 rounded-md flex items-center justify-center">
                                <i class="fas fa-chalkboard-teacher text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-purple-600">Available Instructors</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $reportData['total_instructors'] }}</div>
                        </div>
                    </div>
                </div>

                <!-- Total Enrollments Card -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-600 rounded-md flex items-center justify-center">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-yellow-600">Total Enrollments</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $reportData['total_enrollments'] }}</div>
                        </div>
                    </div>
                </div>

                <!-- Assignment Rate Card -->
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-indigo-600 rounded-md flex items-center justify-center">
                                <i class="fas fa-percentage text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-indigo-600">Assignment Rate</div>
                            <div class="text-2xl font-bold text-gray-900">
                                @if($reportData['total_subjects'] > 0)
                                    {{ round(($reportData['assigned_subjects'] / $reportData['total_subjects']) * 100, 1) }}%
                                @else
                                    0%
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subjects by Year Level -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Subjects by Year Level</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @for($year = 1; $year <= 4; $year++)
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900">
                                {{ $reportData['subjects_by_year'][$year] ?? 0 }}
                            </div>
                            <div class="text-sm text-gray-600">Year {{ $year }}</div>
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Actions -->
            <div class="flex space-x-4">
                <a href="{{ route('gecoordinator.assign-subjects') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-user-plus mr-2"></i>Assign Subjects
                </a>
                <a href="{{ route('gecoordinator.manage-schedule') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-calendar-alt mr-2"></i>Manage Schedule
                </a>
                <button onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-print mr-2"></i>Print Report
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @media print {
        .no-print {
            display: none !important;
        }
        
        .bg-blue-50, .bg-green-50, .bg-red-50, .bg-purple-50, .bg-yellow-50, .bg-indigo-50 {
            background-color: #f9fafb !important;
            border: 1px solid #d1d5db !important;
        }
    }
</style>
@endpush
@endsection
