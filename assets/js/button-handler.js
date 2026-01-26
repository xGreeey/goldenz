/**
 * Comprehensive Button Handler System
 * Ensures all buttons work properly across the entire application
 */

class ButtonHandlerSystem {
    constructor() {
        this.initialized = false;
        this.modalInstances = new Map();
        this.tooltipInstances = new Map();
        this.init();
    }

    init() {
        if (this.initialized) return;
        
        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initializeAll());
        } else {
            this.initializeAll();
        }

        // Re-initialize after AJAX page loads
        document.addEventListener('pageLoaded', () => {
            setTimeout(() => this.initializeAll(), 100);
        });

        // Re-initialize after dynamic content is added
        const observer = new MutationObserver(() => {
            this.initializeModals();
            this.initializeTooltips();
            this.attachButtonHandlers();
        });
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        this.initialized = true;
    }

    initializeAll() {
        this.initializeModals();
        this.initializeTooltips();
        this.initializePopovers();
        this.attachButtonHandlers();
        this.attachFormHandlers();
        this.fixBrokenButtons();
    }

    /**
     * Initialize all Bootstrap modals
     */
    initializeModals() {
        const modals = document.querySelectorAll('.modal');
        
        modals.forEach(modalEl => {
            const modalId = modalEl.id;
            if (!modalId) return;

            // Skip if already initialized
            if (this.modalInstances.has(modalId)) {
                const instance = bootstrap.Modal.getInstance(modalEl);
                if (instance) return;
            }

            try {
                // Special handling for createUserModal (needs backdrop)
                const needsBackdrop = modalId === 'createUserModal';
                
                const modalInstance = new bootstrap.Modal(modalEl, {
                    backdrop: needsBackdrop ? true : false,
                    keyboard: true,
                    focus: true
                });

                this.modalInstances.set(modalId, modalInstance);

                // Ensure modal triggers work
                const triggers = document.querySelectorAll(`[data-bs-toggle="modal"][data-bs-target="#${modalId}"]`);
                triggers.forEach(trigger => {
                    if (!trigger.hasAttribute('data-modal-handler-attached')) {
                        trigger.setAttribute('data-modal-handler-attached', 'true');
                        trigger.addEventListener('click', (e) => {
                            e.preventDefault();
                            modalInstance.show();
                        });
                    }
                });
            } catch (error) {
                console.warn(`Failed to initialize modal ${modalId}:`, error);
            }
        });

        // Clean up any lingering backdrops
        this.cleanupBackdrops();
    }

    /**
     * Initialize all Bootstrap tooltips
     */
    initializeTooltips() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        
        tooltips.forEach(tooltipEl => {
            const tooltipId = tooltipEl.id || `tooltip-${Math.random().toString(36).substr(2, 9)}`;
            
            if (this.tooltipInstances.has(tooltipId)) return;

            try {
                const tooltipInstance = new bootstrap.Tooltip(tooltipEl);
                this.tooltipInstances.set(tooltipId, tooltipInstance);
            } catch (error) {
                console.warn(`Failed to initialize tooltip ${tooltipId}:`, error);
            }
        });
    }

    /**
     * Initialize all Bootstrap popovers
     */
    initializePopovers() {
        const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
        
        popovers.forEach(popoverEl => {
            try {
                new bootstrap.Popover(popoverEl);
            } catch (error) {
                console.warn('Failed to initialize popover:', error);
            }
        });
    }

    /**
     * Attach handlers to common button patterns
     */
    attachButtonHandlers() {
        // Handle buttons with data-action attributes
        document.querySelectorAll('[data-action]').forEach(btn => {
            if (btn.hasAttribute('data-action-handler-attached')) return;
            btn.setAttribute('data-action-handler-attached', 'true');
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const action = btn.getAttribute('data-action');
                this.handleAction(btn, action);
            });
        });

        // Handle delete buttons
        document.querySelectorAll('.delete-btn, [data-delete], [data-action="delete"]').forEach(btn => {
            if (btn.hasAttribute('data-delete-handler-attached')) return;
            btn.setAttribute('data-delete-handler-attached', 'true');
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleDelete(btn);
            });
        });

        // Handle edit buttons
        document.querySelectorAll('.edit-btn, [data-edit], [data-action="edit"]').forEach(btn => {
            if (btn.hasAttribute('data-edit-handler-attached')) return;
            btn.setAttribute('data-edit-handler-attached', 'true');
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleEdit(btn);
            });
        });

        // Handle view buttons
        document.querySelectorAll('.view-btn, [data-view], [data-action="view"]').forEach(btn => {
            if (btn.hasAttribute('data-view-handler-attached')) return;
            btn.setAttribute('data-view-handler-attached', 'true');
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleView(btn);
            });
        });

        // Handle export buttons
        document.querySelectorAll('.export-btn, [data-export], [data-action="export"]').forEach(btn => {
            if (btn.hasAttribute('data-export-handler-attached')) return;
            btn.setAttribute('data-export-handler-attached', 'true');
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleExport(btn);
            });
        });

        // Handle filter buttons
        document.querySelectorAll('.filter-btn, [data-filter], [data-action="filter"]').forEach(btn => {
            if (btn.hasAttribute('data-filter-handler-attached')) return;
            btn.setAttribute('data-filter-handler-attached', 'true');
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleFilter(btn);
            });
        });

        // Handle reset/clear buttons
        document.querySelectorAll('.reset-btn, .clear-btn, [data-reset], [data-clear]').forEach(btn => {
            if (btn.hasAttribute('data-reset-handler-attached')) return;
            btn.setAttribute('data-reset-handler-attached', 'true');
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleReset(btn);
            });
        });

        // Handle close buttons
        document.querySelectorAll('.close-btn, [data-close], [data-dismiss]').forEach(btn => {
            if (btn.hasAttribute('data-close-handler-attached')) return;
            btn.setAttribute('data-close-handler-attached', 'true');
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleClose(btn);
            });
        });

        // Handle tab buttons
        document.querySelectorAll('[data-tab], [data-target][data-tab]').forEach(btn => {
            if (btn.hasAttribute('data-tab-handler-attached')) return;
            btn.setAttribute('data-tab-handler-attached', 'true');
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleTab(btn);
            });
        });

        // Handle segmented control buttons
        document.querySelectorAll('.hrdash-segment__btn').forEach(btn => {
            if (btn.hasAttribute('data-segment-handler-attached')) return;
            btn.setAttribute('data-segment-handler-attached', 'true');
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.handleSegment(btn);
            });
        });
    }

    /**
     * Attach form submission handlers
     */
    attachFormHandlers() {
        // Handle forms with AJAX submission
        document.querySelectorAll('form[data-ajax], form.ajax-form').forEach(form => {
            if (form.hasAttribute('data-ajax-handler-attached')) return;
            form.setAttribute('data-ajax-handler-attached', 'true');
            
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleAjaxForm(form);
            });
        });

        // Ensure submit buttons don't cause double submissions
        document.querySelectorAll('form button[type="submit"]').forEach(btn => {
            if (btn.hasAttribute('data-submit-handler-attached')) return;
            btn.setAttribute('data-submit-handler-attached', 'true');
            
            btn.addEventListener('click', (e) => {
                const form = btn.closest('form');
                if (form && !form.hasAttribute('data-ajax')) {
                    // Prevent double submission
                    if (btn.disabled) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Disable button after click
                    setTimeout(() => {
                        btn.disabled = true;
                        setTimeout(() => {
                            btn.disabled = false;
                        }, 2000);
                    }, 100);
                }
            });
        });
    }

    /**
     * Fix broken buttons
     */
    fixBrokenButtons() {
        // Fix buttons without type attribute
        document.querySelectorAll('button:not([type])').forEach(btn => {
            if (btn.closest('form')) {
                btn.type = 'button'; // Prevent form submission
            } else {
                btn.type = 'button';
            }
        });

        // Fix disabled buttons that should be enabled
        document.querySelectorAll('button[disabled]:not([data-permanently-disabled])').forEach(btn => {
            // Check if button should be enabled based on form validity
            const form = btn.closest('form');
            if (form && form.checkValidity && form.checkValidity()) {
                btn.disabled = false;
            }
        });

        // Ensure all buttons have cursor pointer
        document.querySelectorAll('button:not([disabled])').forEach(btn => {
            if (!btn.style.cursor) {
                btn.style.cursor = 'pointer';
            }
        });
    }

    /**
     * Handle generic actions
     */
    handleAction(btn, action) {
        console.log('Action triggered:', action, btn);
        
        // Try to find a custom handler
        const handlerName = `handle${action.charAt(0).toUpperCase() + action.slice(1)}`;
        if (typeof this[handlerName] === 'function') {
            this[handlerName](btn);
            return;
        }

        // Default action handling
        const url = btn.getAttribute('data-url') || btn.getAttribute('href');
        if (url) {
            window.location.href = url;
        }
    }

    /**
     * Handle delete actions
     */
    handleDelete(btn) {
        const id = btn.getAttribute('data-id') || btn.getAttribute('data-delete');
        const confirmMsg = btn.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
        
        if (!confirm(confirmMsg)) return;

        const url = btn.getAttribute('data-url') || window.location.href;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                btn.closest('tr')?.remove();
                this.showNotification('Item deleted successfully', 'success');
            } else {
                this.showNotification(data.message || 'Failed to delete item', 'error');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            this.showNotification('An error occurred', 'error');
        });
    }

    /**
     * Handle edit actions
     */
    handleEdit(btn) {
        const id = btn.getAttribute('data-id') || btn.getAttribute('data-edit');
        const url = btn.getAttribute('data-url') || `?page=edit&id=${id}`;
        window.location.href = url;
    }

    /**
     * Handle view actions
     */
    handleView(btn) {
        const id = btn.getAttribute('data-id') || btn.getAttribute('data-view');
        const url = btn.getAttribute('data-url') || `?page=view&id=${id}`;
        window.location.href = url;
    }

    /**
     * Handle export actions
     */
    handleExport(btn) {
        const format = btn.getAttribute('data-format') || 'csv';
        const url = btn.getAttribute('data-url') || window.location.href;
        window.open(`${url}&export=${format}`, '_blank');
    }

    /**
     * Handle filter actions
     */
    handleFilter(btn) {
        const filterValue = btn.getAttribute('data-filter');
        // Trigger custom event for page-specific handlers
        document.dispatchEvent(new CustomEvent('filterApplied', { detail: { filter: filterValue } }));
    }

    /**
     * Handle reset actions
     */
    handleReset(btn) {
        const form = btn.closest('form');
        if (form) {
            form.reset();
            // Trigger reset event
            form.dispatchEvent(new Event('reset'));
        }
        
        // Clear search inputs
        document.querySelectorAll('input[type="search"], input[placeholder*="search" i]').forEach(input => {
            input.value = '';
        });
    }

    /**
     * Handle close actions
     */
    handleClose(btn) {
        const target = btn.getAttribute('data-target') || btn.getAttribute('data-dismiss');
        
        if (target) {
            const element = document.querySelector(target);
            if (element) {
                // Try to close as modal
                const modal = bootstrap.Modal.getInstance(element);
                if (modal) {
                    modal.hide();
                } else {
                    element.style.display = 'none';
                }
            }
        } else {
            // Close parent element
            btn.closest('.modal, .alert, .card')?.remove();
        }
    }

    /**
     * Handle tab switching
     */
    handleTab(btn) {
        const tabId = btn.getAttribute('data-tab') || btn.getAttribute('data-target');
        if (!tabId) return;

        // Remove active from all tabs
        btn.closest('.nav-tabs, .hrdash-segment')?.querySelectorAll('.active').forEach(el => {
            el.classList.remove('active');
        });
        
        // Add active to clicked button
        btn.classList.add('active');

        // Show/hide tab content
        document.querySelectorAll(`[id$="-content"], [data-tab-content]`).forEach(content => {
            if (content.id.includes(tabId) || content.getAttribute('data-tab-content') === tabId) {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        });
    }

    /**
     * Handle segmented control buttons
     */
    handleSegment(btn) {
        const target = btn.getAttribute('data-target');
        if (!target) return;

        // Remove active from all buttons in segment
        const segment = btn.closest('.hrdash-segment');
        if (segment) {
            segment.querySelectorAll('.hrdash-segment__btn').forEach(b => {
                b.classList.remove('active');
            });
        }

        // Add active to clicked button
        btn.classList.add('active');

        // Show/hide panes
        document.querySelectorAll(`[data-pane], tbody[data-pane]`).forEach(pane => {
            const paneTarget = pane.getAttribute('data-pane');
            if (paneTarget === target) {
                pane.style.display = '';
            } else {
                pane.style.display = 'none';
            }
        });
    }

    /**
     * Handle AJAX form submission
     */
    async handleAjaxForm(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn?.innerHTML;
        
        // Disable submit button
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        }

        try {
            const formData = new FormData(form);
            const url = form.getAttribute('action') || window.location.href;
            const method = form.getAttribute('method') || 'POST';

            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const contentType = response.headers.get('content-type');
            let data;

            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                // Try to parse as JSON
                try {
                    data = JSON.parse(text);
                } catch {
                    // If not JSON, treat as success if status is OK
                    data = { success: response.ok, message: text.substring(0, 100) };
                }
            }

            if (data.success) {
                this.showNotification(data.message || 'Operation completed successfully', 'success');
                
                // Reload page if needed
                if (form.hasAttribute('data-reload')) {
                    setTimeout(() => window.location.reload(), 1000);
                }
            } else {
                this.showNotification(data.message || 'Operation failed', 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showNotification('An error occurred. Please try again.', 'error');
        } finally {
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText || 'Submit';
            }
        }
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Try to use existing notification system
        if (typeof showNotification === 'function') {
            showNotification(message, type);
            return;
        }

        // Fallback notification
        const alert = document.createElement('div');
        alert.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.style.minWidth = '300px';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }

    /**
     * Clean up modal backdrops
     */
    cleanupBackdrops() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
}

// Initialize the button handler system
const buttonHandler = new ButtonHandlerSystem();

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ButtonHandlerSystem;
}
