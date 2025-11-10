<?php

namespace App\Services;

use App\Models\GradeNotification;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GradeNotificationService
{
    /**
     * Create notifications for chairpersons and GE coordinator when instructor saves grades.
     *
     * @param int $subjectId
     * @param string $term
     * @param int $studentsGraded
     * @return void
     */
    public static function notifyGradeSaved(int $subjectId, string $term, int $studentsGraded): void
    {
        $subject = Subject::with(['course.department'])->find($subjectId);
        if (!$subject) {
            return;
        }

        $instructor = Auth::user();
        if (!$instructor) {
            return;
        }

        $instructorName = trim($instructor->first_name . ' ' . $instructor->last_name);
        $subjectName = $subject->subject_code . ' - ' . $subject->subject_description;
        $termLabel = ucfirst($term);

        $message = "{$instructorName} has saved grades for {$studentsGraded} student(s) in {$subjectName} ({$termLabel})";

        $notifiedUsers = [];

        // Notify chairperson of the course
        if ($subject->course_id) {
            $chairperson = User::where('role', 1) // Chairperson role
                ->where('course_id', $subject->course_id)
                ->where('is_active', true)
                ->first();

            if ($chairperson && $chairperson->id !== $instructor->id) {
                $notifiedUsers[] = $chairperson->id;
                self::createNotification(
                    $instructor->id,
                    $chairperson->id,
                    $subjectId,
                    $term,
                    $studentsGraded,
                    $message
                );
            }
        }

        // Notify GE Coordinator if this is a GE subject
        if ($subject->department_id == 1) { // GE department
            $geCoordinators = User::where('role', 4) // GE Coordinator role
                ->where('is_active', true)
                ->get();

            foreach ($geCoordinators as $geCoordinator) {
                if ($geCoordinator->id !== $instructor->id && !in_array($geCoordinator->id, $notifiedUsers)) {
                    self::createNotification(
                        $instructor->id,
                        $geCoordinator->id,
                        $subjectId,
                        $term,
                        $studentsGraded,
                        $message
                    );
                }
            }
        }
    }

    /**
     * Create a grade notification record.
     *
     * @param int $instructorId
     * @param int $notifiedUserId
     * @param int $subjectId
     * @param string $term
     * @param int $studentsGraded
     * @param string $message
     * @return void
     */
    private static function createNotification(
        int $instructorId,
        int $notifiedUserId,
        int $subjectId,
        string $term,
        int $studentsGraded,
        string $message
    ): void {
        GradeNotification::create([
            'instructor_id' => $instructorId,
            'notified_user_id' => $notifiedUserId,
            'subject_id' => $subjectId,
            'term' => $term,
            'students_graded' => $studentsGraded,
            'message' => $message,
            'is_read' => false,
        ]);
    }

    /**
     * Get unread notifications for a user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUnreadNotifications(int $userId)
    {
        return GradeNotification::with(['instructor', 'subject'])
            ->forUser($userId)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all notifications for a user (with pagination).
     *
     * @param int $userId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getAllNotifications(int $userId, int $perPage = 20)
    {
        return GradeNotification::with(['instructor', 'subject'])
            ->forUser($userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Mark notification as read.
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public static function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = GradeNotification::where('id', $notificationId)
            ->where('notified_user_id', $userId)
            ->first();

        if ($notification && !$notification->is_read) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param int $userId
     * @return int
     */
    public static function markAllAsRead(int $userId): int
    {
        return GradeNotification::forUser($userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get unread notification count for a user.
     *
     * @param int $userId
     * @return int
     */
    public static function getUnreadCount(int $userId): int
    {
        return GradeNotification::forUser($userId)->unread()->count();
    }
}
