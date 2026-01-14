        </main>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include_once __DIR__ . '/paths.php'; ?>
    <script src="<?php echo asset_url('js/app.js'); ?>"></script>
    <script src="<?php echo asset_url('js/page-transitions.js'); ?>"></script>
    <script src="<?php echo asset_url('js/comprehensive-functionality.js'); ?>"></script>
    
    <script>
    // Enhanced Sidebar Navigation System
    class SidebarNavigation {
        constructor() {
            this.sidebar = document.getElementById('sidebar');
            this.sidebarToggle = document.getElementById('sidebarToggle');
            this.sidebarOverlay = document.getElementById('sidebarOverlay');
            this.sidebarMenu = document.getElementById('sidebarMenu');
            
            this.storageKey = 'goldenz_sidebar_state';
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.loadState();
            this.autoExpandActiveSections();
            this.setupKeyboardNavigation();
        }
        
        bindEvents() {
            // Toggle button
            if (this.sidebarToggle) {
                this.sidebarToggle.addEventListener('click', () => this.toggleSidebar());
            }
            
            // Overlay click to close
            if (this.sidebarOverlay) {
                this.sidebarOverlay.addEventListener('click', () => this.closeSidebar());
            }
            
            // Navigation toggles
            document.querySelectorAll('.nav-toggle').forEach(toggle => {
                toggle.addEventListener('click', (e) => this.toggleSection(e));
                toggle.addEventListener('keydown', (e) => this.handleToggleKeydown(e));
            });
            
            // Navigation links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', (e) => this.handleNavClick(e));
            });
            
            // Window resize
            window.addEventListener('resize', () => this.handleResize());
            
            // Escape key to close all sections
            document.addEventListener('keydown', (e) => this.handleGlobalKeydown(e));
        }
        
        toggleSidebar() {
            const isOpen = this.sidebar.classList.contains('show');
            
            if (isOpen) {
                this.closeSidebar();
            } else {
                this.openSidebar();
            }
            
            // Dispatch custom event
            this.dispatchEvent('panel:toggle', { isOpen: !isOpen });
        }
        
        openSidebar() {
            this.sidebar.classList.add('show');
            this.sidebarOverlay.classList.add('show');
            this.sidebarToggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('sidebar-open');
            
            // Focus first focusable element
            const firstFocusable = this.sidebar.querySelector('button, a, input');
            if (firstFocusable) {
                firstFocusable.focus();
            }
            
            this.dispatchEvent('panel:open');
        }
        
        closeSidebar() {
            this.sidebar.classList.remove('show');
            this.sidebarOverlay.classList.remove('show');
            this.sidebarToggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('sidebar-open');
            
            this.dispatchEvent('panel:close');
        }
        
        toggleSection(event) {
            event.preventDefault();
            const toggle = event.currentTarget;
            const targetId = toggle.getAttribute('data-target');
            const submenu = document.getElementById(targetId);
            const arrow = toggle.querySelector('.nav-arrow');
            const isExpanded = submenu.classList.contains('expanded');
            
            if (isExpanded) {
                this.closeSection(toggle, submenu, arrow);
            } else {
                this.openSection(toggle, submenu, arrow);
            }
            
            this.saveState();
        }
        
        openSection(toggle, submenu, arrow) {
            submenu.classList.add('expanded');
            arrow.classList.add('rotated');
            toggle.setAttribute('aria-expanded', 'true');
            
            // Dispatch custom event
            this.dispatchEvent('section:open', { 
                section: toggle.closest('.nav-section').dataset.section 
            });
        }
        
        closeSection(toggle, submenu, arrow) {
            submenu.classList.remove('expanded');
            arrow.classList.remove('rotated');
            toggle.setAttribute('aria-expanded', 'false');
            
            // Dispatch custom event
            this.dispatchEvent('section:close', { 
                section: toggle.closest('.nav-section').dataset.section 
            });
        }
        
        closeAllSections() {
            document.querySelectorAll('.nav-submenu.expanded').forEach(submenu => {
                const toggle = document.querySelector(`[data-target="${submenu.id}"]`);
                const arrow = toggle.querySelector('.nav-arrow');
                this.closeSection(toggle, submenu, arrow);
            });
            
            this.dispatchEvent('sections:closeAll');
        }
        
        
        autoExpandActiveSections() {
            const activeLinks = document.querySelectorAll('.nav-link.active');
            activeLinks.forEach(link => {
                const submenu = link.closest('.nav-submenu');
                if (submenu) {
                    const toggle = document.querySelector(`[data-target="${submenu.id}"]`);
                    const arrow = toggle.querySelector('.nav-arrow');
                    this.openSection(toggle, submenu, arrow);
                }
            });
        }

        /**
         * Central helper to ensure ONLY ONE sidebar link is active at a time.
         * Clears all existing .nav-link.active, then activates the given link.
         */
        setActiveLink(link) {
            if (!link) return;

            // Remove active from all sidebar links (top-level + submenus + bottom)
            document.querySelectorAll('.nav-link.active').forEach(l => {
                l.classList.remove('active');
            });

            // Activate the specific link
            link.classList.add('active');

            // If it's inside a submenu, make sure its parent section is expanded
            const submenu = link.closest('.nav-submenu');
            if (submenu) {
                const toggle = document.querySelector(`[data-target="${submenu.id}"]`);
                const arrow = toggle ? toggle.querySelector('.nav-arrow') : null;
                if (toggle && arrow) {
                    this.openSection(toggle, submenu, arrow);
                }
            }
        }

        /**
         * Update active sidebar link based on current URL (?page=...)
         * This is used after AJAX page transitions so the blue highlight
         * moves immediately to the newly selected page without full reload.
         */
        updateActiveLinks() {
            const url = new URL(window.location.href);
            const currentPage = url.searchParams.get('page') || 'dashboard';

            // Find link matching current page
            const targetLink = document.querySelector(`.nav-link[data-page="${currentPage}"]`);
            this.setActiveLink(targetLink);
        }
        
        setupKeyboardNavigation() {
            // Arrow key navigation within submenus
            document.querySelectorAll('.nav-submenu').forEach(submenu => {
                const links = submenu.querySelectorAll('.nav-link');
                
                links.forEach((link, index) => {
                    link.addEventListener('keydown', (e) => {
                        switch(e.key) {
                            case 'ArrowDown':
                                e.preventDefault();
                                const nextLink = links[index + 1] || links[0];
                                nextLink.focus();
                                break;
                            case 'ArrowUp':
                                e.preventDefault();
                                const prevLink = links[index - 1] || links[links.length - 1];
                                prevLink.focus();
                                break;
                            case 'Home':
                                e.preventDefault();
                                links[0].focus();
                                break;
                            case 'End':
                                e.preventDefault();
                                links[links.length - 1].focus();
                                break;
                        }
                    });
                });
            });
        }
        
        handleToggleKeydown(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.toggleSection(event);
            }
        }
        
        handleGlobalKeydown(event) {
            if (event.key === 'Escape') {
                this.closeAllSections();
                
                // Close sidebar on mobile
                if (window.innerWidth < 768) {
                    this.closeSidebar();
                }
            }
        }
        
        /**
         * Handle click on any sidebar navigation link.
         * We optimistically update the active state IMMEDIATELY based on
         * the clicked link's data-page attribute, so the blue highlight
         * responds instantly without waiting for AJAX/pageLoaded.
         */
        handleNavClick(event) {
            const link = event.currentTarget;

            // Optimistically set active state based on clicked link,
            // using the shared helper so only ONE link is active.
            if (link && link.classList.contains('nav-link')) {
                this.setActiveLink(link);
            }
            
            // Close sidebar on mobile after navigation
            if (window.innerWidth < 768) {
                this.closeSidebar();
            }
        }
        
        handleResize() {
            // Close sidebar on desktop
            if (window.innerWidth >= 768) {
                this.closeSidebar();
            }
        }
        
        saveState() {
            const state = {
                expandedSections: []
            };
            
            document.querySelectorAll('.nav-submenu.expanded').forEach(submenu => {
                state.expandedSections.push(submenu.id);
            });
            
            localStorage.setItem(this.storageKey, JSON.stringify(state));
        }
        
        loadState() {
            try {
                const savedState = localStorage.getItem(this.storageKey);
                if (savedState) {
                    const state = JSON.parse(savedState);
                    
                    state.expandedSections.forEach(sectionId => {
                        const submenu = document.getElementById(sectionId);
                        if (submenu) {
                            const toggle = document.querySelector(`[data-target="${sectionId}"]`);
                            const arrow = toggle.querySelector('.nav-arrow');
                            this.openSection(toggle, submenu, arrow);
                        }
                    });
                }
            } catch (error) {
                console.warn('Failed to load sidebar state:', error);
            }
        }
        
        dispatchEvent(eventName, detail = {}) {
            const event = new CustomEvent(eventName, {
                detail,
                bubbles: true,
                cancelable: true
            });
            document.dispatchEvent(event);
        }
        
        // Public API methods
        addSection(sectionData) {
            // Method to dynamically add new sections
            console.log('Adding new section:', sectionData);
        }
        
        removeSection(sectionId) {
            // Method to remove sections
            console.log('Removing section:', sectionId);
        }
        
        updateBadge(sectionId, count) {
            // Method to update badge counts
            const link = document.querySelector(`[data-page="${sectionId}"]`);
            if (link) {
                let badge = link.querySelector('.nav-badge');
                if (count > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'nav-badge';
                        link.appendChild(badge);
                    }
                    badge.textContent = count;
                    badge.setAttribute('aria-label', `${count} items`);
                } else if (badge) {
                    badge.remove();
                }
            }
        }
    }
    
    // Security features
    // Right-click disabled - commented out to allow developer tools access
    // document.addEventListener('contextmenu', function(e) {
    //     e.preventDefault();
    //     showAlert('Right-click disabled for security', 'warning');
    // });
    
    // Disable F12 and dev tools
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F12' || 
            (e.ctrlKey && e.shiftKey && e.key === 'I') ||
            (e.ctrlKey && e.shiftKey && e.key === 'C')) {
            e.preventDefault();
            showAlert('Developer tools disabled for security', 'warning');
        }
    });
    
    // Show alert
    function showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
    
    // Auto-save functionality (optional)
    let autoSaveTimer;
    const autoSaveInterval = 5 * 60 * 1000; // 5 minutes
    
    function autoSave() {
        // Auto-save functionality can be implemented here
        console.log('Auto-save triggered');
    }
    
    // Set up auto-save
    autoSaveTimer = setInterval(autoSave, autoSaveInterval);
    
    // Initialize sidebar navigation when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        window.sidebarNav = new SidebarNavigation();
        
        // Ensure correct active state on initial load
        if (window.sidebarNav && typeof window.sidebarNav.updateActiveLinks === 'function') {
            window.sidebarNav.updateActiveLinks();
        }
        
        // Listen for custom events
        document.addEventListener('panel:open', (e) => {
            console.log('Sidebar opened');
        });
        
        document.addEventListener('panel:close', (e) => {
            console.log('Sidebar closed');
        });
        
        document.addEventListener('section:open', (e) => {
            console.log('Section opened:', e.detail.section);
        });
        
        document.addEventListener('section:close', (e) => {
            console.log('Section closed:', e.detail.section);
        });
    });

    // After AJAX page transitions, update sidebar active highlight
    document.addEventListener('pageLoaded', function() {
        if (window.sidebarNav && typeof window.sidebarNav.updateActiveLinks === 'function') {
            window.sidebarNav.updateActiveLinks();
        }
    });
    
    // Legacy function for backward compatibility
    function toggleNavSection(element, event) {
        if (window.sidebarNav) {
            window.sidebarNav.toggleSection(event);
        }
    }
    </script>
</body>
</html>