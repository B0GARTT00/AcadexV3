<header class="px-4 py-3 shadow-sm d-flex justify-content-between align-items-center header-stable" style="background-color: var(--dark-green); color: white; height: 70px; position: sticky; top: 0; z-index: 1000; will-change: transform;">
    <!-- Left: Current Academic Period -->
    <div class="d-flex align-items-center" style="width: 400px; flex-shrink: 0;">
        <h1 class="mb-0 d-flex align-items-center" style="line-height: 1; width: 100%; height: 34px; font-size: 1rem; font-weight: 600;">
            <i class="bi bi-calendar-event me-2" style="font-size: 1.125rem; display: inline-block; width: 20px; height: 20px; text-align: center; flex-shrink: 0; line-height: 1;"></i>
            @php
                $activePeriod = \App\Models\AcademicPeriod::find(session('active_academic_period_id'));
            @endphp
            @if($activePeriod)
                @php
                    $semesterLabel = '';
                    $academicYear = $activePeriod->academic_year;
        
                    switch ($activePeriod->semester) {
                        case '1st':
                            $semesterLabel = 'First Semester';
                            break;
                        case '2nd':
                            $semesterLabel = 'Second Semester';
                            break;
                        case 'Summer':
                            $semesterLabel = 'Summer';
                            break;
                        default:
                            $semesterLabel = 'Unknown Semester';
                            break;
                    }
        
                    if ($activePeriod->semester != 'Summer') {
                        list($startYear, $endYear) = explode('-', $academicYear);
                    }
                @endphp
                
                <span class="badge bg-success bg-opacity-25 px-3 py-2 rounded-pill" style="white-space: nowrap; font-size: 0.8125rem; font-weight: 500; line-height: 1; height: 32px; display: inline-flex; align-items: center; min-width: 290px; justify-content: center; letter-spacing: -0.01em;">
                    @if($activePeriod->semester != 'Summer')
                        {{ $semesterLabel }} - AY {{ $startYear }} - {{ $endYear }}
                    @else
                        {{ $semesterLabel }} - AY {{ $academicYear }}
                    @endif
                </span>
            @else
                <span class="badge bg-success bg-opacity-25 px-3 py-2 rounded-pill" style="white-space: nowrap; font-size: 0.8125rem; font-weight: 500; line-height: 1; height: 32px; display: inline-flex; align-items: center; min-width: 290px; justify-content: center; letter-spacing: -0.01em;">Dashboard</span>
            @endif
        </h1>    
    </div>

    <!-- Right: Notifications + Profile Dropdown -->
    <div class="d-flex align-items-center gap-2">
        @php
            $user = Auth::user();
            $showNotifications = in_array($user->role, [1, 4]); // Chairperson=1, GE Coordinator=4
            $nameParts = explode(' ', Auth::user()->name);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[count($nameParts) - 1] ?? '';
            $displayName = $firstName . ' ' . $lastName;
        @endphp
        
        <!-- Notification Bell -->
        @if($showNotifications)
            <div class="position-relative" x-data="notificationBell" x-init="init()" x-cloak style="min-width: 50px;">
                <button @click="toggleDropdown" type="button" class="btn btn-link text-white position-relative p-2" style="min-width: 50px; min-height: 50px;">
                    <i class="bi bi-bell fs-4" style="display: inline-block; line-height: 1;"></i>
                    <span x-show="unreadCount > 0" 
                          x-text="unreadCount"
                          class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                          style="font-size: 0.65rem; min-width: 20px; line-height: 1.4;">
                    </span>
                </button>
                
                <!-- Notification Dropdown -->
                <div x-show="showDropdown" 
                     @click.away="showDropdown = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     x-cloak
                     class="position-absolute end-0 mt-2 bg-white rounded-3 shadow-lg"
                     style="width: 380px; max-height: 500px; overflow-y: auto; z-index: 1050;">
                    
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark fw-semibold">Notifications</h6>
                        <button @click="markAllRead" 
                                x-show="unreadCount > 0"
                                class="btn btn-sm btn-link text-primary p-0">
                            Mark all read
                        </button>
                    </div>
                    
                    <div x-show="notifications.length === 0" class="p-4 text-center text-muted">
                        <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                        <small>No new notifications</small>
                    </div>
                    
                    <template x-for="notification in notifications" :key="notification.id">
                        <div class="notification-item p-3 border-bottom" 
                             @click="markRead(notification.id)"
                             style="cursor: pointer;">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <p class="mb-1 text-dark small" x-text="notification.message"></p>
                                    <div class="d-flex gap-2 text-muted" style="font-size: 0.75rem;">
                                        <span><i class="bi bi-book me-1"></i><span x-text="notification.subject_code"></span></span>
                                        <span><i class="bi bi-calendar me-1"></i><span x-text="notification.term"></span></span>
                                    </div>
                                    <small class="text-muted" x-text="notification.created_at"></small>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <div class="p-2 text-center border-top">
                        <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-link text-primary">
                            View all notifications
                        </a>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Profile Dropdown -->
        <div class="dropdown" style="min-width: 200px; position: relative; z-index: 2000;">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle hover-lift" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="position-relative" style="flex-shrink: 0;">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($displayName) }}&background=259c59&color=fff"
                     alt="avatar"
                     class="rounded-circle me-2 border-2 border-success"
                     width="38"
                     height="38"
                     style="display: block;">
                <span class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-white" style="width: 10px; height: 10px;"></span>
            </div>
            <div class="d-flex flex-column ms-2" style="min-width: 100px;">
                <span class="fw-medium">{{ $displayName }}</span>
                <small class="text-success">Online</small>
            </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg" style="min-width: 280px; z-index: 2010 !important; position: absolute !important;" aria-labelledby="profileDropdown">
            <li class="px-3 py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($displayName) }}&background=259c59&color=fff"
                         alt="avatar"
                         class="rounded-circle me-3"
                         width="45"
                         height="45">
                    <div class="d-flex flex-column">
                        <span class="fw-semibold text-dark">{{ $displayName }}</span>
                        <span class="text-muted small text-truncate" style="max-width: 180px;">{{ Auth::user()->email }}</span>
                    </div>
                </div>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center py-2 px-3" href="{{ route('profile.edit') }}">
                    <i class="bi bi-person-gear me-2 text-muted"></i>
                    <span>Profile Settings</span>
                </a>
            </li>
            <li>
                <button type="button" class="dropdown-item d-flex align-items-center py-2 px-3 text-danger" data-bs-toggle="modal" data-bs-target="#signOutModal">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    <span>Sign Out</span>
                </button>
            </li>
        </ul>
    </div>
    </div>
</header>

{{-- Sign Out Confirmation Modal --}}
<div class="modal fade" id="signOutModal" tabindex="-1" aria-labelledby="signOutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="signOutModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Confirm Sign Out
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to sign out?
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        Yes, Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@if($showNotifications ?? false)
<style>
.notification-item:hover {
    background-color: #f8f9fa;
}
.notification-item:last-child {
    border-bottom: none !important;
}
/* Prevent badge from causing layout shift */
.badge.rounded-pill {
    min-width: 20px;
    text-align: center;
}
/* Smooth opacity changes only - prevent layout shift */
[x-cloak] {
    display: none !important;
}
/* Optimize Alpine transitions */
[x-transition] {
    will-change: opacity, transform;
}
</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('notificationBell', () => ({
        notifications: [],
        unreadCount: 0,
        showDropdown: false,
        pollInterval: null,
        
        init() {
            this.fetchNotifications();
            // Poll every 30 seconds
            this.pollInterval = setInterval(() => {
                this.fetchUnreadCount();
            }, 30000);
        },
        
        destroy() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
            }
        },
        
        async fetchNotifications() {
            try {
                const response = await fetch('{{ route("notifications.unread") }}');
                const data = await response.json();
                this.notifications = data.notifications;
                this.unreadCount = data.count;
            } catch (error) {
                console.error('Error fetching notifications:', error);
            }
        },
        
        async fetchUnreadCount() {
            try {
                const response = await fetch('{{ route("notifications.unread-count") }}');
                const data = await response.json();
                if (data.count !== this.unreadCount) {
                    this.fetchNotifications(); // Refresh if count changed
                }
            } catch (error) {
                console.error('Error fetching count:', error);
            }
        },
        
        toggleDropdown() {
            this.showDropdown = !this.showDropdown;
            if (this.showDropdown) {
                this.fetchNotifications();
            }
        },
        
        async markRead(notificationId) {
            try {
                const response = await fetch(`/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                
                if (response.ok) {
                    this.fetchNotifications();
                }
            } catch (error) {
                console.error('Error marking as read:', error);
            }
        },
        
        async markAllRead() {
            try {
                const response = await fetch('{{ route("notifications.read-all") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                
                if (response.ok) {
                    this.fetchNotifications();
                }
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        }
    }));
});
</script>

<style>
.notification-item:hover {
    background-color: #f8f9fa;
}
.notification-item:last-child {
    border-bottom: none !important;
}
</style>
@endif
