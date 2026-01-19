/**
 * Notification Handler
 * Handles notification actions: mark as read, dismiss, clear all
 */

/**
 * Mark a single notification as read
 * @param {Event} event - Click event
 * @param {string|number} notificationId - Notification ID
 * @param {string} notificationType - Type of notification (alert, license, clearance)
 */
function markNotificationRead(event, notificationId, notificationType) {
    event.preventDefault();
    event.stopPropagation();
    
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('notification_id', notificationId);
    formData.append('notification_type', notificationType);
    
    fetch('api/notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI to show as read
            const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"][data-notification-type="${notificationType}"]`);
            if (notificationItem) {
                notificationItem.classList.remove('unread');
                notificationItem.classList.add('read');
                
                // Remove the "mark as read" button
                const readBtn = notificationItem.querySelector('.notification-actions button[title="Mark as read"]');
                if (readBtn) {
                    readBtn.remove();
                }
            }
            
            // Update badge count
            updateNotificationBadge();
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

/**
 * Dismiss a single notification
 * @param {Event} event - Click event
 * @param {string|number} notificationId - Notification ID
 * @param {string} notificationType - Type of notification (alert, license, clearance)
 */
function dismissNotification(event, notificationId, notificationType) {
    event.preventDefault();
    event.stopPropagation();
    
    const formData = new FormData();
    formData.append('action', 'dismiss');
    formData.append('notification_id', notificationId);
    formData.append('notification_type', notificationType);
    
    fetch('api/notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the notification item from UI
            const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"][data-notification-type="${notificationType}"]`);
            if (notificationItem) {
                notificationItem.style.transition = 'opacity 0.3s ease, max-height 0.3s ease';
                notificationItem.style.opacity = '0';
                notificationItem.style.maxHeight = '0';
                notificationItem.style.overflow = 'hidden';
                
                setTimeout(() => {
                    notificationItem.remove();
                    
                    // Check if there are any notifications left
                    const dropdown = document.getElementById('notificationDropdown');
                    if (dropdown) {
                        const remainingItems = dropdown.querySelectorAll('.notification-item').length;
                        if (remainingItems === 0) {
                            // Show "no notifications" message
                            const emptyMessage = `
                                <li class="dropdown-item-text text-muted text-center py-3">
                                    <i class="far fa-bell-slash fa-2x mb-2 d-block"></i>
                                    <small>No new notifications</small>
                                </li>
                            `;
                            dropdown.innerHTML = `
                                <li class="dropdown-header d-flex justify-content-between align-items-center">
                                    <strong>Notifications</strong>
                                    <a href="?page=alerts" class="text-decoration-none">View All</a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                ${emptyMessage}
                            `;
                        }
                    }
                    
                    // Update badge count
                    updateNotificationBadge();
                }, 300);
            }
        }
    })
    .catch(error => {
        console.error('Error dismissing notification:', error);
    });
}

/**
 * Mark all notifications as read
 * @param {Event} event - Click event
 */
function markAllNotificationsRead(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const formData = new FormData();
    formData.append('action', 'mark_all_read');
    
    fetch('api/notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update all notification items to show as read
            const notificationItems = document.querySelectorAll('.notification-item.unread');
            notificationItems.forEach(item => {
                item.classList.remove('unread');
                item.classList.add('read');
                
                // Remove "mark as read" buttons
                const readBtn = item.querySelector('.notification-actions button[title="Mark as read"]');
                if (readBtn) {
                    readBtn.remove();
                }
            });
            
            // Update badge count
            updateNotificationBadge();
            
            // Show success message (optional)
            if (typeof GoldenNotify !== 'undefined') {
                GoldenNotify.success('All notifications marked as read');
            }
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}

/**
 * Clear (dismiss) all notifications
 * @param {Event} event - Click event
 */
function clearAllNotifications(event) {
    event.preventDefault();
    event.stopPropagation();
    
    // Confirm action
    if (!confirm('Are you sure you want to clear all notifications?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'clear_all');
    
    fetch('api/notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear all notification items from UI
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) {
                const emptyMessage = `
                    <li class="dropdown-item-text text-muted text-center py-3">
                        <i class="far fa-bell-slash fa-2x mb-2 d-block"></i>
                        <small>No new notifications</small>
                    </li>
                `;
                dropdown.innerHTML = `
                    <li class="dropdown-header d-flex justify-content-between align-items-center">
                        <strong>Notifications</strong>
                        <a href="?page=alerts" class="text-decoration-none">View All</a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    ${emptyMessage}
                `;
            }
            
            // Update badge count
            updateNotificationBadge();
            
            // Show success message (optional)
            if (typeof GoldenNotify !== 'undefined') {
                GoldenNotify.success('All notifications cleared');
            }
        }
    })
    .catch(error => {
        console.error('Error clearing all notifications:', error);
    });
}

/**
 * Update the notification badge count
 */
function updateNotificationBadge() {
    fetch('api/notifications.php?action=get_unread_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.querySelector('.hrdash-welcome__icon-btn .hrdash-welcome__badge');
                const count = data.count || 0;
                
                if (count > 0) {
                    if (badge) {
                        badge.textContent = count > 99 ? '99+' : count;
                    } else {
                        // Create badge if it doesn't exist
                        const bellButton = document.querySelector('.hrdash-welcome__icon-btn[title="Notifications"]');
                        if (bellButton) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'hrdash-welcome__badge';
                            newBadge.textContent = count > 99 ? '99+' : count;
                            bellButton.appendChild(newBadge);
                        }
                    }
                } else {
                    // Remove badge if count is 0
                    if (badge) {
                        badge.remove();
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error updating notification badge:', error);
        });
}

/**
 * Initialize notification system
 */
document.addEventListener('DOMContentLoaded', function() {
    // Periodically update notification count (every 60 seconds)
    setInterval(function() {
        updateNotificationBadge();
    }, 60000);
    
    // Prevent dropdown from closing when clicking inside notification items
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (notificationDropdown) {
        notificationDropdown.addEventListener('click', function(event) {
            // Allow dropdown to close only when clicking links or outside notification items
            if (!event.target.closest('.notification-item') || event.target.tagName === 'A') {
                return;
            }
            event.stopPropagation();
        });
    }
});
