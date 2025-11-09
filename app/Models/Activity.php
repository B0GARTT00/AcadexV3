<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $subject_id
 * @property string $term
 * @property string $type
 * @property string $title
 * @property int $number_of_items
 * @property int|null $course_outcome_id
 * @property bool $is_deleted
 * @property-read Subject $subject
 */
class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id', 'term', 'type', 'title', 'number_of_items',
        'course_outcome_id',
        'is_deleted', 'created_by', 'updated_by'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function courseOutcome()
    {
        return $this->belongsTo(CourseOutcomes::class, 'course_outcome_id');
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }
}
