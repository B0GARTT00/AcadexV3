@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-6">Manage Activities</h1>

    @php
        $typeOptions = collect($activityTypes ?? [])
            ->map(fn ($type) => mb_strtolower($type))
            ->unique()
            ->values()
            ->all();

        if (empty($typeOptions)) {
            $typeOptions = ['quiz', 'ocr', 'exam'];
        }

        $formatActivityType = fn ($type) => ucwords(str_replace('_', ' ', $type));
    @endphp

    {{-- Subject Selection Form --}}
    <form method="GET" action="{{ route('instructor.activities.index') }}" class="mb-6">
        <div class="flex items-center space-x-4">
            <div>
                <label class="block text-sm font-medium">Select Subject:</label>
                <select name="subject_id" class="border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="">-- Choose Subject --</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                            {{ $subject->subject_code }} - {{ $subject->subject_description }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    {{-- If a subject is selected --}}
    @if(request('subject_id'))
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-4">Add New Activity</h2>

            <form method="POST" action="{{ route('instructor.activities.store') }}">
                @csrf
                <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Title:</label>
                        <input type="text" name="title" class="border rounded px-3 py-2 w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Type:</label>
                        <select name="type" class="border rounded px-3 py-2 w-full" required>
                            <option value="">-- Select --</option>
                            @foreach($typeOptions as $type)
                                <option value="{{ $type }}">{{ $formatActivityType($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Term:</label>
                        <select name="term" class="border rounded px-3 py-2 w-full" required>
                            <option value="">-- Select --</option>
                            <option value="prelim">Prelim</option>
                            <option value="midterm">Midterm</option>
                            <option value="prefinal">Prefinal</option>
                            <option value="final">Final</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Number of Items:</label>
                        <input type="number" name="number_of_items" class="border rounded px-3 py-2 w-full" required min="1">
                    </div>
                </div>

                <div class="mt-4 text-right">
                    <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                        Save Activity
                    </button>
                </div>
            </form>
        </div>

        {{-- Existing Activities --}}
        <div class="mt-10">
            <h2 class="text-xl font-semibold mb-4">Existing Activities</h2>

            @if($activities->count())
                <div class="overflow-x-auto">
                    <table class="w-full bg-white border rounded">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="p-3 text-left border">Title</th>
                                <th class="p-3 text-center border">Type</th>
                                <th class="p-3 text-center border">Term</th>
                                <th class="p-3 text-center border">Number of Items</th>
                                <th class="p-3 text-center border">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $activity)
                                <tr class="hover:bg-gray-100">
                                    <td class="p-3 border">{{ $activity->title }}</td>
                                    <td class="p-3 border text-center capitalize">{{ $activity->type }}</td>
                                    <td class="p-3 border text-center capitalize">{{ $activity->term }}</td>
                                    <td class="p-3 border text-center">{{ $activity->number_of_items }}</td>
                                    <td class="p-3 border text-center">
                                        <form method="POST" action="{{ route('instructor.activities.delete', $activity->id) }}" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline font-semibold">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500">No activities found for this subject.</p>
            @endif
        </div>
    @endif
</div>
@endsection
