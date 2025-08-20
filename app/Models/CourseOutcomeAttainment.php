<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseOutcomeAttainment extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'course_outcome_id',
        'attainment_level',
        'target_attainment',
        'academic_period_id',
        'term',
        'score',
        'max',
        'semester_total',
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function courseOutcome(): BelongsTo
    {
        return $this->belongsTo(CourseOutcomes::class, 'course_outcome_id');
    }
    
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
    
    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }
}
