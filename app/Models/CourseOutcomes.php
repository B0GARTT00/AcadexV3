<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseOutcomes extends Model
    // For future use if needed
{
    use HasFactory;

    protected $table = 'course_outcomes';

    protected $fillable = [
        'subject_id',
        'academic_period_id',
        'co_code',
        'co_identifier',
        'description',
        'created_by',
        'updated_by',
        'is_deleted',
    ];

    /**
     * Relationships
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'course_outcome_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
