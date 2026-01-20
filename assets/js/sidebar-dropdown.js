/**
 * Sidebar Dropdown Menu Handler
 * Handles expanding/collapsing of sidebar dropdown menus
 */

(function() {
    'use strict';
    
    function initSidebarDropdowns() {
        console.log('🔧 Initializing sidebar dropdowns...');
        
        const toggles = document.querySelectorAll('.nav-toggle');
        console.log(`Found ${toggles.length} dropdown toggles`);
        
        toggles.forEach((toggle, index) => {
            console.log(`Setting up toggle ${index + 1}:`, toggle.textContent.trim());
            
            // Remove any existing listeners to prevent duplicates
            const newToggle = toggle.cloneNode(true);
            toggle.parentNode.replaceChild(newToggle, toggle);
            
            newToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('🖱️ Toggle clicked:', this.textContent.trim());
                
                const targetId = this.getAttribute('data-target');
                console.log('Target ID:', targetId);
                
                const submenu = document.getElementById(targetId);
                const arrow = this.querySelector('.nav-arrow');
                
                if (!submenu) {
                    console.error('❌ Submenu not found for target:', targetId);
                    return;
                }
                
                const isExpanded = submenu.classList.contains('expanded');
                console.log('Current state:', isExpanded ? 'expanded' : 'collapsed');
                
                // Toggle submenu
                if (isExpanded) {
                    submenu.classList.remove('expanded');
                    this.setAttribute('aria-expanded', 'false');
                    this.classList.remove('active');
                    if (arrow) arrow.classList.remove('rotated');
                    console.log('✅ Collapsed');
                } else {
                    submenu.classList.add('expanded');
                    this.setAttribute('aria-expanded', 'true');
                    this.classList.add('active');
                    if (arrow) arrow.classList.add('rotated');
                    console.log('✅ Expanded');
                }
            });
        });
        
        console.log('✅ Sidebar dropdowns initialized');
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarDropdowns);
    } else {
        initSidebarDropdowns();
    }
    
    // Re-initialize after page transitions
    document.addEventListener('pageLoaded', initSidebarDropdowns);
    
})();
