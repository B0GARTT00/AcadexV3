@php
    // NOTE: This term stepper renders progress for the *selected* term only.
    // Prior implementation used the total number of activities across ALL terms
    // when computing the completion percentage. That meant the visual progress
    // shown in the active term would appear incorrect (over/under-stated)
    // whenever activity counts differed across terms. To fix this we filter
    // activities to the current term before calculating cell totals and
    // completion percentages.
    $terms = ['prelim', 'midterm', 'prefinal', 'final'];
    $radius = 36;
    $circumference = 2 * pi() * $radius;
    // Ensure we have a selected term; default to the first term if not provided.
    $term = $term ?? ($terms[0] ?? 'prelim');
    // Ensure $students and $activities are countable and default to empty arrays if missing
    $students = is_countable($students) ? $students : [];
    $activities = is_countable($activities) ? $activities : [];

    // For the stepper, calculate completion *for the active term only* - prior behavior counted
    // across all terms, which made the displayed progress for a particular term appear wrong.
    $activitiesForTerm = collect($activities)->filter(fn($activity) => ($activity->term ?? null) === ($term ?? null));

    $totalCells = count($students) * $activitiesForTerm->count();
    $filledCells = 0;

    foreach ($students as $student) {
        foreach ($activitiesForTerm as $activity) {
            if (isset($scores[$student->id][$activity->id]) && $scores[$student->id][$activity->id] !== null) {
                $filledCells++;
            }
        }
    }

    $completion = $totalCells > 0 ? round(($filledCells / $totalCells) * 100) : 0;
    $completion = min(100, max(0, $completion));
    $offset = $circumference - ($completion / 100) * $circumference;
@endphp

<style>
    .stepper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 2rem 0;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        flex: 1;
        text-decoration: none;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 38px;
        left: calc(50% + 40px);
        width: calc(100% - 80px);
        height: 2px;
        background-color: #d1e7db; /* default line */
        z-index: 0;
        transition: background-color 0.3s ease;
    }

    .step.highlight-line:not(:last-child)::after {
        background-color: #4da674; /* highlighted green line */
    }

    .circle-wrapper {
        position: relative;
        width: 80px;
        height: 80px;
    }

    .circle {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        z-index: 2;
        transition: transform 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .step:hover .circle {
        transform: scale(1.05);
    }

    .completed .circle {
        background-color: #4da674;
    }

    .active .circle {
        background-color: #023336;
        animation: pulse 1.5s infinite;
    }

    .upcoming .circle {
        background-color: #e6f4eb;
        color: #4da674;
    }

    .progress-ring {
        position: absolute;
        top: 0;
        left: 0;
        transform: rotate(-90deg);
        z-index: 1;
    }

    .progress-ring circle {
        fill: none;
        stroke-width: 6;
        stroke-linecap: round;
    }

    .progress-ring-bg {
        stroke: #d1e7db;
    }

    .progress-ring-bar {
        stroke: #4da674;
        transition: stroke-dashoffset 0.4s ease-in-out;
    }

    .step-label {
        font-size: 15px;
        margin-top: 0.75rem;
    }

    .completed .step-label {
        color: #357a55;
        font-weight: 600;
    }

    .active .step-label {
        color: #023336;
        font-weight: 700;
    }

    .upcoming .step-label {
        color: #94a89e;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(2, 51, 54, 0.4);
        }
        70% {
            box-shadow: 0 0 0 15px rgba(2, 51, 54, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(2, 51, 54, 0);
        }
    }

    .fade-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.7);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        transition: opacity 0.3s ease-in-out;
    }

    .fade-overlay.active {
        display: flex;
    }

    .spinner {
        border: 4px solid #e5e7eb;
        border-top: 4px solid #4da674;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<div class="stepper">
    @foreach ($terms as $index => $termSlug)
        @php
            $step = $index + 1;
            $isActive = $term === $termSlug;
            $isCompleted = array_search($term, $terms) > $index;
            $class = $isActive ? 'active' : ($isCompleted ? 'completed' : 'upcoming');

            // highlight line if current step is before or equal to the active one
            $highlightLine = $index < array_search($term, $terms);
        @endphp

        <button type="button"
                class="step term-step {{ $class }} {{ $highlightLine ? 'highlight-line' : '' }}"
                data-term="{{ $termSlug }}">
            <div class="circle-wrapper">
                <svg class="progress-ring" width="80" height="80">
                    <circle class="progress-ring-bg" cx="40" cy="40" r="{{ $radius }}" />
                    @if ($isActive)
                        <circle class="progress-ring-bar" cx="40" cy="40" r="{{ $radius }}"
                                stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $offset }}" />
                    @endif
                </svg>
                <div class="circle">{{ $step }}</div>
            </div>
            <div class="step-label">{{ ucfirst($termSlug) }}</div>
        </button>
    @endforeach
</div>

<div id="fadeOverlay" class="fade-overlay">
    <div class="spinner"></div>
</div>
