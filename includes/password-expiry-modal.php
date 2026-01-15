<?php
/**
 * Password Expiry Modal Component
 * Shows a forced password change modal when user's password has expired
 * This file should be included after checking password expiry
 */

// Check password expiry
$password_expiry_days = 90; // Default expiry period
$user_id = $_SESSION['user_id'] ?? null;
$password_expired = false;
$days_until_expiry = null;

if ($user_id && function_exists('check_password_expiry')) {
    $expiry_check = check_password_expiry($user_id, $password_expiry_days);
    $password_expired = $expiry_check['expired'] ?? false;
    $days_until_expiry = $expiry_check['days_until_expiry'] ?? null;
}

// Only show modal if password is expired
if ($password_expired):
    // Determine the current portal and settings page URL
    $current_portal = 'super-admin'; // Default
    $script_path = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Check user role to determine portal
    $user_role = $_SESSION['user_role'] ?? '';
    if ($user_role === 'hr_admin' || in_array($user_role, ['hr', 'admin', 'accounting', 'operation', 'logistics'])) {
        $current_portal = 'hr-admin';
    } elseif ($user_role === 'developer') {
        $current_portal = 'developer';
    } elseif ($user_role === 'super_admin') {
        $current_portal = 'super-admin';
    }
    
    // Build settings URL - use relative path from current location
    // Footer is included from headers, so we need to go up one level
    $settings_url = '../' . $current_portal . '/index.php?page=settings';
    
    // If we're in the portal directory itself, use direct path
    if (strpos($script_path, $current_portal . '/index.php') !== false || 
        strpos($request_uri, '/' . $current_portal . '/') !== false) {
        $settings_url = 'index.php?page=settings';
    }
?>
<!-- Password Expiry Modal - Forces password change when expired -->
<div class="modal fade show" id="passwordExpiryModal" tabindex="-1" role="dialog" aria-labelledby="passwordExpiryModalLabel" aria-hidden="false" data-bs-backdrop="static" data-bs-keyboard="false" style="display: block !important; background-color: rgba(0,0,0,0.75); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.4);">
            <div class="modal-header bg-danger text-white" style="border-radius: 12px 12px 0 0; padding: 1.5rem;">
                <h5 class="modal-title" id="passwordExpiryModalLabel" style="margin: 0; font-weight: 600;">
                    <i class="fas fa-exclamation-triangle me-2"></i>Password Expired - Action Required
                </h5>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <div class="alert alert-warning mb-4">
                    <h6 class="alert-heading"><i class="fas fa-shield-alt me-2"></i>Security Alert</h6>
                    <p class="mb-0">Your password has expired for security reasons. You must change your password immediately to continue using the system.</p>
                </div>
                <p class="text-muted mb-4">
                    <strong>Why is this required?</strong><br>
                    Passwords expire after <?php echo $password_expiry_days; ?> days to protect your account from unauthorized access. This is a security best practice to prevent potential breaches from compromised passwords.
                </p>
                
                <!-- Password Change Form -->
                <div id="passwordExpiryAlert"></div>
                <form id="passwordExpiryForm" method="post" action="<?php echo htmlspecialchars($settings_url); ?>">
                    <input type="hidden" name="action" value="change_password">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="expiry_current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <div class="password-input-wrapper position-relative">
                                <input type="password" 
                                       class="form-control" 
                                       id="expiry_current_password" 
                                       name="current_password" 
                                       required
                                       autocomplete="current-password"
                                       placeholder="Enter your current password">
                                <button type="button" 
                                        class="btn btn-link password-toggle" 
                                        data-target="expiry_current_password"
                                        aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="expiry_new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <div class="password-input-wrapper position-relative">
                                <input type="password" 
                                       class="form-control" 
                                       id="expiry_new_password" 
                                       name="new_password" 
                                       required
                                       minlength="8"
                                       autocomplete="new-password"
                                       placeholder="Minimum 8 characters">
                                <button type="button" 
                                        class="btn btn-link password-toggle" 
                                        data-target="expiry_new_password"
                                        aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <!-- Password Strength Indicator -->
                            <div class="password-strength-container mt-2">
                                <div class="password-strength-bar">
                                    <div class="password-strength-fill" id="expiryPasswordStrengthBar"></div>
                                </div>
                                <div class="password-strength-text mt-2" id="expiryPasswordStrengthText">
                                    <small class="text-muted">Password strength: <span id="expiryStrengthLabel">None</span></small>
                                </div>
                                <div class="password-requirements mt-2" id="expiryPasswordRequirements">
                                    <small class="d-block mb-1 fw-semibold text-muted">Requirements:</small>
                                    <div class="requirement-item" data-requirement="length">
                                        <i class="fas fa-circle requirement-icon"></i>
                                        <span>Minimum 8 characters</span>
                                    </div>
                                    <div class="requirement-item" data-requirement="lowercase">
                                        <i class="fas fa-circle requirement-icon"></i>
                                        <span>Contains lowercase letter</span>
                                    </div>
                                    <div class="requirement-item" data-requirement="uppercase">
                                        <i class="fas fa-circle requirement-icon"></i>
                                        <span>Contains uppercase letter</span>
                                    </div>
                                    <div class="requirement-item" data-requirement="number">
                                        <i class="fas fa-circle requirement-icon"></i>
                                        <span>Contains number</span>
                                    </div>
                                    <div class="requirement-item" data-requirement="symbol">
                                        <i class="fas fa-circle requirement-icon"></i>
                                        <span>Contains symbol</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="expiry_confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="password-input-wrapper position-relative">
                                <input type="password" 
                                       class="form-control" 
                                       id="expiry_confirm_password" 
                                       name="confirm_password" 
                                       required
                                       minlength="8"
                                       autocomplete="new-password"
                                       placeholder="Re-enter new password">
                                <button type="button" 
                                        class="btn btn-link password-toggle" 
                                        data-target="expiry_confirm_password"
                                        aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <!-- Password Match Indicator -->
                            <div class="password-match-indicator mt-2" id="expiryPasswordMatchIndicator" style="display: none;">
                                <div class="match-status">
                                    <i class="match-icon"></i>
                                    <span class="match-text"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 1rem 1.5rem;">
                <small class="text-muted me-auto">You cannot dismiss this dialog until your password is changed.</small>
                <button type="submit" form="passwordExpiryForm" class="btn btn-primary-modern" id="expiryChangePasswordBtn">
                    <i class="fas fa-key me-2"></i>Change Password
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Password Expiry Modal Styles */
#passwordExpiryModal .password-input-wrapper {
    position: relative;
}

#passwordExpiryModal .password-input-wrapper .form-control {
    padding-right: 50px;
}

#passwordExpiryModal .password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    border: none !important;
    background: transparent !important;
    padding: 0.5rem 0.625rem !important;
    color: #475569 !important;
    cursor: pointer;
    z-index: 10;
    text-decoration: none !important;
    transition: all 0.2s ease;
    border-radius: 6px;
    display: flex !important;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    min-height: 36px;
    box-shadow: none !important;
    outline: none !important;
}

#passwordExpiryModal .password-toggle:hover {
    color: #1e3a8a !important;
    background: transparent !important;
    transform: translateY(-50%) scale(1.1);
    box-shadow: none !important;
}

#passwordExpiryModal .password-toggle:focus,
#passwordExpiryModal .password-toggle:active,
#passwordExpiryModal .password-toggle:focus-visible {
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    border: none !important;
}

#passwordExpiryModal .password-toggle i {
    font-size: 1.125rem !important;
    color: #475569 !important;
    transition: all 0.2s ease;
    display: block !important;
    line-height: 1 !important;
}

#passwordExpiryModal .password-toggle:hover i {
    color: #1e3a8a !important;
}

#passwordExpiryModal .btn-primary-modern {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%) !important;
    color: #ffffff !important;
    border: none !important;
    padding: 0.75rem 1.5rem !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    font-size: 0.9375rem !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.35) !important;
    cursor: pointer !important;
    min-height: 44px !important;
}

#passwordExpiryModal .btn-primary-modern:hover {
    background: linear-gradient(135deg, #1e40af 0%, #1e293b 50%, #1e3a8a 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(30, 58, 138, 0.45) !important;
}
</style>

<script>
(function() {
    // Prevent password expiry modal from being closed
    const passwordExpiryModal = document.getElementById('passwordExpiryModal');
    if (passwordExpiryModal) {
        // Prevent backdrop click from closing modal
        passwordExpiryModal.addEventListener('click', function(e) {
            if (e.target === passwordExpiryModal) {
                e.stopPropagation();
            }
        });
        
        // Prevent ESC key from closing modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && passwordExpiryModal.style.display === 'block') {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
        
        // Initialize Bootstrap modal with static backdrop and no keyboard
        if (typeof bootstrap !== 'undefined') {
            const modalInstance = new bootstrap.Modal(passwordExpiryModal, {
                backdrop: 'static',
                keyboard: false
            });
            modalInstance.show();
        }
        
        // Password Toggle Functionality for expiry modal
        document.querySelectorAll('#passwordExpiryModal .password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (passwordInput) {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                        this.setAttribute('aria-label', 'Hide password');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                        this.setAttribute('aria-label', 'Show password');
                    }
                }
            });
        });
        
        // Password Strength Checker for expiry modal
        const expiryNewPasswordInput = document.getElementById('expiry_new_password');
        const expiryStrengthBar = document.getElementById('expiryPasswordStrengthBar');
        const expiryStrengthLabel = document.getElementById('expiryStrengthLabel');
        const expiryRequirementItems = document.querySelectorAll('#expiryPasswordRequirements .requirement-item');
        
        if (expiryNewPasswordInput && expiryStrengthBar && expiryStrengthLabel) {
            function checkPasswordStrength(password) {
                const requirements = {
                    length: password.length >= 8,
                    lowercase: /[a-z]/.test(password),
                    uppercase: /[A-Z]/.test(password),
                    number: /[0-9]/.test(password),
                    symbol: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
                };
                
                // Update requirement items
                expiryRequirementItems.forEach(item => {
                    const requirement = item.getAttribute('data-requirement');
                    if (requirements[requirement]) {
                        item.classList.add('met');
                    } else {
                        item.classList.remove('met');
                    }
                });
                
                // Count met requirements
                const metCount = Object.values(requirements).filter(Boolean).length;
                
                // Calculate strength - simplified to 3 levels
                let strength = 'None';
                let strengthClass = '';
                
                if (password.length === 0) {
                    strength = 'None';
                    strengthClass = '';
                } else if (metCount < 3 || password.length < 8) {
                    strength = 'Weak';
                    strengthClass = 'weak';
                } else if (metCount < 5) {
                    strength = 'Normal';
                    strengthClass = 'normal';
                } else {
                    strength = 'Strong';
                    strengthClass = 'strong';
                }
                
                // Update strength bar
                expiryStrengthBar.className = 'password-strength-fill ' + strengthClass;
                expiryStrengthLabel.textContent = strength;
                
                // Update strength label color
                const strengthText = document.getElementById('expiryPasswordStrengthText');
                if (strengthText) {
                    const labelColor = strengthClass === 'strong' ? '#10b981' :
                                      strengthClass === 'normal' ? '#f59e0b' :
                                      strengthClass === 'weak' ? '#ef4444' : '#64748b';
                    strengthText.querySelector('small').style.color = labelColor;
                    expiryStrengthLabel.style.color = labelColor;
                }
            }
            
            expiryNewPasswordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });
            
            expiryNewPasswordInput.addEventListener('keyup', function() {
                checkPasswordStrength(this.value);
            });
            
            expiryNewPasswordInput.addEventListener('paste', function() {
                setTimeout(() => checkPasswordStrength(this.value), 10);
            });
            
            checkPasswordStrength(expiryNewPasswordInput.value || '');
        }
        
        // Password Match Checker for expiry modal
        const expiryConfirmPasswordInput = document.getElementById('expiry_confirm_password');
        const expiryMatchIndicator = document.getElementById('expiryPasswordMatchIndicator');
        const expiryMatchIcon = expiryMatchIndicator ? expiryMatchIndicator.querySelector('.match-icon') : null;
        const expiryMatchText = expiryMatchIndicator ? expiryMatchIndicator.querySelector('.match-text') : null;
        
        function checkPasswordMatch() {
            const newPassword = expiryNewPasswordInput ? expiryNewPasswordInput.value : '';
            const confirmPassword = expiryConfirmPasswordInput ? expiryConfirmPasswordInput.value : '';
            
            if (!expiryMatchIndicator || !expiryMatchIcon || !expiryMatchText) return;
            
            if (confirmPassword.length === 0) {
                expiryMatchIndicator.style.display = 'none';
                return;
            }
            
            expiryMatchIndicator.style.display = 'block';
            
            if (newPassword === confirmPassword && newPassword.length > 0) {
                expiryMatchIndicator.className = 'password-match-indicator match mt-2';
                expiryMatchText.textContent = 'Passwords match';
                if (expiryConfirmPasswordInput) {
                    expiryConfirmPasswordInput.setCustomValidity('');
                }
            } else {
                expiryMatchIndicator.className = 'password-match-indicator mismatch mt-2';
                expiryMatchText.textContent = 'Passwords do not match';
                if (expiryConfirmPasswordInput) {
                    expiryConfirmPasswordInput.setCustomValidity('Passwords do not match');
                }
            }
        }
        
        if (expiryNewPasswordInput && expiryConfirmPasswordInput) {
            expiryNewPasswordInput.addEventListener('input', checkPasswordMatch);
            expiryNewPasswordInput.addEventListener('keyup', checkPasswordMatch);
            expiryConfirmPasswordInput.addEventListener('input', checkPasswordMatch);
            expiryConfirmPasswordInput.addEventListener('keyup', checkPasswordMatch);
            expiryConfirmPasswordInput.addEventListener('paste', function() {
                setTimeout(checkPasswordMatch, 10);
            });
            checkPasswordMatch();
        }
        
        // Handle password change form submission
        const passwordExpiryForm = document.getElementById('passwordExpiryForm');
        const expiryChangePasswordBtn = document.getElementById('expiryChangePasswordBtn');
        const expiryAlertDiv = document.getElementById('passwordExpiryAlert');
        
        if (passwordExpiryForm && expiryChangePasswordBtn) {
            passwordExpiryForm.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Clear previous alerts
                if (expiryAlertDiv) {
                    expiryAlertDiv.innerHTML = '';
                }
                
                // Validate form
                if (!passwordExpiryForm.checkValidity()) {
                    passwordExpiryForm.reportValidity();
                    return;
                }
                
                // Get form values
                const currentPassword = document.getElementById('expiry_current_password').value;
                const newPassword = document.getElementById('expiry_new_password').value;
                const confirmPassword = document.getElementById('expiry_confirm_password').value;
                
                // Validate password requirements
                const passwordRequirements = {
                    length: newPassword.length >= 8,
                    lowercase: /[a-z]/.test(newPassword),
                    uppercase: /[A-Z]/.test(newPassword),
                    number: /[0-9]/.test(newPassword),
                    symbol: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(newPassword)
                };
                
                const missingRequirements = [];
                if (!passwordRequirements.length) missingRequirements.push('Minimum 8 characters');
                if (!passwordRequirements.lowercase) missingRequirements.push('Lowercase letter');
                if (!passwordRequirements.uppercase) missingRequirements.push('Uppercase letter');
                if (!passwordRequirements.number) missingRequirements.push('Number');
                if (!passwordRequirements.symbol) missingRequirements.push('Symbol');
                
                if (missingRequirements.length > 0) {
                    if (expiryAlertDiv) {
                        expiryAlertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Password must contain: ' + missingRequirements.join(', ') + '</div>';
                    }
                    return;
                }
                
                // Validate password match
                if (newPassword !== confirmPassword) {
                    if (expiryAlertDiv) {
                        expiryAlertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>New password and confirmation do not match</div>';
                    }
                    return;
                }
                
                // Disable submit button
                expiryChangePasswordBtn.disabled = true;
                expiryChangePasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Changing Password...';
                
                // Prepare form data
                const formData = new FormData();
                formData.append('action', 'change_password');
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
                formData.append('confirm_password', confirmPassword);
                
                // Submit via AJAX
                const formAction = passwordExpiryForm.getAttribute('action');
                
                fetch(formAction, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error('Server error: ' + response.status);
                        });
                    }
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error('Server returned non-JSON response');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    expiryChangePasswordBtn.disabled = false;
                    expiryChangePasswordBtn.innerHTML = '<i class="fas fa-key me-2"></i>Change Password';
                    
                    if (data && data.success) {
                        // Show success message
                        if (expiryAlertDiv) {
                            expiryAlertDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + (data.message || 'Password changed successfully') + '</div>';
                        }
                        
                        // Close modal and reload page after 1 second
                        setTimeout(() => {
                            if (passwordExpiryModal) {
                                passwordExpiryModal.style.display = 'none';
                                passwordExpiryModal.remove();
                            }
                            // Reload page to refresh session and remove expiry check
                            window.location.reload();
                        }, 1500);
                    } else {
                        const errorMsg = (data && data.message) ? data.message : 'Failed to change password. Please try again.';
                        if (expiryAlertDiv) {
                            expiryAlertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + errorMsg + '</div>';
                            expiryAlertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }
                })
                .catch(error => {
                    console.error('Change Password Error:', error);
                    expiryChangePasswordBtn.disabled = false;
                    expiryChangePasswordBtn.innerHTML = '<i class="fas fa-key me-2"></i>Change Password';
                    if (expiryAlertDiv) {
                        expiryAlertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>An error occurred while changing the password. Please try again.</div>';
                        expiryAlertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            });
        }
    }
})();
</script>
<?php endif; ?>
