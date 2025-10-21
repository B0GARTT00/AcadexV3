<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @property int $id
 * @property string|null $name
 * @property string|null $label
 * @property int|null $department_id
 * @property int|null $course_id
 * @property int|null $subject_id
 * @property string|null $semester
 * @property int|null $academic_period_id
 * @property string $scope_level
 * @property bool $is_department_fallback
 * @property float $base_score
 * @property float $scale_multiplier
 * @property float $passing_grade
 * @property-read array $weight_map
 * @property-read Department|null $department
 * @property-read Course|null $course
 * @property-read Subject|null $subject
 */
class GradesFormula extends Model
{
    use HasFactory;

    protected $table = 'grades_formula';

    protected $fillable = [
        'name',
        'label',
        'department_id',
        'course_id',
        'subject_id',
    'semester',
    'academic_period_id',
        'scope_level',
        'is_department_fallback',
        'base_score',
        'scale_multiplier',
        'passing_grade',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function weights()
    {
        return $this->hasMany(GradesFormulaWeight::class);
    }

    public function getWeightMapAttribute(): array
    {
        $weights = $this->relationLoaded('weights')
            ? $this->weights
            : $this->weights()->get();

        $mapped = $weights
            ->pluck('weight', 'activity_type')
            ->map(fn ($value) => (float) $value)
            ->toArray();

        if (empty($mapped)) {
            $mapped = [
                'quiz' => 0.40,
                'ocr' => 0.20,
                'exam' => 0.40,
            ];
        }

        return $mapped;
    }

    protected $casts = [
        'base_score' => 'float',
        'scale_multiplier' => 'float',
        'passing_grade' => 'float',
        'is_department_fallback' => 'bool',
    ];

    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope_level) {
            'subject' => 'Subject Override',
            'course' => 'Course Override',
            'department' => 'Department Default',
            default => 'ASBME Default',
        };
    }
}
