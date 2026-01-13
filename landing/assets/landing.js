/**
 * Golden Z-5 HR System - Professional Landing Page JavaScript
 * Black Theme with Enhanced UX
 */

document.addEventListener('DOMContentLoaded', function() {
    // Toggle Password Visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon with animation
            if (type === 'password') {
                togglePasswordIcon.classList.remove('fa-eye-slash');
                togglePasswordIcon.classList.add('fa-eye');
            } else {
                togglePasswordIcon.classList.remove('fa-eye');
                togglePasswordIcon.classList.add('fa-eye-slash');
            }
            
            // Add visual feedback
            passwordInput.focus();
        });
    }
    
    // Form Validation and Submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            // Basic validation - only prevent if fields are empty
            if (!username || !username.value.trim()) {
                e.preventDefault();
                console.log('Validation failed: username empty');
                showAlert('Please enter your username', 'warning');
                if (username) username.focus();
                return false;
            }
            
            if (!password || !password.value) {
                e.preventDefault();
                console.log('Validation failed: password empty');
                showAlert('Please enter your password', 'warning');
                if (password) password.focus();
                return false;
            }
            
            console.log('Validation passed, allowing form submission');
            
            // Show loading state (but DON'T prevent form submission)
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const btnText = submitBtn.querySelector('.btn-text');
                if (btnText) {
                    btnText.textContent = 'Signing in...';
                }
                const spinner = submitBtn.querySelector('.spinner-border');
                if (spinner) {
                    spinner.classList.remove('d-none');
                }
                
                // Re-enable after 15 seconds as fallback (in case form doesn't submit)
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        if (btnText) {
                            btnText.textContent = 'Sign in';
                        }
                        if (spinner) {
                            spinner.classList.add('d-none');
                        }
                    }
                }, 15000);
            }
            
            // IMPORTANT: Don't call e.preventDefault() here - allow form to submit!
            // The form will POST to server and PHP will handle the redirect
            console.log('Form will submit to:', loginForm.action || window.location.href);
        });
    }
    
    // Auto-fill demo credentials (for testing via URL parameter)
    const urlParams = new URLSearchParams(window.location.search);
    const demo = urlParams.get('demo');
    if (demo) {
        const demoCredentials = {
            'admin': { username: 'admin', password: 'admin123' },
            'hr': { username: 'hr', password: 'hr123' },
            'operation': { username: 'operation', password: 'operation123' },
            'accounting': { username: 'accounting', password: 'accounting123' },
            'employee': { username: 'employee', password: 'employee123' },
            'developer': { username: 'developer', password: 'developer123' }
        };
        
        if (demoCredentials[demo]) {
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            if (usernameInput && passwordInput) {
                usernameInput.value = demoCredentials[demo].username;
                passwordInput.value = demoCredentials[demo].password;
                // Auto-submit after a short delay
                setTimeout(() => {
                    if (loginForm) {
                        loginForm.submit();
                    }
                }, 500);
            }
        }
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Alt + L to focus username field
        if (e.altKey && e.key === 'l') {
            e.preventDefault();
            const usernameInput = document.getElementById('username');
            if (usernameInput) {
                usernameInput.focus();
            }
        }
        
        // Escape to clear form
        if (e.key === 'Escape') {
            if (loginForm) {
                loginForm.reset();
                const usernameInput = document.getElementById('username');
                if (usernameInput) {
                    usernameInput.focus();
                }
            }
        }
        
        // Enter key to submit (when form is focused)
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
            const activeElement = document.activeElement;
            if (activeElement && (activeElement.id === 'username' || activeElement.id === 'password')) {
                // Allow default form submission
                return true;
            }
        }
    });
    
    // Show alert function
    function showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert-custom');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'warning' ? 'danger' : type} alert-dismissible fade show alert-custom`;
        alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px; animation: slideInRight 0.3s ease-out;';
        
        const iconClass = type === 'warning' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle';
        alertDiv.innerHTML = `
            <i class="fas fa-${iconClass} me-2"></i>
            ${message}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
    
    // Add CSS animations for alerts
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Focus username input on load (if not demo mode)
    const usernameInput = document.getElementById('username');
    if (usernameInput && !demo) {
        setTimeout(() => {
            usernameInput.focus();
        }, 300);
    }
    
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Add input animations
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
    
    // Add ripple effect to button (but don't interfere with form submission)
    const submitButton = loginForm ? loginForm.querySelector('button[type="submit"]') : null;
    if (submitButton && submitButton.type === 'submit') {
        // Only add visual effect, don't prevent default
        submitButton.addEventListener('click', function(e) {
            // Don't prevent default - let form submit normally
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    }
    
    // Add ripple CSS
    const rippleStyle = document.createElement('style');
    rippleStyle.textContent = `
        .btn-primary {
            position: relative;
            overflow: hidden;
        }
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(rippleStyle);
    
    // Add smooth transitions for form elements
    const formElements = document.querySelectorAll('.form-control, .form-select, .btn');
    formElements.forEach(element => {
        element.style.transition = 'all 0.3s ease';
    });
});
