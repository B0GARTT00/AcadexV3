<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\GradesFormula;
use App\Models\Subject;
use App\Support\Grades\FormulaStructure;

class GradesFormulaService
{
    /**
     * Cached formula settings keyed by subject/course/department combination.
     */
    protected static array $cache = [];

    /**
     * Cached academic periods to avoid repeated queries.
     */
    protected static array $periodCache = [];

    /**
     * Resolve the most appropriate grades formula for the provided context.
     */
    public static function getSettings(
        ?int $subjectId = null,
        ?int $courseId = null,
        ?int $departmentId = null,
        ?string $semester = null,
        ?int $academicPeriodId = null
    ): array
    {
        $academicPeriodId = $academicPeriodId ?? session('active_academic_period_id');
        $academicPeriod = self::getAcademicPeriod($academicPeriodId);
        $semester = $semester ?? $academicPeriod?->semester;
        $cacheKey = implode(':', [
            $subjectId !== null ? "subject-{$subjectId}" : 'subject-null',
            $courseId !== null ? "course-{$courseId}" : 'course-null',
            $departmentId !== null ? "department-{$departmentId}" : 'department-null',
            $semester !== null ? "semester-{$semester}" : 'semester-null',
            $academicPeriodId !== null ? "period-{$academicPeriodId}" : 'period-null',
        ]);

        if (! array_key_exists($cacheKey, self::$cache)) {
            $resolved = self::resolveFormula($subjectId, $courseId, $departmentId, $semester, $academicPeriodId);

            $structure = $resolved['formula']->structure_config;
            if (! is_array($structure) || empty($structure)) {
                $structure = FormulaStructure::default($resolved['formula']->structure_type ?? 'lecture_only');
            } else {
                $structure = FormulaStructure::normalize($structure);
            }

            $flattened = FormulaStructure::flattenWeights($structure);
            $weights = collect($flattened)
                ->pluck('weight', 'activity_type')
                ->map(fn ($weight) => (float) $weight)
                ->toArray();
            $relativeWeights = collect($flattened)
                ->pluck('relative_weight', 'activity_type')
                ->map(fn ($weight) => (float) $weight)
                ->toArray();

            if (empty($weights)) {
                $fallbackStructure = FormulaStructure::default('lecture_only');
                $structure = $fallbackStructure;
                $flattened = FormulaStructure::flattenWeights($fallbackStructure);
                $weights = collect($flattened)
                    ->pluck('weight', 'activity_type')
                    ->map(fn ($weight) => (float) $weight)
                    ->toArray();
                $relativeWeights = collect($flattened)
                    ->pluck('relative_weight', 'activity_type')
                    ->map(fn ($weight) => (float) $weight)
                    ->toArray();
            }

            $meta = $resolved['meta'];
            $meta['weights'] = collect($flattened)
                ->mapWithKeys(fn ($entry) => [
                    $entry['activity_type'] => round($entry['weight'] * 100, 2),
                ])
                ->all();
            $meta['relative_weights'] = collect($flattened)
                ->mapWithKeys(fn ($entry) => [
                    $entry['activity_type'] => round($entry['relative_weight'] * 100, 2),
                ])
                ->all();
            $meta['weight_details'] = collect($flattened)
                ->map(fn ($entry) => [
                    'activity_type' => $entry['activity_type'],
                    'label' => $entry['label'] ?? FormulaStructure::formatLabel($entry['activity_type']),
                    'weight_percent' => round($entry['weight'] * 100, 2),
                    'relative_weight_percent' => round(($entry['relative_weight'] ?? $entry['weight']) * 100, 2),
                    'max_assessments' => $entry['max_assessments'] ?? null,
                ])
                ->values()
                ->all();
            $meta['structure'] = $structure;
            $meta['structure_type'] = $resolved['formula']->structure_type ?? 'lecture_only';
            $meta['activity_labels'] = FormulaStructure::activityLabelMap($structure);
            $meta['max_assessments'] = FormulaStructure::leafMaxAssessmentMap($structure);
            $meta['base_score'] = (float) $resolved['formula']->base_score;
            $meta['scale_multiplier'] = (float) $resolved['formula']->scale_multiplier;
            $meta['passing_grade'] = (float) $resolved['formula']->passing_grade;
            $meta['academic_period_id'] = $resolved['formula']->academic_period_id;

            self::$cache[$cacheKey] = [
                'id' => $resolved['formula']->id,
                'base_score' => (float) $resolved['formula']->base_score,
                'scale_multiplier' => (float) $resolved['formula']->scale_multiplier,
                'passing_grade' => (float) $resolved['formula']->passing_grade,
                'weights' => $weights,
                'relative_weights' => $relativeWeights,
                'structure' => $structure,
                'meta' => $meta,
            ];
        } elseif (! isset(self::$cache[$cacheKey]['relative_weights'])
            || ! isset(self::$cache[$cacheKey]['meta']['relative_weights'])
            || ! isset(self::$cache[$cacheKey]['meta']['weight_details'][0]['relative_weight_percent'])) {
            unset(self::$cache[$cacheKey]);
            return self::getSettings($subjectId, $courseId, $departmentId, $semester, $academicPeriodId);
        }

        return self::$cache[$cacheKey];
    }

    /**
     * Return the ordered list of activity types defined in the resolved formula.
     */
    public static function getActivityTypes(
        ?int $subjectId = null,
        ?int $courseId = null,
        ?int $departmentId = null,
        ?string $semester = null,
        ?int $academicPeriodId = null
    ): array
    {
        $settings = self::getSettings($subjectId, $courseId, $departmentId, $semester, $academicPeriodId);

        if (isset($settings['structure'])) {
            return FormulaStructure::leafActivityTypes($settings['structure']);
        }

        return array_keys($settings['weights']);
    }

    /**
     * Flush the in-memory cache (useful for tests or after admin updates).
     */
    public static function flushCache(): void
    {
        self::$cache = [];
    }

    /**
     * Determine the effective formula hierarchy.
     */
    protected static function resolveFormula(
        ?int $subjectId,
        ?int $courseId,
        ?int $departmentId,
        ?string $semester = null,
        ?int $academicPeriodId = null
    ): array
    {
        $resolvedSubject = null;
        $resolvedCourse = null;
        $resolvedDepartment = null;

        $formula = null;
        $resolvedScope = 'global';

        if ($subjectId) {
            $query = GradesFormula::with('weights', 'subject', 'course', 'department')
                ->where('subject_id', $subjectId)
                ->where('scope_level', 'subject');
            $formula = self::resolveScopedFormula($query, $academicPeriodId, $semester);

            $resolvedSubject = Subject::with(['course', 'department'])->find($subjectId);

            if ($resolvedSubject) {
                $resolvedCourse = $resolvedSubject->course;
                $resolvedDepartment = $resolvedSubject->department;

                $courseId = $courseId ?? $resolvedSubject->course_id;
                $departmentId = $departmentId ?? $resolvedSubject->department_id;
            }

            if ($formula) {
                $resolvedScope = 'subject';
            }
        }

        if (! $formula && $courseId) {
            $q = GradesFormula::with('weights', 'course', 'department')
                ->where('course_id', $courseId)
                ->where('scope_level', 'course');
            $formula = self::resolveScopedFormula($q, $academicPeriodId, $semester);

            if (! $resolvedCourse) {
                $resolvedCourse = Course::with('department')->find($courseId);
            }

            if (! $resolvedDepartment) {
                $resolvedDepartment = $resolvedCourse?->department;
            }

            if (! $departmentId && $resolvedCourse) {
                $departmentId = $resolvedCourse->department_id;
            }

            if ($formula) {
                $resolvedScope = 'course';
            }
        }

        if (! $formula && $departmentId) {
            $base = GradesFormula::with('weights', 'department')
                ->where('department_id', $departmentId)
                ->where('scope_level', 'department')
                ->orderByDesc('is_department_fallback');
            $formula = self::resolveScopedFormula($base, $academicPeriodId, $semester);

            if (! $resolvedDepartment) {
                $resolvedDepartment = $formula?->department;
            }

            if ($formula) {
                $resolvedScope = 'department';
            }
        }

        if (! $formula) {
            $formula = self::getAsbmeDefault();
            $resolvedScope = 'global';
        }

        $formula->loadMissing(['department', 'course', 'subject']);

        $meta = [
            'scope' => $resolvedScope,
            'label' => $formula->label ?? 'ASBME Default',
            'source_formula_id' => $formula->id,
            'department' => $formula->department?->department_description ?? $resolvedDepartment?->department_description,
            'course' => $formula->course?->course_description ?? $resolvedCourse?->course_description,
            'subject' => $formula->subject?->subject_description ?? $resolvedSubject?->subject_description,
        ];

        return [
            'formula' => $formula,
            'meta' => $meta,
        ];
    }

    protected static function resolveScopedFormula($baseQuery, ?int $academicPeriodId, ?string $semester)
    {
        $ordered = $baseQuery
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');

        if ($academicPeriodId !== null) {
            $periodQuery = (clone $ordered)->where('academic_period_id', $academicPeriodId);
            if ($semester !== null) {
                $formula = (clone $periodQuery)->where('semester', $semester)->first();
                if ($formula) {
                    return $formula;
                }

                $formula = (clone $periodQuery)->whereNull('semester')->first();
                if ($formula) {
                    return $formula;
                }
            } else {
                $formula = $periodQuery->first();
                if ($formula) {
                    return $formula;
                }
            }
        }

        if ($semester !== null) {
            $formula = (clone $ordered)->where('semester', $semester)->first();
            if ($formula) {
                return $formula;
            }
        }

        $formula = (clone $ordered)->whereNull('semester')->first();
        if ($formula) {
            return $formula;
        }

        return $ordered->first();
    }

    protected static function getAcademicPeriod(?int $academicPeriodId): ?\App\Models\AcademicPeriod
    {
        if (! $academicPeriodId) {
            return null;
        }

        if (! array_key_exists($academicPeriodId, self::$periodCache)) {
            self::$periodCache[$academicPeriodId] = AcademicPeriod::find($academicPeriodId);
        }

        return self::$periodCache[$academicPeriodId];
    }

    protected static function getAsbmeDefault(): GradesFormula
    {
        $formula = GradesFormula::with('weights')
            ->where('scope_level', 'global')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();

        if (! $formula) {
            $formula = GradesFormula::with('weights')
                ->whereNull('department_id')
                ->whereNull('course_id')
                ->whereNull('subject_id')
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->first();
        }

        if (! $formula) {
            $formula = new GradesFormula([
                'base_score' => 40,
                'scale_multiplier' => 60,
                'passing_grade' => 75,
                'label' => 'ASBME Default',
                'scope_level' => 'global',
            ]);
            $formula->setRelation('weights', collect());
        }

        return $formula;
    }
}
