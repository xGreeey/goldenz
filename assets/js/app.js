/**
 * Golden Z-5 HR System - Main JavaScript
 * High-Security Client-Side Application
 */

// Disable animations on page load to prevent hover effects on refresh
document.documentElement.classList.add('no-animations');

// Security features
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔒 Golden Z-5 HR System loaded with high security');
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize modals
    var modalList = [].slice.call(document.querySelectorAll('.modal'));
    var modalArray = modalList.map(function (modalEl) {
        return new bootstrap.Modal(modalEl);
    });
});

// Re-enable animations after page is fully loaded
window.addEventListener('load', function() {
    // Small delay to ensure all elements are rendered
    setTimeout(function() {
        document.documentElement.classList.remove('no-animations');
    }, 100);
});

// Show alert function
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
    alertDiv.innerHTML = `
        <i class="fas fa-${getAlertIcon(type)} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function getAlertIcon(type) {
    const icons = {
        success: 'check-circle',
        danger: 'exclamation-triangle',
        warning: 'exclamation-circle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebar && mainContent) {
        if (sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            localStorage.setItem('goldenz-sidebar-collapsed', 'false');
        } else {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            localStorage.setItem('goldenz-sidebar-collapsed', 'true');
        }
    }
}

// Initialize sidebar state
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebar && mainContent) {
        const savedState = localStorage.getItem('goldenz-sidebar-collapsed');
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    }
});

// Form validation
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Add form validation to all forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showAlert('Please fill in all required fields', 'warning');
            }
        });
    });
});

// Auto-logout after 30 minutes of inactivity
let inactivityTimer;
const timeout = 30 * 60 * 1000; // 30 minutes

function resetTimer() {
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(() => {
        showAlert('Session expired due to inactivity', 'warning');
        setTimeout(() => {
            window.location.href = '?page=logout';
        }, 2000);
    }, timeout);
}

// Reset timer on user activity
['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
    document.addEventListener(event, resetTimer, true);
});

resetTimer();

// Sidebar dropdown toggles (fallback to ensure menus open/close)
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.nav-toggle');
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = toggle.getAttribute('data-target');
            const submenu = document.getElementById(targetId);
            const arrow = toggle.querySelector('.nav-arrow');
            const isExpanded = submenu && submenu.classList.contains('expanded');
            
            if (submenu) {
                submenu.classList.toggle('expanded', !isExpanded);
                toggle.setAttribute('aria-expanded', !isExpanded ? 'true' : 'false');
            }
            if (arrow) {
                arrow.classList.toggle('rotated', !isExpanded);
            }
        });
    });
});