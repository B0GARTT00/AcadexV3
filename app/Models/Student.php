<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property int|null $department_id
 * @property int|null $course_id
 * @property int|null $academic_period_id
 * @property int|null $year_level
 * @property bool $is_deleted
 * @property-read Course|null $course
 * @property-read Department|null $department
 */
class Student extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'department_id',
        'course_id',
        'academic_period_id',
        'year_level',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    /**
     * Relationships
     */

    // 🔗 Subjects (Many-to-Many)
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subjects', 'student_id', 'subject_id');
    }

    // 🔗 Department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // 🔗 Course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // 🔗 Academic Period
    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    // 🔗 Term Grades (One-to-Many)
    public function termGrades()
    {
        return $this->hasMany(TermGrade::class);
    }

    // 🔗 Final Grades (One-to-Many)
    public function finalGrades()
    {
        return $this->hasMany(FinalGrade::class);
    }

    // 🔗 Created By User
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 🔗 Updated By User
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scores()
{
    return $this->hasMany(\App\Models\Score::class, 'student_id');
}

    /**
     * Accessors
     */

    // Full Name (First Middle Last)
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    // Formatted Year Level (example: 1 => 1st Year)
    public function getFormattedYearLevelAttribute()
    {
        return match ($this->year_level) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
            default => 'N/A',
        };
    }

    public function subject()
{
    return $this->belongsTo(Subject::class);
}

}
