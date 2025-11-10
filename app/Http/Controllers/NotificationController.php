<?php

namespace App\Http\Controllers;

use App\Services\GradeNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display all notifications for the authenticated user.
     */
    public function index()
    {
        $notifications = GradeNotificationService::getAllNotifications(Auth::id(), 20);
        $unreadCount = GradeNotificationService::getUnreadCount(Auth::id());

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Get unread notifications count (for AJAX polling).
     */
    public function getUnreadCount()
    {
        $count = GradeNotificationService::getUnreadCount(Auth::id());
        return response()->json(['count' => $count]);
    }

    /**
     * Get unread notifications (for dropdown).
     */
    public function getUnread()
    {
        $notifications = GradeNotificationService::getUnreadNotifications(Auth::id());
        
        return response()->json([
            'notifications' => $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'message' => $notification->message,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'instructor_name' => $notification->instructor->first_name . ' ' . $notification->instructor->last_name,
                    'subject_code' => $notification->subject->subject_code,
                    'term' => ucfirst($notification->term),
                ];
            }),
            'count' => $notifications->count(),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead($id)
    {
        $success = GradeNotificationService::markAsRead($id, Auth::id());

        if (request()->expectsJson()) {
            return response()->json(['success' => $success]);
        }

        return redirect()->back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $count = GradeNotificationService::markAllAsRead(Auth::id());

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'count' => $count]);
        }

        return redirect()->back()->with('success', "{$count} notifications marked as read");
    }
}
