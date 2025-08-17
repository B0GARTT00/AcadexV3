<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseOutcomeAttainment extends Model
{
    protected $fillable = [
        'student_id',
        'term',
        'co_id',
        'score',
        'max',
        'percent',
        'semester_total',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courseOutcome()
    {
        return $this->belongsTo(CourseOutcomes::class, 'co_id');
    }
}
