/**
 * Sidebar Dropdown Menu Handler
 * Handles expanding/collapsing of sidebar dropdown menus
 * Uses event delegation to work immediately without removing existing listeners
 */

(function() {
    'use strict';
    
    // Track if delegation is set up to prevent duplicates
    let delegationSetup = false;
    
    function setupDropdownDelegation() {
        // Only set up once
        if (delegationSetup) {
            return;
        }
        
        console.log('🔧 Setting up sidebar dropdown event delegation...');
        
        // Use event delegation on document - works immediately, even before DOMContentLoaded
        // Use bubbling phase so it runs after SidebarNavigation's capture phase handler
        document.addEventListener('click', function(e) {
            // Only handle if event wasn't already handled by SidebarNavigation
            if (e._handledBySidebarNav) {
                console.log('ℹ️ Event already handled by SidebarNavigation, skipping fallback');
                return;
            }
            
            const toggle = e.target.closest('.nav-toggle');
            if (!toggle) return;
            
            // Verify it's actually a nav-toggle button
            if (!toggle.classList.contains('nav-toggle') || toggle.tagName !== 'BUTTON') {
                return;
            }
            
            // If SidebarNavigation exists and is ready, let it handle it
            // But if it didn't handle it (maybe error), we'll handle it here
            if (window.sidebarNav && window.sidebarNav._toggleHandler) {
                // Give SidebarNavigation a chance, but if it didn't mark as handled, we'll handle it
                // This handles the case where SidebarNavigation failed
                if (!e._handledBySidebarNav) {
                    console.log('⚠️ SidebarNavigation exists but didn\'t handle click, using fallback');
                } else {
                    return;
                }
            }
            
            // Fallback handler - only runs if SidebarNavigation isn't available or didn't handle it
            const targetId = toggle.getAttribute('data-target');
            if (!targetId) {
                console.warn('❌ No data-target on toggle:', toggle);
                return;
            }
            
            console.log('🔍 Fallback: Looking for submenu:', targetId);
            const submenu = document.getElementById(targetId);
            if (!submenu) {
                console.error('❌ Fallback: Submenu not found:', targetId);
                return;
            }
            
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🖱️ Toggle clicked (fallback):', toggle.textContent.trim(), 'Target:', targetId);
            
            const arrow = toggle.querySelector('.nav-arrow');
            const isExpanded = submenu.classList.contains('expanded');
            
            console.log('📊 Fallback: Current state - Expanded:', isExpanded);
            
            // Toggle submenu
            if (isExpanded) {
                submenu.classList.remove('expanded');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.classList.remove('active');
                if (arrow) arrow.classList.remove('rotated');
                console.log('✅ Fallback: Collapsed');
            } else {
                submenu.classList.add('expanded');
                toggle.setAttribute('aria-expanded', 'true');
                toggle.classList.add('active');
                if (arrow) arrow.classList.add('rotated');
                console.log('✅ Fallback: Expanded');
            }
        }, false); // Bubbling phase - runs after capture phase handlers
        
        delegationSetup = true;
        console.log('✅ Sidebar dropdown delegation initialized');
    }
    
    // Set up immediately - event delegation works even before DOM is ready
    setupDropdownDelegation();
    
    // Also ensure it's set up on DOM ready (in case script loads late)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupDropdownDelegation);
    }
    
    // Re-setup after page transitions (though delegation should persist)
    document.addEventListener('pageLoaded', setupDropdownDelegation);
    
})();
