/**
 * Golden Z-5 HR System - Global Notification System
 * Professional notification and dialog system to replace browser alerts
 * 
 * Version: 1.0
 * Date: January 19, 2026
 * 
 * USAGE:
 * ------
 * System Alerts (Top Center):
 *   GoldenNotify.alert('Title', 'Message', 'success|info|warning|error');
 *   GoldenNotify.success('Operation completed successfully');
 *   GoldenNotify.error('An error occurred');
 * 
 * Toast Notifications (Bottom Right):
 *   GoldenNotify.toast('Message saved', 'success');
 *   GoldenNotify.toastSuccess('File uploaded successfully');
 *   GoldenNotify.toastInfo('New message received');
 * 
 * Confirmation Dialogs:
 *   GoldenNotify.confirm('Delete this item?', 'warning').then(confirmed => {
 *       if (confirmed) { // User clicked confirm }
 *   });
 */

const GoldenNotify = (function() {
    'use strict';
    
    // Configuration
    const config = {
        alertDuration: 5000,        // Auto-dismiss after 5 seconds
        toastDuration: 4000,        // Auto-dismiss after 4 seconds
        maxAlerts: 3,               // Maximum alerts shown at once
        maxToasts: 4,               // Maximum toasts shown at once
        position: {
            alert: 'top-center',    // System alerts position
            toast: 'bottom-right'   // Toast notifications position
        }
    };
    
    // Icon mapping for Font Awesome
    const icons = {
        success: 'fa-check-circle',
        info: 'fa-info-circle',
        warning: 'fa-exclamation-triangle',
        error: 'fa-times-circle'
    };
    
    // Initialize containers
    function init() {
        if (!document.querySelector('.system-alert-container')) {
            const alertContainer = document.createElement('div');
            alertContainer.className = 'system-alert-container';
            document.body.appendChild(alertContainer);
        }
        
        if (!document.querySelector('.toast-container')) {
            const toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }
    }
    
    // ============================================================
    // SYSTEM ALERTS (Top Center)
    // ============================================================
    
    /**
     * Show a system alert at the top center
     * @param {string} title - Alert title
     * @param {string} message - Alert message
     * @param {string} type - Alert type: success, info, warning, error
     * @param {number} duration - Auto-dismiss duration (0 = manual close only)
     */
    function showAlert(title, message, type = 'info', duration = config.alertDuration) {
        init();
        
        const container = document.querySelector('.system-alert-container');
        
        // Limit number of alerts
        const existingAlerts = container.querySelectorAll('.system-alert');
        if (existingAlerts.length >= config.maxAlerts) {
            existingAlerts[0].remove();
        }
        
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `system-alert alert-${type}`;
        alert.setAttribute('role', 'alert');
        alert.setAttribute('aria-live', 'assertive');
        
        const iconClass = icons[type] || icons.info;
        
        alert.innerHTML = `
            <div class="system-alert-icon">
                <i class="fas ${iconClass}"></i>
            </div>
            <div class="system-alert-content">
                <strong>${escapeHtml(title)}</strong>
                ${message ? `<p>${escapeHtml(message)}</p>` : ''}
            </div>
            <button type="button" class="system-alert-close" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Add close button handler
        const closeBtn = alert.querySelector('.system-alert-close');
        closeBtn.addEventListener('click', () => removeAlert(alert));
        
        // Add to container
        container.appendChild(alert);
        
        // Auto-dismiss
        if (duration > 0) {
            setTimeout(() => removeAlert(alert), duration);
        }
        
        return alert;
    }
    
    function removeAlert(alert) {
        if (!alert || !alert.parentElement) return;
        
        alert.classList.add('hiding');
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 300);
    }
    
    // ============================================================
    // TOAST NOTIFICATIONS (Bottom Right)
    // ============================================================
    
    /**
     * Show a toast notification at the bottom right
     * @param {string} message - Toast message
     * @param {string} type - Toast type: success, info, warning, error
     * @param {string} title - Optional title
     * @param {number} duration - Auto-dismiss duration (0 = manual close only)
     */
    function showToast(message, type = 'info', title = '', duration = config.toastDuration) {
        init();
        
        const container = document.querySelector('.toast-container');
        
        // Limit number of toasts
        const existingToasts = container.querySelectorAll('.toast');
        if (existingToasts.length >= config.maxToasts) {
            existingToasts[0].remove();
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        
        const iconClass = icons[type] || icons.info;
        
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${iconClass}"></i>
            </div>
            <div class="toast-content">
                ${title ? `<strong>${escapeHtml(title)}</strong>` : ''}
                <p>${escapeHtml(message)}</p>
            </div>
            <button type="button" class="toast-close" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            ${duration > 0 ? '<div class="toast-progress"></div>' : ''}
        `;
        
        // Add close button handler
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => removeToast(toast));
        
        // Add to container
        container.appendChild(toast);
        
        // Progress bar animation
        if (duration > 0) {
            const progress = toast.querySelector('.toast-progress');
            if (progress) {
                setTimeout(() => {
                    progress.style.width = '100%';
                    progress.style.transition = `width ${duration}ms linear`;
                }, 10);
            }
            
            // Auto-dismiss
            setTimeout(() => removeToast(toast), duration);
        }
        
        return toast;
    }
    
    function removeToast(toast) {
        if (!toast || !toast.parentElement) return;
        
        toast.classList.add('hiding');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 300);
    }
    
    // ============================================================
    // CONFIRMATION DIALOGS (Replaces confirm())
    // ============================================================
    
    /**
     * Show a confirmation dialog
     * @param {string} message - Confirmation message
     * @param {string} type - Dialog type: warning, danger, info
     * @param {string} title - Dialog title
     * @param {string} confirmText - Confirm button text
     * @param {string} cancelText - Cancel button text
     * @returns {Promise<boolean>} - Resolves to true if confirmed, false if cancelled
     */
    function showConfirm(message, type = 'warning', title = 'Confirm Action', confirmText = 'Confirm', cancelText = 'Cancel') {
        return new Promise((resolve) => {
            // Create overlay
            const overlay = document.createElement('div');
            overlay.className = 'confirm-overlay';
            
            // Icon mapping
            const confirmIcons = {
                warning: 'fa-exclamation-triangle',
                danger: 'fa-exclamation-circle',
                info: 'fa-info-circle'
            };
            
            const iconClass = confirmIcons[type] || confirmIcons.warning;
            const btnClass = type === 'danger' ? 'btn-danger' : '';
            
            overlay.innerHTML = `
                <div class="confirm-dialog confirm-${type}">
                    <div class="confirm-header">
                        <div class="confirm-icon">
                            <i class="fas ${iconClass}"></i>
                        </div>
                        <h3 class="confirm-title">${escapeHtml(title)}</h3>
                    </div>
                    <p class="confirm-message">${escapeHtml(message)}</p>
                    <div class="confirm-actions">
                        <button type="button" class="confirm-btn confirm-btn-cancel">${escapeHtml(cancelText)}</button>
                        <button type="button" class="confirm-btn confirm-btn-confirm ${btnClass}">${escapeHtml(confirmText)}</button>
                    </div>
                </div>
            `;
            
            // Add to body
            document.body.appendChild(overlay);
            document.body.classList.add('confirm-open');
            
            // Handle buttons
            const cancelBtn = overlay.querySelector('.confirm-btn-cancel');
            const confirmBtn = overlay.querySelector('.confirm-btn-confirm');
            
            function closeDialog(result) {
                document.body.classList.remove('confirm-open');
                overlay.style.animation = 'fadeOut 0.2s ease';
                setTimeout(() => {
                    overlay.remove();
                    resolve(result);
                }, 200);
            }
            
            cancelBtn.addEventListener('click', () => closeDialog(false));
            confirmBtn.addEventListener('click', () => closeDialog(true));
            
            // Close on overlay click
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    closeDialog(false);
                }
            });
            
            // Close on Escape key
            function handleEscape(e) {
                if (e.key === 'Escape') {
                    closeDialog(false);
                    document.removeEventListener('keydown', handleEscape);
                }
            }
            document.addEventListener('keydown', handleEscape);
            
            // Focus confirm button
            setTimeout(() => confirmBtn.focus(), 100);
        });
    }
    
    // ============================================================
    // HELPER FUNCTIONS
    // ============================================================
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // ============================================================
    // PUBLIC API
    // ============================================================
    
    return {
        // Configuration
        config: config,
        
        // System Alerts (Top Center)
        alert: showAlert,
        success: (message, title = 'Success') => showAlert(title, message, 'success'),
        info: (message, title = 'Information') => showAlert(title, message, 'info'),
        warning: (message, title = 'Warning') => showAlert(title, message, 'warning'),
        error: (message, title = 'Error') => showAlert(title, message, 'error'),
        
        // Toast Notifications (Bottom Right)
        toast: showToast,
        toastSuccess: (message, title = '') => showToast(message, 'success', title),
        toastInfo: (message, title = '') => showToast(message, 'info', title),
        toastWarning: (message, title = '') => showToast(message, 'warning', title),
        toastError: (message, title = '') => showToast(message, 'error', title),
        
        // Confirmation Dialogs
        confirm: showConfirm,
        confirmDelete: (item = 'this item') => showConfirm(
            `Are you sure you want to delete ${item}? This action cannot be undone.`,
            'danger',
            'Confirm Deletion',
            'Delete',
            'Cancel'
        ),
        
        // Manual close
        closeAlert: removeAlert,
        closeToast: removeToast,
        
        // Initialize (called automatically, but can be called manually)
        init: init
    };
})();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', GoldenNotify.init);
} else {
    GoldenNotify.init();
}

// Add fadeOut animation to CSS
if (!document.querySelector('#golden-notify-animations')) {
    const style = document.createElement('style');
    style.id = 'golden-notify-animations';
    style.textContent = `
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// Make available globally
window.GoldenNotify = GoldenNotify;

// Legacy compatibility - Override native alert and confirm (optional)
// Uncomment to automatically replace all alert() and confirm() calls
/*
window.alert = function(message) {
    GoldenNotify.alert('Alert', message, 'info');
};

window.confirm = function(message) {
    return GoldenNotify.confirm(message);
};
*/
