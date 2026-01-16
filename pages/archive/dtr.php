<?php
$page_title = 'Daily Time Record - Golden Z-5 HR System';
$page = 'dtr';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    try {
        $pdo = get_db_connection();
        
        switch ($action) {
            case 'time_in':
                $employee_id = $_POST['employee_id'];
                $time = $_POST['time'];
                $date = $_POST['date'];
                
                // Check if entry exists for today
                $stmt = $pdo->prepare("SELECT id FROM dtr_entries WHERE employee_id = ? AND date = ?");
                $stmt->execute([$employee_id, $date]);
                $entry = $stmt->fetch();
                
                if ($entry) {
                    // Update existing entry
                    $stmt = $pdo->prepare("UPDATE dtr_entries SET time_in = ?, status = 'on_duty', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$time, $entry['id']]);
                } else {
                    // Create new entry
                    $stmt = $pdo->prepare("INSERT INTO dtr_entries (employee_id, date, time_in, status) VALUES (?, ?, ?, 'on_duty')");
                    $stmt->execute([$employee_id, $date, $time]);
                }
                
                // Log status change
                $stmt = $pdo->prepare("INSERT INTO dtr_status_log (employee_id, status, notes) VALUES (?, 'on_duty', 'Time in at {$time}')");
                $stmt->execute([$employee_id]);
                
                $response = ['success' => true, 'message' => 'Time in recorded successfully'];
                break;
                
            case 'time_out':
                $employee_id = $_POST['employee_id'];
                $time = $_POST['time'];
                $date = $_POST['date'];
                
                $stmt = $pdo->prepare("UPDATE dtr_entries SET time_out = ?, status = 'out_of_duty', updated_at = NOW() WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
                
                // Log status change
                $stmt = $pdo->prepare("INSERT INTO dtr_status_log (employee_id, status, notes) VALUES (?, 'out_of_duty', 'Time out at {$time}')");
                $stmt->execute([$employee_id]);
                
                $response = ['success' => true, 'message' => 'Time out recorded successfully'];
                break;
                
            case 'break_start':
                $employee_id = $_POST['employee_id'];
                $time = $_POST['time'];
                $date = $_POST['date'];
                
                $stmt = $pdo->prepare("UPDATE dtr_entries SET break_start = ?, status = 'on_break', updated_at = NOW() WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
                
                // Log status change
                $stmt = $pdo->prepare("INSERT INTO dtr_status_log (employee_id, status, notes) VALUES (?, 'on_break', 'Break started at {$time}')");
                $stmt->execute([$employee_id]);
                
                $response = ['success' => true, 'message' => 'Break started successfully'];
                break;
                
            case 'break_end':
                $employee_id = $_POST['employee_id'];
                $time = $_POST['time'];
                $date = $_POST['date'];
                
                $stmt = $pdo->prepare("UPDATE dtr_entries SET break_end = ?, status = 'on_duty', updated_at = NOW() WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
                
                // Log status change
                $stmt = $pdo->prepare("INSERT INTO dtr_status_log (employee_id, status, notes) VALUES (?, 'on_duty', 'Break ended at {$time}')");
                $stmt->execute([$employee_id]);
                
                $response = ['success' => true, 'message' => 'Break ended successfully'];
                break;
                
            case 'overtime_start':
                $employee_id = $_POST['employee_id'];
                $time = $_POST['time'];
                $date = $_POST['date'];
                
                $stmt = $pdo->prepare("UPDATE dtr_entries SET overtime_start = ?, status = 'overtime', updated_at = NOW() WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
                
                // Log status change
                $stmt = $pdo->prepare("INSERT INTO dtr_status_log (employee_id, status, notes) VALUES (?, 'overtime', 'Overtime started at {$time}')");
                $stmt->execute([$employee_id]);
                
                $response = ['success' => true, 'message' => 'Overtime started successfully'];
                break;
                
            case 'overtime_end':
                $employee_id = $_POST['employee_id'];
                $time = $_POST['time'];
                $date = $_POST['date'];
                
                $stmt = $pdo->prepare("UPDATE dtr_entries SET overtime_end = ?, status = 'out_of_duty', updated_at = NOW() WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
                
                // Log status change
                $stmt = $pdo->prepare("INSERT INTO dtr_status_log (employee_id, status, notes) VALUES (?, 'out_of_duty', 'Overtime ended at {$time}')");
                $stmt->execute([$employee_id]);
                
                $response = ['success' => true, 'message' => 'Overtime ended successfully'];
                break;
                
            case 'get_all_status':
                $date = $_POST['date'];
                
                $stmt = $pdo->prepare("
                    SELECT e.id, e.first_name, e.surname, e.employee_no, e.post, d.* 
                    FROM employees e 
                    LEFT JOIN dtr_entries d ON e.id = d.employee_id AND d.date = ? 
                    WHERE e.status = 'Active'
                    ORDER BY e.first_name, e.surname
                ");
                $stmt->execute([$date]);
                $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response = ['success' => true, 'data' => $employees];
                break;
                
            case 'get_time_off_requests':
                $stmt = $pdo->prepare("
                    SELECT tor.*, e.first_name, e.surname, e.employee_no, e.post
                    FROM time_off_requests tor
                    JOIN employees e ON tor.employee_id = e.id
                    ORDER BY tor.requested_at DESC
                ");
                $stmt->execute();
                $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response = ['success' => true, 'data' => $requests];
                break;
                
            case 'create_time_off_request':
                $employee_id = $_POST['employee_id'];
                $request_type = $_POST['request_type'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $reason = $_POST['reason'];
                
                // Calculate total days
                $start = new DateTime($start_date);
                $end = new DateTime($end_date);
                $total_days = $start->diff($end)->days + 1;
                
                $stmt = $pdo->prepare("
                    INSERT INTO time_off_requests (employee_id, request_type, start_date, end_date, total_days, reason) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$employee_id, $request_type, $start_date, $end_date, $total_days, $reason]);
                
                $response = ['success' => true, 'message' => 'Time-off request submitted successfully'];
                break;
                
            case 'review_request':
                $request_id = $_POST['request_id'];
                $decision = $_POST['decision'];
                $review_notes = $_POST['review_notes'];
                
                $stmt = $pdo->prepare("
                    UPDATE time_off_requests 
                    SET status = ?, reviewed_at = NOW(), review_notes = ?
                    WHERE id = ?
                ");
                $stmt->execute([$decision, $review_notes, $request_id]);
                
                $response = ['success' => true, 'message' => 'Request reviewed successfully'];
                break;
                
            case 'get_leave_balances':
                $year = $_POST['year'];
                
                $stmt = $pdo->prepare("
                    SELECT lb.*, e.first_name, e.surname, e.employee_no, e.post
                    FROM leave_balances lb
                    JOIN employees e ON lb.employee_id = e.id
                    WHERE lb.year = ?
                    ORDER BY e.first_name, e.surname
                ");
                $stmt->execute([$year]);
                $balances = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response = ['success' => true, 'data' => $balances];
                break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
    
    echo json_encode($response);
    exit;
}

// Get current date and employees
$current_date = date('Y-m-d');
$employees = get_employees();
?>

<div class="dtr-container">
    <!-- DTR Header -->
    <div class="dtr-header">
        <div class="dtr-title">
            <h2>Daily Time Record</h2>
            <p class="text-muted">Real-time attendance tracking and monitoring</p>
        </div>
        <div class="dtr-actions">
            <button class="btn btn-outline-secondary" onclick="exportDTR()">
                <i class="fas fa-download me-1"></i>Export CSV
            </button>
            <button class="btn btn-primary" onclick="refreshStatus()">
                <i class="fas fa-sync me-1"></i>Refresh Status
            </button>
        </div>
    </div>

    <!-- Real-time Status Overview -->
    <div class="status-overview">
        <div class="status-cards">
            <div class="status-card on-duty">
                <div class="status-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="status-info">
                    <div class="status-count" id="onDutyCount">0</div>
                    <div class="status-label">On Duty</div>
                </div>
            </div>
            <div class="status-card on-break">
                <div class="status-icon">
                    <i class="fas fa-coffee"></i>
                </div>
                <div class="status-info">
                    <div class="status-count" id="onBreakCount">0</div>
                    <div class="status-label">On Break</div>
                </div>
            </div>
            <div class="status-card overtime">
                <div class="status-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="status-info">
                    <div class="status-count" id="overtimeCount">0</div>
                    <div class="status-label">Overtime</div>
                </div>
            </div>
            <div class="status-card out-duty">
                <div class="status-icon">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="status-info">
                    <div class="status-count" id="outDutyCount">0</div>
                    <div class="status-label">Out of Duty</div>
                </div>
            </div>
        </div>
    </div>

    <!-- DTR Tabs -->
    <div class="dtr-tabs">
        <button class="tab-button active" data-tab="requested" id="requestedTab">
            <i class="fas fa-clock me-1"></i>Requested
        </button>
        <button class="tab-button" data-tab="balances" id="balancesTab">
            <i class="fas fa-chart-pie me-1"></i>Balances
        </button>
        <button class="tab-button" data-tab="calendar" id="calendarTab">
            <i class="fas fa-calendar me-1"></i>Calendar
        </button>
    </div>

    <!-- View Toggle (for Calendar tab) -->
    <div class="view-toggle" id="viewToggle" style="display: none;">
        <div class="view-buttons">
            <button class="view-btn active" data-view="cards" id="cardsView">
                <i class="fas fa-th-large me-1"></i>Card View
            </button>
            <button class="view-btn" data-view="calendar" id="calendarView">
                <i class="fas fa-calendar me-1"></i>Calendar View
            </button>
        </div>
    </div>

    <!-- Date Selector -->
    <div class="date-selector">
        <div class="date-controls">
            <button class="btn btn-outline-secondary btn-sm calendar-nav-btn" data-direction="prev">
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            <input type="date" id="selectedDate" value="<?php echo $current_date; ?>" class="form-control">
            <button class="btn btn-outline-secondary btn-sm calendar-nav-btn" data-direction="next">
                Next <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div class="current-time">
            <i class="fas fa-clock me-1"></i>
            <span id="currentTime"><?php echo date('H:i:s'); ?></span>
        </div>
    </div>

    <!-- Tab Content Container -->
    <div class="tab-content">
        <!-- Requested Tab -->
        <div class="tab-pane active" id="requestedTabContent">
            <div class="requested-container">
                <div class="requested-header">
                    <h3>Time-Off Requests</h3>
                    <button class="btn btn-primary" onclick="openRequestModal()">
                        <i class="fas fa-plus me-1"></i>New Request
                    </button>
                </div>
                
                <div class="requested-filters">
                    <div class="filter-group">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" id="requestStatusFilter">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="form-label">Type</label>
                        <select class="form-select form-select-sm" id="requestTypeFilter">
                            <option value="">All Types</option>
                            <option value="vacation">Vacation</option>
                            <option value="sick_leave">Sick Leave</option>
                            <option value="personal_leave">Personal Leave</option>
                            <option value="emergency_leave">Emergency Leave</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button class="btn btn-outline-secondary btn-sm" onclick="clearRequestFilters()">
                            <i class="fas fa-times me-1"></i>Clear
                        </button>
                    </div>
                </div>
                
                <div class="requested-table-container">
                    <table class="table" id="requestedTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requestedTableBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Balances Tab -->
        <div class="tab-pane" id="balancesTabContent">
            <div class="balances-container">
                <div class="balances-header">
                    <h3>Leave Balances</h3>
                    <div class="year-selector">
                        <label class="form-label">Year</label>
                        <select class="form-select form-select-sm" id="balanceYear">
                            <option value="2024">2024</option>
                            <option value="2023">2023</option>
                            <option value="2025">2025</option>
                        </select>
                    </div>
                </div>
                
                <div class="balances-grid" id="balancesGrid">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Calendar Tab -->
        <div class="tab-pane" id="calendarTabContent">
            <!-- View Toggle (for Calendar tab) -->
            <div class="view-toggle">
                <div class="view-buttons">
                    <button class="view-btn active" onclick="switchView('cards')" id="cardsView">
                        <i class="fas fa-th-large me-1"></i>Card View
                    </button>
                    <button class="view-btn" onclick="switchView('calendar')" id="calendarView">
                        <i class="fas fa-calendar me-1"></i>Calendar View
                    </button>
                </div>
            </div>

            <!-- Card View -->
            <div class="view-container" id="cardsViewContainer">
                <div class="employee-status-grid" id="employeeStatusGrid">
                    <!-- This will be populated by JavaScript -->
                </div>
            </div>

            <!-- Calendar View -->
            <div class="view-container" id="calendarViewContainer" style="display: none;">
                <!-- Calendar View Options -->
                <div class="calendar-view-options">
                    <div class="calendar-view-buttons">
                        <button class="btn btn-sm btn-outline-primary calendar-view-btn active" data-view="month">
                            <i class="fas fa-calendar-alt me-1"></i>Month
                        </button>
                        <button class="btn btn-sm btn-outline-primary calendar-view-btn" data-view="week">
                            <i class="fas fa-calendar-week me-1"></i>Week
                        </button>
                        <button class="btn btn-sm btn-outline-primary calendar-view-btn" data-view="day">
                            <i class="fas fa-calendar-day me-1"></i>Day
                        </button>
                    </div>
                </div>
                
                <div class="calendar-container">
                    <div class="calendar-grid" id="calendarContainer">
                        <!-- Calendar will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Time-Off Request Modal -->
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Time-Off Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="requestForm">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select class="form-select" id="requestEmployee" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['surname']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Request Type</label>
                        <select class="form-select" id="requestType" required>
                            <option value="">Select Type</option>
                            <option value="vacation">Vacation</option>
                            <option value="sick_leave">Sick Leave</option>
                            <option value="personal_leave">Personal Leave</option>
                            <option value="emergency_leave">Emergency Leave</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="requestStartDate" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" id="requestEndDate" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" id="requestReason" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitRequest()">Submit Request</button>
            </div>
        </div>
    </div>
</div>

<!-- Review Request Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Review Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reviewForm">
                    <input type="hidden" id="reviewRequestId">
                    <div class="mb-3">
                        <label class="form-label">Decision</label>
                        <select class="form-select" id="reviewDecision" required>
                            <option value="">Select Decision</option>
                            <option value="approved">Approve</option>
                            <option value="rejected">Reject</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Review Notes</label>
                        <textarea class="form-control" id="reviewNotes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitReview()">Submit Review</button>
            </div>
        </div>
    </div>
</div>

<script>
class RealTimeDTR {
    constructor() {
        this.currentDate = '<?php echo $current_date; ?>';
        this.employees = <?php echo json_encode($employees); ?>;
        this.statusCounts = {
            on_duty: 0,
            on_break: 0,
            overtime: 0,
            out_of_duty: 0
        };
        this.currentView = 'cards';
        this.currentTab = 'requested';
        this.timeSlots = this.generateTimeSlots();
        
        this.initializeDTR();
        this.startRealTimeUpdates();
    }
    
    initializeDTR() {
        this.loadEmployeeStatus();
        this.updateCurrentTime();
        this.bindEvents();
        this.loadTimeOffRequests();
        this.loadLeaveBalances();
    }
    
    generateTimeSlots() {
        const slots = [];
        for (let hour = 0; hour < 24; hour++) {
            for (let minute = 0; minute < 60; minute += 30) {
                const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                slots.push({
                    time: timeString,
                    hour: hour,
                    minute: minute,
                    display: timeString
                });
            }
        }
        return slots;
    }
    
    bindEvents() {
        // Date selector
        document.getElementById('selectedDate').addEventListener('change', (e) => {
            this.currentDate = e.target.value;
            this.loadEmployeeStatus();
        });
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            this.loadEmployeeStatus();
        }, 30000);
    }
    
    startRealTimeUpdates() {
        // Update current time every second
        setInterval(() => {
            this.updateCurrentTime();
        }, 1000);
    }
    
    updateCurrentTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour12: false, 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        });
        document.getElementById('currentTime').textContent = timeString;
    }
    
    async loadEmployeeStatus() {
        try {
            const response = await fetch('?page=dtr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_all_status&date=${this.currentDate}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.updateStatusGrid(result.data);
                this.updateStatusCounts(result.data);
                if (this.currentView === 'calendar') {
                    this.updateCalendarGrid(result.data);
                }
            }
        } catch (error) {
            console.error('Error loading employee status:', error);
        }
    }
    
    updateStatusGrid(employees) {
        const grid = document.getElementById('employeeStatusGrid');
        grid.innerHTML = '';
        
        employees.forEach(employee => {
            const status = employee.status || 'out_of_duty';
            const timeIn = employee.time_in || '--:--';
            const timeOut = employee.time_out || '--:--';
            const breakStart = employee.break_start || '--:--';
            const breakEnd = employee.break_end || '--:--';
            const overtimeStart = employee.overtime_start || '--:--';
            const overtimeEnd = employee.overtime_end || '--:--';
            
            const employeeCard = document.createElement('div');
            employeeCard.className = `employee-card ${status}`;
            employeeCard.innerHTML = `
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
            
            grid.appendChild(employeeCard);
        });
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
            buttons.push(`<button class="btn btn-success btn-sm" onclick="dtr.timeIn(${employee.id})">
                <i class="fas fa-sign-in-alt me-1"></i>Time In
            </button>`);
        }
        
        if (status === 'on_duty' && employee.time_in && !employee.time_out) {
            buttons.push(`<button class="btn btn-warning btn-sm" onclick="dtr.breakStart(${employee.id})">
                <i class="fas fa-coffee me-1"></i>Break Start
            </button>`);
            buttons.push(`<button class="btn btn-danger btn-sm" onclick="dtr.timeOut(${employee.id})">
                <i class="fas fa-right-from-bracket me-1"></i>Time Out
            </button>`);
        }
        
        if (status === 'on_break' && employee.break_start) {
            buttons.push(`<button class="btn btn-primary btn-sm" onclick="dtr.breakEnd(${employee.id})">
                <i class="fas fa-play me-1"></i>Break End
            </button>`);
        }
        
        if (status === 'on_duty' && employee.time_out && !employee.overtime_start) {
            buttons.push(`<button class="btn btn-info btn-sm" onclick="dtr.overtimeStart(${employee.id})">
                <i class="fas fa-clock me-1"></i>OT Start
            </button>`);
        }
        
        if (status === 'overtime' && employee.overtime_start) {
            buttons.push(`<button class="btn btn-secondary btn-sm" onclick="dtr.overtimeEnd(${employee.id})">
                <i class="fas fa-stop me-1"></i>OT End
            </button>`);
        }
        
        return buttons.join('');
    }
    
    updateStatusCounts(employees) {
        this.statusCounts = {
            on_duty: 0,
            on_break: 0,
            overtime: 0,
            out_of_duty: 0
        };
        
        employees.forEach(employee => {
            const status = employee.status || 'out_of_duty';
            this.statusCounts[status]++;
        });
        
        document.getElementById('onDutyCount').textContent = this.statusCounts.on_duty;
        document.getElementById('onBreakCount').textContent = this.statusCounts.on_break;
        document.getElementById('overtimeCount').textContent = this.statusCounts.overtime;
        document.getElementById('outDutyCount').textContent = this.statusCounts.out_of_duty;
    }
    
    async timeIn(employeeId) {
        await this.submitTimeAction(employeeId, 'time_in');
    }
    
    async timeOut(employeeId) {
        await this.submitTimeAction(employeeId, 'time_out');
    }
    
    async breakStart(employeeId) {
        await this.submitTimeAction(employeeId, 'break_start');
    }
    
    async breakEnd(employeeId) {
        await this.submitTimeAction(employeeId, 'break_end');
    }
    
    async overtimeStart(employeeId) {
        await this.submitTimeAction(employeeId, 'overtime_start');
    }
    
    async overtimeEnd(employeeId) {
        await this.submitTimeAction(employeeId, 'overtime_end');
    }
    
    async submitTimeAction(employeeId, action) {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour12: false, 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        try {
            const response = await fetch('?page=dtr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&employee_id=${employeeId}&date=${this.currentDate}&time=${timeString}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.loadEmployeeStatus();
                this.showNotification(result.message, 'success');
            } else {
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Error submitting time action:', error);
            this.showNotification('Error submitting time action', 'error');
        }
    }
    
    showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    updateCalendarGrid(employees) {
        const grid = document.getElementById('calendarGrid');
        grid.innerHTML = '';
        
        // Create header row with time slots
        const headerRow = document.createElement('div');
        headerRow.className = 'calendar-header-row';
        
        const employeeHeader = document.createElement('div');
        employeeHeader.className = 'calendar-cell calendar-employee-header';
        employeeHeader.innerHTML = '<strong>Employee</strong>';
        headerRow.appendChild(employeeHeader);
        
        // Add time slot headers
        this.timeSlots.forEach(slot => {
            const timeHeader = document.createElement('div');
            timeHeader.className = 'calendar-cell calendar-time-header';
            timeHeader.textContent = slot.display;
            headerRow.appendChild(timeHeader);
        });
        
        grid.appendChild(headerRow);
        
        // Create rows for each employee
        employees.forEach(employee => {
            const employeeRow = document.createElement('div');
            employeeRow.className = 'calendar-employee-row';
            
            // Employee info cell
            const employeeCell = document.createElement('div');
            employeeCell.className = 'calendar-cell calendar-employee-cell';
            employeeCell.innerHTML = `
                <div class="employee-info-compact">
                    <div class="employee-avatar-small">
                        ${employee.first_name.charAt(0).toUpperCase()}${employee.surname.charAt(0).toUpperCase()}
                    </div>
                    <div class="employee-details-compact">
                        <div class="employee-name-compact">${employee.first_name} ${employee.surname}</div>
                        <div class="employee-post-compact">${employee.post || 'N/A'}</div>
                        <div class="employee-id-compact">${employee.employee_no}</div>
                    </div>
                </div>
            `;
            employeeRow.appendChild(employeeCell);
            
            // Time slot cells
            this.timeSlots.forEach(slot => {
                const timeCell = document.createElement('div');
                timeCell.className = 'calendar-cell calendar-time-cell';
                
                const status = this.getStatusForTimeSlot(employee, slot.time);
                if (status) {
                    timeCell.className += ` ${status}`;
                    timeCell.innerHTML = `<div class="time-indicator"></div>`;
                }
                
                employeeRow.appendChild(timeCell);
            });
            
            grid.appendChild(employeeRow);
        });
    }
    
    getStatusForTimeSlot(employee, time) {
        const timeIn = employee.time_in;
        const timeOut = employee.time_out;
        const breakStart = employee.break_start;
        const breakEnd = employee.break_end;
        const overtimeStart = employee.overtime_start;
        const overtimeEnd = employee.overtime_end;
        
        if (!timeIn) return null;
        
        // Check if time is within work hours
        if (time >= timeIn && time <= timeOut) {
            // Check if on break
            if (breakStart && breakEnd && time >= breakStart && time <= breakEnd) {
                return 'break';
            }
            return 'on_duty';
        }
        
        // Check if in overtime
        if (overtimeStart && overtimeEnd && time >= overtimeStart && time <= overtimeEnd) {
            return 'overtime';
        }
        
        return null;
    }
    
    switchView(view) {
        this.currentView = view;
        
        // Update button states
        document.getElementById('cardsView').classList.toggle('active', view === 'cards');
        document.getElementById('calendarView').classList.toggle('active', view === 'calendar');
        
        // Show/hide containers
        document.getElementById('cardsViewContainer').style.display = view === 'cards' ? 'block' : 'none';
        document.getElementById('calendarViewContainer').style.display = view === 'calendar' ? 'block' : 'none';
        
        // Load data for calendar view if needed
        if (view === 'calendar') {
            this.loadEmployeeStatus();
        }
    }
    
    switchTab(tab) {
        this.currentTab = tab;
        
        // Update tab button states
        document.getElementById('requestedTab').classList.toggle('active', tab === 'requested');
        document.getElementById('balancesTab').classList.toggle('active', tab === 'balances');
        document.getElementById('calendarTab').classList.toggle('active', tab === 'calendar');
        
        // Show/hide tab content
        document.getElementById('requestedTabContent').classList.toggle('active', tab === 'requested');
        document.getElementById('balancesTabContent').classList.toggle('active', tab === 'balances');
        document.getElementById('calendarTabContent').classList.toggle('active', tab === 'calendar');
        
        // Show/hide view toggle for calendar tab
        document.getElementById('viewToggle').style.display = tab === 'calendar' ? 'block' : 'none';
        
        // Load data for specific tabs
        if (tab === 'requested') {
            this.loadTimeOffRequests();
        } else if (tab === 'balances') {
            this.loadLeaveBalances();
        } else if (tab === 'calendar') {
            this.loadEmployeeStatus();
        }
    }
    
    async loadTimeOffRequests() {
        try {
            const response = await fetch('?page=dtr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_time_off_requests'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.updateRequestsTable(result.data);
            }
        } catch (error) {
            console.error('Error loading time-off requests:', error);
        }
    }
    
    updateRequestsTable(requests) {
        const tbody = document.getElementById('requestedTableBody');
        tbody.innerHTML = '';
        
        requests.forEach(request => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div class="employee-info-compact">
                        <div class="employee-avatar-small">
                            ${request.first_name.charAt(0).toUpperCase()}${request.surname.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div class="employee-name-compact">${request.first_name} ${request.surname}</div>
                            <div class="employee-id-compact">${request.employee_no}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge badge-${request.request_type}">${this.getRequestTypeText(request.request_type)}</span>
                </td>
                <td>${request.start_date}</td>
                <td>${request.end_date}</td>
                <td>${request.total_days}</td>
                <td>
                    <span class="status-badge ${request.status}">${this.getStatusText(request.status)}</span>
                </td>
                <td>${new Date(request.requested_at).toLocaleDateString()}</td>
                <td>
                    <div class="action-buttons">
                        ${request.status === 'pending' ? `
                            <button class="btn btn-success btn-sm" onclick="dtr.reviewRequest(${request.id}, 'approved')">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="dtr.reviewRequest(${request.id}, 'rejected')">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                        <button class="btn btn-outline-info btn-sm" onclick="dtr.viewRequest(${request.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    async loadLeaveBalances() {
        const year = document.getElementById('balanceYear').value;
        
        try {
            const response = await fetch('?page=dtr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_leave_balances&year=${year}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.updateBalancesGrid(result.data);
            }
        } catch (error) {
            console.error('Error loading leave balances:', error);
        }
    }
    
    updateBalancesGrid(balances) {
        const grid = document.getElementById('balancesGrid');
        grid.innerHTML = '';
        
        balances.forEach(balance => {
            const card = document.createElement('div');
            card.className = 'balance-card';
            card.innerHTML = `
                <div class="balance-header">
                    <div class="employee-info-compact">
                        <div class="employee-avatar-small">
                            ${balance.first_name.charAt(0).toUpperCase()}${balance.surname.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div class="employee-name-compact">${balance.first_name} ${balance.surname}</div>
                            <div class="employee-post-compact">${balance.post}</div>
                        </div>
                    </div>
                </div>
                <div class="balance-content">
                    <div class="balance-item">
                        <div class="balance-label">Vacation</div>
                        <div class="balance-progress">
                            <div class="progress-bar" style="width: ${(balance.vacation_days_used / balance.vacation_days_total) * 100}%"></div>
                        </div>
                        <div class="balance-text">${balance.vacation_days_used}/${balance.vacation_days_total} days</div>
                    </div>
                    <div class="balance-item">
                        <div class="balance-label">Sick Leave</div>
                        <div class="balance-progress">
                            <div class="progress-bar" style="width: ${(balance.sick_leave_days_used / balance.sick_leave_days_total) * 100}%"></div>
                        </div>
                        <div class="balance-text">${balance.sick_leave_days_used}/${balance.sick_leave_days_total} days</div>
                    </div>
                    <div class="balance-item">
                        <div class="balance-label">Personal Leave</div>
                        <div class="balance-progress">
                            <div class="progress-bar" style="width: ${(balance.personal_leave_days_used / balance.personal_leave_days_total) * 100}%"></div>
                        </div>
                        <div class="balance-text">${balance.personal_leave_days_used}/${balance.personal_leave_days_total} days</div>
                    </div>
                    <div class="balance-item">
                        <div class="balance-label">Emergency Leave</div>
                        <div class="balance-progress">
                            <div class="progress-bar" style="width: ${(balance.emergency_leave_days_used / balance.emergency_leave_days_total) * 100}%"></div>
                        </div>
                        <div class="balance-text">${balance.emergency_leave_days_used}/${balance.emergency_leave_days_total} days</div>
                    </div>
                </div>
            `;
            grid.appendChild(card);
        });
    }
    
    getRequestTypeText(type) {
        const types = {
            'vacation': 'Vacation',
            'sick_leave': 'Sick Leave',
            'personal_leave': 'Personal Leave',
            'emergency_leave': 'Emergency Leave',
            'other': 'Other'
        };
        return types[type] || type;
    }
    
    getStatusText(status) {
        const statuses = {
            'pending': 'Pending',
            'approved': 'Approved',
            'rejected': 'Rejected',
            'cancelled': 'Cancelled'
        };
        return statuses[status] || status;
    }
    
    async reviewRequest(requestId, decision) {
        try {
            const response = await fetch('?page=dtr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=review_request&request_id=${requestId}&decision=${decision}&review_notes=`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(result.message, 'success');
                this.loadTimeOffRequests();
            } else {
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Error reviewing request:', error);
            this.showNotification('Error reviewing request', 'error');
        }
    }
    
    viewRequest(requestId) {
        // Implementation for viewing request details
        console.log('View request:', requestId);
    }
}

// Global functions
function changeDate(direction) {
    const dateInput = document.getElementById('selectedDate');
    const currentDate = new Date(dateInput.value);
    currentDate.setDate(currentDate.getDate() + direction);
    dateInput.value = currentDate.toISOString().split('T')[0];
    dtr.currentDate = dateInput.value;
    dtr.loadEmployeeStatus();
}

function refreshStatus() {
    dtr.loadEmployeeStatus();
}

function exportDTR() {
    // Export functionality
    console.log('Export DTR');
}

function switchView(view) {
    dtr.switchView(view);
}

function switchTab(tab) {
    dtr.switchTab(tab);
}

function openRequestModal() {
    const modal = new bootstrap.Modal(document.getElementById('requestModal'));
    modal.show();
}

function submitRequest() {
    const formData = {
        employee_id: document.getElementById('requestEmployee').value,
        request_type: document.getElementById('requestType').value,
        start_date: document.getElementById('requestStartDate').value,
        end_date: document.getElementById('requestEndDate').value,
        reason: document.getElementById('requestReason').value
    };
    
    if (!formData.employee_id || !formData.request_type || !formData.start_date || !formData.end_date || !formData.reason) {
        alert('Please fill in all required fields');
        return;
    }
    
    fetch('?page=dtr', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=create_time_off_request&employee_id=${formData.employee_id}&request_type=${formData.request_type}&start_date=${formData.start_date}&end_date=${formData.end_date}&reason=${encodeURIComponent(formData.reason)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('requestModal')).hide();
            dtr.showNotification(data.message, 'success');
            dtr.loadTimeOffRequests();
        } else {
            dtr.showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        dtr.showNotification('Error submitting request', 'error');
    });
}

function clearRequestFilters() {
    document.getElementById('requestStatusFilter').value = '';
    document.getElementById('requestTypeFilter').value = '';
    // Add filter logic here
}

// Initialize DTR system
let dtr;
document.addEventListener('DOMContentLoaded', function() {
    dtr = new RealTimeDTR();
});
</script>

<style>
.dtr-container {
    padding: var(--spacing-2xl);
    max-width: 1400px;
    margin: 0 auto;
}

.dtr-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-2xl);
    padding-bottom: var(--spacing-xl);
    border-bottom: 2px solid var(--interface-border);
}

.dtr-title h2 {
    margin: 0;
    color: var(--interface-text);
    font-size: 1.75rem;
    font-weight: 600;
}

.dtr-title .text-muted {
    margin: var(--spacing-xs) 0 0 0;
    color: var(--interface-text-light);
    font-size: 0.875rem;
}

.dtr-actions {
    display: flex;
    gap: var(--spacing-md);
}

.status-overview {
    margin-bottom: var(--spacing-2xl);
}

.status-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
}

.status-card {
    display: flex;
    align-items: center;
    padding: var(--spacing-xl);
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: var(--white);
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
}

.status-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
}

.status-card.on-duty {
    border-left: 4px solid var(--success-500);
}

.status-card.on-break {
    border-left: 4px solid var(--warning-500);
}

.status-card.overtime {
    border-left: 4px solid var(--info-500);
}

.status-card.out-duty {
    border-left: 4px solid var(--danger-500);
}

.status-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: var(--spacing-lg);
    font-size: 1.25rem;
}

.status-card.on-duty .status-icon {
    background: var(--success-100);
    color: var(--success-600);
}

.status-card.on-break .status-icon {
    background: var(--warning-100);
    color: var(--warning-600);
}

.status-card.overtime .status-icon {
    background: var(--info-100);
    color: var(--info-600);
}

.status-card.out-duty .status-icon {
    background: var(--danger-100);
    color: var(--danger-600);
}

.status-info {
    flex: 1;
}

.status-count {
    font-size: 2rem;
    font-weight: 700;
    color: var(--interface-text);
    line-height: 1;
}

.status-label {
    font-size: 0.875rem;
    color: var(--interface-text-light);
    margin-top: var(--spacing-xs);
}

.date-selector {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-2xl);
    padding: var(--spacing-lg);
    background: var(--white);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
}

.date-selector:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
}

.date-controls {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.date-controls input {
    width: 150px;
}

.view-toggle {
    margin-bottom: var(--spacing-xl);
    display: flex;
    justify-content: center;
}

.view-buttons {
    display: flex;
    gap: var(--spacing-sm);
    background: var(--white);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: var(--spacing-xs);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
}

.view-btn {
    padding: var(--spacing-sm) var(--spacing-lg);
    border: none;
    background: transparent;
    color: var(--interface-text-light);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border-radius: 0px;
}

.view-btn:hover {
    background: var(--interface-hover);
    color: var(--interface-text);
}

.view-btn.active {
    background: var(--primary-500);
    color: var(--white);
}

.view-container {
    width: 100%;
}

.calendar-container {
    background: var(--white);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 80vh;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
}

.calendar-grid {
    display: grid;
    grid-template-columns: 200px repeat(48, 30px);
    min-width: 1640px;
    font-size: 0.75rem;
}

.calendar-header-row {
    display: contents;
    background: var(--interface-hover);
    position: sticky;
    top: 0;
    z-index: 10;
}

.calendar-employee-row {
    display: contents;
    border-bottom: 1px solid var(--interface-border-light);
}

.calendar-employee-row:hover {
    background: var(--interface-hover);
}

.calendar-cell {
    padding: var(--spacing-sm);
    border-right: 1px solid var(--interface-border-light);
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
}

.calendar-employee-header {
    background: var(--interface-hover);
    font-weight: 600;
    color: var(--interface-text);
    justify-content: flex-start;
    position: sticky;
    left: 0;
    z-index: 5;
}

.calendar-time-header {
    background: var(--interface-hover);
    font-weight: 500;
    color: var(--interface-text-light);
    font-size: 0.7rem;
    writing-mode: vertical-rl;
    text-orientation: mixed;
    min-height: 60px;
    padding: var(--spacing-xs);
}

.calendar-employee-cell {
    background: var(--white);
    justify-content: flex-start;
    position: sticky;
    left: 0;
    z-index: 3;
    border-right: 2px solid var(--interface-border);
}

.calendar-time-cell {
    background: var(--white);
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
}

.calendar-time-cell:hover {
    background: var(--interface-hover);
}

.calendar-time-cell.on_duty {
    background: var(--success-100);
    border-left: 3px solid var(--success-500);
}

.calendar-time-cell.break {
    background: var(--warning-100);
    border-left: 3px solid var(--warning-500);
}

.calendar-time-cell.overtime {
    background: var(--info-100);
    border-left: 3px solid var(--info-500);
}

.time-indicator {
    width: 100%;
    height: 100%;
    background: currentColor;
    opacity: 0.3;
    border-radius: 0px;
}

.employee-info-compact {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    width: 100%;
}

.employee-avatar-small {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--primary-500);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.employee-details-compact {
    flex: 1;
    min-width: 0;
}

.employee-name-compact {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--interface-text);
    margin-bottom: var(--spacing-xs);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.employee-post-compact {
    font-size: 0.7rem;
    color: var(--interface-text-light);
    margin-bottom: var(--spacing-xs);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.employee-id-compact {
    font-size: 0.65rem;
    color: var(--interface-text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.current-time {
    display: flex;
    align-items: center;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--primary-500);
}

.employee-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--spacing-lg);
}

.employee-card {
    background: var(--white);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: var(--spacing-xl);
    transition: all 0.3s ease;
    position: relative;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
}

.employee-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
}

.employee-card.on_duty {
    border-left: 4px solid var(--success-500);
}

.employee-card.on_break {
    border-left: 4px solid var(--warning-500);
}

.employee-card.overtime {
    border-left: 4px solid var(--info-500);
}

.employee-card.out_of_duty {
    border-left: 4px solid var(--danger-500);
}

.employee-info {
    display: flex;
    align-items: center;
    margin-bottom: var(--spacing-lg);
}

.employee-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--primary-500);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.125rem;
    margin-right: var(--spacing-lg);
}

.employee-details {
    flex: 1;
}

.employee-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--interface-text);
    margin-bottom: var(--spacing-xs);
}

.employee-id {
    font-size: 0.875rem;
    color: var(--interface-text-light);
    margin-bottom: var(--spacing-xs);
}

.employee-post {
    font-size: 0.875rem;
    color: var(--interface-text-muted);
}

.status-indicator {
    margin-bottom: var(--spacing-lg);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: 0px;
    font-size: 0.875rem;
    font-weight: 500;
    gap: var(--spacing-sm);
}

.status-badge.on_duty {
    background: var(--success-100);
    color: var(--success-700);
    border: 1px solid var(--success-200);
}

.status-badge.on_break {
    background: var(--warning-100);
    color: var(--warning-700);
    border: 1px solid var(--warning-200);
}

.status-badge.overtime {
    background: var(--info-100);
    color: var(--info-700);
    border: 1px solid var(--info-200);
}

.status-badge.out_of_duty {
    background: var(--danger-100);
    color: var(--danger-700);
    border: 1px solid var(--danger-200);
}

.time-display {
    margin-bottom: var(--spacing-lg);
}

.time-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--interface-border-light);
}

.time-row:last-child {
    border-bottom: none;
}

.time-label {
    font-size: 0.875rem;
    color: var(--interface-text-light);
    font-weight: 500;
}

.time-value {
    font-size: 0.875rem;
    color: var(--interface-text);
    font-weight: 600;
    font-family: 'Courier New', monospace;
}

.action-buttons {
    display: flex;
    gap: var(--spacing-sm);
    flex-wrap: wrap;
}

.action-buttons .btn {
    font-size: 0.75rem;
    padding: var(--spacing-sm) var(--spacing-md);
}

.notification {
    position: fixed;
    top: var(--spacing-xl);
    right: var(--spacing-xl);
    background: var(--white);
    border: 1px solid var(--interface-border);
    border-radius: 0px;
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    z-index: 1050;
    animation: slideIn 0.3s ease;
}

.notification.success {
    border-left: 4px solid var(--success-500);
}

.notification.error {
    border-left: 4px solid var(--danger-500);
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .dtr-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-lg);
    }
    
    .dtr-actions {
        width: 100%;
        justify-content: flex-end;
    }
    
    .status-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .employee-status-grid {
        grid-template-columns: 1fr;
    }
    
    .date-selector {
        flex-direction: column;
        gap: var(--spacing-lg);
    }
    
    .date-controls {
        width: 100%;
        justify-content: center;
    }
    
    .calendar-grid {
        grid-template-columns: 150px repeat(24, 40px);
        min-width: 1110px;
        font-size: 0.7rem;
    }
    
    .calendar-time-header {
        font-size: 0.65rem;
        min-height: 50px;
    }
    
    .employee-avatar-small {
        width: 24px;
        height: 24px;
        font-size: 0.65rem;
    }
    
    .employee-name-compact {
        font-size: 0.75rem;
    }
    
    .employee-post-compact {
        font-size: 0.65rem;
    }
    
    .employee-id-compact {
        font-size: 0.6rem;
    }
}

/* DTR Tabs */
.dtr-tabs {
    display: flex;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-xl);
    border-bottom: 2px solid var(--interface-border);
    padding-bottom: var(--spacing-sm);
}

.tab-button {
    padding: var(--spacing-sm) var(--spacing-lg);
    border: none;
    background: transparent;
    color: var(--interface-text-light);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border-radius: 0px;
    border-bottom: 2px solid transparent;
}

.tab-button:hover {
    color: var(--interface-text);
    background: var(--interface-hover);
}

.tab-button.active {
    color: var(--primary-500);
    border-bottom-color: var(--primary-500);
    background: var(--primary-50);
}

.tab-content {
    width: 100%;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* Requested Tab Styles */
.requested-container {
    background: var(--white);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: var(--spacing-xl);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
}

.requested-container:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
}

.requested-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-xl);
    padding-bottom: var(--spacing-lg);
    border-bottom: 1px solid var(--interface-border);
}

.requested-header h3 {
    margin: 0;
    color: var(--interface-text);
    font-size: 1.5rem;
    font-weight: 600;
}

.requested-filters {
    display: flex;
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.filter-group .form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--interface-text);
    margin: 0;
}

.requested-table-container {
    overflow-x: auto;
}

.requested-table-container .table {
    margin-bottom: 0;
}

.badge {
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: 0px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-vacation {
    background: var(--info-100);
    color: var(--info-700);
    border: 1px solid var(--info-200);
}

.badge-sick_leave {
    background: var(--warning-100);
    color: var(--warning-700);
    border: 1px solid var(--warning-200);
}

.badge-personal_leave {
    background: var(--success-100);
    color: var(--success-700);
    border: 1px solid var(--success-200);
}

.badge-emergency_leave {
    background: var(--danger-100);
    color: var(--danger-700);
    border: 1px solid var(--danger-200);
}

.badge-other {
    background: var(--gray-100);
    color: var(--gray-700);
    border: 1px solid var(--gray-200);
}

/* Balances Tab Styles */
.balances-container {
    background: var(--white);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: var(--spacing-xl);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
}

.balances-container:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
}

.balances-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-xl);
    padding-bottom: var(--spacing-lg);
    border-bottom: 1px solid var(--interface-border);
}

.balances-header h3 {
    margin: 0;
    color: var(--interface-text);
    font-size: 1.5rem;
    font-weight: 600;
}

.year-selector {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.year-selector .form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--interface-text);
    margin: 0;
}

.balances-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--spacing-lg);
}

.balance-card {
    background: var(--white);
    border: 1px solid var(--interface-border);
    border-radius: 0px;
    padding: var(--spacing-xl);
    transition: all 0.2s ease;
}

.balance-card:hover {
    box-shadow: var(--shadow-sm);
    transform: translateY(-1px);
}

.balance-header {
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-lg);
    border-bottom: 1px solid var(--interface-border-light);
}

.balance-content {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

.balance-item {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.balance-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--interface-text);
}

.balance-progress {
    width: 100%;
    height: 8px;
    background: var(--interface-border-light);
    border-radius: 0px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: var(--primary-500);
    transition: width 0.3s ease;
}

.balance-text {
    font-size: 0.75rem;
    color: var(--interface-text-light);
    text-align: right;
}

@media (max-width: 768px) {
    .dtr-tabs {
        flex-direction: column;
        gap: var(--spacing-xs);
    }
    
    .tab-button {
        width: 100%;
        text-align: center;
    }
    
    .requested-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .requested-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-lg);
    }
    
    .balances-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-lg);
    }
    
    .balances-grid {
        grid-template-columns: 1fr;
    }
}

/* Calendar View Options */
.calendar-view-options {
    margin-bottom: var(--spacing-lg);
    padding: var(--spacing-md);
    background: var(--white);
    border: 1px solid var(--interface-border);
    border-radius: var(--border-radius-sm);
}

.calendar-view-buttons {
    display: flex;
    gap: var(--spacing-sm);
}

.calendar-view-btn {
    padding: var(--spacing-sm) var(--spacing-md);
    border: 1px solid var(--interface-border);
    background: var(--white);
    color: var(--interface-text);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border-radius: var(--border-radius-sm);
}

.calendar-view-btn:hover {
    background: var(--interface-hover);
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.calendar-view-btn.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: var(--white);
}

/* Calendar Styles */
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
    padding: var(--spacing-lg);
    background: var(--white);
    border: 1px solid var(--interface-border);
    border-radius: var(--border-radius-sm);
}

.calendar-header h3 {
    margin: 0;
    color: var(--interface-text);
    font-size: 1.5rem;
    font-weight: 600;
}

.calendar-controls {
    display: flex;
    gap: var(--spacing-sm);
}

.calendar-grid {
    background: var(--white);
    border: 1px solid var(--interface-border);
    border-radius: var(--border-radius-sm);
    overflow: hidden;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: var(--interface-hover);
}

.weekday {
    padding: var(--spacing-md);
    text-align: center;
    font-weight: 600;
    color: var(--interface-text);
    border-right: 1px solid var(--interface-border);
}

.weekday:last-child {
    border-right: none;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    grid-template-rows: repeat(6, 120px);
}

.calendar-day {
    padding: var(--spacing-sm);
    border-right: 1px solid var(--interface-border);
    border-bottom: 1px solid var(--interface-border);
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.calendar-day:hover {
    background: var(--interface-hover);
}

.calendar-day.other-month {
    background: var(--gray-50);
    color: var(--interface-text-muted);
}

.calendar-day.today {
    background: var(--primary-50);
    border-color: var(--primary-color);
}

.calendar-day.current-month {
    background: var(--white);
}

.day-number {
    font-weight: 600;
    margin-bottom: var(--spacing-xs);
}

.day-events {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.event-item {
    padding: var(--spacing-xs);
    border-radius: var(--border-radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.event-item.update {
    background: var(--info-100);
    color: var(--info-700);
    border: 1px solid var(--info-200);
}

.event-item.announcement {
    background: var(--warning-100);
    color: var(--warning-700);
    border: 1px solid var(--warning-200);
}

.event-item.urgent {
    background: var(--danger-100);
    color: var(--danger-700);
    border: 1px solid var(--danger-200);
}

.event-item.celebration {
    background: var(--success-100);
    color: var(--success-700);
    border: 1px solid var(--success-200);
}

/* Week View */
.calendar-grid.week-view {
    display: grid;
    grid-template-columns: 80px repeat(7, 1fr);
    min-height: 600px;
}

.calendar-time-column {
    background: var(--interface-hover);
    border-right: 2px solid var(--interface-border);
}

.time-header {
    height: 40px;
    border-bottom: 1px solid var(--interface-border);
}

.time-slot {
    height: 60px;
    padding: var(--spacing-sm);
    border-bottom: 1px solid var(--interface-border-light);
    font-size: 0.75rem;
    color: var(--interface-text-light);
    display: flex;
    align-items: center;
}

.calendar-day-column {
    border-right: 1px solid var(--interface-border);
    position: relative;
}

.calendar-day-column:last-child {
    border-right: none;
}

.day-header {
    height: 40px;
    padding: var(--spacing-sm);
    border-bottom: 1px solid var(--interface-border);
    background: var(--interface-hover);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.day-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--interface-text-light);
}

.day-number {
    font-size: 1rem;
    font-weight: 700;
    color: var(--interface-text);
}

.day-events {
    position: relative;
    height: 100%;
}

.event-item {
    position: absolute;
    left: 2px;
    right: 2px;
    padding: var(--spacing-xs);
    border-radius: var(--border-radius-sm);
    font-size: 0.7rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    z-index: 1;
}

/* Day View */
.calendar-grid.day-view {
    display: grid;
    grid-template-columns: 80px 1fr;
    min-height: 600px;
}

/* Responsive adjustments for calendar */
@media (max-width: 992px) {
    .calendar-view-buttons {
        flex-direction: column;
    }
    
    .calendar-days {
        grid-template-rows: repeat(6, 80px);
    }
    
    .calendar-grid.week-view {
        grid-template-columns: 60px repeat(7, 1fr);
    }
    
    .calendar-grid.day-view {
        grid-template-columns: 60px 1fr;
    }
}

/* Card styling to match HR admin dashboard */
.card-modern,
.card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    background: #ffffff;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card-modern:hover,
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
}

.card-body-modern,
.card-body {
    padding: 1.5rem;
}

.card-header-modern {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.card-title-modern {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}

/* Dark theme support for DTR page */
html[data-theme="dark"] .dtr-container {
    background-color: transparent;
    color: var(--interface-text);
}

html[data-theme="dark"] .status-card {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .status-count {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .status-label {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .date-selector {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .date-selector .form-control {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .view-buttons {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .view-btn {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .view-btn:hover {
    background: var(--interface-hover) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .calendar-container {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .calendar-time-cell {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .calendar-employee-cell {
    background: #1a1d23 !important;
}

html[data-theme="dark"] .employee-card {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .requested-container {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .requested-header {
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .requested-header h3 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .requested-filters .form-label {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .requested-filters .form-select {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .requested-table-container .table {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .requested-table-container .table thead {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .requested-table-container .table thead th {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .requested-table-container .table tbody {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .requested-table-container .table tbody tr {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .requested-table-container .table tbody tr:hover {
    background: var(--interface-hover) !important;
}

html[data-theme="dark"] .requested-table-container .table td {
    background: transparent !important;
    color: var(--interface-text) !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .balances-container {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .balances-header {
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .balances-header h3 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .balance-card {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .dtr-tabs .tab-button {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .dtr-tabs .tab-button:hover {
    background: var(--interface-hover) !important;
}

html[data-theme="dark"] .dtr-tabs .tab-button.active {
    background: var(--primary-500) !important;
    color: #fff !important;
}

html[data-theme="dark"] .notification {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}
</style>
