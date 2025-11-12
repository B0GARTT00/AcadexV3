@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-white rounded-pill px-3 py-1 shadow-sm mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('chairperson.structureTemplates.index') }}" class="text-success text-decoration-none">
                            <i class="bi bi-diagram-3 me-1"></i>Formula Requests
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
                        <label for="template_name" class="form-label fw-semibold">Template Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="template_name" name="template_name" value="{{ old('template_name') }}" required maxlength="255" placeholder="e.g., CS Theory Courses, Lab-Heavy Structure">
                        <small class="text-muted">Choose a descriptive name that identifies the purpose of this template.</small>
                    </div>
                    <div class="col-12">
                        <label for="label" class="form-label fw-semibold">Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="label" name="label" value="{{ old('label') }}" required maxlength="255" placeholder="Auto-generated from template name" readonly>
                        <small class="text-muted">This is automatically generated based on the template name.</small>
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
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Grading Components</h5>
                    <div id="weight-indicator" class="badge bg-secondary">
                        Total: <span id="weight-total">0</span>%
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="weight-alert" class="alert alert-warning d-none mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <span id="weight-message">Component weights must total exactly 100%</span>
                </div>
                
                <div id="components-container">
                    <!-- Components will be added dynamically -->
                </div>
                <button type="button" class="btn btn-outline-success btn-sm mt-2" id="add-component-btn">
                    <i class="bi bi-plus-circle me-1"></i>Add Main Component
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <a href="{{ route('chairperson.structureTemplates.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-success px-4" id="submit-btn" disabled>
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
                <div class="col-md-3">
                    <label class="form-label small">Name</label>
                    <input type="text" class="form-control form-control-sm activity-name-input" placeholder="e.g., Quiz" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Label</label>
                    <input type="text" class="form-control form-control-sm component-label" required data-auto="true">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Weight (%)</label>
                    <input type="number" class="form-control form-control-sm component-weight" min="0" max="100" step="0.01" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Max Components</label>
                    <input type="number" class="form-control form-control-sm component-max-items" min="1" max="5" step="1" placeholder="1-5">
                    <small class="text-muted">Limit: 1-5</small>
                </div>
            </div>
            <div class="mt-2 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-sm btn-outline-success add-subcomponent-btn">
                    <i class="bi bi-plus me-1"></i>Add Sub-component
                </button>
                <small class="text-muted sub-weight-indicator d-none">
                    Sub-components: <span class="sub-weight-total fw-semibold">0</span>%
                </small>
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
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm activity-name-input" placeholder="e.g., Quiz" required>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm component-label" placeholder="Label" required data-auto="true">
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control form-control-sm component-weight" min="0" max="100" step="0.01" placeholder="Weight %" required>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control form-control-sm component-max-items" min="1" max="5" step="1" placeholder="Max 1-5">
                </div>
            </div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const templateNameInput = document.getElementById('template_name');
    const labelInput = document.getElementById('label');
    const componentsContainer = document.getElementById('components-container');
    const addComponentBtn = document.getElementById('add-component-btn');
    const form = document.getElementById('templateRequestForm');
    const submitBtn = document.getElementById('submit-btn');
    const weightTotalSpan = document.getElementById('weight-total');
    const weightIndicator = document.getElementById('weight-indicator');
    const weightAlert = document.getElementById('weight-alert');
    const weightMessage = document.getElementById('weight-message');
    
    let componentIdCounter = 1;
    let subComponentIdCounter = 1;

    // Auto-generate label from template name
    templateNameInput.addEventListener('input', () => {
        labelInput.value = templateNameInput.value;
    });

    // Initialize with one component
    addComponent();

    addComponentBtn.addEventListener('click', () => addComponent());

    function addComponent() {
        const template = document.getElementById('component-template');
        const clone = template.content.cloneNode(true);
        const componentId = 'comp_' + componentIdCounter++;
        
        const componentItem = clone.querySelector('.component-item');
        componentItem.dataset.componentId = componentId;
        
        const removeBtn = clone.querySelector('.remove-component-btn');
        removeBtn.addEventListener('click', () => {
            componentItem.remove();
            updateWeightTotal();
        });
        
        const addSubBtn = clone.querySelector('.add-subcomponent-btn');
        addSubBtn.addEventListener('click', () => addSubComponent(componentItem, componentId));
        
        // Add weight change listener
        const weightInput = clone.querySelector('.component-weight');
        weightInput.addEventListener('input', updateWeightTotal);
        
        // Auto-generate label from name unless user edits label
        const nameInput = clone.querySelector('.activity-name-input');
        const labelInputLocal = clone.querySelector('.component-label');
        if (nameInput && labelInputLocal) {
            // initialize data-auto if not present
            if (typeof labelInputLocal.dataset.auto === 'undefined') {
                labelInputLocal.dataset.auto = 'true';
            }

            const toTitleCase = (str) => {
                return String(str).replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase());
            };

            nameInput.addEventListener('input', () => {
                if (labelInputLocal.dataset.auto !== 'false') {
                    labelInputLocal.value = toTitleCase(nameInput.value.trim());
                }
            });

            labelInputLocal.addEventListener('input', () => {
                // if user types into label, stop auto-updating
                labelInputLocal.dataset.auto = 'false';
            });
        }

        componentsContainer.appendChild(clone);
        updateWeightTotal();
    }

    function addSubComponent(parentElement, parentId) {
        const template = document.getElementById('subcomponent-template');
        const clone = template.content.cloneNode(true);
        const subId = 'sub_' + subComponentIdCounter++;
        
        const subItem = clone.querySelector('.subcomponent-item');
        subItem.dataset.subcomponentId = subId;
        subItem.dataset.parentId = parentId;
        
        const removeBtn = clone.querySelector('.remove-subcomponent-btn');
        removeBtn.addEventListener('click', () => {
            subItem.remove();
            updateWeightTotal();
        });
        
        // Add weight change listener
        const weightInput = clone.querySelector('.component-weight');
        weightInput.addEventListener('input', updateWeightTotal);
        
        // Auto-generate label from name for subcomponent
        const nameInput = clone.querySelector('.activity-name-input');
        const labelInputLocal = clone.querySelector('.component-label');
        if (nameInput && labelInputLocal) {
            if (typeof labelInputLocal.dataset.auto === 'undefined') {
                labelInputLocal.dataset.auto = 'true';
            }

            const toTitleCase = (str) => {
                return String(str).replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase());
            };

            nameInput.addEventListener('input', () => {
                if (labelInputLocal.dataset.auto !== 'false') {
                    labelInputLocal.value = toTitleCase(nameInput.value.trim());
                }
            });

            labelInputLocal.addEventListener('input', () => {
                labelInputLocal.dataset.auto = 'false';
            });
        }
        
        const subContainer = parentElement.querySelector('.subcomponents-container');
        subContainer.appendChild(clone);
        updateWeightTotal();
    }

    function updateWeightTotal() {
        let total = 0;
        let hasSubComponentError = false;
        const mainComponents = componentsContainer.querySelectorAll('.component-item');
        
        mainComponents.forEach((comp) => {
            const weight = parseFloat(comp.querySelector('.component-weight').value) || 0;
            total += weight;
            
            // Check sub-component weights separately
            const subs = comp.querySelectorAll('.subcomponent-item');
            const subWeightIndicator = comp.querySelector('.sub-weight-indicator');
            const subWeightTotalSpan = comp.querySelector('.sub-weight-total');
            
            if (subs.length > 0) {
                let subTotal = 0;
                subs.forEach((sub) => {
                    const subWeight = parseFloat(sub.querySelector('.component-weight').value) || 0;
                    subTotal += subWeight;
                });
                
                // Show sub-component indicator
                if (subWeightIndicator && subWeightTotalSpan) {
                    subWeightIndicator.classList.remove('d-none');
                    subWeightTotalSpan.textContent = subTotal.toFixed(2);
                    
                    // Color code the sub-component indicator
                    if (subTotal === 100) {
                        subWeightTotalSpan.classList.remove('text-danger', 'text-warning');
                        subWeightTotalSpan.classList.add('text-success');
                    } else {
                        subWeightTotalSpan.classList.remove('text-success');
                        if (subTotal > 100) {
                            subWeightTotalSpan.classList.remove('text-warning');
                            subWeightTotalSpan.classList.add('text-danger');
                        } else {
                            subWeightTotalSpan.classList.remove('text-danger');
                            subWeightTotalSpan.classList.add('text-warning');
                        }
                        hasSubComponentError = true;
                    }
                }
            } else {
                // Hide indicator if no sub-components
                if (subWeightIndicator) {
                    subWeightIndicator.classList.add('d-none');
                }
            }
        });
        
        // Update main display
        weightTotalSpan.textContent = total.toFixed(2);
        
        // Update main indicator badge
        const totalValid = total === 100;
        const allSubComponentsValid = !hasSubComponentError;
        
        if (totalValid && allSubComponentsValid) {
            weightIndicator.classList.remove('bg-secondary', 'bg-danger', 'bg-warning');
            weightIndicator.classList.add('bg-success');
            weightAlert.classList.add('d-none');
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
            
            if (!totalValid) {
                if (total > 100) {
                    weightIndicator.classList.remove('bg-secondary', 'bg-success', 'bg-warning');
                    weightIndicator.classList.add('bg-danger');
                    weightAlert.classList.remove('d-none');
                    weightMessage.textContent = `Main component weights total ${total.toFixed(2)}% (exceeds 100%). Please adjust.`;
                } else {
                    weightIndicator.classList.remove('bg-success', 'bg-danger', 'bg-warning');
                    weightIndicator.classList.add('bg-secondary');
                    weightAlert.classList.remove('d-none');
                    weightMessage.textContent = `Main component weights total ${total.toFixed(2)}% (must be exactly 100%). Please add ${(100 - total).toFixed(2)}% more.`;
                }
            } else if (hasSubComponentError) {
                weightIndicator.classList.remove('bg-secondary', 'bg-danger');
                weightIndicator.classList.add('bg-warning');
                weightAlert.classList.remove('d-none');
                weightMessage.textContent = `Main components are correct, but some sub-components don't total 100%. Check each component's sub-component weights.`;
            }
        }
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Validate weights first
        let total = 0;
        let hasSubComponentError = false;
        const mainComponents = componentsContainer.querySelectorAll('.component-item');
        
        mainComponents.forEach((comp) => {
            const weight = parseFloat(comp.querySelector('.component-weight').value) || 0;
            total += weight;
            
            // Check sub-components
            const subs = comp.querySelectorAll('.subcomponent-item');
            if (subs.length > 0) {
                let subTotal = 0;
                subs.forEach((sub) => {
                    const subWeight = parseFloat(sub.querySelector('.component-weight').value) || 0;
                    subTotal += subWeight;
                });
                
                if (subTotal !== 100) {
                    hasSubComponentError = true;
                }
            }
        });
        
        if (total !== 100) {
            alert(`Main component weights must total exactly 100%. Current total: ${total.toFixed(2)}%`);
            return;
        }
        
        if (hasSubComponentError) {
            alert('Each component with sub-components must have sub-component weights totaling exactly 100%. Please check your sub-component weights.');
            return;
        }
        
        const structure = [];
        
        mainComponents.forEach((comp) => {
            const componentId = comp.dataset.componentId;
            const activityType = comp.querySelector('.activity-name-input').value.trim();
            const label = comp.querySelector('.component-label').value.trim();
            const weight = parseFloat(comp.querySelector('.component-weight').value);
            const maxItemsInput = comp.querySelector('.component-max-items');
            const maxItems = maxItemsInput && maxItemsInput.value ? parseInt(maxItemsInput.value) : null;
            
            if (label && !isNaN(weight)) {
                structure.push({
                    component_id: componentId,
                    activity_type: activityType,
                    label: label,
                    weight: weight,
                    max_items: maxItems,
                    is_main: true,
                    parent_id: null
                });
                
                const subs = comp.querySelectorAll('.subcomponent-item');
                subs.forEach((sub) => {
                    const subId = sub.dataset.subcomponentId;
                    const subActivityType = sub.querySelector('.activity-name-input').value.trim();
                    const subLabel = sub.querySelector('.component-label').value.trim();
                    const subWeight = parseFloat(sub.querySelector('.component-weight').value);
                    const subMaxItemsInput = sub.querySelector('.component-max-items');
                    const subMaxItems = subMaxItemsInput && subMaxItemsInput.value ? parseInt(subMaxItemsInput.value) : null;
                    
                    if (subLabel && !isNaN(subWeight)) {
                        structure.push({
                            subcomponent_id: subId,
                            activity_type: subActivityType,
                            label: subLabel,
                            weight: subWeight,
                            max_items: subMaxItems,
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

#weight-indicator {
    font-size: 1rem;
    font-weight: 600;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
}

#submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.component-weight:focus {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

.sub-weight-indicator {
    font-size: 0.85rem;
    transition: opacity 0.3s ease;
}

.sub-weight-total.text-success {
    color: #198754 !important;
}

.sub-weight-total.text-warning {
    color: #ffc107 !important;
}

.sub-weight-total.text-danger {
    color: #dc3545 !important;
}
</style>
@endpush
