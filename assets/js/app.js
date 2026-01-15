/**
 * Golden Z-5 HR System - Main JavaScript
 * High-Security Client-Side Application
 */

/**
 * Fix "boxed digits" rendering (often keycap/emoji digit sequences).
 * Some environments can end up with numeric text containing U+FE0F (VS16) and/or U+20E3 (enclosing keycap),
 * which renders as blue boxed digits in Chrome/Edge/Firefox.
 *
 * This normalizes those sequences back to plain ASCII digits across the UI.
 */
function normalizeNumericText(root = document) {
    const selectors = [
        '.stat-number',
        '.card-number',
        '.quick-stat-value',
        '.rate-number',
        '.progress-value',
        '.badge',
        '.badge-success-modern',
        '.badge-primary-modern',
        '.badge-warning-modern',
        '.badge-danger-modern',
        '.badge-live',
        'table td',
        'table th',
        'code',
        '[data-numeric]',
        '[aria-valuenow]'
    ].join(',');

    const nodes = root.querySelectorAll(selectors);
    if (!nodes || nodes.length === 0) return;

    nodes.forEach((el) => {
        // Only touch leaf-ish nodes so we don't blow away icons/markup.
        if (!el) return;
        const hasElementChildren = Array.from(el.childNodes).some((n) => n.nodeType === Node.ELEMENT_NODE);
        if (hasElementChildren && !el.matches('.stat-number, .card-number, .progress-value, .badge, code')) {
            return;
        }

        const text = el.textContent;
        if (!text) return;

        // Replace keycap sequences: "3\uFE0F\u20E3" or "3\u20E3" -> "3"
        const normalized = text
            .replace(/([0-9])\uFE0F?\u20E3/g, '$1')
            .replace(/\uFE0F/g, '')
            .replace(/\u20E3/g, '');

        if (normalized !== text) {
            el.textContent = normalized;
        }
    });
}

// Disable animations on page load to prevent hover effects on refresh
document.documentElement.classList.add('no-animations');

// THEME MANAGEMENT (Light / Dark / Auto)
const THEME_STORAGE_KEY = 'goldenz-theme';

function resolveTheme(theme) {
    if (theme === 'auto') {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }
    return theme || 'light';
}

function applyTheme(theme) {
    const effective = resolveTheme(theme);

    // Apply to <html> so CSS like html[data-theme="dark"] works
    document.documentElement.setAttribute('data-theme', effective);

    try {
        localStorage.setItem(THEME_STORAGE_KEY, theme);
    } catch (e) {
        // Ignore storage errors (private mode, etc.)
    }

    // Sync all theme selects across the UI
    document.querySelectorAll('.theme-select').forEach((select) => {
        if (!select) return;
        if (select.disabled) {
            select.disabled = false;
        }
        if (select.value !== theme) {
            select.value = theme;
        }
    });
}

function initThemeControls() {
    let savedTheme = 'light';
    try {
        const stored = localStorage.getItem(THEME_STORAGE_KEY);
        if (stored === 'light' || stored === 'dark' || stored === 'auto') {
            savedTheme = stored;
        }
    } catch (e) {
        // ignore and fall back to light
    }

    // Apply theme first
    applyTheme(savedTheme);
    
    // Then ensure all selects show the correct value
    document.querySelectorAll('.theme-select').forEach((select) => {
        if (select && select.value !== savedTheme) {
            select.value = savedTheme;
        }
    });

    // Attach change handlers (live preview)
    document.querySelectorAll('.theme-select').forEach((select) => {
        if (!select) return;
        select.disabled = false;
        select.addEventListener('change', () => {
            const value = select.value || 'light';
            applyTheme(value);
        });
    });

    // Confirm button in UI & Preferences (visual confirmation)
    document.querySelectorAll('.save-ui-preferences-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            // Find the theme select within the same form/card context
            const form = btn.closest('form');
            const select = form ? form.querySelector('.theme-select') : document.querySelector('#settingsThemeSelect') || document.querySelector('.theme-select');
            const value = (select && select.value) ? select.value : 'light';
            applyTheme(value);
            if (typeof showAlert === 'function') {
                showAlert('UI preferences updated successfully.', 'success');
            }
        });
    });

    // React to system theme changes when in "auto" mode
    if (window.matchMedia) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', () => {
                let current = 'light';
                try {
                    current = localStorage.getItem(THEME_STORAGE_KEY) || 'light';
                } catch (e) {}
                if (current === 'auto') {
                    applyTheme('auto');
                }
            });
        }
    }
}

// Security features + Theme init
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔒 Golden Z-5 HR System loaded with high security');

    // Initialize theme controls (UI & Preferences + Dashboard quick settings)
    initThemeControls();

    // Normalize numeric text on initial load (fix boxed digits)
    normalizeNumericText(document);
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize modals without backdrops to keep background interactive
    var modalList = [].slice.call(document.querySelectorAll('.modal'));
    var modalArray = modalList.map(function (modalEl) {
        return new bootstrap.Modal(modalEl, {
            backdrop: false,
            keyboard: true
        });
    });
});

// Also re-run after AJAX-style page transitions (page-transitions.js dispatches `pageLoaded`)
document.addEventListener('pageLoaded', function() {
    normalizeNumericText(document);
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