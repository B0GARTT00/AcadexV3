@extends('layouts.app')

@section('content')
@php
    $structurePayload = [
        'type' => $formula->structure_type ?? 'lecture_only',
        'structure' => \App\Support\Grades\FormulaStructure::toPercentPayload($formula->structure_config ?? []),
    ];
    
    $structureCatalog = collect(\App\Support\Grades\FormulaStructure::STRUCTURE_DEFINITIONS)
        ->map(function ($definition, $key) {
            return [
                'key' => $key,
                'label' => $definition['label'] ?? \App\Support\Grades\FormulaStructure::formatLabel($key),
                'description' => $definition['description'] ?? '',
                'structure' => $definition['structure'] ?? [],
            ];
        })
        ->values()
        ->all();

    $queryParams = array_filter([
        'academic_year' => $selectedAcademicYear ?? null,
        'academic_period_id' => $selectedAcademicPeriodId ?? null,
        'semester' => $semester ?? null,
    ], function ($value) {
        return $value !== null && $value !== '';
    });

    $backRoute = route('admin.gradesFormula', array_merge($queryParams, ['view' => 'formulas']));
@endphp

<div class="container-fluid px-3 py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="row mb-3">
        <div class="col">
            <a href="{{ $backRoute }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Formulas
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h4 class="mb-0 fw-semibold">
                        <i class="bi bi-globe2 me-2 text-info"></i>Edit Global Formula
                    </h4>
                    <p class="text-muted small mb-0 mt-1">Update this department-independent formula</p>
                </div>

                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.gradesFormula.update', ['formula' => $formula->id]) }}" id="formula-form">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="scope_level" value="global">
                        <input type="hidden" name="semester" value="{{ $semester ?? '' }}">
                        <input type="hidden" name="academic_period_id" value="{{ $selectedAcademicPeriodId ?? '' }}">
                        <input type="hidden" name="structure_type" id="structure-type-input" value="{{ old('structure_type', $formula->structure_type ?? 'lecture_only') }}">
                        <input type="hidden" name="structure_config" id="structure-config-input" value="{{ old('structure_config', json_encode($structurePayload['structure'] ?? [])) }}">

                        <!-- Formula Label -->
                        <div class="mb-4">
                            <label for="formula-label" class="form-label fw-semibold">Formula Label</label>
                            <input type="text" 
                                   class="form-control @error('label') is-invalid @enderror" 
                                   id="formula-label" 
                                   name="label" 
                                   value="{{ old('label', $formula->label ?? 'Custom Global Formula') }}"
                                   placeholder="e.g., ASBME Default, Engineering Standard">
                            @error('label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Give this formula a descriptive name</small>
                        </div>

                        <!-- Structure Template Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Structure Template</label>
                            <p class="text-muted small mb-3">Select a template that matches your grading structure</p>
                            
                            <div class="row g-3" id="structure-template-grid">
                                @foreach($structureCatalog as $template)
                                    <div class="col-12 col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input structure-template-radio" 
                                                   type="radio" 
                                                   name="structure_template_radio" 
                                                   id="template-{{ $template['key'] }}"
                                                   value="{{ $template['key'] }}"
                                                   data-structure="{{ json_encode($template['structure']) }}"
                                                   {{ old('structure_type', $formula->structure_type ?? '') === $template['key'] ? 'checked' : '' }}>
                                            <label class="form-check-label w-100" for="template-{{ $template['key'] }}">
                                                <div class="card h-100 border">
                                                    <div class="card-body">
                                                        <h6 class="fw-semibold mb-1">{{ $template['label'] }}</h6>
                                                        <p class="text-muted small mb-0">{{ $template['description'] }}</p>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Weight Display -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Weight Distribution</label>
                            <div class="card bg-light">
                                <div class="card-body" id="weight-display">
                                    <p class="text-muted small mb-0">Select a template to see weight distribution</p>
                                </div>
                            </div>
                        </div>

                        <!-- Grade Calculation Settings -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="base-score" class="form-label fw-semibold">Base Score</label>
                                <input type="number" 
                                       class="form-control @error('base_score') is-invalid @enderror" 
                                       id="base-score" 
                                       name="base_score" 
                                       step="0.01" 
                                       min="0" 
                                       max="100"
                                       value="{{ old('base_score', $formula->base_score ?? 0) }}">
                                @error('base_score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="scale-multiplier" class="form-label fw-semibold">Scale Multiplier</label>
                                <input type="number" 
                                       class="form-control @error('scale_multiplier') is-invalid @enderror" 
                                       id="scale-multiplier" 
                                       name="scale_multiplier" 
                                       step="0.01" 
                                       min="0" 
                                       max="100"
                                       value="{{ old('scale_multiplier', $formula->scale_multiplier ?? 100) }}">
                                @error('scale_multiplier')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="passing-grade" class="form-label fw-semibold">Passing Grade</label>
                                <input type="number" 
                                       class="form-control @error('passing_grade') is-invalid @enderror" 
                                       id="passing-grade" 
                                       name="passing_grade" 
                                       step="0.01" 
                                       min="0" 
                                       max="100"
                                       value="{{ old('passing_grade', $formula->passing_grade ?? 75) }}">
                                @error('passing_grade')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Password Confirmation -->
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Confirm Password</label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   required
                                   placeholder="Enter your password to confirm changes">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Password required to update formula</small>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ $backRoute }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Update Formula
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const structureTypeInput = document.getElementById('structure-type-input');
    const structureConfigInput = document.getElementById('structure-config-input');
    const weightDisplay = document.getElementById('weight-display');
    const templateRadios = document.querySelectorAll('.structure-template-radio');

    function updateWeightDisplay(structure) {
        if (!structure || typeof structure !== 'object') {
            weightDisplay.innerHTML = '<p class="text-muted small mb-0">No weights available</p>';
            return;
        }

        let html = '<div class="d-flex flex-wrap gap-2">';
        
        function flattenWeights(obj, prefix = '') {
            const weights = [];
            for (const [key, value] of Object.entries(obj)) {
                if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                    weights.push(...flattenWeights(value, key + ' '));
                } else if (typeof value === 'number') {
                    const label = (prefix + key).trim().replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    weights.push({ label, percent: Math.round(value) });
                }
            }
            return weights;
        }

        const weights = flattenWeights(structure);
        weights.forEach(weight => {
            html += `<span class="badge bg-info-subtle text-info">${weight.label} ${weight.percent}%</span>`;
        });
        
        html += '</div>';
        weightDisplay.innerHTML = html;
    }

    templateRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const templateKey = this.value;
                const structureData = JSON.parse(this.dataset.structure || '{}');
                
                structureTypeInput.value = templateKey;
                structureConfigInput.value = JSON.stringify(structureData);
                
                updateWeightDisplay(structureData);
            }
        });
    });

    // Initialize on page load
    const checkedRadio = document.querySelector('.structure-template-radio:checked');
    if (checkedRadio) {
        const structureData = JSON.parse(checkedRadio.dataset.structure || '{}');
        updateWeightDisplay(structureData);
    }
});
</script>
@endpush

@push('styles')
<style>
.form-check-input.structure-template-radio {
    display: none;
}

.form-check-input.structure-template-radio:checked + label .card {
    border-color: #0dcaf0;
    background-color: rgba(13, 202, 240, 0.1);
}

.form-check-input.structure-template-radio + label .card {
    cursor: pointer;
    transition: all 0.2s ease;
}

.form-check-input.structure-template-radio + label .card:hover {
    border-color: #0dcaf0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 202, 240, 0.2);
}
</style>
@endpush
