<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_code', 'department_description', 'is_deleted', 'created_by', 'updated_by'
    ];

    public function courses()
    {
        return $this->hasMany(Course::class, 'department_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'department_id');
    }
}
