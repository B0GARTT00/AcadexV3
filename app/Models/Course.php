<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $course_code
 * @property string $course_description
 * @property int|null $department_id
 * @property bool $is_deleted
 * @property-read \Illuminate\Database\Eloquent\Collection|Subject[] $subjects
 * @property-read int|null $subjects_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Student[] $students
 * @property-read Department|null $department
 */
class Course extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'course_code',
        'course_description',
        'name',
        'department_id',
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

    // 🔗 Department (Many Courses belong to one Department)
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // 🔗 Students (One Course has many Students)
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class)
            ->where('is_deleted', false);
    }

    /**
     * Accessors
     */

    // Combined label accessor (ex: "BSIT - Bachelor of Science in Information Technology")
    public function getCourseLabelAttribute()
    {
        return "{$this->course_code} - {$this->course_description}";
    }
}
