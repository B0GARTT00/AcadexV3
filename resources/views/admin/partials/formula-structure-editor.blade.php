<div class="col-12">
    <h6 class="fw-semibold">Grading Layout</h6>
    <p class="text-muted">Select the grading structure and fine-tune component weights. Each component group must total 100%.</p>

    <div class="mb-4">
    <label class="form-label fw-semibold">Department Formula</label>
        <select class="form-select" x-model="structureType" @change="switchStructure()">
            <template x-for="(definition, key) in catalog" :key="key">
                <option :value="key" x-text="definition.label"></option>
            </template>
        </select>
        <small class="text-muted" x-text="catalog[structureType]?.description ?? ''"></small>
    </div>

    <div class="border rounded-4 bg-white shadow-sm-sm">
        <template x-for="node in orderedNodes()" :key="node.ref.uid">
            <div class="px-3 py-3 border-bottom" :style="`margin-left: ${node.depth * 24}px;`">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="fw-semibold" x-text="node.ref.label"></div>
                        <div class="text-muted small" x-show="isComposite(node.ref)">
                            Relative weight: <span x-text="formatPercent(node.ref.weight_percent)"></span>
                            · Child total: <span x-text="formatPercent(node.ref.total_percent)"></span>
                            <span class="text-danger" x-show="compositeWarning(node.ref)">· must total 100%</span>
                            <div>Overall contribution: <span x-text="formatPercent(node.ref.overall_percent)"></span></div>
                        </div>
                        <div class="text-muted small" x-show="!isComposite(node.ref)">
                            Max assessments: <span x-text="displayMaxAssessments(node.ref)"></span>
                            · Relative weight: <span x-text="formatPercent(node.ref.weight_percent)"></span>
                            <div>Overall contribution: <span x-text="formatPercent(node.ref.overall_percent)"></span></div>
                        </div>
                    </div>
                    <div class="ms-auto" x-show="node.parent">
                        <div class="input-group input-group-sm" style="width: 140px;">
                            <input
                                type="number"
                                class="form-control"
                                min="0"
                                max="100"
                                step="0.1"
                                x-model.number="node.ref.weight_percent"
                                @input="syncWeight(node.ref)"
                            >
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <div class="text-center text-muted py-3" x-show="orderedNodes().length === 0">
            No configurable assessments available for this layout.
        </div>
    </div>

    <input type="hidden" name="structure_type" :value="structureType">
    <input type="hidden" name="structure_config" :value="serializeStructure()">

    <div class="alert alert-danger mt-3 py-2 mb-0" x-show="!structureIsBalanced()" style="display: none;">
        Please ensure each component group totals 100% before saving.
    </div>
</div>
