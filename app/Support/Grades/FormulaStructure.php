<?php

namespace App\Support\Grades;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Helper utilities for encoding, validating, and flattening structured grade formulas.
 */
class FormulaStructure
{
    /**
     * Supported structure keys mapped to human-friendly labels and descriptions.
     */
    public const STRUCTURE_DEFINITIONS = [
        'lecture_only' => [
            'label' => 'Lecture Only',
            'description' => 'Single-track lecture classes where quizzes, exams, and OCR make up the entire period grade.',
        ],
        'lecture_laboratory' => [
            'label' => 'Lecture + Laboratory',
            'description' => 'Blended lecture and laboratory courses. Lecture and lab grades are calculated separately then combined.',
        ],
        'skills_laboratory' => [
            'label' => 'Skills Laboratory',
            'description' => 'Skills lab courses where lab activities are blended with an exam component.',
        ],
        'clinical_laboratory' => [
            'label' => 'Clinical Laboratory',
            'description' => 'Clinical placements where the grade is the mean of concept evaluations.',
        ],
        'skills_clinical' => [
            'label' => 'Skills + Clinical Laboratory',
            'description' => 'Courses that combine skills lab performance with clinical concept evaluations.',
        ],
    ];

    /**
     * Return the default normalized structure for the requested type.
     */
    public static function default(string $type): array
    {
        return match ($type) {
            'lecture_laboratory' => self::lectureLaboratory(),
            'skills_laboratory' => self::skillsLaboratory(),
            'clinical_laboratory' => self::clinicalLaboratory(),
            'skills_clinical' => self::skillsClinical(),
            default => self::lectureOnly(),
        };
    }

    /**
     * Produce a deep clone of the provided structure with sensible defaults applied.
     */
    public static function normalize(array $structure): array
    {
        $normalized = self::clone($structure);

        $normalized['key'] ??= 'period_grade';
        $normalized['type'] = self::normalizeType($normalized['type'] ?? 'composite');
        $normalized['label'] = $normalized['label'] ?? 'Period Grade';
        $normalized['children'] = collect($normalized['children'] ?? [])
            ->map(fn ($child, $index) => self::normalizeNode($child, $normalized['key'], $index))
            ->values()
            ->all();

        return $normalized;
    }

    /**
     * Flatten a structure into a list of leaf activity weights expressed as decimals.
     */
    public static function flattenWeights(array $structure): array
    {
        $structure = self::normalize($structure);
        return self::gatherLeafWeights($structure);
    }

    /**
     * Return an ordered list of leaf activity types present in the structure.
     */
    public static function leafActivityTypes(array $structure): array
    {
        return collect(self::flattenWeights($structure))
            ->pluck('activity_type')
            ->values()
            ->all();
    }

    /**
     * Map activity types to their configured max assessment counts (null defaults to unlimited).
     */
    public static function leafMaxAssessmentMap(array $structure): array
    {
        $structure = self::normalize($structure);
        $map = [];
        self::walk($structure, function (array $node) use (&$map) {
            if (($node['type'] ?? null) === 'activity') {
                $activityType = mb_strtolower($node['activity_type'] ?? $node['key']);
                $map[$activityType] = Arr::get($node, 'max_assessments');
            }
        });

        return $map;
    }

    /**
     * Provide user-facing labels for each activity path.
     */
    public static function activityLabelMap(array $structure): array
    {
        $structure = self::normalize($structure);
        $map = [];
        self::walk($structure, function (array $node) use (&$map) {
            if (($node['type'] ?? null) === 'activity') {
                $activityType = mb_strtolower($node['activity_type'] ?? $node['key']);
                $map[$activityType] = $node['label'] ?? self::formatLabel($activityType);
            }
        });

        return $map;
    }

    /**
     * Validate that each composite node has weights that sum to 1 (within tolerance) and that
     * activity nodes contain required metadata. Returns an array of error messages.
     */
    public static function validate(array $structure, float $tolerance = 0.001): array
    {
        $structure = self::normalize($structure);
        $errors = [];

        $walker = function (array $node, array $path) use (&$walker, &$errors, $tolerance) {
            $type = $node['type'] ?? 'activity';
            $label = $node['label'] ?? self::formatLabel(end($path) ?: 'Period Grade');
            $pathString = implode(' › ', $path ?: ['Period Grade']);

            if ($type === 'composite') {
                $children = $node['children'] ?? [];
                if (empty($children)) {
                    $errors[] = sprintf('%s must include at least one component.', $pathString);
                    return;
                }

                $total = 0.0;
                foreach ($children as $child) {
                    $weight = (float) ($child['weight'] ?? 0);
                    if ($weight < 0) {
                        $errors[] = sprintf('%s has an invalid weight.', $pathString);
                    }
                    $total += $weight;
                    $walker($child, array_merge($path, [$child['label'] ?? self::formatLabel($child['key'] ?? 'component')]));
                }

                if (abs($total - 1.0) > $tolerance) {
                    $errors[] = sprintf('%s weights must sum to 100%%. Currently at %.2f%%.', $pathString, $total * 100);
                }
            } else {
                $activityType = $node['activity_type'] ?? $node['key'] ?? null;
                if (! $activityType) {
                    $errors[] = sprintf('%s activity is missing its type identifier.', $pathString);
                }

                $maxAssessments = Arr::get($node, 'max_assessments');
                if ($maxAssessments !== null) {
                    if (! is_numeric($maxAssessments) || $maxAssessments < 1) {
                        $errors[] = sprintf('%s must allow at least one assessment.', $pathString);
                    }

                    $limitedTypes = ['quiz', 'ocr', 'return_demo', 'concept'];
                    $baseType = self::baseActivityType($activityType);
                    if (in_array($baseType, $limitedTypes, true) && $maxAssessments > 5) {
                        $errors[] = sprintf('%s may only include up to 5 assessments.', $pathString);
                    }
                }
            }
        };

        $walker($structure, []);

        return $errors;
    }

    /**
     * Recursively map the structure and transform front-end weight percents into decimals.
     */
    public static function fromPercentPayload(array $payload): array
    {
        $structure = self::clone($payload);
        self::convertPercentWeights($structure, true);
        return self::normalize($structure);
    }

    /**
     * Convert a structure that already uses decimals into a percent-based tree for front-end editing.
     */
    public static function toPercentPayload(array $structure): array
    {
        $structure = self::normalize($structure);
        self::convertDecimalWeightsToPercent($structure, true);
        return $structure;
    }

    /**
     * Utility: deep clone helper.
     */
    public static function clone(array $value): array
    {
        return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Determine the label for an activity identifier.
     */
    public static function formatLabel(string $identifier): string
    {
        return Str::of($identifier)
            ->replace(['.', '_'], ' ')
            ->lower()
            ->title()
            ->toString();
    }

    /**
     * Reduce an activity path to its base activity keyword (e.g. lecture.quiz -> quiz).
     */
    public static function baseActivityType(string $activityType): string
    {
        $segments = explode('.', $activityType);
        return mb_strtolower(end($segments));
    }

    /**
     * Build the default lecture-only structure.
     */
    protected static function lectureOnly(): array
    {
        return [
            'key' => 'period_grade',
            'type' => 'composite',
            'label' => 'Period Grade',
            'children' => [
                self::activityNode('quiz', 'Quizzes', 0.40, 5),
                self::activityNode('exam', 'Exam', 0.40, 1),
                self::activityNode('ocr', 'Other Course Requirements', 0.20, 5),
            ],
        ];
    }

    /**
     * Build the default lecture + laboratory structure.
     */
    protected static function lectureLaboratory(): array
    {
        return [
            'key' => 'period_grade',
            'type' => 'composite',
            'label' => 'Period Grade',
            'children' => [
                self::compositeNode('lecture', 'Lecture Component', 0.60, [
                    self::activityNode('lecture.quiz', 'Lecture Quizzes', 0.40, 5),
                    self::activityNode('lecture.exam', 'Lecture Exams', 0.40, 1),
                    self::activityNode('lecture.ocr', 'Lecture OCR', 0.20, 5),
                ]),
                self::compositeNode('laboratory', 'Laboratory Component', 0.40, [
                    self::activityNode('laboratory.quiz', 'Laboratory Quizzes', 0.40, 5),
                    self::activityNode('laboratory.exam', 'Laboratory Exams', 0.40, 1),
                    self::activityNode('laboratory.ocr', 'Laboratory OCR', 0.20, 5),
                ]),
            ],
        ];
    }

    /**
     * Build the default skills laboratory structure.
     */
    protected static function skillsLaboratory(): array
    {
        return [
            'key' => 'period_grade',
            'type' => 'composite',
            'label' => 'Period Grade',
            'children' => [
                self::compositeNode('skills_lab', 'Skills Laboratory', 0.70, [
                    self::activityNode('skills.quiz', 'Skills Lab Quizzes', 0.40, 5),
                    self::activityNode('skills.return_demo', 'Return Demonstrations', 0.40, 5),
                    self::activityNode('skills.ocr', 'Skills OCR', 0.20, 5),
                ]),
                self::activityNode('exam', 'Major Exam', 0.30, 1),
            ],
        ];
    }

    /**
     * Build the default clinical laboratory structure.
     */
    protected static function clinicalLaboratory(): array
    {
        return [
            'key' => 'period_grade',
            'type' => 'composite',
            'label' => 'Period Grade',
            'children' => [
                self::activityNode('clinical.concept', 'Clinical Concepts', 1.00, 5),
            ],
        ];
    }

    /**
     * Build the default skills + clinical laboratory structure.
     */
    protected static function skillsClinical(): array
    {
        return [
            'key' => 'period_grade',
            'type' => 'composite',
            'label' => 'Period Grade',
            'children' => [
                self::compositeNode('skills_cluster', 'Skills Laboratory', 0.50, [
                    self::compositeNode('skills_lab', 'Skills Lab Breakdown', 0.70, [
                        self::activityNode('skills.quiz', 'Skills Lab Quizzes', 0.40, 5),
                        self::activityNode('skills.return_demo', 'Return Demonstrations', 0.40, 5),
                        self::activityNode('skills.ocr', 'Skills OCR', 0.20, 5),
                    ]),
                    self::activityNode('exam', 'Major Exam', 0.30, 1),
                ]),
                self::activityNode('clinical.concept', 'Clinical Concepts', 0.50, 5),
            ],
        ];
    }

    /**
     * Construct an activity node definition.
     */
    protected static function activityNode(string $key, string $label, float $weight, ?int $maxAssessments = null): array
    {
        return [
            'key' => $key,
            'type' => 'activity',
            'label' => $label,
            'weight' => round($weight, 4),
            'activity_type' => $key,
            'max_assessments' => $maxAssessments,
        ];
    }

    /**
     * Construct a composite node definition.
     */
    protected static function compositeNode(string $key, string $label, float $weight, array $children): array
    {
        return [
            'key' => $key,
            'type' => 'composite',
            'label' => $label,
            'weight' => round($weight, 4),
            'children' => collect($children)
                ->map(fn ($child, $index) => self::normalizeNode($child, $key, $index))
                ->all(),
        ];
    }

    /**
     * Walk the structure tree and invoke the callback for each node.
     */
    protected static function walk(array $node, callable $callback, array $path = []): void
    {
        $callback($node, $path);
        foreach ($node['children'] ?? [] as $child) {
            self::walk($child, $callback, array_merge($path, [$node['label'] ?? $node['key'] ?? 'component']));
        }
    }

    protected static function normalizeNode(array $node, string $parentKey, int $index): array
    {
        $normalized = self::clone($node);
        $normalized['key'] = $normalized['key'] ?? ($parentKey . '_' . $index);
        $normalized['type'] = self::normalizeType($normalized['type'] ?? 'activity');
        $normalized['label'] = $normalized['label'] ?? self::formatLabel($normalized['key']);
        $normalized['weight'] = isset($normalized['weight'])
            ? round((float) $normalized['weight'], 6)
            : 0.0;

        if ($normalized['type'] === 'composite') {
            $normalized['children'] = collect($normalized['children'] ?? [])
                ->map(fn ($child, $childIndex) => self::normalizeNode($child, $normalized['key'], $childIndex))
                ->values()
                ->all();
        } else {
            $normalized['activity_type'] = mb_strtolower($normalized['activity_type'] ?? $normalized['key']);
            if (! Arr::has($normalized, 'max_assessments')) {
                $baseType = self::baseActivityType($normalized['activity_type']);
                $normalized['max_assessments'] = match ($baseType) {
                    'exam' => 1,
                    'quiz', 'ocr', 'return_demo', 'concept' => 5,
                    default => null,
                };
            }
        }

        return $normalized;
    }

    protected static function normalizeType(string $type): string
    {
        $type = mb_strtolower($type);
        return in_array($type, ['composite', 'activity'], true) ? $type : 'activity';
    }

    protected static function gatherLeafWeights(array $node, float $parentWeight = 1.0): array
    {
        $results = [];
        foreach ($node['children'] ?? [] as $child) {
            $weight = (float) ($child['weight'] ?? 0);
            $composedWeight = $parentWeight * $weight;
            if (($child['type'] ?? null) === 'composite') {
                $results = array_merge($results, self::gatherLeafWeights($child, $composedWeight));
            } elseif (($child['type'] ?? null) === 'activity') {
                $results[] = [
                    'activity_type' => mb_strtolower($child['activity_type'] ?? $child['key']),
                    'weight' => $composedWeight,
                    'relative_weight' => $weight,
                    'label' => $child['label'] ?? self::formatLabel($child['activity_type'] ?? $child['key']),
                    'max_assessments' => Arr::get($child, 'max_assessments'),
                ];
            }
        }

        return $results;
    }

    protected static function convertPercentWeights(array &$node, bool $isRoot = false): void
    {
        if (! $isRoot) {
            $weight = Arr::get($node, 'weight_percent', Arr::get($node, 'weight', 0));
            $node['weight'] = round(((float) $weight) / 100, 6);
        }

        unset($node['weight_percent']);

        if (! empty($node['children'])) {
            foreach ($node['children'] as $index => $child) {
                self::convertPercentWeights($node['children'][$index], false);
            }
        }
    }

    protected static function convertDecimalWeightsToPercent(array &$node, bool $isRoot = false): void
    {
        if (! $isRoot) {
            $weight = Arr::get($node, 'weight', 0);
            $node['weight_percent'] = round(((float) $weight) * 100, 4);
        }

        if (! empty($node['children'])) {
            foreach ($node['children'] as $index => $child) {
                self::convertDecimalWeightsToPercent($node['children'][$index], false);
            }
        }
    }

    /**
     * Get all structure definitions including custom templates from the database.
     */
    public static function getAllStructureDefinitions(): array
    {
        $definitions = self::STRUCTURE_DEFINITIONS;

        // Load custom templates from database
        $customTemplates = \App\Models\StructureTemplate::where('is_deleted', false)
            ->get();

        foreach ($customTemplates as $template) {
            $definitions[$template->template_key] = [
                'id' => $template->id,
                'template_key' => $template->template_key,
                'label' => $template->label,
                'description' => $template->description ?? '',
                'is_custom' => true,
                'is_system_default' => (bool) $template->is_system_default,
                'structure_config' => $template->structure_config,
            ];
        }

        return $definitions;
    }
}
