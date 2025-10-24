// Simple notification system
document.addEventListener('DOMContentLoaded', function() {
    const bell = document.getElementById('notificationBell');
    const dropdown = document.getElementById('notificationDropdown');
    const badge = document.getElementById('notificationBadge');
    const notificationList = document.getElementById('notificationList');
    
    let notifications = [];
    
    if (!bell || !dropdown || !badge || !notificationList) {
        console.error('Notification elements not found');
        return;
    }
    
    // Toggle dropdown
    bell.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
        if (!dropdown.classList.contains('hidden')) {
            loadNotifications();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
    
    // Load notifications from API
    function loadNotifications() {
        fetch('api/notifications.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Notifications loaded:', data);
                notifications = data.notifications || [];
                updateNotificationDisplay();
                updateBadge();
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                // Show error in dropdown
                notificationList.innerHTML = `
                    <div class="p-4 text-center text-red-500">
                        <p>Error loading notifications</p>
                    </div>
                `;
            });
    }
    
    // Update notification display
    function updateNotificationDisplay() {
        if (notifications.length === 0) {
            notificationList.innerHTML = `
                <div class="p-4 text-center text-gray-500">
                    <p>No notifications yet</p>
                </div>
            `;
        } else {
            notificationList.innerHTML = notifications.map(notification => `
                <div class="p-4 border-b border-gray-100 hover:bg-gray-50">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">New petition: "${notification.title}"</p>
                            <p class="text-xs text-gray-600">by ${notification.holder}</p>
                            <p class="text-xs text-gray-500">${getTimeAgo(notification.timestamp)}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="petition-details.php?id=${notification.id}" class="text-purple-600 hover:text-purple-800 text-xs font-medium">
                                View
                            </a>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    }
    
    // Update badge
    function updateBadge() {
        if (notifications.length > 0) {
            badge.textContent = notifications.length;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
    
    // Get time ago
    function getTimeAgo(timestamp) {
        const now = Math.floor(Date.now() / 1000);
        const diff = now - timestamp;
        
        if (diff < 60) return 'Just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        return `${Math.floor(diff / 86400)}d ago`;
    }
    
    // Poll for new notifications every 10 seconds
    setInterval(loadNotifications, 10000);
    
    // Initial load
    loadNotifications();
});