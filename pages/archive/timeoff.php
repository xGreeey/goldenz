<?php
$page_title = 'Time off - Golden Z-5 HR System';
$page = 'timeoff';

// Get current date and month
$current_date = new DateTime();
$current_month = $current_date->format('n');
$current_year = $current_date->format('Y');
$current_day = $current_date->format('j');

// Handle month navigation
$display_month = isset($_GET['month']) ? (int)$_GET['month'] : $current_month;
$display_year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;

// Create calendar object
$calendar = new DateTime();
$calendar->setDate($display_year, $display_month, 1);

// Get first day of month and number of days
$first_day = $calendar->format('N'); // 1 = Monday, 7 = Sunday
$days_in_month = $calendar->format('t');

// Sample leave data (in a real system, this would come from database)
$leave_entries = [
    '2025-03-05' => [
        ['type' => 'Sick leave', 'employee' => 'John Doe', 'icon' => 'fas fa-face-frown', 'color' => 'warning']
    ],
    '2025-03-07' => [
        ['type' => 'Sick leave', 'employee' => 'Jane Smith', 'icon' => 'fas fa-face-frown', 'color' => 'warning'],
        ['type' => 'Annual leave', 'employee' => 'Mike Johnson', 'icon' => 'fas fa-leaf', 'color' => 'success']
    ],
    '2025-03-13' => [
        ['type' => 'Personal leave', 'employee' => 'Sarah Wilson', 'icon' => 'fas fa-user', 'color' => 'info']
    ],
    '2025-03-19' => [
        ['type' => 'Annual leave', 'employee' => 'David Brown', 'icon' => 'fas fa-leaf', 'color' => 'success']
    ],
    '2025-03-20' => [
        ['type' => 'Annual leave', 'employee' => 'Lisa Davis', 'icon' => 'fas fa-leaf', 'color' => 'success']
    ],
    '2025-03-22' => [
        ['type' => 'Personal leave', 'employee' => 'Tom Wilson', 'icon' => 'fas fa-user', 'color' => 'info']
    ],
    '2025-03-28' => [
        ['type' => 'National holiday', 'employee' => 'System', 'icon' => 'fas fa-flag', 'color' => 'danger']
    ],
    '2025-03-30' => [
        ['type' => 'Personal leave', 'employee' => 'Anna Taylor', 'icon' => 'fas fa-user', 'color' => 'info']
    ]
];

// Get month name
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$month_name = $month_names[$display_month];

// Calculate previous and next month
$prev_month = $display_month == 1 ? 12 : $display_month - 1;
$prev_year = $display_month == 1 ? $display_year - 1 : $display_year;
$next_month = $display_month == 12 ? 1 : $display_month + 1;
$next_year = $display_month == 12 ? $display_year + 1 : $display_year;
?>

<div class="timeoff-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>Time off</h1>
        </div>
        <div class="page-actions">
            <button class="btn btn-outline-secondary">
                <i class="fas fa-envelope"></i>
            </button>
            <button class="btn btn-outline-secondary">
                <i class="fas fa-bell"></i>
            </button>
            <button class="btn btn-outline-primary" id="exportBtn">
                <i class="fas fa-download me-2"></i>Export CSV
            </button>
            <button class="btn btn-primary" id="addNewBtn">
                <i class="fas fa-plus me-2"></i>Add new
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="page-tabs">
        <button class="tab-button" data-tab="requested">Requested</button>
        <button class="tab-button" data-tab="balances">Balances</button>
        <button class="tab-button active" data-tab="calendar">Calendar</button>
    </div>

    <!-- Calendar Tab Content -->
    <div class="tab-content active" id="calendar-tab">
        <!-- Calendar Controls -->
        <div class="calendar-controls">
            <div class="calendar-navigation">
                <button class="btn btn-outline-secondary" onclick="navigateMonth(<?php echo $prev_month; ?>, <?php echo $prev_year; ?>)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="current-date">
                    <span id="currentDate">Today, <?php echo $current_day; ?> <?php echo $month_name; ?> <?php echo $current_year; ?></span>
                </div>
                <button class="btn btn-outline-secondary" onclick="navigateMonth(<?php echo $next_month; ?>, <?php echo $next_year; ?>)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="calendar-options">
                <select class="form-select" id="viewSelect">
                    <option value="monthly">Monthly</option>
                    <option value="weekly">Weekly</option>
                    <option value="daily">Daily</option>
                </select>
                <div class="view-toggle">
                    <button class="view-btn" data-view="list">
                        <i class="fas fa-list"></i>
                    </button>
                    <button class="view-btn" data-view="grid">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="view-btn active" data-view="calendar">
                        <i class="fas fa-calendar"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar-grid">
            <div class="calendar-header">
                <div class="day-header">Monday</div>
                <div class="day-header">Tuesday</div>
                <div class="day-header">Wednesday</div>
                <div class="day-header">Thursday</div>
                <div class="day-header">Friday</div>
                <div class="day-header weekend">Saturday</div>
                <div class="day-header weekend">Sunday</div>
            </div>
            
            <div class="calendar-body">
                <?php
                // Calculate days to show from previous month
                $prev_month_obj = new DateTime();
                $prev_month_obj->setDate($prev_year, $prev_month, 1);
                $prev_month_days = $prev_month_obj->format('t');
                $days_from_prev = $first_day - 1;
                
                // Show previous month days
                for ($i = $days_from_prev; $i > 0; $i--) {
                    $day = $prev_month_days - $i + 1;
                    echo '<div class="calendar-day other-month">' . $day . '</div>';
                }
                
                // Show current month days
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $date_str = sprintf('%04d-%02d-%02d', $display_year, $display_month, $day);
                    $is_today = ($day == $current_day && $display_month == $current_month && $display_year == $current_year);
                    $is_weekend = (($first_day + $day - 1) % 7 == 6 || ($first_day + $day - 1) % 7 == 0);
                    
                    echo '<div class="calendar-day' . ($is_today ? ' today' : '') . ($is_weekend ? ' weekend' : '') . '">';
                    echo '<div class="day-number">' . $day . '</div>';
                    
                    // Show leave entries for this day
                    if (isset($leave_entries[$date_str])) {
                        foreach ($leave_entries[$date_str] as $entry) {
                            echo '<div class="leave-entry ' . $entry['color'] . '">';
                            echo '<i class="' . $entry['icon'] . '"></i>';
                            echo '<span class="leave-type">' . $entry['type'] . '</span>';
                            echo '</div>';
                        }
                    }
                    
                    echo '</div>';
                }
                
                // Show next month days to fill the grid
                $total_cells = $days_from_prev + $days_in_month;
                $remaining_cells = 42 - $total_cells; // 6 weeks * 7 days
                for ($day = 1; $day <= $remaining_cells; $day++) {
                    echo '<div class="calendar-day other-month">' . $day . '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Requested Tab Content -->
    <div class="tab-content" id="requested-tab">
        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="card-content">
                    <div class="card-label">Total time off</div>
                    <div class="card-number">491</div>
                    <div class="card-trend positive">
                        <i class="fas fa-arrow-up"></i> +28
                    </div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-bolt"></i>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-content">
                    <div class="card-label">Approval time off</div>
                    <div class="card-number">276</div>
                    <div class="card-trend negative">
                        <i class="fas fa-arrow-down"></i> -12
                    </div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-content">
                    <div class="card-label">Rejected time off</div>
                    <div class="card-number">68</div>
                    <div class="card-trend positive">
                        <i class="fas fa-arrow-up"></i> +29
                    </div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-content">
                    <div class="card-label">Pending time off</div>
                    <div class="card-number">147</div>
                    <div class="card-trend negative">
                        <i class="fas fa-arrow-down"></i> -31
                    </div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-arrow-down"></i>
                </div>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="table-controls">
            <div class="search-control">
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search..." id="requestSearch">
                </div>
            </div>
            <div class="control-buttons">
                <button class="btn btn-outline-secondary btn-sm" id="filterBtn">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="sortBtn">
                    <i class="fas fa-sort me-1"></i>Sort
                </button>
            </div>
        </div>

        <!-- Time Off Requests Table -->
        <div class="table-container">
            <table class="requests-table">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllRequests" class="form-check-input">
                        </th>
                        <th>Employee name</th>
                        <th>Period time off</th>
                        <th>Request type</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th width="50"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input request-checkbox" value="1">
                        </td>
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    OCM
                                </div>
                                <div class="employee-details">
                                    <div class="employee-name">Olivia Carter Martinez</div>
                                    <div class="employee-team">Marketing Team</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="period-info">
                                <div class="period-dates">5 Apr → 7 Apr 2025</div>
                                <div class="period-days">3 days</div>
                            </div>
                        </td>
                        <td>
                            <span class="request-type sick-leave">Sick Leave</span>
                        </td>
                        <td>
                            <div class="reason-text">Recovering from flu...</div>
                        </td>
                        <td>
                            <span class="status-badge pending">
                                <i class="fas fa-clock me-1"></i>Pending
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="approveRequest(1)">
                                        <i class="fas fa-check me-2"></i>Approve
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="rejectRequest(1)">
                                        <i class="fas fa-times me-2"></i>Reject
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="viewRequest(1)">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input request-checkbox" value="2">
                        </td>
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    JR
                                </div>
                                <div class="employee-details">
                                    <div class="employee-name">James Richardson</div>
                                    <div class="employee-team">Development Team</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="period-info">
                                <div class="period-dates">12 Apr → 18 Apr 2025</div>
                                <div class="period-days">7 days</div>
                            </div>
                        </td>
                        <td>
                            <span class="request-type annual-leave">Annual Leave</span>
                        </td>
                        <td>
                            <div class="reason-text">Taking a family vacation...</div>
                        </td>
                        <td>
                            <span class="status-badge approved">
                                <i class="fas fa-check me-1"></i>Approve
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="viewRequest(2)">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="editRequest(2)">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input request-checkbox" value="3">
                        </td>
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    SM
                                </div>
                                <div class="employee-details">
                                    <div class="employee-name">Sophia Martinez</div>
                                    <div class="employee-team">Design Team</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="period-info">
                                <div class="period-dates">20 Apr → 21 Apr 2025</div>
                                <div class="period-days">2 days</div>
                            </div>
                        </td>
                        <td>
                            <span class="request-type personal-leave">Personal Leave</span>
                        </td>
                        <td>
                            <div class="reason-text">Attending a wedding...</div>
                        </td>
                        <td>
                            <span class="status-badge rejected">
                                <i class="fas fa-times me-1"></i>Rejected
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="viewRequest(3)">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="editRequest(3)">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input request-checkbox" value="4">
                        </td>
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    EB
                                </div>
                                <div class="employee-details">
                                    <div class="employee-name">Ethan Bennett</div>
                                    <div class="employee-team">Marketing Team</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="period-info">
                                <div class="period-dates">25 Apr → 25 Apr 2025</div>
                                <div class="period-days">1 day</div>
                            </div>
                        </td>
                        <td>
                            <span class="request-type sick-leave">Sick Leave</span>
                        </td>
                        <td>
                            <div class="reason-text">Visiting the doctor...</div>
                        </td>
                        <td>
                            <span class="status-badge approved">
                                <i class="fas fa-check me-1"></i>Approve
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="viewRequest(4)">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="editRequest(4)">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input request-checkbox" value="5">
                        </td>
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    PP
                                </div>
                                <div class="employee-details">
                                    <div class="employee-name">Phillip Passaquindici</div>
                                    <div class="employee-team">Management Team</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="period-info">
                                <div class="period-dates">1 May → 5 May 2025</div>
                                <div class="period-days">5 days</div>
                            </div>
                        </td>
                        <td>
                            <span class="request-type annual-leave">Annual Leave</span>
                        </td>
                        <td>
                            <div class="reason-text">Traveling abroad...</div>
                        </td>
                        <td>
                            <span class="status-badge pending">
                                <i class="fas fa-clock me-1"></i>Pending
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="approveRequest(5)">
                                        <i class="fas fa-check me-2"></i>Approve
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="rejectRequest(5)">
                                        <i class="fas fa-times me-2"></i>Reject
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="viewRequest(5)">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <div class="pagination-info">
                <select class="form-select form-select-sm" id="perPageSelect">
                    <option value="10" selected>10 records</option>
                    <option value="25">25 records</option>
                    <option value="50">50 records</option>
                </select>
            </div>
            <div class="pagination-controls">
                <button class="btn btn-outline-secondary btn-sm" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="btn btn-primary btn-sm">1</button>
                <button class="btn btn-outline-secondary btn-sm">2</button>
                <button class="btn btn-outline-secondary btn-sm">3</button>
                <span class="pagination-ellipsis">...</span>
                <button class="btn btn-outline-secondary btn-sm">50</button>
                <button class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="pagination-info">
                <span>10 - 491</span>
            </div>
        </div>
    </div>

    <!-- Balances Tab Content -->
    <div class="tab-content" id="balances-tab">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-balance-scale fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Leave Balances</h5>
                <p class="text-muted">This view is under construction.</p>
            </div>
        </div>
    </div>
</div>

<style>
.timeoff-container {
    padding: var(--spacing-lg);
    background-color: var(--interface-bg);
    min-height: 100vh;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-xl);
}

.page-title h1 {
    font-size: 2rem;
    font-weight: 600;
    color: var(--interface-text);
    margin: 0;
}

.page-actions {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.page-tabs {
    display: flex;
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
    border-bottom: 1px solid var(--interface-border);
}

.tab-button {
    background: none;
    border: none;
    padding: var(--spacing-md) 0;
    font-weight: 500;
    color: var(--muted-color);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.tab-button.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-button:hover {
    color: var(--interface-text);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.calendar-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
    padding: var(--spacing-md);
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
}

.calendar-controls:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
}

.calendar-navigation {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.current-date {
    font-weight: 600;
    color: var(--interface-text);
    min-width: 200px;
    text-align: center;
}

.calendar-options {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.view-toggle {
    display: flex;
    gap: var(--spacing-xs);
}

.view-btn {
    background: none;
    border: 1px solid var(--interface-border);
    padding: var(--spacing-sm);
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 0;
}

.view-btn.active {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.view-btn:hover {
    background-color: var(--light-color);
}

.calendar-grid {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background-color: #f8f9fa;
    border-bottom: 2px solid var(--interface-border);
}

.day-header {
    padding: var(--spacing-md);
    font-weight: 600;
    color: var(--interface-text);
    text-align: center;
    border-right: 1px solid var(--interface-border);
}

.day-header.weekend {
    background-color: #f1f3f4;
    color: var(--muted-color);
}

.day-header:last-child {
    border-right: none;
}

.calendar-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    min-height: 500px;
}

.calendar-day {
    border-right: 1px solid var(--interface-border);
    border-bottom: 1px solid var(--interface-border);
    padding: var(--spacing-sm);
    min-height: 80px;
    position: relative;
    background: white;
}

.calendar-day:last-child {
    border-right: none;
}

.calendar-day.other-month {
    background-color: #f8f9fa;
    color: var(--muted-color);
}

.calendar-day.weekend {
    background-color: #f1f3f4;
    background-image: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 2px,
        rgba(0,0,0,0.05) 2px,
        rgba(0,0,0,0.05) 4px
    );
}

.calendar-day.today {
    border: 2px solid #ff6b35;
    background-color: #fff5f0;
}

.day-number {
    font-weight: 600;
    color: var(--interface-text);
    margin-bottom: var(--spacing-xs);
}

.leave-entry {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: 2px 4px;
    margin-bottom: 2px;
    font-size: 0.75rem;
    border-radius: 0;
    cursor: pointer;
    transition: all 0.3s ease;
}

.leave-entry:hover {
    opacity: 0.8;
}

.leave-entry.warning {
    background-color: #fff3cd;
    color: #856404;
    border-left: 3px solid #ffc107;
}

.leave-entry.success {
    background-color: #d4edda;
    color: #155724;
    border-left: 3px solid #28a745;
}

.leave-entry.info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-left: 3px solid #17a2b8;
}

.leave-entry.danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 3px solid #dc3545;
}

.leave-type {
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Requests Table Styles */
.requests-table {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
}

.requests-table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid var(--interface-border);
    padding: var(--spacing-md);
    font-weight: 600;
    color: var(--interface-text);
    text-align: left;
}

.requests-table td {
    padding: var(--spacing-md);
    border-bottom: 1px solid var(--interface-border);
    vertical-align: middle;
}

.requests-table tbody tr:hover {
    background-color: #f8f9fa;
}

.period-info {
    display: flex;
    flex-direction: column;
}

.period-dates {
    font-weight: 600;
    color: var(--interface-text);
    margin-bottom: 2px;
}

.period-days {
    font-size: 0.875rem;
    color: var(--muted-color);
}

.request-type {
    padding: 0.25rem 0.75rem;
    border-radius: 0;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.request-type.sick-leave {
    background-color: #fff3cd;
    color: #856404;
}

.request-type.annual-leave {
    background-color: #d4edda;
    color: #155724;
}

.request-type.personal-leave {
    background-color: #d1ecf1;
    color: #0c5460;
}

.request-type.maternity-leave {
    background-color: #f8d7da;
    color: #721c24;
}

.reason-text {
    font-size: 0.875rem;
    color: var(--muted-color);
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 0;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
}

.status-badge.pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-badge.approved {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.rejected {
    background-color: #f8d7da;
    color: #721c24;
}

.employee-team {
    font-size: 0.875rem;
    color: var(--muted-color);
    margin-top: 2px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-md);
    }
    
    .page-actions {
        width: 100%;
        justify-content: flex-end;
    }
    
    .calendar-controls {
        flex-direction: column;
        gap: var(--spacing-md);
    }
    
    .calendar-navigation {
        width: 100%;
        justify-content: center;
    }
    
    .calendar-options {
        width: 100%;
        justify-content: space-between;
    }
    
    .calendar-day {
        min-height: 60px;
        padding: var(--spacing-xs);
    }
    
    .leave-entry {
        font-size: 0.7rem;
        padding: 1px 2px;
    }
    
    .requests-table {
        font-size: 0.875rem;
    }
    
    .requests-table th,
    .requests-table td {
        padding: var(--spacing-sm);
    }
    
    .reason-text {
        max-width: 150px;
    }
}

/* Card styling to match HR admin dashboard */
.card-modern,
.card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    background: #ffffff;
    overflow: hidden;
    transition: all 0.3s ease;
    outline: none !important;
}

.card-modern:hover,
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0 !important;
    outline: none !important;
}

.card-modern:focus,
.card:focus,
.card-modern:focus-visible,
.card:focus-visible {
    outline: none !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0 !important;
}

/* Summary cards with enhanced shadows */
.summary-card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
    outline: none !important;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0 !important;
    outline: none !important;
}

.summary-card:focus,
.summary-card:focus-visible {
    outline: none !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0 !important;
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

/* Summary cards container */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

/* Table container with shadows */
.table-container {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: var(--spacing-lg);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
}

.table-container:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
}

/* Table controls with shadows */
.table-controls {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
/* Dark theme support for Time Off page */
html[data-theme="dark"] .timeoff-container {
    background-color: transparent;
    color: var(--interface-text);
}

html[data-theme="dark"] .page-title h1 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-tabs {
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .tab-button {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .tab-button.active {
    color: var(--primary-color) !important;
    border-bottom-color: var(--primary-color) !important;
}

html[data-theme="dark"] .tab-button:hover {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .calendar-controls {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .current-date {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .calendar-controls .form-select {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .view-btn {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .view-btn.active {
    background-color: var(--primary-color) !important;
    color: white !important;
    border-color: var(--primary-color) !important;
}

html[data-theme="dark"] .view-btn:hover {
    background-color: var(--interface-hover) !important;
}

html[data-theme="dark"] .calendar-grid {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .calendar-header {
    background-color: #1a1d23 !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .day-header {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-right-color: var(--interface-border) !important;
}

html[data-theme="dark"] .day-header.weekend {
    background-color: rgba(30, 41, 59, 0.5) !important;
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .calendar-day {
    background: #1a1d23 !important;
    border-right-color: var(--interface-border) !important;
    border-bottom-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .calendar-day.other-month {
    background-color: rgba(30, 41, 59, 0.5) !important;
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .calendar-day.weekend {
    background-color: rgba(30, 41, 59, 0.5) !important;
    background-image: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 2px,
        rgba(255, 255, 255, 0.05) 2px,
        rgba(255, 255, 255, 0.05) 4px
    ) !important;
}

html[data-theme="dark"] .calendar-day.today {
    border-color: #ff6b35 !important;
    background-color: rgba(255, 107, 53, 0.1) !important;
}

html[data-theme="dark"] .day-number {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .summary-card {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .summary-card .card-label {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .summary-card .card-number {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table-controls {
    background: transparent !important;
}

html[data-theme="dark"] .search-input {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .search-input input {
    background-color: transparent !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .search-input i {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .table-container {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .requests-table {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .requests-table thead {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .requests-table th {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .requests-table tbody {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .requests-table tbody tr {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .requests-table tbody tr:hover {
    background-color: var(--interface-hover) !important;
}

html[data-theme="dark"] .requests-table td {
    background-color: transparent !important;
    color: var(--interface-text) !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .employee-name {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .employee-team {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .period-dates {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .period-days {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .reason-text {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .dropdown-menu {
    background-color: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .dropdown-item {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .dropdown-item:hover {
    background-color: var(--interface-hover) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .dropdown-divider {
    border-top-color: var(--interface-border) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });
    
    // View toggle functionality
    const viewButtons = document.querySelectorAll('.view-btn');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.getAttribute('data-view');
            
            // Remove active class from all view buttons
            viewButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Handle view change
            console.log('Switched to', view, 'view');
        });
    });
    
    // Export functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        // Simple CSV export for calendar data
        let csv = 'Date,Type,Employee,Status\n';
        
        // Add sample data
        const sampleData = [
            ['2025-03-05', 'Sick leave', 'John Doe', 'Approved'],
            ['2025-03-07', 'Sick leave', 'Jane Smith', 'Approved'],
            ['2025-03-07', 'Annual leave', 'Mike Johnson', 'Approved'],
            ['2025-03-13', 'Personal leave', 'Sarah Wilson', 'Pending'],
            ['2025-03-19', 'Annual leave', 'David Brown', 'Approved'],
            ['2025-03-20', 'Annual leave', 'Lisa Davis', 'Approved'],
            ['2025-03-22', 'Personal leave', 'Tom Wilson', 'Approved'],
            ['2025-03-28', 'National holiday', 'System', 'Holiday'],
            ['2025-03-30', 'Personal leave', 'Anna Taylor', 'Pending']
        ];
        
        sampleData.forEach(row => {
            csv += `"${row[0]}","${row[1]}","${row[2]}","${row[3]}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'timeoff_calendar.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    });
    
    // Add new functionality
    document.getElementById('addNewBtn').addEventListener('click', function() {
        alert('Add new time off request functionality will be implemented soon.');
    });
    
    // Request search functionality
    const requestSearch = document.getElementById('requestSearch');
    if (requestSearch) {
        requestSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.requests-table tbody tr');
            
            rows.forEach(row => {
                const employeeName = row.querySelector('.employee-name').textContent.toLowerCase();
                const employeeTeam = row.querySelector('.employee-team').textContent.toLowerCase();
                const requestType = row.querySelector('.request-type').textContent.toLowerCase();
                const reason = row.querySelector('.reason-text').textContent.toLowerCase();
                
                if (employeeName.includes(searchTerm) || employeeTeam.includes(searchTerm) || 
                    requestType.includes(searchTerm) || reason.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Select all requests functionality
    const selectAllRequests = document.getElementById('selectAllRequests');
    if (selectAllRequests) {
        selectAllRequests.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.request-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Filter and Sort button placeholders
    const filterBtn = document.getElementById('filterBtn');
    const sortBtn = document.getElementById('sortBtn');
    
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            alert('Filter functionality will be implemented soon.');
        });
    }
    
    if (sortBtn) {
        sortBtn.addEventListener('click', function() {
            alert('Sort functionality will be implemented soon.');
        });
    }
});

function navigateMonth(month, year) {
    const url = new URL(window.location);
    url.searchParams.set('month', month);
    url.searchParams.set('year', year);
    window.location.href = url.toString();
}

// Request management functions
function approveRequest(id) {
    if (confirm('Are you sure you want to approve this time off request?')) {
        // Implement approval logic
        console.log('Approving request:', id);
        alert('Request approved successfully!');
    }
}

function rejectRequest(id) {
    if (confirm('Are you sure you want to reject this time off request?')) {
        // Implement rejection logic
        console.log('Rejecting request:', id);
        alert('Request rejected successfully!');
    }
}

function viewRequest(id) {
    // Implement view request details
    console.log('Viewing request:', id);
    alert('View request details functionality will be implemented soon.');
}

function editRequest(id) {
    // Implement edit request
    console.log('Editing request:', id);
    alert('Edit request functionality will be implemented soon.');
}
</script>
