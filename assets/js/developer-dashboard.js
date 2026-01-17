/**
 * Developer Dashboard JavaScript
 * Handles interactions, confirm dialogs, and live clock
 */

(function() {
    'use strict';

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        initLiveClock();
        initToolButtons();
    }

    /**
     * Live Clock Display
     */
    function initLiveClock() {
        const serverTimeEl = document.getElementById('serverTime');
        if (!serverTimeEl) return;

        // Get initial server time from element
        const initialTime = serverTimeEl.textContent.trim();
        if (!initialTime) return;

        // Parse initial time (assuming format: YYYY-MM-DD HH:MM:SS)
        let currentTime = new Date(initialTime);
        if (isNaN(currentTime.getTime())) {
            // Fallback to current time if parsing fails
            currentTime = new Date();
        }

        // Update clock every second
        setInterval(function() {
            currentTime.setSeconds(currentTime.getSeconds() + 1);
            const timeString = formatDateTime(currentTime);
            serverTimeEl.textContent = timeString;
        }, 1000);
    }

    /**
     * Format date time for display
     */
    function formatDateTime(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    /**
     * Initialize Tool Buttons
     */
    function initToolButtons() {
        const toolButtons = document.querySelectorAll('.tool-btn[data-action]');
        
        toolButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                handleToolAction(this);
            });
        });
    }

    /**
     * Handle Tool Action
     */
    function handleToolAction(button) {
        const action = button.getAttribute('data-action');
        const confirmMessage = button.getAttribute('data-confirm') || 'Are you sure?';
        
        if (!action) return;

        // Show confirmation dialog
        if (confirm(confirmMessage)) {
            executeToolAction(action, button);
        }
    }

    /**
     * Execute Tool Action via POST
     */
    function executeToolAction(action, button) {
        const form = document.getElementById('toolActionForm');
        if (!form) {
            console.error('Tool action form not found');
            showMessage('Error: Form not found', 'error');
            return;
        }

        const actionInput = document.getElementById('toolAction');
        if (!actionInput) {
            console.error('Tool action input not found');
            showMessage('Error: Action input not found', 'error');
            return;
        }

        // Disable button during request
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span>Processing...</span>';

        // Set action and submit
        actionInput.value = action;
        
        // Submit via fetch for better UX
        const formData = new FormData(form);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            return response.json().catch(function() {
                // If response is not JSON, return text
                return response.text().then(function(text) {
                    return { success: false, message: text || 'Unknown error' };
                });
            });
        })
        .then(function(data) {
            if (data.success) {
                showMessage(data.message || 'Action completed successfully', 'success');
                // Optionally refresh page or update UI
                if (action === 'clear-sessions') {
                    // Refresh page to update session count
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                showMessage(data.message || 'Action failed', 'error');
            }
        })
        .catch(function(error) {
            console.error('Error executing tool action:', error);
            showMessage('An error occurred while executing the action', 'error');
        })
        .finally(function() {
            // Re-enable button
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }

    /**
     * Show Message (Simple implementation)
     */
    function showMessage(message, type) {
        // Try to use existing message display system if available
        if (typeof displayMessage === 'function') {
            displayMessage(message, type);
            return;
        }

        // Fallback: Create temporary alert
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + (type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info');
        alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.textContent = message;
        
        document.body.appendChild(alertDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            alertDiv.remove();
        }, 5000);
    }

    /**
     * Refresh Data (Optional - for future AJAX refresh)
     */
    function refreshData() {
        // This can be implemented later for AJAX data refresh
        window.location.reload();
    }

    // Export functions for potential external use
    window.DeveloperDashboard = {
        refreshData: refreshData,
        executeToolAction: executeToolAction
    };
})();
