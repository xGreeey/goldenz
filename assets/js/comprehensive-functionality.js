/**
 * Comprehensive Functionality Manager
 * Handles all buttons, tabs, calendars, and features across the HR system
 */

class ComprehensiveFunctionalityManager {
    constructor() {
        this.currentTab = 'employee-list';
        this.currentView = 'cards';
        this.currentCalendarView = 'month';
        this.employees = [];
        this.posts = [];
        this.dtrData = [];
        this.timeOffRequests = [];
        this.leaveBalances = [];
        
        this.init();
    }

    init() {
        this.bindGlobalEvents();
        this.initializeTabs();
        this.initializeCalendar();
        this.initializeDTR();
        this.initializePosts();
        this.loadInitialData();
    }

    bindGlobalEvents() {
        // Global event delegation for dynamic content
        document.addEventListener('click', (e) => {
            // Tab switching
            if (e.target.matches('.tab-button')) {
                this.switchTab(e.target.dataset.tab);
            }
            
            // View switching
            if (e.target.matches('.view-btn')) {
                this.switchView(e.target.dataset.view);
            }
            
            // Calendar view switching
            if (e.target.matches('.calendar-view-btn')) {
                this.switchCalendarView(e.target.dataset.view);
            }
            
            // Action buttons
            if (e.target.matches('.action-btn')) {
                this.handleAction(e.target);
            }
            
            // Export buttons
            if (e.target.matches('.export-btn')) {
                this.handleExport(e.target);
            }
            
            // Filter buttons
            if (e.target.matches('.filter-btn')) {
                this.handleFilter(e.target);
            }
        });

        // Search functionality
        document.addEventListener('input', (e) => {
            if (e.target.matches('.search-input')) {
                this.handleSearch(e.target);
            }
        });

        // Form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.dynamic-form')) {
                e.preventDefault();
                this.handleFormSubmit(e.target);
            }
        });
    }

    // Tab Management
    initializeTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                this.switchTab(button.dataset.tab);
            });
        });
    }

    switchTab(tabId) {
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabId}"]`)?.classList.add('active');
        
        // Update tab panes
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        document.getElementById(tabId)?.classList.add('active');
        
        this.currentTab = tabId;
        
        // Load tab-specific data
        this.loadTabData(tabId);
    }

    loadTabData(tabId) {
        switch (tabId) {
            case 'employee-list':
                this.loadEmployeeList();
                break;
            case 'directory':
                this.loadDirectory();
                break;
            case 'org-chart':
                this.loadOrgChart();
                break;
            case 'requested':
                this.loadTimeOffRequests();
                break;
            case 'balances':
                this.loadLeaveBalances();
                break;
            case 'calendar':
                this.loadCalendar();
                break;
        }
    }

    // View Management
    switchView(viewId) {
        this.currentView = viewId;
        
        // Update view buttons
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-view="${viewId}"]`)?.classList.add('active');
        
        // Show/hide view containers
        document.querySelectorAll('.view-container').forEach(container => {
            container.style.display = 'none';
        });
        document.getElementById(`${viewId}ViewContainer`)?.style.display = 'block';
        
        // Load view-specific data
        this.loadViewData(viewId);
    }

    loadViewData(viewId) {
        switch (viewId) {
            case 'cards':
                this.loadCardView();
                break;
            case 'calendar':
                this.loadCalendarView();
                break;
            case 'table':
                this.loadTableView();
                break;
        }
    }

    // Calendar Management
    initializeCalendar() {
        this.calendarData = {
            currentDate: new Date(),
            view: 'month',
            events: []
        };
        
        this.bindCalendarEvents();
    }

    bindCalendarEvents() {
        // Calendar navigation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.calendar-nav-btn')) {
                this.navigateCalendar(e.target.dataset.direction);
            }
            
            if (e.target.matches('.calendar-view-btn')) {
                this.switchCalendarView(e.target.dataset.view);
            }
            
            if (e.target.matches('.calendar-date')) {
                this.selectDate(e.target.dataset.date);
            }
        });
    }

    switchCalendarView(view) {
        this.calendarData.view = view;
        
        // Update view buttons
        document.querySelectorAll('.calendar-view-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-view="${view}"]`)?.classList.add('active');
        
        // Render calendar
        this.renderCalendar();
    }

    navigateCalendar(direction) {
        const current = this.calendarData.currentDate;
        
        switch (this.calendarData.view) {
            case 'month':
                if (direction === 'prev') {
                    current.setMonth(current.getMonth() - 1);
                } else {
                    current.setMonth(current.getMonth() + 1);
                }
                break;
            case 'week':
                if (direction === 'prev') {
                    current.setDate(current.getDate() - 7);
                } else {
                    current.setDate(current.getDate() + 7);
                }
                break;
            case 'day':
                if (direction === 'prev') {
                    current.setDate(current.getDate() - 1);
                } else {
                    current.setDate(current.getDate() + 1);
                }
                break;
        }
        
        this.renderCalendar();
    }

    renderCalendar() {
        const container = document.getElementById('calendarContainer');
        if (!container) return;
        
        const { currentDate, view } = this.calendarData;
        
        switch (view) {
            case 'month':
                this.renderMonthView(container, currentDate);
                break;
            case 'week':
                this.renderWeekView(container, currentDate);
                break;
            case 'day':
                this.renderDayView(container, currentDate);
                break;
        }
    }

    renderMonthView(container, date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());
        
        let html = `
            <div class="calendar-header">
                <h3>${date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</h3>
                <div class="calendar-controls">
                    <button class="btn btn-sm btn-outline-secondary calendar-nav-btn" data-direction="prev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary calendar-nav-btn" data-direction="next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <div class="calendar-grid month-view">
                <div class="calendar-weekdays">
                    <div class="weekday">Sun</div>
                    <div class="weekday">Mon</div>
                    <div class="weekday">Tue</div>
                    <div class="weekday">Wed</div>
                    <div class="weekday">Thu</div>
                    <div class="weekday">Fri</div>
                    <div class="weekday">Sat</div>
                </div>
                <div class="calendar-days">
        `;
        
        const current = new Date(startDate);
        for (let week = 0; week < 6; week++) {
            for (let day = 0; day < 7; day++) {
                const isCurrentMonth = current.getMonth() === month;
                const isToday = current.toDateString() === new Date().toDateString();
                const events = this.getEventsForDate(current);
                
                html += `
                    <div class="calendar-day ${isCurrentMonth ? 'current-month' : 'other-month'} ${isToday ? 'today' : ''}" 
                         data-date="${current.toISOString().split('T')[0]}">
                        <div class="day-number">${current.getDate()}</div>
                        <div class="day-events">
                            ${events.map(event => `
                                <div class="event-item ${event.type}" title="${event.title}">
                                    ${event.title}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
                
                current.setDate(current.getDate() + 1);
            }
        }
        
        html += `
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    }

    renderWeekView(container, date) {
        const startOfWeek = new Date(date);
        startOfWeek.setDate(date.getDate() - date.getDay());
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);
        
        let html = `
            <div class="calendar-header">
                <h3>${startOfWeek.toLocaleDateString()} - ${endOfWeek.toLocaleDateString()}</h3>
                <div class="calendar-controls">
                    <button class="btn btn-sm btn-outline-secondary calendar-nav-btn" data-direction="prev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary calendar-nav-btn" data-direction="next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <div class="calendar-grid week-view">
                <div class="calendar-time-column">
                    <div class="time-header"></div>
                    ${this.generateTimeSlots().map(time => `
                        <div class="time-slot">${time}</div>
                    `).join('')}
                </div>
        `;
        
        for (let day = 0; day < 7; day++) {
            const currentDay = new Date(startOfWeek);
            currentDay.setDate(startOfWeek.getDate() + day);
            const isToday = currentDay.toDateString() === new Date().toDateString();
            
            html += `
                <div class="calendar-day-column ${isToday ? 'today' : ''}">
                    <div class="day-header">
                        <div class="day-name">${currentDay.toLocaleDateString('en-US', { weekday: 'short' })}</div>
                        <div class="day-number">${currentDay.getDate()}</div>
                    </div>
                    <div class="day-events">
                        ${this.getEventsForDate(currentDay).map(event => `
                            <div class="event-item ${event.type}" 
                                 style="top: ${this.getEventPosition(event)}px; height: ${this.getEventHeight(event)}px;">
                                ${event.title}
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        html += `</div>`;
        container.innerHTML = html;
    }

    renderDayView(container, date) {
        const isToday = date.toDateString() === new Date().toDateString();
        
        let html = `
            <div class="calendar-header">
                <h3>${date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</h3>
                <div class="calendar-controls">
                    <button class="btn btn-sm btn-outline-secondary calendar-nav-btn" data-direction="prev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary calendar-nav-btn" data-direction="next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <div class="calendar-grid day-view ${isToday ? 'today' : ''}">
                <div class="calendar-time-column">
                    <div class="time-header"></div>
                    ${this.generateTimeSlots().map(time => `
                        <div class="time-slot">${time}</div>
                    `).join('')}
                </div>
                <div class="calendar-day-column">
                    <div class="day-events">
                        ${this.getEventsForDate(date).map(event => `
                            <div class="event-item ${event.type}" 
                                 style="top: ${this.getEventPosition(event)}px; height: ${this.getEventHeight(event)}px;">
                                <div class="event-time">${event.startTime}</div>
                                <div class="event-title">${event.title}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    }

    generateTimeSlots() {
        const slots = [];
        for (let hour = 0; hour < 24; hour++) {
            slots.push(`${hour.toString().padStart(2, '0')}:00`);
        }
        return slots;
    }

    getEventsForDate(date) {
        // This would typically fetch from the database
        // For now, return sample data
        return this.calendarData.events.filter(event => {
            const eventDate = new Date(event.date);
            return eventDate.toDateString() === date.toDateString();
        });
    }

    getEventPosition(event) {
        const startTime = event.startTime.split(':');
        const hour = parseInt(startTime[0]);
        const minute = parseInt(startTime[1]);
        return (hour * 60 + minute) * 2; // 2px per minute
    }

    getEventHeight(event) {
        const start = new Date(`2000-01-01 ${event.startTime}`);
        const end = new Date(`2000-01-01 ${event.endTime}`);
        const duration = (end - start) / (1000 * 60); // minutes
        return duration * 2; // 2px per minute
    }

    // DTR Management
    initializeDTR() {
        this.dtrData = {
            currentDate: new Date(),
            employees: [],
            statusCounts: {
                on_duty: 0,
                on_break: 0,
                overtime: 0,
                out_of_duty: 0
            }
        };
        
        this.bindDTREvents();
        this.startDTRUpdates();
    }

    bindDTREvents() {
        // DTR action buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.dtr-action-btn')) {
                this.handleDTRAction(e.target);
            }
        });
    }

    handleDTRAction(button) {
        const action = button.dataset.action;
        const employeeId = button.dataset.employeeId;
        
        switch (action) {
            case 'time-in':
                this.recordTimeIn(employeeId);
                break;
            case 'time-out':
                this.recordTimeOut(employeeId);
                break;
            case 'break-start':
                this.recordBreakStart(employeeId);
                break;
            case 'break-end':
                this.recordBreakEnd(employeeId);
                break;
            case 'overtime-start':
                this.recordOvertimeStart(employeeId);
                break;
            case 'overtime-end':
                this.recordOvertimeEnd(employeeId);
                break;
        }
    }

    async recordTimeIn(employeeId) {
        try {
            const response = await this.makeRequest('dtr', {
                action: 'time_in',
                employee_id: employeeId,
                date: this.dtrData.currentDate.toISOString().split('T')[0],
                time: new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' })
            });
            
            if (response.success) {
                this.showNotification('Time in recorded successfully', 'success');
                this.loadDTRData();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error recording time in', 'error');
        }
    }

    async recordTimeOut(employeeId) {
        try {
            const response = await this.makeRequest('dtr', {
                action: 'time_out',
                employee_id: employeeId,
                date: this.dtrData.currentDate.toISOString().split('T')[0],
                time: new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' })
            });
            
            if (response.success) {
                this.showNotification('Time out recorded successfully', 'success');
                this.loadDTRData();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error recording time out', 'error');
        }
    }

    async recordBreakStart(employeeId) {
        try {
            const response = await this.makeRequest('dtr', {
                action: 'break_start',
                employee_id: employeeId,
                date: this.dtrData.currentDate.toISOString().split('T')[0],
                time: new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' })
            });
            
            if (response.success) {
                this.showNotification('Break started successfully', 'success');
                this.loadDTRData();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error starting break', 'error');
        }
    }

    async recordBreakEnd(employeeId) {
        try {
            const response = await this.makeRequest('dtr', {
                action: 'break_end',
                employee_id: employeeId,
                date: this.dtrData.currentDate.toISOString().split('T')[0],
                time: new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' })
            });
            
            if (response.success) {
                this.showNotification('Break ended successfully', 'success');
                this.loadDTRData();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error ending break', 'error');
        }
    }

    async recordOvertimeStart(employeeId) {
        try {
            const response = await this.makeRequest('dtr', {
                action: 'overtime_start',
                employee_id: employeeId,
                date: this.dtrData.currentDate.toISOString().split('T')[0],
                time: new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' })
            });
            
            if (response.success) {
                this.showNotification('Overtime started successfully', 'success');
                this.loadDTRData();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error starting overtime', 'error');
        }
    }

    async recordOvertimeEnd(employeeId) {
        try {
            const response = await this.makeRequest('dtr', {
                action: 'overtime_end',
                employee_id: employeeId,
                date: this.dtrData.currentDate.toISOString().split('T')[0],
                time: new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' })
            });
            
            if (response.success) {
                this.showNotification('Overtime ended successfully', 'success');
                this.loadDTRData();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error ending overtime', 'error');
        }
    }

    startDTRUpdates() {
        // Update DTR data every 30 seconds
        setInterval(() => {
            this.loadDTRData();
        }, 30000);
    }

    async loadDTRData() {
        try {
            const response = await this.makeRequest('dtr', {
                action: 'get_all_status',
                date: this.dtrData.currentDate.toISOString().split('T')[0]
            });
            
            if (response.success) {
                this.dtrData.employees = response.data;
                this.updateDTRDisplay();
                this.updateStatusCounts();
            }
        } catch (error) {
            console.error('Error loading DTR data:', error);
        }
    }

    updateDTRDisplay() {
        const container = document.getElementById('employeeStatusGrid');
        if (!container) return;
        
        container.innerHTML = '';
        
        this.dtrData.employees.forEach(employee => {
            const card = this.createEmployeeCard(employee);
            container.appendChild(card);
        });
    }

    createEmployeeCard(employee) {
        const card = document.createElement('div');
        card.className = `employee-card ${employee.status || 'out_of_duty'}`;
        
        const status = employee.status || 'out_of_duty';
        const timeIn = employee.time_in || '--:--';
        const timeOut = employee.time_out || '--:--';
        const breakStart = employee.break_start || '--:--';
        const breakEnd = employee.break_end || '--:--';
        const overtimeStart = employee.overtime_start || '--:--';
        const overtimeEnd = employee.overtime_end || '--:--';
        
        card.innerHTML = `
            <div class="employee-info">
                <div class="employee-avatar">
                    ${employee.first_name.charAt(0).toUpperCase()}${employee.surname.charAt(0).toUpperCase()}
                </div>
                <div class="employee-details">
                    <div class="employee-name">${employee.first_name} ${employee.surname}</div>
                    <div class="employee-id">${employee.employee_no}</div>
                    <div class="employee-post">${employee.post || 'N/A'}</div>
                </div>
            </div>
            <div class="status-indicator">
                <div class="status-badge ${status}">
                    <i class="fas ${this.getStatusIcon(status)}"></i>
                    ${this.getStatusText(status)}
                </div>
            </div>
            <div class="time-display">
                <div class="time-row">
                    <span class="time-label">Time In:</span>
                    <span class="time-value">${timeIn}</span>
                </div>
                <div class="time-row">
                    <span class="time-label">Time Out:</span>
                    <span class="time-value">${timeOut}</span>
                </div>
                <div class="time-row">
                    <span class="time-label">Break:</span>
                    <span class="time-value">${breakStart} - ${breakEnd}</span>
                </div>
                <div class="time-row">
                    <span class="time-label">Overtime:</span>
                    <span class="time-value">${overtimeStart} - ${overtimeEnd}</span>
                </div>
            </div>
            <div class="action-buttons">
                ${this.getActionButtons(employee, status)}
            </div>
        `;
        
        return card;
    }

    getStatusIcon(status) {
        const icons = {
            'on_duty': 'fa-user-check',
            'on_break': 'fa-coffee',
            'overtime': 'fa-clock',
            'out_of_duty': 'fa-user-times'
        };
        return icons[status] || 'fa-user-times';
    }

    getStatusText(status) {
        const texts = {
            'on_duty': 'On Duty',
            'on_break': 'On Break',
            'overtime': 'Overtime',
            'out_of_duty': 'Out of Duty'
        };
        return texts[status] || 'Out of Duty';
    }

    getActionButtons(employee, status) {
        const buttons = [];
        
        if (status === 'out_of_duty' && !employee.time_in) {
            buttons.push(`<button class="btn btn-success btn-sm dtr-action-btn" data-action="time-in" data-employee-id="${employee.id}">
                <i class="fas fa-sign-in-alt me-1"></i>Time In
            </button>`);
        }
        
        if (status === 'on_duty' && employee.time_in && !employee.time_out) {
            buttons.push(`<button class="btn btn-warning btn-sm dtr-action-btn" data-action="break-start" data-employee-id="${employee.id}">
                <i class="fas fa-coffee me-1"></i>Break Start
            </button>`);
            buttons.push(`<button class="btn btn-danger btn-sm dtr-action-btn" data-action="time-out" data-employee-id="${employee.id}">
                <i class="fas fa-sign-out-alt me-1"></i>Time Out
            </button>`);
        }
        
        if (status === 'on_break' && employee.break_start) {
            buttons.push(`<button class="btn btn-primary btn-sm dtr-action-btn" data-action="break-end" data-employee-id="${employee.id}">
                <i class="fas fa-play me-1"></i>Break End
            </button>`);
        }
        
        if (status === 'on_duty' && employee.time_out && !employee.overtime_start) {
            buttons.push(`<button class="btn btn-info btn-sm dtr-action-btn" data-action="overtime-start" data-employee-id="${employee.id}">
                <i class="fas fa-clock me-1"></i>OT Start
            </button>`);
        }
        
        if (status === 'overtime' && employee.overtime_start) {
            buttons.push(`<button class="btn btn-secondary btn-sm dtr-action-btn" data-action="overtime-end" data-employee-id="${employee.id}">
                <i class="fas fa-stop me-1"></i>OT End
            </button>`);
        }
        
        return buttons.join('');
    }

    updateStatusCounts() {
        this.dtrData.statusCounts = {
            on_duty: 0,
            on_break: 0,
            overtime: 0,
            out_of_duty: 0
        };
        
        this.dtrData.employees.forEach(employee => {
            const status = employee.status || 'out_of_duty';
            this.dtrData.statusCounts[status]++;
        });
        
        // Update status count displays
        document.getElementById('onDutyCount').textContent = this.dtrData.statusCounts.on_duty;
        document.getElementById('onBreakCount').textContent = this.dtrData.statusCounts.on_break;
        document.getElementById('overtimeCount').textContent = this.dtrData.statusCounts.overtime;
        document.getElementById('outDutyCount').textContent = this.dtrData.statusCounts.out_of_duty;
    }

    // Posts Management
    initializePosts() {
        this.bindPostsEvents();
    }

    bindPostsEvents() {
        // Post creation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.create-post-btn')) {
                this.openCreatePostModal();
            }
            
            if (e.target.matches('.post-action-btn')) {
                this.handlePostAction(e.target);
            }
        });
    }

    openCreatePostModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create Post</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form class="dynamic-form">
                            <div class="mb-3">
                                <label class="form-label">Post Content</label>
                                <textarea class="form-control" name="content" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Post Type</label>
                                <select class="form-select" name="type" required>
                                    <option value="update">General Update</option>
                                    <option value="announcement">Announcement</option>
                                    <option value="urgent">Urgent Notice</option>
                                    <option value="celebration">Celebration</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="comprehensiveManager.submitPost()">Publish Post</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    async submitPost() {
        const form = document.querySelector('.dynamic-form');
        const formData = new FormData(form);
        
        try {
            const response = await this.makeRequest('posts', {
                action: 'create_post',
                content: formData.get('content'),
                type: formData.get('type')
            });
            
            if (response.success) {
                this.showNotification('Post created successfully', 'success');
                this.loadPosts();
                bootstrap.Modal.getInstance(document.querySelector('.modal')).hide();
            } else {
                this.showNotification(response.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error creating post', 'error');
        }
    }

    // Data Loading
    async loadInitialData() {
        await Promise.all([
            this.loadEmployees(),
            this.loadPosts(),
            this.loadTimeOffRequests(),
            this.loadLeaveBalances()
        ]);
    }

    async loadEmployees() {
        try {
            const response = await this.makeRequest('employees', { action: 'get_all' });
            if (response.success) {
                this.employees = response.data;
            }
        } catch (error) {
            console.error('Error loading employees:', error);
        }
    }

    async loadPosts() {
        try {
            const response = await this.makeRequest('posts', { action: 'get_all' });
            if (response.success) {
                this.posts = response.data;
            }
        } catch (error) {
            console.error('Error loading posts:', error);
        }
    }

    async loadTimeOffRequests() {
        try {
            const response = await this.makeRequest('dtr', { action: 'get_time_off_requests' });
            if (response.success) {
                this.timeOffRequests = response.data;
            }
        } catch (error) {
            console.error('Error loading time-off requests:', error);
        }
    }

    async loadLeaveBalances() {
        try {
            const response = await this.makeRequest('dtr', { action: 'get_leave_balances' });
            if (response.success) {
                this.leaveBalances = response.data;
            }
        } catch (error) {
            console.error('Error loading leave balances:', error);
        }
    }

    // Utility Methods
    async makeRequest(page, data) {
        const formData = new FormData();
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });
        
        const response = await fetch(`?page=${page}`, {
            method: 'POST',
            body: formData
        });
        
        return await response.json();
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    handleAction(button) {
        const action = button.dataset.action;
        const target = button.dataset.target;
        
        switch (action) {
            case 'edit':
                this.editItem(target);
                break;
            case 'delete':
                this.deleteItem(target);
                break;
            case 'view':
                this.viewItem(target);
                break;
            case 'export':
                this.exportData(target);
                break;
        }
    }

    handleExport(button) {
        const type = button.dataset.type;
        this.exportData(type);
    }

    handleFilter(button) {
        const filter = button.dataset.filter;
        const value = button.dataset.value;
        this.applyFilter(filter, value);
    }

    handleSearch(input) {
        const query = input.value.toLowerCase();
        const target = input.dataset.target;
        this.searchData(target, query);
    }

    handleFormSubmit(form) {
        const formData = new FormData(form);
        const action = form.dataset.action;
        
        switch (action) {
            case 'create_employee':
                this.createEmployee(formData);
                break;
            case 'create_post':
                this.createPost(formData);
                break;
            case 'create_time_off_request':
                this.createTimeOffRequest(formData);
                break;
        }
    }

    // Placeholder methods for specific functionality
    editItem(id) {
        console.log('Edit item:', id);
    }

    deleteItem(id) {
        if (confirm('Are you sure you want to delete this item?')) {
            console.log('Delete item:', id);
        }
    }

    viewItem(id) {
        console.log('View item:', id);
    }

    exportData(type) {
        console.log('Export data:', type);
    }

    applyFilter(filter, value) {
        console.log('Apply filter:', filter, value);
    }

    searchData(target, query) {
        console.log('Search data:', target, query);
    }

    createEmployee(formData) {
        console.log('Create employee:', formData);
    }

    createPost(formData) {
        console.log('Create post:', formData);
    }

    createTimeOffRequest(formData) {
        console.log('Create time-off request:', formData);
    }
}

// Initialize the comprehensive functionality manager
let comprehensiveManager;
document.addEventListener('DOMContentLoaded', function() {
    comprehensiveManager = new ComprehensiveFunctionalityManager();
});

// Global functions for backward compatibility
function switchTab(tabId) {
    if (comprehensiveManager) {
        comprehensiveManager.switchTab(tabId);
    }
}

function switchView(viewId) {
    if (comprehensiveManager) {
        comprehensiveManager.switchView(viewId);
    }
}

function switchCalendarView(view) {
    if (comprehensiveManager) {
        comprehensiveManager.switchCalendarView(view);
    }
}

function changeDate(direction) {
    if (comprehensiveManager) {
        comprehensiveManager.navigateCalendar(direction);
    }
}

function refreshStatus() {
    if (comprehensiveManager) {
        comprehensiveManager.loadDTRData();
    }
}

function exportDTR() {
    if (comprehensiveManager) {
        comprehensiveManager.exportData('dtr');
    }
}

function openRequestModal() {
    if (comprehensiveManager) {
        comprehensiveManager.openCreatePostModal();
    }
}

function submitRequest() {
    if (comprehensiveManager) {
        comprehensiveManager.submitPost();
    }
}

function clearRequestFilters() {
    document.getElementById('requestStatusFilter').value = '';
    document.getElementById('requestTypeFilter').value = '';
}

function loadMorePosts() {
    if (comprehensiveManager) {
        comprehensiveManager.loadPosts();
    }
}
