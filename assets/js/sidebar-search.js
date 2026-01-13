/**
 * Golden Z-5 HR System - Enhanced Sidebar Search Functionality
 * Professional search with real-time filtering and smooth animations
 */

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('sidebarSearch');
    const searchClear = document.getElementById('searchClear');
    const searchResults = document.getElementById('searchResults');
    const sidebarMenu = document.getElementById('sidebarMenu');
    
    if (!searchInput || !sidebarMenu) return;
    
    // Get all menu items for search
    const menuItems = Array.from(sidebarMenu.querySelectorAll('.nav-link, .nav-toggle'));
    const menuData = menuItems.map(item => {
        const text = item.textContent.trim();
        const link = item.href || item.getAttribute('data-page') || '';
        const icon = item.querySelector('i')?.className || '';
        return { element: item, text: text.toLowerCase(), originalText: text, link, icon };
    });
    
    // Search functionality
    let searchTimeout;
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim().toLowerCase();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Show/hide clear button
        if (query.length > 0) {
            if (searchClear) {
                searchClear.classList.remove('d-none');
            }
        } else {
            if (searchClear) {
                searchClear.classList.add('d-none');
            }
            if (searchResults) {
                searchResults.classList.remove('show');
                searchResults.innerHTML = '';
            }
            // Show all menu items
            menuItems.forEach(item => {
                item.closest('li')?.classList.remove('hidden');
            });
            return;
        }
        
        // Debounce search
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 200);
    });
    
    // Clear search
    if (searchClear) {
        searchClear.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            searchInput.value = '';
            searchInput.focus();
            searchClear.classList.add('d-none');
            if (searchResults) {
                searchResults.classList.remove('show');
                searchResults.innerHTML = '';
            }
            // Show all menu items
            menuItems.forEach(item => {
                item.closest('li')?.classList.remove('hidden');
            });
        });
    }
    
    // Keyboard shortcuts
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchInput.value = '';
            if (searchClear) searchClear.classList.add('d-none');
            if (searchResults) {
                searchResults.classList.remove('show');
                searchResults.innerHTML = '';
            }
            menuItems.forEach(item => {
                item.closest('li')?.classList.remove('hidden');
            });
            searchInput.blur();
        }
    });
    
    function performSearch(query) {
        if (!query) return;
        
        const results = menuData.filter(item => 
            item.text.includes(query) || 
            item.originalText.toLowerCase().includes(query)
        );
        
        // Hide/show menu items
        menuItems.forEach(item => {
            const itemData = menuData.find(m => m.element === item);
            if (itemData && results.includes(itemData)) {
                item.closest('li')?.classList.remove('hidden');
            } else {
                item.closest('li')?.classList.add('hidden');
            }
        });
        
        // Show search results dropdown
        if (searchResults && results.length > 0) {
            searchResults.innerHTML = '';
            results.slice(0, 5).forEach(result => {
                const resultItem = document.createElement('div');
                resultItem.className = 'search-result-item';
                resultItem.innerHTML = `
                    <i class="${result.icon} me-2"></i>
                    <span>${result.originalText}</span>
                `;
                resultItem.addEventListener('click', function() {
                    if (result.link) {
                        if (result.element.tagName === 'A') {
                            window.location.href = result.element.href;
                        } else {
                            result.element.click();
                        }
                    }
                });
                searchResults.appendChild(resultItem);
            });
            searchResults.classList.add('show');
        } else if (searchResults) {
            searchResults.classList.remove('show');
        }
    }
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && 
            !searchResults?.contains(e.target) && 
            !searchClear?.contains(e.target)) {
            if (searchResults) {
                searchResults.classList.remove('show');
            }
        }
    });
});

