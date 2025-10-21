<div class="col-12">
    <h6 class="fw-semibold">Activity Weights</h6>
    <p class="text-muted">Ensure each activity type is unique and that the total equals 100%.</p>

    <template x-for="(weight, index) in weights" :key="index">
        <div class="row align-items-end g-3 mb-3 border rounded-4 p-3 shadow-sm-sm weight-editor-card">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Activity Type</label>
                <input type="text" class="form-control" :name="`weights[${index}][activity_type]`" x-model="weight.activity_type" placeholder="e.g., quiz" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Weight (%)</label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0" max="100" class="form-control" :name="`weights[${index}][weight]`" x-model.number="weight.weight" placeholder="e.g., 40" required>
                    <span class="input-group-text">%</span>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger w-100" @click="removeRow(index)" x-show="weights.length > 1">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </template>

    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <button type="button" class="btn btn-outline-success" @click="addRow">
            <i class="bi bi-plus-circle me-2"></i>Add Activity Weight
        </button>
        <span class="badge bg-light text-dark fw-semibold">Total weight: <span x-text="weightTotal().toFixed(2) + '%'"></span></span>
    </div>
</div>
