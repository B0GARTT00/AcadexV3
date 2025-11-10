@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-bell text-primary me-2"></i>Notifications
            </h2>
            <p class="text-muted mb-0">Grade submission notifications from instructors</p>
        </div>
        <div>
            @if($unreadCount > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-check-all me-1"></i>Mark All as Read
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            @forelse($notifications as $notification)
                <div class="notification-item p-4 border-bottom {{ !$notification->is_read ? 'bg-light' : '' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2">
                                @if(!$notification->is_read)
                                    <span class="badge bg-primary me-2">New</span>
                                @endif
                                <h6 class="mb-0 {{ !$notification->is_read ? 'fw-bold' : '' }}">
                                    {{ $notification->instructor->first_name }} {{ $notification->instructor->last_name }}
                                </h6>
                                <small class="text-muted ms-auto">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-2 {{ !$notification->is_read ? 'fw-semibold' : '' }}">{{ $notification->message }}</p>
                            <div class="d-flex gap-3 text-muted small">
                                <span><i class="bi bi-book me-1"></i>{{ $notification->subject->subject_code }}</span>
                                <span><i class="bi bi-calendar-check me-1"></i>{{ ucfirst($notification->term) }}</span>
                                <span><i class="bi bi-people me-1"></i>{{ $notification->students_graded }} students</span>
                            </div>
                        </div>
                        @if(!$notification->is_read)
                            <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="ms-3">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Mark as read">
                                    <i class="bi bi-check"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash text-muted fs-1 d-block mb-3"></i>
                    <p class="text-muted mb-0">No notifications yet</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($notifications->hasPages())
        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

<style>
.notification-item:hover {
    background-color: #f8f9fa !important;
}
.notification-item:last-child {
    border-bottom: none !important;
}
</style>
@endsection
