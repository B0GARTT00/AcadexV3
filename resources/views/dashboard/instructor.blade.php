@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Welcome Back, {{ Auth::user()->name }}! 👋</h2>
            <p class="text-muted mb-0">Here's what's happening with your classes today.</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-4">
        @php
            $cards = [
                [
                    'label' => 'Total Students',
                    'icon' => 'bi bi-people-fill',
                    'value' => $instructorStudents,
                    'color' => 'primary',
                    'trend' => 'Currently enrolled'
                ],
                [
                    'label' => 'Course Load',
                    'icon' => 'bi bi-journal-text',
                    'value' => $enrolledSubjectsCount,
                    'color' => 'info',
                    'trend' => 'Current semester'
                ],
                [
                    'label' => 'Students Passed',
                    'icon' => 'bi bi-check-circle-fill',
                    'value' => $totalPassedStudents,
                    'color' => 'success',
                    'trend' => 'Final grades'
                ],
                [
                    'label' => 'Students Failed',
                    'icon' => 'bi bi-x-circle-fill',
                    'value' => $totalFailedStudents,
                    'color' => 'danger',
                    'trend' => 'Final grades'
                ],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-3 p-2 bg-{{ $card['color'] }}-subtle me-3">
                                <i class="{{ $card['icon'] }} text-{{ $card['color'] }} fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">{{ $card['label'] }}</h6>
                                <h3 class="fw-bold text-{{ $card['color'] }} mb-0">{{ $card['value'] }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-arrow-right"></i> {{ $card['trend'] }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mt-4">
        {{-- Term Completion --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-semibold mb-0">
                            <i class="bi bi-graph-up-arrow me-2"></i>Grading Progress
                        </h5>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3">Current Term</span>
                    </div>
                    
                    @foreach(['prelim', 'midterm', 'prefinal', 'final'] as $term)
                        @php
                            $progress = $termCompletions[$term]['total'] > 0
                                ? round(($termCompletions[$term]['graded'] / $termCompletions[$term]['total']) * 100)
                                : 0;

                            $color = match(true) {
                                $progress === 100 => 'success',
                                $progress > 75 => 'info',
                                $progress > 50 => 'warning',
                                default => 'danger'
                            };
                        @endphp
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-capitalize mb-0">{{ ucfirst($term) }}</h6>
                                <span class="text-{{ $color }}">{{ $progress }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-{{ $color }}" role="progressbar" 
                                     style="width: {{ $progress }}%;" 
                                     aria-valuenow="{{ $progress }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <small class="text-muted">
                                {{ $termCompletions[$term]['graded'] }} of {{ $termCompletions[$term]['total'] }} grades submitted
                            </small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Subject Performance --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-semibold mb-0">
                            <i class="bi bi-bar-chart-fill me-2"></i>Course Completion Status
                        </h5>
                    </div>
                    <div class="mb-4" style="height: 300px;">
                        <canvas id="subjectPerformanceChart"></canvas>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Course Code</th>
                                    <th>Description</th>
                                    <th class="text-center">Completion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subjectCharts as $subject)
                                    @php
                                        $avgCompletion = array_sum($subject['termPercentages']) / count($subject['termPercentages']);
                                        $statusColor = match(true) {
                                            $avgCompletion === 100 => 'success',
                                            $avgCompletion >= 75 => 'info',
                                            $avgCompletion >= 50 => 'warning',
                                            default => 'danger'
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $subject['code'] }}</td>
                                        <td>{{ $subject['description'] }}</td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-{{ $statusColor }}" 
                                                         role="progressbar" 
                                                         style="width: {{ $avgCompletion }}%">
                                                    </div>
                                                </div>
                                                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} rounded-pill">
                                                    {{ round($avgCompletion) }}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    :root {
        --theme-primary: #4e73df;
        --theme-success: #1cc88a;
        --theme-info: #36b9cc;
        --theme-warning: #f6c23e;
        --theme-danger: #e74a3b;
        --theme-secondary: #858796;
        --theme-light: #f8f9fc;
        --theme-dark: #5a5c69;
    }

    .hover-lift {
        transition: transform 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
    }

    /* Custom Theme Colors */
    .bg-primary-subtle {
        background-color: rgba(78, 115, 223, 0.1) !important;
    }
    .bg-success-subtle {
        background-color: rgba(28, 200, 138, 0.1) !important;
    }
    .bg-info-subtle {
        background-color: rgba(54, 185, 204, 0.1) !important;
    }
    .bg-warning-subtle {
        background-color: rgba(246, 194, 62, 0.1) !important;
    }
    .bg-danger-subtle {
        background-color: rgba(231, 74, 59, 0.1) !important;
    }

    .text-primary {
        color: var(--theme-primary) !important;
    }
    .text-success {
        color: var(--theme-success) !important;
    }
    .text-info {
        color: var(--theme-info) !important;
    }
    .text-warning {
        color: var(--theme-warning) !important;
    }
    .text-danger {
        color: var(--theme-danger) !important;
    }

    .btn-primary {
        background-color: var(--theme-primary);
        border-color: var(--theme-primary);
    }
    .btn-primary:hover {
        background-color: #2e59d9;
        border-color: #2653d4;
    }

    .progress-bar {
        background-color: var(--theme-primary);
    }

    .badge.bg-primary-subtle {
        background-color: rgba(78, 115, 223, 0.1) !important;
        color: var(--theme-primary) !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('subjectPerformanceChart').getContext('2d');
    
    const gradientFill = ctx.createLinearGradient(0, 0, 0, 400);
    gradientFill.addColorStop(0, 'rgba(13, 110, 253, 0.3)');
    gradientFill.addColorStop(1, 'rgba(13, 110, 253, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
            datasets: @json($subjectCharts)
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        color: '#5a5c69'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                        color: '#eaecf4'
                    },
                    ticks: {
                        color: '#5a5c69'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#5a5c69'
                    }
                }
            },
            elements: {
                line: {
                    tension: 0.4,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)'
                },
                point: {
                    radius: 4,
                    hoverRadius: 6,
                    backgroundColor: '#4e73df'
                }
            }
        }
    });
});
</script>
@endpush
