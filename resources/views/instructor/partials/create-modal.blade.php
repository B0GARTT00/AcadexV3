<div class="modal fade {{ $errors->any() ? 'show d-block' : '' }}" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityModalLabel" aria-hidden="{{ $errors->any() ? 'false' : 'true' }}">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('instructor.activities.store') }}">
            @csrf
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
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-semibold" id="addActivityModalLabel">➕ Add New Activity</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body row g-3">
                    {{-- Hidden subject_id & term if coming from filtered --}}
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

                    <div class="col-md-12">
                        <label class="form-label">Course Outcome</label>
                        <select name="course_outcome_id" class="form-select">
                            <option value="">-- Select Course Outcome --</option>
                            @if(isset($courseOutcomes))
                                @foreach($courseOutcomes as $co)
                                    <option value="{{ $co->id }}">{{ $co->co_code }} - {{ $co->co_identifier }}</option>
                                @endforeach
                            @endif
                        </select>
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
