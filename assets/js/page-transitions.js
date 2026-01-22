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
        window.addEventListener('popstate', (e) => {
            // Prevent default if we're handling it
            if (e.state) {
                this.handlePageLoad();
            } else {
                // If no state, do a full page reload to be safe
                window.location.reload();
            }
        });
        
        // Store initial page state on load
        this.storeInitialState();
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
        if (this.isTransitioning) {
            console.log('⏸️ Transition already in progress, skipping');
            return;
        }

        console.log('🚀 Starting page transition to:', url);
        this.isTransitioning = true;
        
        try {
            // Show loading state
            this.showPageLoading();
            
            // Fetch the new page
            console.log('📡 Fetching page content...');
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Page not found');
            }
            
            const html = await response.text();
            console.log('📥 Page content received (', html.length, 'chars)');
            
            // Parse the response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Extract only the content area (not the header)
            const newContentWrapper = doc.querySelector('.main-content');
            const newContent = newContentWrapper ? newContentWrapper.querySelector('.content') : null;
            const currentContent = document.querySelector('.content');
            
            if (!newContent) {
                console.error('❌ .content not found in fetched page');
                throw new Error('.content element not found');
            }
            
            if (!currentContent) {
                console.error('❌ .content not found in current page');
                throw new Error('.content element not found in current DOM');
            }
            
            // Extract page title from the fetched document
            let newTitle = doc.querySelector('title')?.textContent;
            
            // If title not found in head, try to extract from page content
            if (!newTitle) {
                const pageTitleElement = newContent?.querySelector('.page-title-main, .page-title h1, h1.page-title-main');
                if (pageTitleElement) {
                    const pageTitleText = pageTitleElement.textContent.trim();
                    // Extract role prefix from current document title
                    const currentTitle = document.title;
                    let rolePrefix = 'Super Admin';
                    if (currentTitle.includes('HR Admin')) {
                        rolePrefix = 'HR Admin';
                    } else if (currentTitle.includes('Accounting')) {
                        rolePrefix = 'Accounting';
                    } else if (currentTitle.includes('Operation')) {
                        rolePrefix = 'Operation';
                    } else if (currentTitle.includes('Employee Portal')) {
                        rolePrefix = 'Employee Portal';
                    }
                    newTitle = `${pageTitleText} - ${rolePrefix} - Golden Z-5 HR System`;
                } else {
                    // Fallback to current title
                    newTitle = document.title;
                }
            }
            
            // Extract page name from URL for state storage
            const urlObj = new URL(url, window.location.origin);
            const pageParam = urlObj.searchParams.get('page') || 'dashboard';
            
            // IMPORTANT: Inline scripts inside injected HTML do NOT execute when using innerHTML.
            // We must extract scripts, inject HTML without scripts, then execute them manually.
            console.log('🔍 Extracting content and scripts...');
            const extracted = this.extractContentAndScripts(newContent);
            console.log(`📦 Extracted: ${extracted.scripts.length} script(s), ${extracted.html.length} chars of HTML`);

            // Animate out current content
            await this.animateOut(currentContent);
            
            // Replace content
            console.log('🔄 Injecting new content into DOM...');
            currentContent.innerHTML = extracted.html;
            
            // Update document title
            document.title = newTitle;
            console.log('📝 Updated page title:', newTitle);
            
            // Update URL and store state for back/forward navigation
            const state = {
                url: url,
                title: newTitle,
                page: pageParam,
                content: extracted.html,
                scripts: extracted.scripts,
                timestamp: Date.now()
            };
            
            history.pushState(state, newTitle, url);
            console.log('📍 Updated URL and history state');
            
            // Animate in new content
            await this.animateIn(currentContent);
            
            // Execute extracted page scripts AFTER DOM is in place
            console.log('⚡ Executing page scripts...');
            this.executeScripts(extracted.scripts);

            // Update page header title and subtitle
            this.updatePageHeader(pageParam);

            // Reinitialize page-specific JavaScript
            console.log('🔄 Reinitializing page scripts...');
            this.reinitializePageScripts({ page: pageParam, url });
            
            console.log('✅ Page transition completed successfully');
            
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

    async handlePageLoad() {
        // This is called when back/forward buttons are used
        // We need to restore the content from the URL
        
        const url = window.location.href;
        const state = history.state;
        
        // If we have stored state with content, use it (faster)
        if (state && state.content && state.timestamp) {
            // Check if state is recent (less than 5 minutes old)
            const stateAge = Date.now() - state.timestamp;
            if (stateAge < 300000) { // 5 minutes
                const currentContent = document.querySelector('.content');
                if (currentContent) {
                    // Restore content from state
                    currentContent.innerHTML = state.content;
                    document.title = state.title || document.title;
                    // Update page header
                    this.updatePageHeader(state.page || 'dashboard');
                    // Execute scripts stored in state (needed for injected pages)
                    if (Array.isArray(state.scripts) && state.scripts.length) {
                        this.executeScripts(state.scripts);
                    }
                    this.reinitializePageScripts({ page: state.page, url: state.url || url });
                    return;
                }
            }
        }
        
        // Otherwise, fetch the page fresh
        try {
            this.showPageLoading();
            
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Page not found');
            }
            
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            const newContentWrapper = doc.querySelector('.main-content');
            const newContent = newContentWrapper ? newContentWrapper.querySelector('.content') : null;
            const currentContent = document.querySelector('.content');
            const newTitle = doc.querySelector('title')?.textContent || document.title;
            
            if (newContent && currentContent) {
                const extracted = this.extractContentAndScripts(newContent);
                currentContent.innerHTML = extracted.html;
                document.title = newTitle;
                
                // Update state with new content
                const urlObj = new URL(url, window.location.origin);
                const pageParam = urlObj.searchParams.get('page') || 'dashboard';
                
                const newState = {
                    url: url,
                    title: newTitle,
                    page: pageParam,
                    content: extracted.html,
                    scripts: extracted.scripts,
                    timestamp: Date.now()
                };
                
                // Replace current state with updated one
                history.replaceState(newState, newTitle, url);
                
                // Update page header
                this.updatePageHeader(pageParam);
                
                this.executeScripts(extracted.scripts);
                this.reinitializePageScripts({ page: pageParam, url });
            }
        } catch (error) {
            console.error('Failed to restore page:', error);
            // Fallback to full page reload
            window.location.href = url;
        } finally {
            this.hidePageLoading();
        }
    }

    reinitializePageScripts(detail = {}) {
        // Reinitialize any page-specific JavaScript
        // Keep backward compatibility with existing listeners
        document.dispatchEvent(new CustomEvent('pageLoaded', { detail }));
        // New event: richer semantic name for SPA page swaps
        document.dispatchEvent(new CustomEvent('pageContentLoaded', { detail }));
        
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

    updatePageHeader(page) {
        // Page titles mapping
        const pageTitles = {
            'dashboard': 'Dashboard',
            'employees': 'Employee Management',
            'add_employee': 'Add New Employee',
            'add_employee_page2': 'Add New Employee - Page 2',
            'edit_employee': 'Edit Employee',
            'view_employee': 'View Employee',
            'dtr': 'Daily Time Record',
            'timeoff': 'Time Off Management',
            'checklist': 'Employee Checklist',
            'hiring': 'Hiring Process',
            'onboarding': 'Employee Onboarding',
            'handbook': 'Hiring Handbook',
            'alerts': 'Employee Alerts',
            'add_alert': 'Add New Alert',
            'tasks': 'Tasks',
            'posts': 'Posts & Locations',
            'add_post': 'Add New Post',
            'edit_post': 'Edit Post',
            'post_assignments': 'Post Assignments',
            'settings': 'System Settings',
            'profile': 'My Profile',
            'integrations': 'Integrations',
            'help': 'Help & Support',
            'system_logs': 'System Logs',
            'documents': '201 Files - Document Management',
            'leaves': 'Leave Requests',
            'leave_balance': 'Leave Balance',
            'leave_reports': 'Leave Reports',
            'attendance': 'Attendance Management',
            'violations': 'Employee Violations',
            'violation_types': 'Violation Types & Sanctions'
        };

        // Page subtitles mapping
        const pageSubtitles = {
            'dashboard': 'Overview of your HR management system',
            'employees': 'Manage employee information and records',
            'posts': 'Manage posts, locations, and assignments',
            'post_assignments': 'Assign employees to specific posts',
            'alerts': 'View and manage employee alerts',
            'tasks': 'Manage your tasks and assignments',
            'settings': 'Configure system settings and preferences',
            'profile': 'View and edit your profile information',
            'system_logs': 'View system activity and audit logs',
            'users': 'Manage system users and permissions',
            'teams': 'Manage teams and departments',
            'add_employee': 'Add a new employee to the system',
            'edit_employee': 'Edit employee information',
            'view_employee': 'View employee details',
            'add_post': 'Create a new post location',
            'edit_post': 'Edit post information',
            'add_alert': 'Create a new employee alert',
            'help': 'Get help and support for the HR system',
            'integrations': 'Manage third-party integrations',
            'dtr': 'Track daily time and attendance records',
            'timeoff': 'Manage time off requests and approvals',
            'checklist': 'View and manage employee checklists',
            'hiring': 'Manage the recruitment and hiring process',
            'onboarding': 'Manage employee onboarding procedures',
            'handbook': 'Access the employee handbook and policies',
            'documents': 'Manage employee 201 files and documents',
            'leaves': 'View and manage leave requests',
            'leave_balance': 'View employee leave balances',
            'leave_reports': 'Generate leave reports and analytics',
            'attendance': 'Track and manage employee attendance',
            'violations': 'View and manage employee violations',
            'violation_types': 'Manage violation categories and sanctions'
        };

        // Get title and subtitle
        const title = pageTitles[page] || 'Dashboard';
        const subtitle = pageSubtitles[page] || 'Manage your HR operations';

        // Update header title
        const titleElement = document.querySelector('.hrdash-welcome__title');
        if (titleElement) {
            titleElement.textContent = title;
            console.log(`📝 Updated page title to: ${title}`);
        }

        // Update header subtitle
        const subtitleElement = document.querySelector('.hrdash-welcome__subtitle');
        if (subtitleElement) {
            subtitleElement.textContent = subtitle;
            console.log(`📝 Updated page subtitle to: ${subtitle}`);
        }

        // Update browser title
        document.title = title;
    }

    /**
     * Extract HTML content and <script> blocks from a container.
     * Returns { html, scripts } where scripts is an array like:
     *  - { src: string } OR { code: string }
     */
    extractContentAndScripts(containerEl) {
        const clone = containerEl.cloneNode(true);
        const scripts = [];

        // Extract script tags
        clone.querySelectorAll('script').forEach((s) => {
            const src = s.getAttribute('src');
            if (src) {
                scripts.push({ src });
            } else {
                const code = s.textContent || s.innerHTML || '';
                if (code.trim()) {
                    scripts.push({ code });
                }
            }
            s.remove();
        });

        console.log(`📋 Found ${scripts.length} script tag(s) in page content`);
        return { html: clone.innerHTML, scripts };
    }

    /**
     * Execute extracted scripts. Inline scripts are executed by creating a new
     * <script> element and injecting it into the DOM, then removing it.
     */
    executeScripts(scripts = []) {
        if (!Array.isArray(scripts) || scripts.length === 0) {
            console.log('📜 No scripts to execute');
            return;
        }

        console.log(`📜 Executing ${scripts.length} script(s) from loaded page...`);
        
        scripts.forEach((s, index) => {
            try {
                const scriptEl = document.createElement('script');
                scriptEl.type = 'text/javascript';

                if (s.src) {
                    console.log(`📜 Loading external script ${index + 1}:`, s.src);
                    scriptEl.src = s.src;
                    scriptEl.async = false;
                    scriptEl.onload = () => console.log(`✅ External script loaded:`, s.src);
                    scriptEl.onerror = () => console.error(`❌ Failed to load external script:`, s.src);
                    document.head.appendChild(scriptEl);
                    // keep external scripts in head
                } else if (s.code) {
                    console.log(`📜 Executing inline script ${index + 1} (${s.code.length} chars)`);
                    scriptEl.text = s.code;
                    document.body.appendChild(scriptEl);
                    // Script executes when appended to DOM
                    scriptEl.remove();
                    console.log(`✅ Inline script ${index + 1} executed`);
                }
            } catch (err) {
                console.error(`❌ Failed to execute page script ${index + 1}:`, err);
            }
        });
        
        console.log('📜 All scripts execution completed');
    }
    
    storeInitialState() {
        // Store the initial page state for back/forward navigation
        const currentContent = document.querySelector('.content');
        if (currentContent) {
            const url = window.location.href;
            const urlObj = new URL(url, window.location.origin);
            const pageParam = urlObj.searchParams.get('page') || 'dashboard';
            
            const state = {
                url: url,
                title: document.title,
                page: pageParam,
                content: currentContent.innerHTML,
                timestamp: Date.now()
            };
            
            // Replace the current history entry with our state
            history.replaceState(state, document.title, url);
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
let pageTransitionManager;

// Initialize immediately if DOM is already ready, otherwise wait
function initializePageTransitions() {
    pageTransitionManager = new PageTransitionManager();
    // Expose for other scripts (e.g., sidebar nav) to call explicitly
    window.pageTransitionManager = pageTransitionManager;
    console.log('✅ PageTransitionManager initialized and exposed as window.pageTransitionManager');
    new SmoothScrollManager();
    new ContentAnimationManager();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePageTransitions);
} else {
    // DOM already ready, initialize immediately
    initializePageTransitions();
}

// Export utility function for updating page title programmatically
window.updatePageTitle = function(newTitle) {
    if (newTitle) {
        document.title = newTitle;
        // Update history state if it exists
        if (history.state) {
            const updatedState = {
                ...history.state,
                title: newTitle,
                timestamp: Date.now()
            };
            history.replaceState(updatedState, newTitle, window.location.href);
        }
    }
};

// Export utility function for getting page title from current page
window.getPageTitleFromContent = function() {
    // Try to get title from page title element
    const pageTitleElement = document.querySelector('.page-title-main, .page-title h1, h1.page-title-main');
    if (pageTitleElement) {
        return pageTitleElement.textContent.trim();
    }
    
    // Fallback to document title
    return document.title;
};

// Listen for pageLoaded event to ensure title is updated
document.addEventListener('pageLoaded', function() {
    // Small delay to ensure DOM is updated
    setTimeout(() => {
        const pageTitle = window.getPageTitleFromContent();
        if (pageTitle && pageTitle !== document.title) {
            // Extract base title format (e.g., "User Management - Super Admin - Golden Z-5 HR System")
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page') || 'dashboard';
            
            // Try to match the title format from header
            const titleMap = {
                'dashboard': 'Dashboard',
                'employees': 'Employee Management',
                'users': 'User Management',
                'settings': 'System Settings',
                'posts': 'Posts & Locations',
                'alerts': 'Employee Alerts',
                'help': 'Help & Support',
                'integrations': 'Integrations',
                'teams': 'Teams',
                'dtr': 'Daily Time Record',
                'timeoff': 'Time Off Management',
                'checklist': 'Employee Checklist',
                'hiring': 'Hiring Process',
                'onboarding': 'Employee Onboarding',
                'handbook': 'Hiring Handbook',
                'add_employee': 'Add New Employee',
                'add_post': 'Add New Post',
                'add_alert': 'Add New Alert',
                'post_assignments': 'Post Assignments'
            };
            
            const pageTitleName = titleMap[page] || page.charAt(0).toUpperCase() + page.slice(1).replace(/_/g, ' ');
            
            // Determine role prefix from current URL or document title
            let rolePrefix = 'Super Admin';
            if (document.title.includes('HR Admin')) {
                rolePrefix = 'HR Admin';
            } else if (document.title.includes('Accounting')) {
                rolePrefix = 'Accounting';
            } else if (document.title.includes('Operation')) {
                rolePrefix = 'Operation';
            } else if (document.title.includes('Employee Portal')) {
                rolePrefix = 'Employee Portal';
            }
            
            const fullTitle = `${pageTitleName} - ${rolePrefix} - Golden Z-5 HR System`;
            window.updatePageTitle(fullTitle);
        }
    }, 100);
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
