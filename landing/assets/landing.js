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
    
    // Professional Form Validation System
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    const validationAlert = document.getElementById('validationAlert');
    const closeAlertBtn = document.getElementById('closeAlert');
    
    // System Alert Functions
    function showSystemAlert(title, message) {
        if (validationAlert) {
            document.getElementById('alertTitle').textContent = title;
            document.getElementById('alertMessage').textContent = message;
            validationAlert.classList.remove('d-none');
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                hideSystemAlert();
            }, 5000);
        }
    }
    
    function hideSystemAlert() {
        if (validationAlert) {
            validationAlert.classList.add('d-none');
        }
    }
    
    // Close alert button
    if (closeAlertBtn) {
        closeAlertBtn.addEventListener('click', hideSystemAlert);
    }
    
    // Validation Rules
    const validationRules = {
        username: {
            required: true,
            minLength: 3,
            maxLength: 100,
            pattern: /^[a-zA-Z0-9._@+-]+$/,
            messages: {
                required: 'Username is required',
                minLength: 'Username must be at least 3 characters',
                maxLength: 'Username cannot exceed 100 characters',
                pattern: 'Username contains invalid characters'
            }
        },
        password: {
            required: true,
            minLength: 8,
            maxLength: 255,
            messages: {
                required: 'Password is required',
                minLength: 'Password must be at least 8 characters',
                maxLength: 'Password is too long'
            }
        }
    };
    
    // Validation Function
    function validateField(input, rules) {
        const value = input.value.trim();
        const errorElement = document.getElementById(input.id + '-error');
        let isValid = true;
        let errorMessage = '';
        
        // Required check
        if (rules.required && value === '') {
            isValid = false;
            errorMessage = rules.messages.required;
        }
        
        // Min length check
        else if (rules.minLength && value.length > 0 && value.length < rules.minLength) {
            isValid = false;
            errorMessage = rules.messages.minLength;
        }
        
        // Max length check
        else if (rules.maxLength && value.length > rules.maxLength) {
            isValid = false;
            errorMessage = rules.messages.maxLength;
        }
        
        // Pattern check
        else if (rules.pattern && value.length > 0 && !rules.pattern.test(value)) {
            isValid = false;
            errorMessage = rules.messages.pattern;
        }
        
        // Update UI
        if (!isValid) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            input.setAttribute('aria-invalid', 'true');
            if (errorElement) {
                errorElement.textContent = errorMessage;
            }
        } else {
            input.classList.remove('is-invalid');
            if (value.length > 0) {
                input.classList.add('is-valid');
            }
            input.setAttribute('aria-invalid', 'false');
            if (errorElement) {
                errorElement.textContent = '';
            }
        }
        
        return isValid;
    }
    
    // Real-time validation on blur
    if (usernameInput) {
        usernameInput.addEventListener('blur', function() {
            validateField(this, validationRules.username);
        });
        
        // Clear error on input
        usernameInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this, validationRules.username);
            }
        });
    }
    
    if (passwordInput) {
        passwordInput.addEventListener('blur', function() {
            validateField(this, validationRules.password);
        });
        
        // Clear error on input
        passwordInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this, validationRules.password);
            }
        });
    }
    
    // Form Submission with Complete Validation
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            
            let isFormValid = true;
            
            // Validate all fields
            if (usernameInput) {
                if (!validateField(usernameInput, validationRules.username)) {
                    isFormValid = false;
                }
            }
            
            if (passwordInput) {
                if (!validateField(passwordInput, validationRules.password)) {
                    isFormValid = false;
                }
            }
            
            // Prevent submission if validation fails
            if (!isFormValid) {
                e.preventDefault();
                console.log('Validation failed, form submission prevented');
                
                // Show professional system alert
                showSystemAlert(
                    'Required Fields Missing',
                    'Please enter both username and password to continue.'
                );
                
                // Focus on first invalid field
                const firstInvalid = loginForm.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
                
                return false;
            }
            
            console.log('Validation passed, allowing form submission');
            
            // Show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.setAttribute('aria-busy', 'true');
                
                const btnText = submitBtn.querySelector('.btn-text');
                const btnSpinner = submitBtn.querySelector('.btn-spinner');
                
                if (btnText) {
                    btnText.style.opacity = '0';
                }
                if (btnSpinner) {
                    btnSpinner.classList.remove('d-none');
                }
                
                // Re-enable after 15 seconds as fallback
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        submitBtn.setAttribute('aria-busy', 'false');
                        if (btnText) {
                            btnText.style.opacity = '1';
                        }
                        if (btnSpinner) {
                            btnSpinner.classList.add('d-none');
                        }
                    }
                }, 15000);
            }
            
            // Allow form to submit
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
