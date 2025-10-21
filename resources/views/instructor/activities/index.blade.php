@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
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

    {{-- Back and Add Button --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('instructor.grades.index') }}" class="text-success fw-semibold">
            ← Back to Grades
        </a>
        <button type="button" class="btn btn-success d-flex align-items-center gap-2 shadow-sm"
                data-bs-toggle="modal" data-bs-target="#addActivityModal">
            <i class="bi bi-plus-circle-fill"></i> Add New Activity
        </button>
    </div>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('instructor.activities.index') }}" class="mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-medium">Select Subject</label>
                <select name="subject_id" onchange="this.form.submit()" class="form-select">
                    <option value="">-- All Subjects --</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                            {{ $subject->subject_code }} - {{ $subject->subject_description }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-medium">Select Term</label>
                <select name="term" onchange="this.form.submit()" class="form-select">
                    <option value="">-- All Terms --</option>
                    <option value="prelim" {{ request('term') == 'prelim' ? 'selected' : '' }}>Prelim</option>
                    <option value="midterm" {{ request('term') == 'midterm' ? 'selected' : '' }}>Midterm</option>
                    <option value="prefinal" {{ request('term') == 'prefinal' ? 'selected' : '' }}>Prefinal</option>
                    <option value="final" {{ request('term') == 'final' ? 'selected' : '' }}>Final</option>
                </select>
            </div>
        </div>
    </form>

    {{-- Activities Table --}}
    @if(count($activities))
        <div class="table-responsive shadow rounded bg-white">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Term</th>
                        <th class="text-center">Items</th>
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
                                <button type="button"
                                        class="btn btn-success btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editActivityModal{{ $activity->id }}">
                                    Edit
                                </button>
                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#confirmDeleteModal"
                                        data-activity-id="{{ $activity->id }}"
                                        data-activity-title="{{ $activity->title }}">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Edit Modals --}}
        @foreach($activities as $activity)
        <div class="modal fade" id="editActivityModal{{ $activity->id }}" tabindex="-1" aria-labelledby="editActivityModalLabel{{ $activity->id }}" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ route('instructor.activities.update', $activity->id) }}">
                @csrf
                @method('PUT')
                <div class="modal-content rounded-4 shadow-lg overflow-hidden">
                    <div class="modal-header text-white" style="background: linear-gradient(135deg, #4da674, #3d865f);">
                        <h5 class="modal-title fw-semibold" id="editActivityModalLabel{{ $activity->id }}">Edit Activity</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <input type="hidden" name="subject_id" value="{{ $activity->subject_id }}">
                            <input type="hidden" name="term" value="{{ $activity->term }}">

                            <div class="col-md-6">
                                <label class="form-label">Activity Type</label>
                                @php
                                    $editTypeOptions = collect($typeOptions);
                                    if (! $editTypeOptions->contains(mb_strtolower($activity->type))) {
                                        $editTypeOptions->push(mb_strtolower($activity->type));
                                    }
                                @endphp
                                <select name="type" class="form-select" required>
                                    @foreach($editTypeOptions as $type)
                                        <option value="{{ $type }}" {{ mb_strtolower($activity->type) == $type ? 'selected' : '' }}>
                                            {{ $formatActivityType($type) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" value="{{ $activity->title }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Number of Items</label>
                                <input type="number" name="number_of_items" class="form-control" min="1" value="{{ $activity->number_of_items }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Activity</button>
                    </div>
                </div>
            </form>
          </div>
        </div>
        @endforeach

    @else
        <div class="alert alert-warning text-center mt-5 rounded">
            No activities found for selected subject or term.
        </div>
    @endif
</div>

{{-- Add Activity Modal --}}
<div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form method="POST" action="{{ route('instructor.activities.store') }}">
        @csrf
        <div class="modal-content rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #4da674, #3d865f);">
                <h5 class="modal-title fw-semibold" id="addActivityModalLabel">📋 Add New Activity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    @if(request('subject_id') && request('term'))
                        <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                        <input type="hidden" name="term" value="{{ request('term') }}">
                    @else
                        <div class="col-md-6">
                            <label class="form-label">Select Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">-- Select Subject --</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->subject_code }} - {{ $subject->subject_description }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Select Term</label>
                            <select name="term" class="form-select" required>
                                <option value="">-- Select Term --</option>
                                @foreach(['prelim','midterm','prefinal','final'] as $termOption)
                                    <option value="{{ $termOption }}" {{ old('term') == $termOption ? 'selected' : '' }}>
                                        {{ ucfirst($termOption) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-6">
                        <label class="form-label">Activity Type</label>
                        <select name="type" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            @foreach($typeOptions as $type)
                                <option value="{{ $type }}" {{ mb_strtolower(old('type', '')) == $type ? 'selected' : '' }}>
                                    {{ $formatActivityType($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Number of Items</label>
                        <input type="number" name="number_of_items" class="form-control" min="1" value="{{ old('number_of_items') }}" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Activity</button>
            </div>
        </div>
    </form>
  </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" id="deleteActivityForm">
      @csrf
      @method('DELETE')
      <div class="modal-content rounded-4 shadow">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete the activity: <strong id="activityTitlePlaceholder">this activity</strong>?
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Yes, Delete</button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
    const deleteModal = document.getElementById('confirmDeleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const activityId = button.getAttribute('data-activity-id');
        const activityTitle = button.getAttribute('data-activity-title');

        const form = deleteModal.querySelector('#deleteActivityForm');
        form.action = `/instructor/activities/${activityId}`;
        deleteModal.querySelector('#activityTitlePlaceholder').textContent = activityTitle;
    });

    document.addEventListener('DOMContentLoaded', () => {
        @if ($errors->any())
            const modal = new bootstrap.Modal(document.getElementById('addActivityModal'));
            modal.show();
        @endif
    });
</script>
@endpush
@endsection
