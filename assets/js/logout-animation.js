/**
 * Logout Animation Handler - Production-Ready Implementation
 * Golden Z-5 HR System
 * 
 * Features:
 * - Smooth 60fps animations
 * - Accessible (keyboard-safe, screen-reader friendly)
 * - Production-safe with proper error handling
 * - Non-blocking with automatic fallback
 * - Clean state management
 * 
 * @version 1.0.0
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        ANIMATION_DURATION: 1200, // Total duration before logout
        MIN_DISPLAY_TIME: 800,    // Minimum time to show message
        MAX_DELAY: 1500,          // Maximum delay before forcing logout
        PARTICLE_COUNT: 7,        // Number of decorative particles
        MESSAGES: {
            main: "Logging out...",
            sub: "Please wait while we sign you out.",
            srAnnouncement: "Logout in progress. You will be logged out shortly."
        }
    };
    
    // State management
    let isAnimating = false;
    let overlayElement = null;
    let originalLogoutUrl = null;
    let animationStartTime = null;
    
    /**
     * Create the logout overlay DOM structure
     * @returns {HTMLElement} The overlay element
     */
    function createLogoutOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'logout-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'logout-message');
        overlay.setAttribute('aria-describedby', 'logout-description');
        overlay.setAttribute('tabindex', '-1');
        
        // Screen reader announcement
        const srAnnouncement = document.createElement('div');
        srAnnouncement.className = 'logout-sr-only';
        srAnnouncement.setAttribute('role', 'status');
        srAnnouncement.setAttribute('aria-live', 'polite');
        srAnnouncement.textContent = CONFIG.MESSAGES.srAnnouncement;
        
        // Particles container
        const particlesContainer = document.createElement('div');
        particlesContainer.className = 'logout-particles';
        particlesContainer.setAttribute('aria-hidden', 'true');
        
        // Create particles
        for (let i = 0; i < CONFIG.PARTICLE_COUNT; i++) {
            const particle = document.createElement('div');
            particle.className = 'logout-particle';
            particlesContainer.appendChild(particle);
        }
        
        // Message card
        const messageCard = document.createElement('div');
        messageCard.className = 'logout-message-card';
        
        // Main message
        const messageText = document.createElement('h2');
        messageText.className = 'logout-message-text';
        messageText.id = 'logout-message';
        messageText.textContent = CONFIG.MESSAGES.main;
        
        // Subtext
        const subtext = document.createElement('p');
        subtext.className = 'logout-message-subtext';
        subtext.id = 'logout-description';
        subtext.textContent = CONFIG.MESSAGES.sub;
        
        // Progress bar
        const progressContainer = document.createElement('div');
        progressContainer.className = 'logout-progress';
        progressContainer.setAttribute('role', 'progressbar');
        progressContainer.setAttribute('aria-valuenow', '0');
        progressContainer.setAttribute('aria-valuemin', '0');
        progressContainer.setAttribute('aria-valuemax', '100');
        progressContainer.setAttribute('aria-label', 'Logout progress');
        
        const progressBar = document.createElement('div');
        progressBar.className = 'logout-progress-bar';
        progressContainer.appendChild(progressBar);
        
        // Assemble the card
        messageCard.appendChild(messageText);
        messageCard.appendChild(subtext);
        messageCard.appendChild(progressContainer);
        
        // Assemble the overlay
        overlay.appendChild(srAnnouncement);
        overlay.appendChild(particlesContainer);
        overlay.appendChild(messageCard);
        
        return overlay;
    }
    
    /**
     * Handle keyboard navigation during animation
     * @param {KeyboardEvent} event - The keyboard event
     */
    function handleKeyboard(event) {
        // Allow Escape to skip animation immediately
        if (event.key === 'Escape' && isAnimating) {
            event.preventDefault();
            event.stopPropagation();
            completeLogout(true); // Force immediate logout
        }
        
        // Trap focus within overlay
        if (event.key === 'Tab') {
            event.preventDefault();
            // Since overlay has no interactive elements, keep focus on overlay itself
            if (overlayElement) {
                overlayElement.focus();
            }
        }
    }
    
    /**
     * Start the logout animation
     * @param {string} logoutUrl - The URL to redirect to after animation
     */
    function startLogoutAnimation(logoutUrl) {
        // Prevent multiple simultaneous animations
        if (isAnimating) {
            return;
        }
        
        isAnimating = true;
        originalLogoutUrl = logoutUrl;
        animationStartTime = Date.now();
        
        // Create and inject overlay
        overlayElement = createLogoutOverlay();
        document.body.appendChild(overlayElement);
        
        // Force reflow to ensure transition works
        overlayElement.offsetHeight;
        
        // Activate overlay (triggers CSS transition)
        requestAnimationFrame(() => {
            overlayElement.classList.add('active');
            overlayElement.focus(); // Focus for accessibility
        });
        
        // Add keyboard handler
        document.addEventListener('keydown', handleKeyboard, true);
        
        // Update progress bar ARIA value
        const progressBar = overlayElement.querySelector('.logout-progress');
        if (progressBar) {
            updateProgressAria(progressBar);
        }
        
        // Schedule logout completion
        setTimeout(() => {
            completeLogout(false);
        }, CONFIG.ANIMATION_DURATION);
        
        // Failsafe: Force logout if animation hangs
        setTimeout(() => {
            if (isAnimating) {
                console.warn('[Logout Animation] Failsafe triggered - forcing logout');
                completeLogout(true);
            }
        }, CONFIG.MAX_DELAY);
    }
    
    /**
     * Update progress bar ARIA attributes for accessibility
     * @param {HTMLElement} progressBar - The progress bar element
     */
    function updateProgressAria(progressBar) {
        const interval = 100; // Update every 100ms
        const totalSteps = CONFIG.ANIMATION_DURATION / interval;
        let currentStep = 0;
        
        const updateInterval = setInterval(() => {
            if (!isAnimating) {
                clearInterval(updateInterval);
                return;
            }
            
            currentStep++;
            const percentage = Math.min(100, Math.round((currentStep / totalSteps) * 100));
            progressBar.setAttribute('aria-valuenow', percentage.toString());
            
            if (currentStep >= totalSteps) {
                clearInterval(updateInterval);
            }
        }, interval);
    }
    
    /**
     * Complete the logout process
     * @param {boolean} immediate - Whether to skip animation cleanup
     */
    function completeLogout(immediate) {
        if (!isAnimating) {
            return;
        }
        
        // Remove keyboard handler
        document.removeEventListener('keydown', handleKeyboard, true);
        
        // Cleanup function
        const cleanup = () => {
            if (overlayElement && overlayElement.parentNode) {
                overlayElement.parentNode.removeChild(overlayElement);
            }
            overlayElement = null;
            isAnimating = false;
            
            // Redirect to logout URL
            if (originalLogoutUrl) {
                window.location.href = originalLogoutUrl;
            }
        };
        
        if (immediate) {
            // Skip animation, logout immediately
            cleanup();
        } else {
            // Ensure minimum display time has passed
            const elapsedTime = Date.now() - animationStartTime;
            const remainingTime = Math.max(0, CONFIG.MIN_DISPLAY_TIME - elapsedTime);
            
            setTimeout(() => {
                // Fade out overlay
                if (overlayElement) {
                    overlayElement.classList.remove('active');
                }
                
                // Wait for fade out transition, then cleanup
                setTimeout(cleanup, 300);
            }, remainingTime);
        }
    }
    
    /**
     * Initialize logout animation on all logout links
     */
    function initLogoutAnimation() {
        // Use event delegation for better performance and dynamic content support
        document.addEventListener('click', function(event) {
            // Find logout link in event path
            const logoutLink = event.target.closest('a[href*="logout"]');
            
            if (!logoutLink) {
                return;
            }
            
            // Skip if animation is already running
            if (isAnimating) {
                event.preventDefault();
                return;
            }
            
            // Check if link has data-no-transition attribute (skip animation)
            if (logoutLink.hasAttribute('data-no-animation') || 
                logoutLink.hasAttribute('data-skip-animation')) {
                // Allow default logout behavior
                return;
            }
            
            // Prevent default navigation
            event.preventDefault();
            
            // Get logout URL
            const logoutUrl = logoutLink.getAttribute('href');
            
            // Start animation
            startLogoutAnimation(logoutUrl);
        }, false);
        
        console.log('✨ Logout animation initialized');
    }
    
    /**
     * Check for reduced motion preference
     * @returns {boolean} Whether reduced motion is preferred
     */
    function prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }
    
    /**
     * Auto-initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLogoutAnimation);
    } else {
        initLogoutAnimation();
    }
    
    // Re-initialize after page transitions (if using AJAX navigation)
    document.addEventListener('pageLoaded', initLogoutAnimation);
    
    // Expose API for manual control if needed
    window.GoldenzLogoutAnimation = {
        start: startLogoutAnimation,
        isActive: () => isAnimating,
        config: CONFIG
    };
    
})();
