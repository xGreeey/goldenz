/**
 * Seamless Page Transitions
 * Provides smooth transitions between pages and loading states
 */

class PageTransitionManager {
    constructor() {
        this.isTransitioning = false;
        this.transitionDuration = 300;
        this.init();
    }

    init() {
        this.bindEvents();
        this.addTransitionClasses();
    }

    bindEvents() {
        // Handle navigation clicks
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (link && this.shouldTransition(link)) {
                e.preventDefault();
                this.transitionToPage(link.href);
            }
        });

        // Handle form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.tagName === 'FORM') {
                this.showLoadingState(e.target);
            }
        });

        // Handle back/forward navigation
        window.addEventListener('popstate', () => {
            this.handlePageLoad();
        });
    }

    shouldTransition(link) {
        // Only transition for internal links
        const href = link.getAttribute('href');
        return href && 
               !href.startsWith('http') && 
               !href.startsWith('mailto:') && 
               !href.startsWith('tel:') &&
               !href.startsWith('#') &&
               !link.hasAttribute('data-no-transition');
    }

    async transitionToPage(url) {
        if (this.isTransitioning) return;

        this.isTransitioning = true;
        
        try {
            // Show loading state
            this.showPageLoading();
            
            // Fetch the new page
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Page not found');
            }
            
            const html = await response.text();
            
            // Parse the response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Extract the main content
            const newContent = doc.querySelector('.main-content');
            const currentContent = document.querySelector('.main-content');
            
            if (newContent && currentContent) {
                // Animate out current content
                await this.animateOut(currentContent);
                
                // Replace content
                currentContent.innerHTML = newContent.innerHTML;
                
                // Animate in new content
                await this.animateIn(currentContent);
                
                // Update URL without page reload
                history.pushState({}, '', url);
                
                // Reinitialize page-specific JavaScript
                this.reinitializePageScripts();
            }
            
        } catch (error) {
            console.error('Page transition failed:', error);
            // Fallback to normal navigation
            window.location.href = url;
        } finally {
            this.hidePageLoading();
            this.isTransitioning = false;
        }
    }

    showPageLoading() {
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.add('loading');
            
            // Add loading spinner
            const spinner = document.createElement('div');
            spinner.className = 'page-loading-spinner';
            spinner.innerHTML = `
                <div class="loading-spinner"></div>
                <span>Loading...</span>
            `;
            mainContent.appendChild(spinner);
        }
    }

    hidePageLoading() {
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.remove('loading');
            
            // Remove loading spinner
            const spinner = mainContent.querySelector('.page-loading-spinner');
            if (spinner) {
                spinner.remove();
            }
        }
    }

    async animateOut(element) {
        return new Promise((resolve) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(-20px)';
            element.style.transition = 'all 0.3s ease';
            
            setTimeout(resolve, this.transitionDuration);
        });
    }

    async animateIn(element) {
        return new Promise((resolve) => {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
            element.style.transition = 'all 0.4s ease';
            
            setTimeout(resolve, this.transitionDuration);
        });
    }

    addTransitionClasses() {
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.add('page-enter');
        }
    }

    handlePageLoad() {
        // Reinitialize page-specific functionality
        this.reinitializePageScripts();
    }

    reinitializePageScripts() {
        // Reinitialize any page-specific JavaScript
        const event = new CustomEvent('pageLoaded');
        document.dispatchEvent(event);
        
        // Reinitialize common components
        this.initializeCommonComponents();
    }

    initializeCommonComponents() {
        // Reinitialize tooltips, dropdowns, etc.
        if (typeof bootstrap !== 'undefined') {
            // Reinitialize Bootstrap components
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }

    showLoadingState(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="loading-spinner"></div> Processing...';
            submitBtn.disabled = true;
            
            // Reset after 3 seconds (fallback)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        }
    }
}

// Smooth scrolling for anchor links
class SmoothScrollManager {
    constructor() {
        this.init();
    }

    init() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="#"]');
            if (link) {
                e.preventDefault();
                this.smoothScrollTo(link.getAttribute('href'));
            }
        });
    }

    smoothScrollTo(target) {
        const element = document.querySelector(target);
        if (element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }
}

// Content fade-in animations
class ContentAnimationManager {
    constructor() {
        this.init();
    }

    init() {
        this.observeElements();
        document.addEventListener('pageLoaded', () => {
            this.observeElements();
        });
    }

    observeElements() {
        const elements = document.querySelectorAll('.card, .summary-card, .table-container');
        
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
}

// Initialize all managers when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PageTransitionManager();
    new SmoothScrollManager();
    new ContentAnimationManager();
});

// Add CSS for loading spinner
const style = document.createElement('style');
style.textContent = `
    .page-loading-spinner {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        z-index: 1000;
    }
    
    .page-loading-spinner span {
        color: var(--interface-text);
        font-weight: 500;
    }
    
    .main-content.loading {
        position: relative;
    }
    
    .main-content.loading::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        z-index: 999;
    }
`;
document.head.appendChild(style);
