<!-- Trigger Button -->
<div class="d-flex justify-content-between align-items-center mb-4 mt-2 flex-wrap gap-2">
    <h4 class="mb-0 fw-semibold text-dark">
        <i class="bi bi-journal-text me-2 text-primary"></i>
        Activities & Grades – {{ ucfirst($term) }}
    </h4>

    <button class="btn btn-success d-flex align-items-center gap-2 shadow-sm"
            data-bs-toggle="modal" data-bs-target="#addActivityModal">
        <i class="bi bi-plus-circle-fill"></i> Add Activity
    </button>
</div>

<!-- Add Activity Modal -->
<div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('instructor.activities.store') }}">
            @csrf
            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
            <input type="hidden" name="term" value="{{ $term }}">

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

            <div class="modal-content rounded-4 shadow-lg overflow-hidden">
                <!-- Modal Header -->
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #4da674, #3d865f);">
                    <h5 class="modal-title" id="addActivityModalLabel">📋 Add New Activity</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Activity Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="">-- Select Type --</option>
                                @foreach($typeOptions as $type)
                                    <option value="{{ $type }}">{{ $formatActivityType($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Number of Items <span class="text-danger">*</span></label>
                            <input type="number" name="number_of_items" class="form-control" required min="1">
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Activity</button>
                </div>
            </div>
        </form>
    </div>
</div>
