@extends('layouts.app')

@php
    $queryParams = array_filter([
        'academic_year' => $selectedAcademicYear ?? null,
        'academic_period_id' => $selectedAcademicPeriodId ?? null,
        'semester' => $semester ?? null,
    ], function ($value) {
        return $value !== null && $value !== '';
    });

    $buildRoute = function (string $name, array $parameters = []) use ($queryParams) {
        $url = route($name, $parameters);
        if (empty($queryParams)) {
            return $url;
        }
        return $url . '?' . http_build_query($queryParams);
    };

    $backRoute = $buildRoute('admin.gradesFormula', ['view' => 'formulas']);
@endphp

@section('content')
<div class="container-fluid px-3 py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="row mb-2">
        <div class="col">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-white rounded-pill px-3 py-1 shadow-sm mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" class="text-decoration-none" style="color: #198754; font-size: 0.9rem;">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ $buildRoute('admin.gradesFormula') }}" class="text-decoration-none" style="color: #198754; font-size: 0.9rem;">
                            <i class="bi bi-sliders me-1"></i>Grades Formula
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: #6c757d; font-size: 0.9rem;">
                        Edit Structure Template
                    </li>
                </ol>
            </nav>

            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-circle me-2" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <i class="bi bi-diagram-3 text-white" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0" style="color: #198754;">Edit Structure Template</h4>
                        <small class="text-muted">Modify the grading structure template</small>
                    </div>
                </div>
                <a href="{{ $backRoute }}" class="btn btn-outline-success btn-sm rounded-pill shadow-sm" style="font-weight: 600;">
                    <i class="bi bi-arrow-left me-1"></i>Back to Formulas
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <div class="d-flex align-items-start">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                <div>
                    <strong class="d-block mb-2">Please fix the following issues:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-4 px-4">
            <h5 class="mb-0 fw-semibold" style="color: #198754;">Template Information</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.gradesFormula.structureTemplate.update', $template) }}" x-data="structureTemplateEditor()" x-init="init()">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="template_label" class="form-label fw-semibold">Template Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('template_label') is-invalid @enderror" id="template_label" name="template_label" value="{{ old('template_label', $template->label) }}" required maxlength="100">
                        @error('template_label')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="template_key" class="form-label fw-semibold">Template Key <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('template_key') is-invalid @enderror" id="template_key" name="template_key" value="{{ old('template_key', $template->template_key) }}" required maxlength="50" pattern="[a-z0-9_]+" title="Use lowercase letters, numbers, and underscores only">
                        @error('template_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label for="template_description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control @error('template_description') is-invalid @enderror" id="template_description" name="template_description" rows="2" maxlength="500">{{ old('template_description', $template->description) }}</textarea>
                        @error('template_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-semibold mb-3">Component Structure</h6>
                <p class="text-muted small mb-3">Define the grading components and their weights. Main components can contain sub-components. Each group must total 100%.</p>

                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill" @click="addMainComponent()">
                        <i class="bi bi-plus-circle me-1"></i>Add Main Component
                    </button>
                </div>

                <div class="border rounded-3 p-3 bg-light">
                    <template x-if="components.length === 0">
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 opacity-50"></i>
                            <p class="mb-0 mt-2">No components added yet. Click "Add Main Component" to start.</p>
                        </div>
                    </template>

                    <template x-for="(component, index) in components" :key="component.id">
                        <div class="card mb-2 border">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="flex-grow-1">
                                        <div class="row g-2 align-items-center mb-2">
                                            <div class="col-md-4">
                                                <label class="form-label small mb-0">Component Label</label>
                                                <input type="text" class="form-control form-control-sm" x-model="component.label" :name="`components[${component.id}][label]`" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-0">Activity Type</label>
                                                <input type="text" class="form-control form-control-sm" x-model="component.activity_type" :name="`components[${component.id}][activity_type]`" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-0">Weight (%)</label>
                                                <input type="number" class="form-control form-control-sm" x-model.number="component.weight" :name="`components[${component.id}][weight]`" min="0" max="100" step="0.1" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-0">Max Components</label>
                                                <input type="number" class="form-control form-control-sm" x-model.number="component.max_items" :name="`components[${component.id}][max_items]`" min="1" max="5" step="1">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small mb-0">&nbsp;</label>
                                                <button type="button" class="btn btn-sm btn-outline-danger w-100" @click="removeComponent(component.id)" title="Remove">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <input type="hidden" :name="`components[${component.id}][is_main]`" :value="component.is_main ? 1 : 0">
                                        <input type="hidden" :name="`components[${component.id}][parent_id]`" :value="component.parent_id ?? ''">

                                        <template x-if="component.is_main">
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-xs btn-outline-secondary" @click="addSubComponent(component.id)">
                                                    <i class="bi bi-plus-circle me-1"></i>Add Sub-Component
                                                </button>
                                            </div>
                                        </template>

                                        <template x-for="subComponent in getSubComponents(component.id)" :key="subComponent.id">
                                            <div class="card mt-2 border-start border-3 border-info ms-3 bg-white">
                                                <div class="card-body py-2 px-3">
                                                    <div class="row g-2 align-items-center">
                                                        <div class="col-md-4">
                                                            <label class="form-label small mb-0">Sub-Component Label</label>
                                                            <input type="text" class="form-control form-control-sm" x-model="subComponent.label" :name="`components[${subComponent.id}][label]`" required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small mb-0">Activity Type</label>
                                                            <input type="text" class="form-control form-control-sm" x-model="subComponent.activity_type" :name="`components[${subComponent.id}][activity_type]`" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label small mb-0">Weight (%)</label>
                                                            <input type="number" class="form-control form-control-sm" x-model.number="subComponent.weight" :name="`components[${subComponent.id}][weight]`" min="0" max="100" step="0.1" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label small mb-0">Max Components</label>
                                                            <input type="number" class="form-control form-control-sm" x-model.number="subComponent.max_items" :name="`components[${subComponent.id}][max_items]`" min="1" max="5" step="1">
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label small mb-0">&nbsp;</label>
                                                            <button type="button" class="btn btn-sm btn-outline-danger w-100" @click="removeComponent(subComponent.id)" title="Remove">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" :name="`components[${subComponent.id}][is_main]`" value="0">
                                                    <input type="hidden" :name="`components[${subComponent.id}][parent_id]`" :value="subComponent.parent_id">
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <hr class="my-4">

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Enter your password to confirm changes">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ $backRoute }}" class="btn btn-outline-secondary rounded-pill">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-success rounded-pill">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function structureTemplateEditor() {
    return {
        components: [],
        nextId: 1,

        init() {
            // Load existing template structure
            const existingStructure = @json($template->structure_config ?? []);
            this.loadExistingStructure(existingStructure);
        },

        loadExistingStructure(structure) {
            // Reset before hydrating so repeated Alpine init cycles do not append duplicates
            this.components = [];
            this.nextId = 1;

            if (!structure || !Array.isArray(structure.children) || structure.children.length === 0) {
                return;
            }

            structure.children.forEach(child => {
                const component = {
                    id: this.nextId++,
                    label: child.label || '',
                    activity_type: child.activity_type || child.key || '',
                    weight: (child.weight || 0) * 100, // Convert decimal to percentage
                    max_items: child.max_assessments || null,
                    is_main: true,
                    parent_id: null
                };
                this.components.push(component);

                // Load sub-components if any
                if (child.type === 'composite' && child.children) {
                    child.children.forEach(subChild => {
                        const subComponent = {
                            id: this.nextId++,
                            label: subChild.label || '',
                            activity_type: subChild.activity_type || subChild.key || '',
                            weight: (subChild.weight || 0) * 100,
                            max_items: subChild.max_assessments || null,
                            is_main: false,
                            parent_id: component.id
                        };
                        this.components.push(subComponent);
                    });
                }
            });
        },

        addMainComponent() {
            this.components.push({
                id: this.nextId++,
                label: '',
                activity_type: '',
                weight: 0,
                max_items: null,
                is_main: true,
                parent_id: null
            });
        },

        addSubComponent(parentId) {
            this.components.push({
                id: this.nextId++,
                label: '',
                activity_type: '',
                weight: 0,
                max_items: null,
                is_main: false,
                parent_id: parentId
            });
        },

        removeComponent(id) {
            // Remove the component and all its sub-components
            this.components = this.components.filter(c => {
                if (c.id === id) return false;
                if (c.parent_id === id) return false;
                return true;
            });
        },

        getSubComponents(parentId) {
            return this.components.filter(c => c.parent_id === parentId);
        }
    };
}
</script>
@endsection
