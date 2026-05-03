import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Notification Bell Component with WebSocket support
 * Displays unread count and dropdown list of notifications
 * Listens to real-time events via Laravel Reverb/Pusher
 */
export function initNotificationBell() {
    const bellEl = document.getElementById('notification-bell');
    
    if (!bellEl) return;

    // Initialize state
    let unreadCount = parseInt(bellEl.dataset.unreadCount || 0);
    let notifications = [];
    let echo = null;

    // Update UI
    function updateUI() {
        const countBadge = bellEl.querySelector('.notification-count');
        const dropdownList = document.getElementById('notification-dropdown-list');
        
        // Update badge
        if (countBadge) {
            countBadge.textContent = unreadCount;
            countBadge.style.display = unreadCount > 0 ? 'block' : 'none';
        }

        // Update dropdown list
        if (dropdownList) {
            dropdownList.innerHTML = renderNotifications(notifications);
        }
    }

    // Render notification items
    function renderNotifications(notifs) {
        if (notifs.length === 0) {
            return '<div class="p-4 text-center text-gray-500">No notifications</div>';
        }

        return notifs.map(notif => `
            <a href="${notif.url || '#'}" class="block p-3 hover:bg-gray-100 border-b ${notif.read ? '' : 'bg-blue-50'}">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <span class="fas fa-${getIconForType(notif.type)} text-${getColorForType(notif.type)}"></span>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900">${notif.title}</p>
                        <p class="text-xs text-gray-500">${notif.message}</p>
                        <p class="text-xs text-gray-400 mt-1">${formatTime(notif.created_at)}</p>
                    </div>
                </div>
            </a>
        `).join('');
    }

    // Get icon based on notification type
    function getIconForType(type) {
        const icons = {
            'complaint': 'exclamation-circle',
            'work_order': 'tools',
            'sla_breach': 'exclamation-triangle',
            'asset_alert': 'cog',
            'schedule_reminder': 'calendar'
        };
        return icons[type] || 'info-circle';
    }

    // Get color based on notification type
    function getColorForType(type) {
        const colors = {
            'complaint': 'red',
            'work_order': 'blue',
            'sla_breach': 'red',
            'asset_alert': 'orange',
            'schedule_reminder': 'green'
        };
        return colors[type] || 'gray';
    }

    // Format timestamp
    function formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        
        const diffHours = Math.floor(diffMins / 60);
        if (diffHours < 24) return `${diffHours}h ago`;
        
        return date.toLocaleDateString();
    }

    // Mark notification as read
    async function markAsRead(notificationId) {
        try {
            await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            // Update local state
            const notif = notifications.find(n => n.id === notificationId);
            if (notif) {
                notif.read = true;
                unreadCount = Math.max(0, unreadCount - 1);
                updateUI();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    // Mark all as read
    async function markAllAsRead() {
        try {
            await fetch('/api/notifications/read-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            // Update local state
            notifications.forEach(n => n.read = true);
            unreadCount = 0;
            updateUI();
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    // Initialize WebSocket connection
    function initWebSocket() {
        const userId = bellEl.dataset.userId;
        if (!userId) return;

        echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY || window.reverbKey,
            wsHost: import.meta.env.VITE_REVERB_HOST || window.reverbHost,
            wsPort: import.meta.env.VITE_REVERB_PORT || 80,
            wssPort: import.meta.env.VITE_REVERB_PORT || 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }
        });

        // Listen for new notifications
        echo.private(`App.Models.User.${userId}`)
            .notification((notification) => {
                // Add to local list
                notifications.unshift({
                    id: notification.id,
                    type: notification.type,
                    title: notification.title,
                    message: notification.message,
                    url: notification.url,
                    created_at: new Date().toISOString(),
                    read: false
                });
                
                unreadCount++;
                updateUI();
                
                // Show browser notification if supported
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification(notification.title, {
                        body: notification.message,
                        icon: '/favicon.ico'
                    });
                }
            });
    }

    // Load initial notifications
    async function loadNotifications() {
        try {
            const response = await fetch('/api/notifications', {
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                notifications = data.notifications || [];
                unreadCount = data.unread_count || 0;
                updateUI();
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    // Event listeners
    bellEl.addEventListener('click', () => {
        const dropdown = document.getElementById('notification-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
        }
    });

    // Mark all button
    const markAllBtn = document.getElementById('mark-all-read');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', (e) => {
            e.preventDefault();
            markAllAsRead();
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!bellEl.contains(e.target)) {
            const dropdown = document.getElementById('notification-dropdown');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }
        }
    });

    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Initialize
    loadNotifications();
    initWebSocket();
    updateUI();
}

// Auto-initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotificationBell);
} else {
    initNotificationBell();
}
