@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 py-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
  <!-- Page Header -->
  <div class="row mb-3">
    <div class="col">
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-white rounded-pill px-3 py-2 shadow-sm mb-0">
          <li class="breadcrumb-item">
            <a href="{{ route('dashboard') }}" class="text-decoration-none" style="color: #198754; font-size: 0.9rem;">
              <i class="bi bi-house-door me-1"></i>Home
            </a>
          </li>
          <li class="breadcrumb-item">
            <a href="{{ route('instructor.grades.index') }}" class="text-decoration-none" style="color: #198754; font-size: 0.9rem;">
              <i class="bi bi-clipboard-data me-1"></i>Grades
            </a>
          </li>
          <li class="breadcrumb-item active" aria-current="page" style="color: #6c757d; font-size: 0.9rem;">
            Activities
          </li>
        </ol>
      </nav>

      <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-3">
        <div class="d-flex align-items-center">
          <div class="p-3 rounded-circle me-3" style="background: linear-gradient(135deg, #198754, #20c997);">
            <i class="bi bi-list-check text-white" style="font-size: 1.5rem;"></i>
          </div>
          <div>
            <h1 class="h3 fw-bold mb-1" style="color: #198754;">Assessment Activities</h1>
            <p class="text-muted mb-0 small">Plan and align your assessments with grading formulas across all terms</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Alerts -->
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert" style="border-left: 4px solid #198754 !important;">
      <div class="d-flex align-items-center">
        <i class="bi bi-check-circle-fill me-3 fs-4" style="color: #198754;"></i>
        <div class="flex-grow-1">{{ session('success') }}</div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if (session('info'))
    <div class="alert alert-info alert-dismissible fade show shadow-sm border-0" role="alert" style="border-left: 4px solid #0dcaf0 !important;">
      <div class="d-flex align-items-center">
        <i class="bi bi-info-circle-fill me-3 fs-4" style="color: #0dcaf0;"></i>
        <div class="flex-grow-1">{{ session('info') }}</div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert" style="border-left: 4px solid #dc3545 !important;">
      <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill me-3 fs-4" style="color: #dc3545;"></i>
        <div class="flex-grow-1">{{ session('error') }}</div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert" style="border-left: 4px solid #dc3545 !important;">
      <div class="d-flex align-items-start">
        <i class="bi bi-exclamation-octagon-fill me-3 fs-4 mt-1" style="color: #dc3545;"></i>
        <div class="flex-grow-1">
          <strong class="d-block mb-2">Please review the following issues:</strong>
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if ($subjects->isEmpty())
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center py-5">
            <div class="mb-4">
              <i class="bi bi-inbox text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
            </div>
            <h5 class="fw-semibold mb-2" style="color: #198754;">No Assigned Subjects Found</h5>
            <p class="text-muted mb-4">You don't have any subjects assigned for the current academic period.</p>
            <p class="small text-muted">
              <i class="bi bi-info-circle me-1"></i>
              Contact your chairperson to get subjects assigned so you can start managing activities.
            </p>
          </div>
        </div>
      </div>
    </div>
  @else
    @php
      $meta = $formulaSettings['meta'] ?? null;
      $structureTypeKey = $meta['structure_type'] ?? 'lecture_only';
      $structureDefinition = \App\Support\Grades\FormulaStructure::STRUCTURE_DEFINITIONS[$structureTypeKey] ?? null;
    @endphp

    <div class="row g-4">
      <!-- Left Sidebar: Filters & Formula Info -->
      <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold d-flex align-items-center" style="color: #198754;">
              <i class="bi bi-funnel me-2"></i>
              Filters & Formula
            </h5>
          </div>
          <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('instructor.activities.create') }}" class="mb-4">
              <div class="mb-3">
                <label class="form-label fw-semibold small text-uppercase" style="color: #198754; letter-spacing: 0.5px;">
                  <i class="bi bi-book me-1"></i>Subject
                </label>
                <select name="subject_id" class="form-select shadow-sm" onchange="this.form.submit()" style="border: 2px solid #e9ecef;">
                  @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ optional($selectedSubject)->id === $subject->id ? 'selected' : '' }}>
                      {{ $subject->subject_code }} — {{ $subject->subject_description }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="form-label fw-semibold small text-uppercase" style="color: #198754; letter-spacing: 0.5px;">
                  <i class="bi bi-calendar3 me-1"></i>Term
                </label>
                <select name="term" class="form-select shadow-sm" onchange="this.form.submit()" style="border: 2px solid #e9ecef;">
                  <option value="">All Terms</option>
                  @foreach ($termLabels as $key => $label)
                    <option value="{{ $key }}" {{ $selectedTerm === $key ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
            </form>

            @if ($meta)
              <hr class="my-4" style="border-color: #e9ecef;">
              
              <!-- Formula Info -->
              <div class="mb-3">
                <p class="text-uppercase fw-semibold small mb-2" style="color: #6c757d; letter-spacing: 0.5px;">Active Formula</p>
                <h6 class="fw-bold mb-3" style="color: #198754;">{{ $meta['label'] ?? 'ASBME Default' }}</h6>
                
                <div class="d-flex flex-wrap gap-2 mb-3">
                  <span class="badge bg-light text-dark px-3 py-2" style="font-weight: 500;">
                    <i class="bi bi-plus-circle me-1"></i>Base {{ number_format($formulaSettings['base_score'] ?? 0, 0) }}
                  </span>
                  <span class="badge bg-light text-dark px-3 py-2" style="font-weight: 500;">
                    <i class="bi bi-x-circle me-1"></i>Scale ×{{ number_format($formulaSettings['scale_multiplier'] ?? 0, 0) }}
                  </span>
                  <span class="badge bg-light text-dark px-3 py-2" style="font-weight: 500;">
                    <i class="bi bi-check-circle me-1"></i>Passing {{ number_format($meta['passing_grade'] ?? ($formulaSettings['passing_grade'] ?? 0), 0) }}
                  </span>
                </div>

                <div class="badge" style="background: linear-gradient(135deg, #198754, #20c997); color: white; font-weight: 500; padding: 0.5rem 1rem;">
                  <i class="bi bi-diagram-3 me-1"></i>{{ $structureDefinition['label'] ?? 'Lecture Only' }}
                </div>
                
                @if (! empty($meta['scope']))
                  <div class="mt-2">
                    <small class="text-muted">
                      <i class="bi bi-info-circle me-1"></i>
                      Scope: <span class="fw-semibold">{{ ucfirst($meta['scope']) }}</span>
                    </small>
                  </div>
                @endif
              </div>

              @if ($structureDefinition && ! empty($structureDefinition['description']))
                <div class="alert alert-success border-0 shadow-sm mb-3" style="background-color: #EAF8E7;">
                  <div class="d-flex align-items-start">
                    <i class="bi bi-lightbulb text-success me-2 mt-1"></i>
                    <small class="text-success mb-0">{{ $structureDefinition['description'] }}</small>
                  </div>
                </div>
              @endif
            @endif

            @if ($structureDetails->isNotEmpty())
              <div>
                <p class="text-uppercase fw-semibold small mb-2" style="color: #6c757d; letter-spacing: 0.5px;">
                  Component Breakdown
                </p>
                <div class="list-group list-group-flush">
                  @foreach ($structureDetails as $detail)
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center border-0">
                      <div>
                        <div class="fw-semibold small">{{ $detail['label'] }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                          {{ $detail['max_assessments'] ? 'Max '.$detail['max_assessments'] : 'Flexible' }}
                        </div>
                      </div>
                      <div class="text-end">
                        <div class="badge bg-success-subtle text-success fw-semibold">
                          {{ number_format($detail['weight_percent'], 1) }}%
                        </div>
                        @if (abs($detail['overall_weight_percent'] - $detail['weight_percent']) > 0.05)
                          <div class="text-muted" style="font-size: 0.7rem;">
                            Overall {{ number_format($detail['overall_weight_percent'], 1) }}%
                          </div>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Right Section: Alignment & Activities -->
      <div class="col-12 col-xl-8">
        <!-- Formula Alignment Card -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
              <div>
                <h5 class="mb-1 fw-semibold d-flex align-items-center" style="color: #198754;">
                  <i class="bi bi-bar-chart-line me-2"></i>
                  Formula Alignment Status
                </h5>
                <small class="text-muted">Track assessment distribution across all terms</small>
              </div>
              <div class="d-flex align-items-center gap-2">
                @if ($isAligned)
                  <span class="badge bg-success px-3 py-2" style="font-size: 0.9rem;">
                    <i class="bi bi-check-circle me-1"></i>Perfectly Aligned
                  </span>
                @else
                  <span class="badge bg-danger px-3 py-2" style="font-size: 0.9rem;">
                    <i class="bi bi-exclamation-triangle me-1"></i>Needs Attention
                  </span>
                @endif
                
                @if ($selectedSubject)
                  <form method="POST" action="{{ route('instructor.activities.realign') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="subject_id" value="{{ $selectedSubject->id }}">
                    @if ($selectedTerm)
                      <input type="hidden" name="term" value="{{ $selectedTerm }}">
                    @endif
                    <button 
                      type="submit" 
                      class="btn btn-outline-success btn-sm shadow-sm"
                      style="font-weight: 500;"
                      title="Auto-adjust activities to match formula"
                    >
                      <i class="bi bi-arrow-repeat me-1"></i>Realign Activities
                    </button>
                  </form>
                @endif
              </div>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table align-middle mb-0" style="font-size: 0.9rem;">
                <thead style="background-color: #f8f9fa;">
                  <tr>
                    <th class="px-3 py-3 fw-semibold" style="min-width: 220px; color: #198754;">Component</th>
                    <th class="text-center px-3 py-3 fw-semibold" style="color: #198754;">Weight</th>
                    <th class="text-center px-3 py-3 fw-semibold" style="color: #198754;">Max/Term</th>
                    @foreach ($termLabels as $key => $label)
                      <th class="text-center px-3 py-3 fw-semibold" style="color: #198754;">{{ $label }}</th>
                    @endforeach
                  </tr>
                </thead>
                <tbody>
                  @foreach ($structureDetails as $detail)
                    <tr class="border-bottom" style="transition: background-color 0.2s;">
                      <td class="px-3 py-3">
                        <div class="fw-semibold" style="color: #212529;">{{ $detail['label'] }}</div>
                        <div class="text-muted small">{{ $detail['activity_type'] }}</div>
                      </td>
                      <td class="text-center px-3 py-3">
                        <div class="fw-semibold" style="color: #198754;">{{ number_format($detail['weight_percent'], 1) }}%</div>
                        @if (abs($detail['overall_weight_percent'] - $detail['weight_percent']) > 0.05)
                          <div class="text-muted small">Overall {{ number_format($detail['overall_weight_percent'], 1) }}%</div>
                        @endif
                      </td>
                      <td class="text-center px-3 py-3">
                        <span class="badge bg-light text-dark">
                          {{ $detail['max_assessments'] ? $detail['max_assessments'] : '∞' }}
                        </span>
                      </td>
                      @foreach ($termLabels as $termKey => $termLabel)
                        @php
                          $termComponent = collect($componentStatuses[$termKey]['components'] ?? [])->firstWhere('type', $detail['activity_type']);
                          $count = $termComponent['count'] ?? 0;
                          $status = $termComponent['status'] ?? 'missing';
                          $minRequired = $termComponent['min_required'] ?? 1;
                          $maxAllowed = $termComponent['max_allowed'] ?? null;
                          $badgeClass = match ($status) {
                            'ok' => 'bg-success',
                            'exceeds' => 'bg-danger',
                            default => 'bg-warning text-dark',
                          };
                          $badgeIcon = match ($status) {
                            'ok' => 'check-circle',
                            'exceeds' => 'x-circle',
                            default => 'exclamation-circle',
                          };
                          $tooltip = match ($status) {
                            'ok' => 'Matches the active formula',
                            'exceeds' => 'Exceeds the maximum of '.($maxAllowed ?? 'n/a').' assessments',
                            default => 'Add at least '.$minRequired.' assessment'.($minRequired > 1 ? 's' : ''),
                          };
                        @endphp
                        <td class="text-center px-3 py-3">
                          <span 
                            class="badge {{ $badgeClass }} px-3 py-2" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="{{ $tooltip }}"
                            style="font-size: 0.85rem; cursor: help;"
                          >
                            <i class="bi bi-{{ $badgeIcon }} me-1"></i>{{ $count }}
                          </span>
                        </td>
                      @endforeach
                    </tr>
                  @endforeach

                  @foreach ($termLabels as $termKey => $termLabel)
                    @if (! empty($componentStatuses[$termKey]['extras']))
                      <tr style="background-color: #fff3cd;">
                        <td colspan="{{ 3 + count($termLabels) }}" class="px-3 py-3 small">
                          <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-2 mt-1"></i>
                            <div>
                              <strong class="text-warning">{{ $termLabel }} Extra Components:</strong>
                              <div class="mt-1">
                                @foreach ($componentStatuses[$termKey]['extras'] as $extra)
                                  <span class="badge bg-warning text-dark me-2 mb-1">
                                    {{ $extra['type'] }} × {{ $extra['count'] }}
                                  </span>
                                @endforeach
                              </div>
                              <span class="text-muted">These components are not defined in the current formula.</span>
                            </div>
                          </div>
                        </td>
                      </tr>
                    @endif
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Activities List Card -->
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
              <div>
                <h5 class="mb-1 fw-semibold d-flex align-items-center" style="color: #198754;">
                  <i class="bi bi-list-task me-2"></i>
                  Activities
                </h5>
                <small class="text-muted">
                  @if ($selectedSubject)
                    {{ $selectedSubject->subject_code }} • {{ $selectedTerm ? $termLabels[$selectedTerm] : 'All Terms' }}
                  @else
                    Select a subject to view activities
                  @endif
                </small>
              </div>
              <button 
                type="button" 
                class="btn btn-success shadow-sm" 
                data-bs-toggle="modal" 
                data-bs-target="#createActivityModal"
                style="font-weight: 500;"
              >
                <i class="bi bi-plus-circle me-1"></i>New Activity
              </button>
            </div>
          </div>
          <div class="card-body p-0">
            @if ($activities->isNotEmpty())
              <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size: 0.9rem;">
                  <thead style="background-color: #f8f9fa;">
                    <tr>
                      <th class="px-3 py-3 fw-semibold" style="color: #198754;">Title</th>
                      <th class="px-3 py-3 fw-semibold" style="color: #198754;">Component</th>
                      <th class="text-center px-3 py-3 fw-semibold" style="color: #198754;">Term</th>
                      <th class="text-center px-3 py-3 fw-semibold" style="color: #198754;">Items</th>
                      <th class="text-center px-3 py-3 fw-semibold" style="color: #198754;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($activities as $activity)
                      @php
                        $activityLabel = optional($structureDetails->firstWhere('activity_type', mb_strtolower($activity->type)))['label']
                          ?? \App\Support\Grades\FormulaStructure::formatLabel($activity->type);
                      @endphp
                      <tr class="border-bottom" style="transition: background-color 0.2s;">
                        <td class="px-3 py-3">
                          <div class="fw-semibold" style="color: #212529;">{{ $activity->title }}</div>
                        </td>
                        <td class="px-3 py-3">
                          <span class="badge bg-light text-dark px-2 py-1">{{ $activityLabel }}</span>
                        </td>
                        <td class="text-center px-3 py-3">
                          <span class="badge bg-success-subtle text-success px-2 py-1 text-capitalize">
                            {{ $activity->term }}
                          </span>
                        </td>
                        <td class="text-center px-3 py-3">
                          <span class="fw-semibold" style="color: #198754;">{{ $activity->number_of_items }}</span>
                        </td>
                        <td class="text-center px-3 py-3">
                          <button 
                            type="button" 
                            class="btn btn-sm btn-outline-danger shadow-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#confirmDeleteModal" 
                            data-activity-id="{{ $activity->id }}" 
                            data-activity-title="{{ $activity->title }}"
                            title="Delete activity"
                          >
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <div class="p-5 text-center">
                <div class="mb-3">
                  <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
                <h6 class="fw-semibold mb-2" style="color: #6c757d;">No Activities Found</h6>
                <p class="text-muted small mb-3">No activities match your current filter selection.</p>
                <button 
                  type="button" 
                  class="btn btn-success btn-sm shadow-sm" 
                  data-bs-toggle="modal" 
                  data-bs-target="#createActivityModal"
                >
                  <i class="bi bi-plus-circle me-1"></i>Create Your First Activity
                </button>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="deleteActivityForm">
          @csrf
          @method('DELETE')
          <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
              <h5 class="modal-title fw-bold" id="confirmDeleteModalLabel" style="color: #dc3545;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Activity
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
              <p class="mb-2">You are about to permanently delete:</p>
              <div class="alert alert-danger-subtle border-0 mb-3" style="background-color: #f8d7da;">
                <strong id="activityTitlePlaceholder">this activity</strong>
              </div>
              <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>This action cannot be undone.
              </p>
            </div>
            <div class="modal-footer border-0">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                <i class="bi bi-x-circle me-1"></i>Cancel
              </button>
              <button type="submit" class="btn btn-danger shadow-sm">
                <i class="bi bi-trash me-1"></i>Delete Activity
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Create Activity Modal -->
    <div class="modal fade" id="createActivityModal" tabindex="-1" aria-labelledby="createActivityModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('instructor.activities.store') }}" class="modal-content border-0 shadow-lg needs-validation" novalidate>
          @csrf
          <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, #198754, #20c997);">
            <h5 class="modal-title fw-bold text-white" id="createActivityModalLabel">
              <i class="bi bi-plus-circle me-2"></i>Create New Activity
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase" style="color: #198754; letter-spacing: 0.5px;">
                  <i class="bi bi-book me-1"></i>Subject
                </label>
                <select name="subject_id" class="form-select shadow-sm" required style="border: 2px solid #e9ecef;">
                  @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ optional($selectedSubject)->id === $subject->id ? 'selected' : '' }}>
                      {{ $subject->subject_code }} — {{ $subject->subject_description }}
                    </option>
                  @endforeach
                </select>
                <div class="invalid-feedback">
                  <i class="bi bi-exclamation-circle me-1"></i>Please select a subject.
                </div>
              </div>
              
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase" style="color: #198754; letter-spacing: 0.5px;">
                  <i class="bi bi-calendar3 me-1"></i>Term
                </label>
                <select name="term" class="form-select shadow-sm" required style="border: 2px solid #e9ecef;">
                  <option value="">Select Term</option>
                  @foreach ($termLabels as $key => $label)
                    <option value="{{ $key }}" {{ $selectedTerm === $key ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
                <div class="invalid-feedback">
                  <i class="bi bi-exclamation-circle me-1"></i>Please select a grading term.
                </div>
              </div>
              
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase" style="color: #198754; letter-spacing: 0.5px;">
                  <i class="bi bi-diagram-3 me-1"></i>Component Type
                </label>
                <select name="type" class="form-select shadow-sm" required style="border: 2px solid #e9ecef;">
                  <option value="">Select Component</option>
                  @forelse ($structureDetails as $detail)
                    <option value="{{ $detail['activity_type'] }}">
                      {{ $detail['label'] }} · {{ number_format($detail['weight_percent'], 1) }}%
                      @if ($detail['max_assessments'])
                        (Max {{ $detail['max_assessments'] }})
                      @endif
                    </option>
                  @empty
                    @foreach ($activityTypes as $type)
                      <option value="{{ $type }}">{{ \App\Support\Grades\FormulaStructure::formatLabel($type) }}</option>
                    @endforeach
                  @endforelse
                </select>
                <div class="invalid-feedback">
                  <i class="bi bi-exclamation-circle me-1"></i>Select the activity component type.
                </div>
              </div>
              
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase" style="color: #198754; letter-spacing: 0.5px;">
                  <i class="bi bi-pencil me-1"></i>Activity Title
                </label>
                <input 
                  type="text" 
                  name="title" 
                  class="form-control shadow-sm" 
                  placeholder="e.g., Quiz on Chapters 1-3" 
                  required
                  style="border: 2px solid #e9ecef;"
                >
                <div class="invalid-feedback">
                  <i class="bi bi-exclamation-circle me-1"></i>Provide a descriptive activity title.
                </div>
              </div>
              
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase" style="color: #198754; letter-spacing: 0.5px;">
                  <i class="bi bi-hash me-1"></i>Number of Items
                </label>
                <input 
                  type="number" 
                  name="number_of_items" 
                  class="form-control shadow-sm" 
                  min="1" 
                  max="500" 
                  required
                  style="border: 2px solid #e9ecef;"
                  placeholder="Enter total items"
                >
                <div class="invalid-feedback">
                  <i class="bi bi-exclamation-circle me-1"></i>Enter the total number of items.
                </div>
              </div>
              
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase" style="color: #198754; letter-spacing: 0.5px;">
                  <i class="bi bi-target me-1"></i>Course Outcome (Optional)
                </label>
                <select name="course_outcome_id" class="form-select shadow-sm" style="border: 2px solid #e9ecef;">
                  <option value="">No specific outcome</option>
                  @foreach ($courseOutcomes as $outcome)
                    <option value="{{ $outcome->id }}">
                      {{ $outcome->co_code ?? 'Outcome' }} — {{ \Illuminate\Support\Str::limit($outcome->description ?? 'No description provided', 60) }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">
                  <i class="bi bi-info-circle me-1"></i>Link to a course outcome for attainment tracking.
                </small>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-light shadow-sm" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-1"></i>Cancel
            </button>
            <button type="submit" class="btn btn-success shadow-sm" style="font-weight: 500;">
              <i class="bi bi-check-circle me-1"></i>Save Activity
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Initialize Bootstrap tooltips
    const bootstrapLib = window.bootstrap ?? null;
    if (bootstrapLib) {
      const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.forEach(triggerEl => new bootstrapLib.Tooltip(triggerEl));
    }

    // Delete modal handler
    const deleteModal = document.getElementById('confirmDeleteModal');
    if (deleteModal) {
      deleteModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const activityId = button?.getAttribute('data-activity-id');
        const activityTitle = button?.getAttribute('data-activity-title') ?? 'this activity';

        const form = deleteModal.querySelector('#deleteActivityForm');
        if (form && activityId) {
          form.action = `/instructor/activities/${activityId}`;
        }

        const placeholder = deleteModal.querySelector('#activityTitlePlaceholder');
        if (placeholder) {
          placeholder.textContent = activityTitle;
        }
      });
    }

    // Create modal auto-focus
    const createActivityModal = document.getElementById('createActivityModal');
    if (createActivityModal) {
      createActivityModal.addEventListener('shown.bs.modal', () => {
        const firstInput = createActivityModal.querySelector('input[name="title"]');
        if (firstInput) {
          firstInput.focus();
        }
      });
    }

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });

    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach(row => {
      row.addEventListener('mouseenter', () => {
        row.style.backgroundColor = '#f8f9fa';
      });
      row.addEventListener('mouseleave', () => {
        row.style.backgroundColor = '';
      });
    });
  });
</script>
@endpush

@push('styles')
<style>
  /* Custom animations */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .card {
    animation: fadeInUp 0.5s ease-out;
  }

  /* Table hover effects */
  table tbody tr {
    transition: all 0.2s ease;
  }

  table tbody tr:hover {
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(25, 135, 84, 0.1);
  }

  /* Form control focus */
  .form-control:focus,
  .form-select:focus {
    border-color: #198754 !important;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15) !important;
  }

  /* Badge enhancements */
  .badge {
    font-weight: 500;
    letter-spacing: 0.3px;
  }

  /* Modal animations */
  .modal.fade .modal-dialog {
    transform: scale(0.8);
    opacity: 0;
    transition: all 0.3s ease;
  }

  .modal.show .modal-dialog {
    transform: scale(1);
    opacity: 1;
  }

  /* Button hover effects */
  .btn {
    transition: all 0.2s ease;
  }

  .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  .btn:active {
    transform: translateY(0);
  }

  /* Alert enhancements */
  .alert {
    animation: fadeInUp 0.4s ease-out;
  }
</style>
@endpush
