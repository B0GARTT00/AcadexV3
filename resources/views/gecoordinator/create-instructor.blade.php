@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-8 px-6 bg-white rounded-4 shadow-lg">
    <h1 class="text-2xl font-bold mb-6">
        <i class="bi bi-person-plus-fill text-success me-2"></i>
        Add New Instructor
    </h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="bg-green-100 text-green-800 p-4 rounded mb-6">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('gecoordinator.storeInstructor') }}" method="POST" class="space-y-5">
        @csrf

        {{-- Name --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block font-medium text-sm mb-1">First Name</label>
                <input type="text" name="first_name"
                       class="w-full border border-gray-300 shadow-sm px-3 py-2 rounded focus:ring-2 focus:ring-green-300 focus:outline-none"
                       value="{{ old('first_name') }}" required>
            </div>
            <div>
                <label class="block font-medium text-sm mb-1">Middle Name</label>
                <input type="text" name="middle_name"
                       class="w-full border border-gray-300 shadow-sm px-3 py-2 rounded focus:ring-2 focus:ring-green-300 focus:outline-none"
                       value="{{ old('middle_name') }}">
            </div>
            <div>
                <label class="block font-medium text-sm mb-1">Last Name</label>
                <input type="text" name="last_name"
                       class="w-full border border-gray-300 shadow-sm px-3 py-2 rounded focus:ring-2 focus:ring-green-300 focus:outline-none"
                       value="{{ old('last_name') }}" required>
            </div>
        </div>

        {{-- Email (username only) --}}
        <div>
            <label class="block font-medium text-sm mb-1">Email Username</label>
            <div class="flex">
                <input type="text" name="email"
                       class="w-full border border-gray-300 shadow-sm px-3 py-2 rounded-l focus:ring-2 focus:ring-green-300 focus:outline-none"
                       value="{{ old('email') }}" pattern="^[^@]+$" required placeholder="jdelacruz">
                <span class="inline-flex items-center px-3 border border-l-0 border-gray-300 bg-gray-100 rounded-r text-sm text-gray-600">
                    @brokenshire.edu.ph
                </span>
            </div>
        </div>

        {{-- Department and Course --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-medium text-sm mb-1">Department</label>
                <select name="department_id"
                        class="w-full border border-gray-300 shadow-sm px-3 py-2 rounded focus:ring-2 focus:ring-green-300 focus:outline-none" required>
                    <option value="">-- Select Department --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->department_description }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-medium text-sm mb-1">Course</label>
                <select name="course_id"
                        class="w-full border border-gray-300 shadow-sm px-3 py-2 rounded focus:ring-2 focus:ring-green-300 focus:outline-none" required>
                    <option value="">-- Select Course --</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                            {{ $course->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Password --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-medium text-sm mb-1">Password</label>
                <input type="password" name="password"
                       class="w-full border border-gray-300 shadow-sm px-3 py-2 rounded focus:ring-2 focus:ring-green-300 focus:outline-none"
                       required>
            </div>
            <div>
                <label class="block font-medium text-sm mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation"
                       class="w-full border border-gray-300 shadow-sm px-3 py-2 rounded focus:ring-2 focus:ring-green-300 focus:outline-none"
                       required>
            </div>
        </div>

        {{-- Submit --}}
        <div class="text-end">
            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded shadow-sm transition">
                Submit for Approval
            </button>
        </div>
    </form>
</div>
@endsection
