<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    return $this->belongsTo(User::class, 'instructor_id');
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




}
