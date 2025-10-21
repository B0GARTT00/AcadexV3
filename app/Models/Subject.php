<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $subject_code
 * @property string|null $subject_description
 * @property int|null $units
 * @property int|null $academic_period_id
 * @property int|null $department_id
 * @property int|null $course_id
 * @property int|null $instructor_id
 * @property bool $is_deleted
 * @property int|null $year_level
 * @property-read Course|null $course
 * @property-read Department|null $department
 * @property-read User|null $instructor
 * @property-read \Illuminate\Database\Eloquent\Collection|Student[] $students
 */
class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_code', 'subject_description', 'units', 'is_universal', 
        'academic_period_id', 'department_id', 'course_id', 'instructor_id',
        'is_deleted', 'created_by', 'updated_by', 'year_level'
    ];

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }
    
    public function instructor()
    {
        // Keep this for backward compatibility
        return $this->belongsTo(User::class, 'instructor_id');
    }
    
    public function instructors()
    {
        return $this->belongsToMany(User::class, 'instructor_subject', 'subject_id', 'instructor_id')
            ->withTimestamps();
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function students()
    {   
        return $this->belongsToMany(Student::class, 'student_subjects', 'subject_id', 'student_id')
            ->withTimestamps()
            ->wherePivot('is_deleted', false)
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    public function courseOutcomes()
    {
        return $this->hasMany(\App\Models\CourseOutcomes::class, 'subject_id')->where('is_deleted', false);
    }




}
