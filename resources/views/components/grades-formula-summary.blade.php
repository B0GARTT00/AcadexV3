@props([
    'formula',
    'label' => null,
])

@php
    $weights = $formula->weights ?? collect();
    $weightList = $weights->map(fn($weight) => strtoupper($weight->activity_type) . ' ' . number_format($weight->weight_percent, 0) . '%');
@endphp

<div class="border rounded-4 p-4 shadow-sm-sm">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-semibold text-success">{{ $label ?? $formula->label }}</h6>
        <span class="badge bg-light text-success">Passing {{ number_format($formula->passing_grade, 0) }}</span>
    </div>
    <div class="text-muted small mb-3">
        Base {{ number_format($formula->base_score, 0) }} · Scale ×{{ number_format($formula->scale_multiplier, 0) }}
    </div>
    @if($weightList->isNotEmpty())
        <div class="d-flex flex-wrap gap-2">
            @foreach($weightList as $weight)
                <span class="badge bg-success-subtle text-success">{{ $weight }}</span>
            @endforeach
        </div>
    @else
        <p class="text-muted small mb-0">No activity weights configured.</p>
    @endif
</div>
