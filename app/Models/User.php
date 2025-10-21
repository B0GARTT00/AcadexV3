<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string $email
 * @property int $role
 * @property int|null $department_id
 * @property int|null $course_id
 * @property string $password
 * @property bool $is_active
 * @property bool $is_universal
 * @property-read Department|null $department
 * @property-read Course|null $course
 * @property-read string $full_name
 * @property-read string $name
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'role',
        'is_active',
        'can_teach_ge',
        'department_id',
        'course_id',
        'is_universal',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => 'integer',
        'is_active' => 'boolean',
        'is_universal' => 'boolean',
    ];

    /**
     * Accessor to get the full name of the user.
     * Example: Juan Pedro Santos
     */
    public function getFullNameAttribute(): string
    {
        $names = [$this->first_name];
        if ($this->middle_name) {
            $names[] = $this->middle_name;
        }
        $names[] = $this->last_name;

        return implode(' ', $names);
    }

    /**
     * Accessor to get a virtual `name` attribute for compatibility.
     * Example: Juan Pedro Santos
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Relationships
     */

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'instructor_subject', 'instructor_id', 'subject_id')
            ->withTimestamps();
    }

    public function createdStudents()
    {
        return $this->hasMany(Student::class, 'created_by');
    }

    public function createdSubjects()
    {
        return $this->hasMany(Subject::class, 'created_by');
    }

    public function createdActivities()
    {
        return $this->hasMany(Activity::class, 'created_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Check if the user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 3;
    }

    /**
     * Check if the user is a Chairperson
     */
    public function isChairperson(): bool
    {
        return $this->role === 1;
    }

    /**
     * Check if the user is a GE Coordinator
     */
    public function isGECoordinator(): bool
    {
        return $this->role === 4;
    }

    /**
     * Check if the user is a VPAA
     */
    public function isVPAA(): bool
    {
        return $this->role === 5;
    }
}
