@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-2xl font-bold text-gray-900">Manage Schedule - GE Subjects</h2>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if($subjects->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-300">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                    Subject Code
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                    Subject Title
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                    Units
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                    Year Level
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                    Assigned Instructors
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                    Enrolled Students
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($subjects as $subject)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-b">
                                        {{ $subject->subject_code }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 border-b">
                                        {{ $subject->subject_title }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 border-b">
                                        {{ $subject->units }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 border-b">
                                        Year {{ $subject->year_level }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 border-b">
                                        @if($subject->instructors->count() > 0)
                                            @foreach($subject->instructors as $instructor)
                                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-1 mb-1">
                                                    {{ $instructor->first_name }} {{ $instructor->last_name }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-gray-500 italic">No instructor assigned</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 border-b">
                                        {{ $subject->students->count() }} students
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium border-b">
                                        <button onclick="openScheduleModal({{ $subject->id }}, '{{ $subject->subject_code }}', '{{ addslashes($subject->subject_title) }}')" 
                                                class="text-indigo-600 hover:text-indigo-900 mr-2 px-3 py-1 rounded border border-indigo-600 hover:bg-indigo-50">
                                            <i class="fas fa-calendar-alt mr-1"></i>Manage Schedule
                                        </button>
                                        <button onclick="viewSubjectDetails({{ $subject->id }})" 
                                                class="text-green-600 hover:text-green-900 px-3 py-1 rounded border border-green-600 hover:bg-green-50">
                                            <i class="fas fa-eye mr-1"></i>View Details
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-gray-500">
                        <i class="fas fa-calendar-alt text-4xl mb-4"></i>
                        <p class="text-lg">No GE subjects found for the current academic period.</p>
                        <p class="text-sm">Please check if subjects have been created for this academic period.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
