<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $instructor_id
 * @property int $notified_user_id
 * @property int $subject_id
 * @property string $term
 * @property int $students_graded
 * @property string $message
 * @property bool $is_read
 * @property \Carbon\Carbon|null $read_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $instructor
 * @property-read User $notifiedUser
 * @property-read Subject $subject
 */
class GradeNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'notified_user_id',
        'subject_id',
        'term',
        'students_graded',
        'message',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'students_graded' => 'integer',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function notifiedUser()
    {
        return $this->belongsTo(User::class, 'notified_user_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('notified_user_id', $userId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
