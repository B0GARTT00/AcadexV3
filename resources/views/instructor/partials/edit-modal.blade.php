<!-- resources/views/instructor/activities/partials/edit-modal.blade.php -->

<div class="modal fade" id="editActivityModal" tabindex="-1" aria-labelledby="editActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('instructor.activities.update', ['activity' => $activity->id]) }}">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editActivityModalLabel">Edit Activity</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form fields for editing the activity -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ $activity->title }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="quiz" {{ $activity->type == 'quiz' ? 'selected' : '' }}>Quiz</option>
                            <option value="ocr" {{ $activity->type == 'ocr' ? 'selected' : '' }}>OCR</option>
                            <option value="exam" {{ $activity->type == 'exam' ? 'selected' : '' }}>Exam</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="number_of_items" class="form-label">Number of Items</label>
                        <input type="number" class="form-control" id="number_of_items" name="number_of_items" value="{{ $activity->number_of_items }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="course_outcome_id" class="form-label">Course Outcome</label>
                        <select class="form-select" id="course_outcome_id" name="course_outcome_id">
                            <option value="">-- Select Course Outcome --</option>
                            @if(isset($courseOutcomes))
                                @foreach($courseOutcomes as $co)
                                    <option value="{{ $co->id }}" @if($activity->course_outcome_id == $co->id) selected @endif>{{ $co->co_code }} - {{ $co->co_identifier }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
