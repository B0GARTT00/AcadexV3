@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-white rounded-pill px-3 py-1 shadow-sm mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('chairperson.structureTemplates.index') }}" class="text-success text-decoration-none">
                            <i class="bi bi-diagram-3 me-1"></i>Template Requests
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Create New Request</li>
                </ol>
            </nav>

            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #198754, #20c997);">
                    <i class="bi bi-plus-circle text-white" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-1" style="color: #198754;">Create Structure Template Request</h3>
                    <p class="text-muted mb-0">Design a custom grading structure and submit it for admin approval</p>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('chairperson.structureTemplates.store') }}" id="templateRequestForm">
        @csrf

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-bold">Template Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="label" class="form-label fw-semibold">Template Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="label" name="label" value="{{ old('label') }}" required maxlength="255" placeholder="e.g., CS Theory Courses, Lab-Heavy Structure">
                        <small class="text-muted">Choose a descriptive name that identifies the purpose of this template.</small>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="1000" placeholder="Explain when and why this template should be used...">{{ old('description') }}</textarea>
                        <small class="text-muted">Optional: Provide context to help admins understand your template's purpose.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-bold">Structure Configuration</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label for="structure_type" class="form-label fw-semibold">Structure Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="structure_type" name="structure_type" required>
                        <option value="">-- Select Structure Type --</option>
                        @foreach ($structureCatalog as $structDef)
                            <option value="{{ $structDef['key'] }}" {{ old('structure_type') === $structDef['key'] ? 'selected' : '' }}>
                                {{ $structDef['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="structure-builder" class="d-none">
                    <h6 class="fw-bold mb-3">Grading Components</h6>
                    <div id="components-container">
                        <!-- Components will be added dynamically -->
                    </div>
                    <button type="button" class="btn btn-outline-success btn-sm mt-2" id="add-component-btn">
                        <i class="bi bi-plus-circle me-1"></i>Add Main Component
                    </button>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <a href="{{ route('chairperson.structureTemplates.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-send me-1"></i>Submit Request
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Component Template -->
<template id="component-template">
    <div class="component-item card mb-3" data-component-id="">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="mb-0 fw-bold">Main Component</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-component-btn">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small">Activity Type</label>
                    <select class="form-select form-select-sm activity-type-select" required>
                        <option value="quiz">Quiz</option>
                        <option value="exam">Exam</option>
                        <option value="assignment">Assignment</option>
                        <option value="project">Project</option>
                        <option value="participation">Participation</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Label</label>
                    <input type="text" class="form-control form-control-sm component-label" placeholder="e.g., Midterm Exam" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Weight (%)</label>
                    <input type="number" class="form-control form-control-sm component-weight" min="0" max="100" step="0.01" required>
                </div>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-sm btn-outline-success add-subcomponent-btn">
                    <i class="bi bi-plus me-1"></i>Add Sub-component
                </button>
            </div>
            <div class="subcomponents-container mt-2"></div>
        </div>
    </div>
</template>

<!-- Subcomponent Template -->
<template id="subcomponent-template">
    <div class="subcomponent-item card bg-light ms-4 mb-2" data-subcomponent-id="">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <small class="fw-semibold">Sub-component</small>
                <button type="button" class="btn btn-sm btn-outline-danger remove-subcomponent-btn py-0 px-1">
                    <i class="bi bi-x" style="font-size: 0.875rem;"></i>
                </button>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <select class="form-select form-select-sm activity-type-select" required>
                        <option value="quiz">Quiz</option>
                        <option value="exam">Exam</option>
                        <option value="assignment">Assignment</option>
                        <option value="project">Project</option>
                        <option value="participation">Participation</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm component-label" placeholder="Label" required>
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control form-control-sm component-weight" min="0" max="100" step="0.01" placeholder="Weight %" required>
                </div>
            </div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const structureTypeSelect = document.getElementById('structure_type');
    const structureBuilder = document.getElementById('structure-builder');
    const componentsContainer = document.getElementById('components-container');
    const addComponentBtn = document.getElementById('add-component-btn');
    const form = document.getElementById('templateRequestForm');
    
    let componentIdCounter = 1;
    let subComponentIdCounter = 1;

    structureTypeSelect.addEventListener('change', () => {
        if (structureTypeSelect.value) {
            structureBuilder.classList.remove('d-none');
            if (componentsContainer.children.length === 0) {
                addComponent();
            }
        } else {
            structureBuilder.classList.add('d-none');
        }
    });

    addComponentBtn.addEventListener('click', () => addComponent());

    function addComponent() {
        const template = document.getElementById('component-template');
        const clone = template.content.cloneNode(true);
        const componentId = 'comp_' + componentIdCounter++;
        
        const componentItem = clone.querySelector('.component-item');
        componentItem.dataset.componentId = componentId;
        
        const removeBtn = clone.querySelector('.remove-component-btn');
        removeBtn.addEventListener('click', () => componentItem.remove());
        
        const addSubBtn = clone.querySelector('.add-subcomponent-btn');
        addSubBtn.addEventListener('click', () => addSubComponent(componentItem, componentId));
        
        componentsContainer.appendChild(clone);
    }

    function addSubComponent(parentElement, parentId) {
        const template = document.getElementById('subcomponent-template');
        const clone = template.content.cloneNode(true);
        const subId = 'sub_' + subComponentIdCounter++;
        
        const subItem = clone.querySelector('.subcomponent-item');
        subItem.dataset.subcomponentId = subId;
        subItem.dataset.parentId = parentId;
        
        const removeBtn = clone.querySelector('.remove-subcomponent-btn');
        removeBtn.addEventListener('click', () => subItem.remove());
        
        const subContainer = parentElement.querySelector('.subcomponents-container');
        subContainer.appendChild(clone);
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const structure = [];
        const mainComponents = componentsContainer.querySelectorAll('.component-item');
        
        mainComponents.forEach((comp) => {
            const componentId = comp.dataset.componentId;
            const activityType = comp.querySelector('.activity-type-select').value;
            const label = comp.querySelector('.component-label').value.trim();
            const weight = parseFloat(comp.querySelector('.component-weight').value);
            
            if (label && !isNaN(weight)) {
                structure.push({
                    activity_type: activityType,
                    label: label,
                    weight: weight,
                    is_main: true,
                    parent_id: null
                });
                
                const subs = comp.querySelectorAll('.subcomponent-item');
                subs.forEach((sub) => {
                    const subActivityType = sub.querySelector('.activity-type-select').value;
                    const subLabel = sub.querySelector('.component-label').value.trim();
                    const subWeight = parseFloat(sub.querySelector('.component-weight').value);
                    
                    if (subLabel && !isNaN(subWeight)) {
                        structure.push({
                            activity_type: subActivityType,
                            label: subLabel,
                            weight: subWeight,
                            is_main: false,
                            parent_id: componentId
                        });
                    }
                });
            }
        });
        
        // Add structure to form as hidden input
        const structureInput = document.createElement('input');
        structureInput.type = 'hidden';
        structureInput.name = 'structure';
        structureInput.value = JSON.stringify(structure);
        form.appendChild(structureInput);
        
        form.submit();
    });

    // Initialize if old input exists
    if (structureTypeSelect.value) {
        structureBuilder.classList.remove('d-none');
    }
});
</script>
@endpush

@push('styles')
<style>
.component-item, .subcomponent-item {
    transition: all 0.2s ease;
}

.component-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
@endpush
