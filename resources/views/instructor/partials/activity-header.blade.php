@php
    $componentStatus = $componentStatus ?? null;
    $componentComponents = collect($componentStatus['components'] ?? []);
    $componentOptions = $componentComponents->map(function ($component) {
        $maxLabel = $component['max_allowed'] ?? null;
        $available = $component['available_slots'];
        return [
            'value' => $component['type'],
            'label' => $component['label'],
            'count' => $component['count'],
            'max' => $component['max_allowed'],
            'available' => $available,
            'status' => $component['status'],
            'helper' => $maxLabel !== null
                ? sprintf('%d/%d used%s', $component['count'], $maxLabel, ($available === 0 ? ' • Full' : ''))
                : sprintf('%d scheduled', $component['count']),
        ];
    });
@endphp

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

@if ($componentComponents->isNotEmpty())
    <div class="alert alert-light border shadow-sm py-3 px-4 mb-3" id="componentUsageSummary">
        <div class="d-flex align-items-center mb-2">
            <i class="bi bi-sliders me-2 text-success"></i>
            <span class="fw-semibold text-success">Component availability — {{ $componentStatus['label'] ?? ucfirst($term) }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach ($componentComponents as $component)
                @php
                    $isFull = $component['max_allowed'] !== null && $component['available_slots'] === 0;
                    $isMissing = $component['status'] === 'missing';
                    $badgeClass = $isFull
                        ? 'bg-danger-subtle text-danger border-danger'
                        : ($isMissing ? 'bg-warning-subtle text-warning border-warning' : 'bg-success-subtle text-success border-success');
                    $maxLabel = $component['max_allowed'] ?? '∞';
                    $remaining = $component['max_allowed'] !== null
                        ? max(0, $component['max_allowed'] - $component['count'])
                        : null;
                @endphp
                <div class="px-3 py-2 rounded-pill border {{ $badgeClass }}" style="font-size: 0.85rem;">
                    {{ $component['label'] }} · {{ $component['count'] }}/{{ $maxLabel }}
                    @if ($isFull)
                        <span class="fw-semibold ms-1">Full</span>
                    @elseif ($isMissing)
                        <span class="fw-semibold ms-1">Need more</span>
                    @elseif ($remaining !== null)
                        <span class="ms-1 text-muted">{{ $remaining }} left</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

@if ($componentStatus && ($componentStatus['all_full'] ?? false))
    <div class="alert alert-warning d-flex align-items-center gap-2 shadow-sm mb-3">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>
            <strong>All {{ $componentStatus['label'] ?? ucfirst($term) }} components reached their limit.</strong>
            <div class="small mb-0">Realign or archive activities from Manage Activities to free up slots.</div>

        <script>
            (function() {
                function initializeActivityComponentGuard() {
                    const modal = document.getElementById('addActivityModal');
                    if (!modal) {
                        return;
                    }

                    const select = modal.querySelector('select[name="type"]');
                    const notice = modal.querySelector('[data-component-notice]');
                    const emptyAlert = modal.querySelector('[data-component-empty]');
                    const saveButton = modal.querySelector('[data-component-save]');

                    const hasAvailableOptions = () => {
                        if (!select) {
                            return false;
                        }

                        return Array.from(select.options).some(option => option.value && !option.disabled);
                    };

                    const renderNotice = () => {
                        if (!select || !notice) {
                            return;
                        }

                        const option = select.options[select.selectedIndex];
                        if (!option || !option.value) {
                            notice.classList.add('d-none');
                            notice.textContent = '';
                            return;
                        }

                        const label = option.dataset.label || option.textContent.trim();
                        const count = option.dataset.count ?? '0';
                        const max = option.dataset.max && option.dataset.max !== '' ? option.dataset.max : '∞';
                        const available = option.dataset.available ?? '';
                        const status = option.dataset.status || 'ok';

                        let helper = `${label} · ${count}/${max} used`;
                        if (available !== '' && max !== '∞') {
                            helper += ` · ${available} slot${available === '1' ? '' : 's'} left`;
                        }

                        notice.textContent = helper;
                        notice.classList.remove('d-none');
                        notice.classList.toggle('text-success', status === 'ok');
                        notice.classList.toggle('text-warning', status === 'missing');
                        notice.classList.toggle('text-danger', status === 'full');
                    };

                    const updateSaveState = () => {
                        if (!saveButton || !select) {
                            return;
                        }

                        const available = hasAvailableOptions();
                        const option = select.options[select.selectedIndex];
                        const optionAvailable = option && option.dataset
                            ? parseInt(option.dataset.available ?? '1', 10)
                            : 1;

                        if (!available) {
                            saveButton.disabled = true;
                        } else if (!select.value) {
                            saveButton.disabled = true;
                        } else if (!Number.isNaN(optionAvailable) && optionAvailable <= 0) {
                            saveButton.disabled = true;
                        } else {
                            saveButton.disabled = false;
                        }

                        if (emptyAlert) {
                            emptyAlert.classList.toggle('d-none', available);
                        }
                    };

                    if (select) {
                        select.addEventListener('change', () => {
                            renderNotice();
                            updateSaveState();
                        });
                    }

                    modal.addEventListener('shown.bs.modal', () => {
                        renderNotice();
                        updateSaveState();
                    });

                    renderNotice();
                    updateSaveState();
                }

                window.initializeActivityComponentGuard = initializeActivityComponentGuard;
                document.addEventListener('DOMContentLoaded', initializeActivityComponentGuard);
            })();
        </script>
        </div>
    </div>
@endif

<!-- Add Activity Modal -->
<div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('instructor.activities.store') }}">
            @csrf
            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
            <input type="hidden" name="term" value="{{ $term }}">
            {{-- When adding an activity from the Manage Grades quick-add modal, we should only create a single activity by default --}}
            <input type="hidden" name="create_single" value="1">

            @php
                $fallbackTypes = collect($activityTypes ?? [])
                    ->map(fn ($type) => mb_strtolower($type))
                    ->unique()
                    ->values();

                if ($fallbackTypes->isEmpty()) {
                    $fallbackTypes = collect(['quiz', 'ocr', 'exam']);
                }
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
                                @forelse($componentOptions as $option)
                                    @php
                                        $isDisabled = $option['max'] !== null && $option['available'] === 0;
                                    @endphp
                                    <option
                                        value="{{ $option['value'] }}"
                                        {{ $isDisabled ? 'disabled' : '' }}
                                        data-label="{{ $option['label'] }}"
                                        data-count="{{ $option['count'] }}"
                                        data-max="{{ $option['max'] ?? '' }}"
                                        data-available="{{ $option['available'] ?? '' }}"
                                        data-status="{{ $option['status'] }}"
                                    >
                                        {{ $option['label'] }} — {{ $option['helper'] }}
                                    </option>
                                @empty
                                    @foreach($fallbackTypes as $type)
                                        @php
                                            $formatted = \App\Support\Grades\FormulaStructure::formatLabel($type);
                                        @endphp
                                        <option value="{{ $type }}" data-label="{{ $formatted }}">
                                            {{ $formatted }}
                                        </option>
                                    @endforeach
                                @endforelse
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Number of Items <span class="text-danger">*</span></label>
                               <input type="number" name="number_of_items" class="form-control" required min="1" value="{{ old('number_of_items', 100) }}">
                        </div>
                        <div class="col-12">
                            <div class="small text-muted d-none" data-component-notice></div>
                            <div class="alert alert-warning d-none mt-3" data-component-empty>
                                All components for this term are at capacity. Please manage activities to free up slots.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" data-component-save>Save Activity</button>
                </div>
            </div>
        </form>
    </div>
</div>
