@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <h1 class="h2 fw-bold mb-4">Manage Activities</h1>

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
    <form method="GET" action="{{ route('instructor.activities.index') }}" class="mb-4">
        <div class="d-flex align-items-center gap-3">
            <div>
                <label class="form-label small fw-medium mb-1">Select Subject:</label>
                <select name="subject_id" class="form-select" onchange="this.form.submit()">
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
        <div class="mb-4">
            <h2 class="h4 fw-semibold mb-3">Add New Activity</h2>

            <form method="POST" action="{{ route('instructor.activities.store') }}">
                @csrf
                <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Title:</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Type:</label>
                        <select name="type" class="form-select" required>
                            <option value="">-- Select --</option>
                            @foreach($typeOptions as $type)
                                <option value="{{ $type }}">{{ $formatActivityType($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Term:</label>
                        <select name="term" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="prelim">Prelim</option>
                            <option value="midterm">Midterm</option>
                            <option value="prefinal">Prefinal</option>
                            <option value="final">Final</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Number of Items:</label>
                        <input type="number" name="number_of_items" class="form-control" required min="1" value="{{ old('number_of_items', 100) }}">
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-success px-4">
                        Save Activity
                    </button>
                </div>
            </form>
        </div>

        {{-- Existing Activities --}}
        <div class="mt-5">
            <h2 class="h4 fw-semibold mb-3">Existing Activities</h2>

            @if($activities->count())
                <div class="table-responsive">
                    <table class="table table-bordered table-hover bg-white">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">Title</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Term</th>
                                <th class="text-center">Number of Items</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $activity)
                                <tr>
                                    <td>{{ $activity->title }}</td>
                                    <td class="text-center text-capitalize">{{ $activity->type }}</td>
                                    <td class="text-center text-capitalize">{{ $activity->term }}</td>
                                    <td class="text-center">{{ $activity->number_of_items }}</td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('instructor.activities.delete', $activity->id) }}" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-0 fw-semibold">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No activities found for this subject.</p>
            @endif
        </div>
    @endif
</div>
@endsection
