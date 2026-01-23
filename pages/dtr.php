<?php
$page_title = 'Daily Time Record (DTR) - Golden Z-5 HR System';
$page = 'dtr';

// Get database connection
$pdo = get_db_connection();

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-t'); // Last day of current month
$employee_id = $_GET['employee_id'] ?? '';
$post_id = $_GET['post_id'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Get all employees for filter dropdown
$employees = get_employees();

// Get all posts for filter dropdown
$posts = get_posts(['status' => 'Active']);

// Get selected month and year for display
$selected_month = date('F Y', strtotime($date_from));
$days_in_month = date('t', strtotime($date_from));
$month_start = date('Y-m-01', strtotime($date_from));

// Mock DTR data - in real implementation, this would come from database
$dtr_records = [];
$sample_employees = array_slice($employees, 0, 15); // Limit to 15 for demo

$total_complete = 0;
$total_missing = 0;
$total_on_leave = 0;
$total_hours_all = 0;

foreach ($sample_employees as $emp) {
    $employee_record = [
        'id' => $emp['id'],
        'name' => ($emp['surname'] ?? '') . ', ' . ($emp['first_name'] ?? ''),
        'post' => $emp['post'] ?? 'N/A',
        'days' => [],
        'total_days' => 0,
        'total_hours' => 0,
        'complete_days' => 0,
        'missing_days' => 0,
        'status' => 'Complete',
    ];
    
    // Generate data for each day of the month
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = date('Y-m-' . str_pad($day, 2, '0', STR_PAD_LEFT), strtotime($month_start));
        $day_of_week = date('w', strtotime($date)); // 0 = Sunday, 6 = Saturday
        
        // Check if weekend
        if ($day_of_week == 0 || $day_of_week == 6) {
            $employee_record['days'][$day] = [
                'date' => $date,
                'time_in' => null,
                'time_out' => null,
                'status' => 'RD',
                'hours' => 0,
                'badge_class' => 'bg-secondary',
            ];
            continue;
        }
        
        // Randomly assign attendance status
        $rand = rand(1, 100);
        if ($rand <= 3) {
            // 3% absent
            $employee_record['days'][$day] = [
                'date' => $date,
                'time_in' => null,
                'time_out' => null,
                'status' => 'A',
                'hours' => 0,
                'badge_class' => 'bg-danger',
            ];
            $employee_record['missing_days']++;
        } elseif ($rand <= 8) {
            // 5% on leave
            $employee_record['days'][$day] = [
                'date' => $date,
                'time_in' => null,
                'time_out' => null,
                'status' => 'L',
                'hours' => 0,
                'badge_class' => 'bg-info',
            ];
        } elseif ($rand <= 18) {
            // 10% late
            $time_in = date('H:i', strtotime('08:' . rand(15, 59) . ':00'));
            $time_out = date('H:i', strtotime('17:' . rand(0, 30) . ':00'));
            $hours = 8.5;
            $employee_record['days'][$day] = [
                'date' => $date,
                'time_in' => $time_in,
                'time_out' => $time_out,
                'status' => 'Late',
                'hours' => $hours,
                'badge_class' => 'bg-warning',
            ];
            $employee_record['total_days']++;
            $employee_record['total_hours'] += $hours;
            $employee_record['complete_days']++;
        } else {
            // 82% present
            $time_in = date('H:i', strtotime('08:' . rand(0, 10) . ':00'));
            $time_out = date('H:i', strtotime('17:' . rand(0, 30) . ':00'));
            $hours = 8.5;
            $employee_record['days'][$day] = [
                'date' => $date,
                'time_in' => $time_in,
                'time_out' => $time_out,
                'status' => 'P',
                'hours' => $hours,
                'badge_class' => 'bg-success',
            ];
            $employee_record['total_days']++;
            $employee_record['total_hours'] += $hours;
            $employee_record['complete_days']++;
        }
    }
    
    // Determine overall status
    if ($employee_record['missing_days'] > 0) {
        $employee_record['status'] = 'Missing';
        $total_missing++;
    } else {
        $employee_record['status'] = 'Complete';
        $total_complete++;
    }
    
    $total_hours_all += $employee_record['total_hours'];
    
    $dtr_records[] = $employee_record;
}

// Apply filters
if (!empty($employee_id)) {
    $dtr_records = array_filter($dtr_records, function($r) use ($employee_id) {
        return $r['id'] == $employee_id;
    });
}

if (!empty($post_id)) {
    $selected_post = null;
    foreach ($posts as $post) {
        if ($post['id'] == $post_id) {
            $selected_post = $post;
            break;
        }
    }
    if ($selected_post) {
        $dtr_records = array_filter($dtr_records, function($r) use ($selected_post) {
            return $r['post'] == $selected_post['post_title'];
        });
    }
}

if (!empty($status_filter)) {
    $dtr_records = array_filter($dtr_records, function($r) use ($status_filter) {
        return strtolower($r['status']) === strtolower($status_filter);
    });
}

// Count on leave
foreach ($dtr_records as $record) {
    foreach ($record['days'] as $day) {
        if ($day['status'] === 'L') {
            $total_on_leave++;
            break;
        }
    }
}
?>

<div class="container-fluid hrdash">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold">Daily Time Record</h4>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Track and review official monthly DTR for <?php echo htmlspecialchars($selected_month); ?></p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-modern btn-sm" id="exportPrintBtn" title="Print DTR">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button type="button" class="btn btn-outline-modern btn-sm" id="exportPdfBtn" title="Export to PDF">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </button>
                    <button type="button" class="btn btn-outline-modern btn-sm" id="exportExcelBtn" title="Export to Excel">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Complete Logs</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($total_complete); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Employees with complete DTR</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Missing Logs</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-danger"><?php echo number_format($total_missing); ?></div>
                </div>
                <div class="hrdash-stat__meta">Incomplete attendance records</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">On Leave</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-info"><?php echo number_format($total_on_leave); ?></div>
                </div>
                <div class="hrdash-stat__meta">Employees on leave this month</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Total Hours</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($total_hours_all, 1); ?>h</div>
                </div>
                <div class="hrdash-stat__meta">Combined working hours</div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card card-modern mb-4">
        <div class="card-body-modern">
            <form method="GET" action="" class="mb-0">
                <input type="hidden" name="page" value="dtr">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Post/Deployment</label>
                        <select name="post_id" class="form-select">
                            <option value="">All Posts</option>
                            <?php foreach ($posts as $post): ?>
                                <option value="<?php echo $post['id']; ?>" <?php echo $post_id == $post['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($post['post_title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select">
                            <option value="">All Employees</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" <?php echo $employee_id == $emp['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(($emp['surname'] ?? '') . ', ' . ($emp['first_name'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Complete" <?php echo $status_filter === 'Complete' ? 'selected' : ''; ?>>Complete</option>
                            <option value="Missing" <?php echo $status_filter === 'Missing' ? 'selected' : ''; ?>>Missing</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary-modern">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-modern" onclick="window.location.href='?page=dtr'" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Legend -->
    <div class="card card-modern mb-3">
        <div class="card-body-modern py-2">
            <div class="dtr-legend">
                <span class="dtr-legend-label">Legend:</span>
                <span class="dtr-legend-item"><span class="dtr-legend-indicator dtr-legend-present"></span>Present</span>
                <span class="dtr-legend-item"><span class="dtr-legend-indicator dtr-legend-late"></span>Late</span>
                <span class="dtr-legend-item"><span class="dtr-legend-indicator dtr-legend-absent"></span>Absent</span>
                <span class="dtr-legend-item"><span class="dtr-legend-indicator dtr-legend-leave"></span>Leave</span>
                <span class="dtr-legend-item"><span class="dtr-legend-indicator dtr-legend-rd"></span>Rest Day</span>
                <span class="dtr-legend-item"><span class="dtr-legend-indicator dtr-legend-holiday"></span>Holiday</span>
            </div>
        </div>
    </div>

    <!-- DTR Grid Table - Print-Ready Container -->
    <div class="card card-modern">
        <div class="card-body-modern p-0">
            <!-- Print Button -->
            <div class="dtr-print-controls p-3 border-bottom">
                <button type="button" class="btn btn-primary-modern" onclick="window.printDTR()">
                    <i class="fas fa-print me-1"></i>Print DTR
                </button>
            </div>
            
            <!-- DTR Document Container (Print-Ready) -->
            <div id="dtr-print-container" class="dtr-print-container">
                <!-- Header Section: Date Covered & Post/Detachment -->
                <div class="dtr-document-header">
                    <div class="dtr-header-field">
                        <span class="dtr-header-label">Date Covered:</span>
                        <span class="dtr-header-value"><?php echo date('F d', strtotime($date_from)); ?> - <?php echo date('F d, Y', strtotime($date_to)); ?></span>
                    </div>
                    <div class="dtr-header-field">
                        <span class="dtr-header-label">Post / Detachment:</span>
                        <span class="dtr-header-value"><?php echo $post_id ? htmlspecialchars($posts[array_search($post_id, array_column($posts, 'id'))]['post_title'] ?? 'All Posts') : 'All Posts'; ?></span>
                    </div>
                </div>
                
                <!-- Main DTR Table -->
                <div class="dtr-table-wrapper">
                    <table class="table table-bordered table-sm dtr-table mb-0">
                        <colgroup>
                            <col class="dtr-col-name">
                            <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                                <col class="dtr-col-day">
                            <?php endfor; ?>
                            <col class="dtr-col-days">
                            <col class="dtr-col-hours">
                            <col class="dtr-col-signature">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th class="dtr-sticky-col dtr-sticky-name text-center align-middle">
                                    <strong>NAME</strong>
                                </th>
                                <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                                    <?php 
                                    // Calculate date for this day
                                    $date = date('Y-m-' . str_pad($day, 2, '0', STR_PAD_LEFT), strtotime($month_start));
                                    $day_name = date('D', strtotime($date));
                                    // Weekend detection: date('w') returns 0 (Sunday) or 6 (Saturday)
                                    $is_weekend = in_array(date('w', strtotime($date)), [0, 6]);
                                    // Visual separator every 7 days to chunk the month
                                    $separator_class = ($day % 7 === 0 && $day < $days_in_month) ? 'dtr-day-separator' : '';
                                    ?>
                                    <th class="dtr-day-header text-center <?php echo $is_weekend ? 'dtr-weekend' : ''; ?> <?php echo $separator_class; ?>" data-day="<?php echo $day; ?>" data-weekend="<?php echo $is_weekend ? '1' : '0'; ?>">
                                        <div class="fw-bold"><?php echo $day; ?></div>
                                        <div class="dtr-day-name"><?php echo $day_name; ?></div>
                                    </th>
                                <?php endfor; ?>
                                <th colspan="2" class="dtr-total-header text-center align-middle">
                                    <strong>TOTAL</strong>
                                </th>
                                <th class="dtr-summary-col dtr-sticky-right dtr-sticky-signature text-center align-middle">
                                    <strong>SIGNATURE</strong>
                                </th>
                            </tr>
                            <tr>
                                <th class="dtr-sticky-col dtr-sticky-name"></th>
                                <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                                    <th class="dtr-day-header-sub"></th>
                                <?php endfor; ?>
                                <th class="dtr-summary-col dtr-sticky-right dtr-sticky-days text-center align-middle">
                                    <strong>Days</strong>
                                </th>
                                <th class="dtr-summary-col dtr-sticky-right dtr-sticky-hours text-center align-middle">
                                    <strong>Hours</strong>
                                </th>
                                <th class="dtr-summary-col dtr-sticky-right dtr-sticky-signature"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dtr_records)): ?>
                                <tr>
                                    <td colspan="<?php echo $days_in_month + 4; ?>" class="text-center py-5">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No DTR records found for the selected period</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dtr_records as $record): ?>
                                    <tr class="dtr-row">
                                        <td class="dtr-sticky-col dtr-sticky-name">
                                            <div class="fw-semibold text-nowrap"><?php echo htmlspecialchars($record['name']); ?></div>
                                        </td>
                                        <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                                            <?php 
                                            $day_data = $record['days'][$day] ?? null;
                                            // Weekend detection: consistent with header calculation
                                            $date_for_day = date('Y-m-' . str_pad($day, 2, '0', STR_PAD_LEFT), strtotime($month_start));
                                            $is_weekend = in_array(date('w', strtotime($date_for_day)), [0, 6]);
                                            // Visual separator every 7 days
                                            $separator_class = ($day % 7 === 0 && $day < $days_in_month) ? 'dtr-day-separator' : '';
                                            $status_class = $day_data ? 'dtr-status-' . strtolower($day_data['status']) : '';
                                            ?>
                                            <td class="dtr-day-cell text-center <?php echo $is_weekend ? 'dtr-weekend' : ''; ?> <?php echo $separator_class; ?> <?php echo $status_class; ?>" data-day="<?php echo $day; ?>" data-status="<?php echo $day_data ? htmlspecialchars($day_data['status']) : ''; ?>" data-weekend="<?php echo $is_weekend ? '1' : '0'; ?>">
                                                <?php if ($day_data): ?>
                                                    <?php if ($day_data['status'] === 'P' || $day_data['status'] === 'Late'): ?>
                                                        <div class="dtr-time-stack">
                                                            <div class="dtr-time-in"><?php echo htmlspecialchars($day_data['time_in']); ?></div>
                                                            <div class="dtr-time-divider"></div>
                                                            <div class="dtr-time-out"><?php echo htmlspecialchars($day_data['time_out']); ?></div>
                                                        </div>
                                                        <?php if ($day_data['status'] === 'Late'): ?>
                                                            <div class="dtr-status-indicator dtr-status-late-indicator" title="Late arrival"></div>
                                                        <?php else: ?>
                                                            <div class="dtr-status-indicator dtr-status-present-indicator" title="Present"></div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="dtr-status-badge-wrapper">
                                                            <span class="dtr-status-badge dtr-status-<?php echo strtolower($day_data['status']); ?>"><?php echo htmlspecialchars($day_data['status']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="dtr-empty">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endfor; ?>
                                        <td class="dtr-summary-col dtr-sticky-right dtr-sticky-days text-center fw-bold">
                                            <?php echo $record['total_days']; ?>
                                        </td>
                                        <td class="dtr-summary-col dtr-sticky-right dtr-sticky-hours text-center fw-bold">
                                            <?php echo number_format($record['total_hours'], 1); ?>
                                        </td>
                                        <td class="dtr-summary-col dtr-sticky-right dtr-sticky-signature text-center">
                                            <span class="dtr-signature-placeholder">_____</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   DTR TABLE ENHANCED - COMPACT & READABLE
   ============================================ */

/* Table Wrapper - Smooth Scrolling with Locked Layout */
.dtr-table-wrapper {
    max-height: 75vh;
    overflow: auto;
    position: relative;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
    width: 100%;
}

.dtr-table-wrapper::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.dtr-table-wrapper::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 5px;
}

.dtr-table-wrapper::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 5px;
}

.dtr-table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Base Table - Fixed Layout for Consistent Column Alignment */
.dtr-table {
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.7rem;
    width: 100%;
    min-width: max-content;
    background: #ffffff;
}

/* Explicit Column Width Definitions for table-layout: fixed */
.dtr-col-name {
    width: 180px;
}

.dtr-col-day {
    width: 58px;
}

.dtr-col-days {
    width: 55px;
}

.dtr-col-hours {
    width: 65px;
}

.dtr-col-signature {
    width: 120px;
}

.dtr-table th,
.dtr-table td {
    border: 1px solid #e5e7eb;
    vertical-align: middle;
    padding: 0.4rem 0.3rem;
    line-height: 1.35;
}

/* Header Row - Sticky with High Contrast */
.dtr-table thead th {
    background: #1f2937;
    color: #ffffff;
    font-weight: 600;
    font-size: 0.7rem;
    position: sticky;
    top: 0;
    z-index: 20;
    border-bottom: 2px solid #111827;
    padding: 0.5rem 0.3rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

/* Document Header Section (Date Covered & Post/Detachment) */
.dtr-document-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 2px solid #1f2937;
    background: #ffffff;
}

.dtr-header-field {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dtr-header-label {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1f2937;
    white-space: nowrap;
}

.dtr-header-value {
    font-size: 0.85rem;
    color: #374151;
    border-bottom: 1px solid #9ca3af;
    min-width: 200px;
    padding-bottom: 0.2rem;
}

/* Print Controls */
.dtr-print-controls {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

/* Print Container */
.dtr-print-container {
    background: #ffffff;
}

/* Sticky Left Columns - NAME only */
.dtr-sticky-col {
    position: sticky;
    background: #ffffff;
    z-index: 10;
    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
}

.dtr-sticky-name {
    left: 0;
    width: 180px !important;
    min-width: 180px !important;
    max-width: 180px !important;
    padding-left: 0.75rem;
    padding-right: 0.5rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    box-sizing: border-box;
}

.dtr-table thead .dtr-sticky-col {
    background: #1f2937;
    z-index: 25;
    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
}

/* Employee Name - Bold for Hierarchy & Easy Scanning */
.dtr-sticky-name .fw-semibold {
    font-weight: 700;
    font-size: 0.76rem;
    color: #111827;
    line-height: 1.4;
    letter-spacing: -0.01em;
}

/* Day Headers - Fixed Width, Compact, Locked */
.dtr-day-header {
    width: 58px !important;
    min-width: 58px !important;
    max-width: 58px !important;
    padding: 0.4rem 0.25rem !important;
    white-space: nowrap;
    overflow: hidden;
    box-sizing: border-box;
}

.dtr-day-header .fw-bold {
    font-size: 0.75rem;
    font-weight: 700;
}

.dtr-day-name {
    font-size: 0.6rem;
    color: #9ca3af;
    font-weight: 400;
    text-transform: uppercase;
    margin-top: 0.15rem;
}

/* Visual Separators - Every 7 Days for Month Chunking */
.dtr-day-separator {
    border-right: 2px solid #cbd5e1 !important;
    position: relative;
}

.dtr-day-separator::after {
    content: '';
    position: absolute;
    right: -1px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, transparent 0%, #cbd5e1 10%, #cbd5e1 90%, transparent 100%);
    pointer-events: none;
}

/* Day Cells - Compact with Status Indicators, Locked Width */
.dtr-day-cell {
    width: 58px !important;
    min-width: 58px !important;
    max-width: 58px !important;
    padding: 0.35rem 0.25rem !important;
    font-size: 0.65rem;
    background: #ffffff;
    position: relative;
    transition: background-color 0.2s ease, box-shadow 0.2s ease;
    white-space: nowrap;
    overflow: hidden;
    box-sizing: border-box;
    text-align: center;
}

/* Standardize cell padding for consistency */
.dtr-table th,
.dtr-table td {
    padding: 0.4rem 0.3rem;
    box-sizing: border-box;
    white-space: normal;
}

.dtr-table thead th {
    padding: 0.5rem 0.3rem;
    box-sizing: border-box;
}

/* Weekend Highlighting - Clear but Subtle */
.dtr-weekend {
    background-color: #f0f4f8 !important;
    border-left: 2px solid #cbd5e1 !important;
    border-right: 2px solid #cbd5e1 !important;
}

.dtr-table thead .dtr-weekend {
    background-color: #1e3a5f !important;
    border-left: 2px solid #3b82f6 !important;
    border-right: 2px solid #3b82f6 !important;
}

.dtr-table tbody .dtr-weekend {
    background-color: #f0f4f8 !important;
}

/* Ensure weekend cells maintain highlight on hover */
.dtr-row:hover .dtr-weekend {
    background-color: #e0e7ef !important;
}

/* Time Stack - Monospace for Perfect Alignment */
.dtr-time-stack {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    align-items: center;
    position: relative;
    padding: 0.2rem 0;
    min-height: 2.2rem;
    max-height: 2.2rem;
    justify-content: center;
    overflow: hidden;
}

.dtr-time-in,
.dtr-time-out {
    font-family: 'Courier New', 'Monaco', 'Menlo', 'Consolas', monospace;
    font-size: 0.64rem;
    font-weight: 600;
    line-height: 1.3;
    letter-spacing: 0.03em;
    font-variant-numeric: tabular-nums;
    display: block;
    width: 100%;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
}

.dtr-time-in {
    color: #059669;
    font-weight: 700;
}

.dtr-time-out {
    color: #0284c7;
    font-weight: 700;
}

.dtr-time-divider {
    width: 24px;
    height: 1px;
    background: #d1d5db;
    margin: 0.1rem 0;
    opacity: 0.6;
}

/* Status Indicators - Thin Accents (Not Heavy Fills) */
.dtr-status-indicator {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    border-radius: 0;
}

.dtr-status-present-indicator {
    background: #10b981;
    box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.2);
}

.dtr-status-late-indicator {
    background: #f59e0b;
    width: 3px;
    box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.2);
}

/* Status Badges - Light Background with Accent Border */
.dtr-status-badge-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 1.2rem;
    max-height: 2.2rem;
    padding: 0.1rem 0;
    overflow: hidden;
}

.dtr-status-badge {
    font-size: 0.6rem;
    font-weight: 600;
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    display: inline-block;
    border-left: 3px solid;
    background: transparent;
    white-space: nowrap;
}

/* Absent - Red with strong contrast */
.dtr-status-a {
    color: #991b1b;
    border-left-color: #dc2626;
    background: #fee2e2;
    font-weight: 700;
}

/* Leave - Blue */
.dtr-status-l {
    color: #1e40af;
    border-left-color: #3b82f6;
    background: #dbeafe;
}

/* Rest Day - Muted gray */
.dtr-status-rd {
    color: #4b5563;
    border-left-color: #9ca3af;
    background: #f3f4f6;
}

/* Holiday - Darker gray */
.dtr-status-h {
    color: #374151;
    border-left-color: #6b7280;
    background: #f3f4f6;
}

.dtr-empty {
    color: #d1d5db;
    font-size: 0.7rem;
    display: inline-block;
    width: 100%;
    text-align: center;
}

/* Ensure consistent row heights */
.dtr-row td {
    height: 3rem;
    max-height: 3rem;
    vertical-align: middle;
}

/* Sticky Right Columns - Summary, Locked Widths */
.dtr-sticky-right {
    position: sticky;
    background: #ffffff;
    z-index: 10;
    box-shadow: -2px 0 4px rgba(0, 0, 0, 0.05);
    white-space: nowrap;
    overflow: hidden;
}

/* TOTAL Header - Merged Column */
.dtr-total-header {
    background: #1f2937 !important;
    color: #ffffff !important;
    border-left: 2px solid #111827 !important;
}

.dtr-day-header-sub {
    background: #1f2937;
    color: #ffffff;
    border: 1px solid #374151;
    height: 0.5rem;
    padding: 0 !important;
}

/* Sticky Right Columns - Days, Hours, Signature */
.dtr-sticky-signature {
    right: 0;
    width: 120px !important;
    min-width: 120px !important;
    max-width: 120px !important;
    box-sizing: border-box;
}

.dtr-sticky-hours {
    right: 120px;
    width: 65px !important;
    min-width: 65px !important;
    max-width: 65px !important;
    background: #f9fafb;
    font-weight: 600;
    box-sizing: border-box;
}

.dtr-sticky-days {
    right: 185px;
    width: 55px !important;
    min-width: 55px !important;
    max-width: 55px !important;
    background: #f9fafb;
    font-weight: 600;
    box-sizing: border-box;
}

.dtr-table thead .dtr-sticky-right {
    background: #1f2937;
    z-index: 25;
    box-shadow: -2px 0 4px rgba(0, 0, 0, 0.1);
}

.dtr-signature-placeholder {
    color: #9ca3af;
    font-size: 0.75rem;
    font-style: italic;
    letter-spacing: 0.1em;
}

/* Row Hover - Full Row Highlight with Smooth Transition */
.dtr-row {
    transition: background-color 0.2s ease, box-shadow 0.2s ease;
}

.dtr-row:hover {
    background-color: #f0f9ff;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.08);
}

.dtr-row:hover .dtr-sticky-col,
.dtr-row:hover .dtr-sticky-right {
    background-color: #f0f9ff;
}

.dtr-row:hover .dtr-weekend {
    background-color: #e0e7ef !important;
}

/* Column Hover - Day Column Highlight (All cells in column) */
.dtr-day-cell {
    transition: background-color 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}

.dtr-day-cell:hover {
    background-color: #eff6ff !important;
    box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.2);
    z-index: 5;
}

/* Column highlight on hover - applied via JavaScript */
.dtr-table tbody tr .dtr-day-cell.dtr-column-hover {
    background-color: #eff6ff !important;
    box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.3);
}

.dtr-table tbody tr .dtr-day-cell.dtr-column-hover.dtr-weekend {
    background-color: #dbeafe !important;
}

.dtr-table thead .dtr-day-header.dtr-column-hover {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
    border-left-color: #2563eb !important;
    border-right-color: #2563eb !important;
}

/* Legend - Compact & Visually Aligned with Table */
.dtr-legend {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex-wrap: wrap;
    font-size: 0.68rem;
    padding: 0.5rem 0;
}

.dtr-legend-label {
    font-weight: 600;
    color: #374151;
    margin-right: 0.5rem;
    font-size: 0.7rem;
}

.dtr-legend-item {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    color: #4b5563;
    font-weight: 500;
}

.dtr-legend-indicator {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 2px;
    flex-shrink: 0;
    border-left: 3px solid;
    background: transparent;
}

.dtr-legend-present {
    border-left-color: #10b981;
    background: rgba(16, 185, 129, 0.1);
}

.dtr-legend-late {
    border-left-color: #f59e0b;
    background: rgba(245, 158, 11, 0.1);
}

.dtr-legend-absent {
    border-left-color: #dc2626;
    background: rgba(220, 38, 38, 0.1);
}

.dtr-legend-leave {
    border-left-color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
}

.dtr-legend-rd {
    border-left-color: #9ca3af;
    background: #f3f4f6;
}

.dtr-legend-holiday {
    border-left-color: #6b7280;
    background: #f3f4f6;
}

/* Prevent Column Drift - Lock All Widths */
.dtr-table col {
    width: auto;
}

.dtr-table th,
.dtr-table td {
    box-sizing: border-box;
    overflow: hidden;
}

/* Ensure borders don't affect width calculation */
.dtr-table {
    border-width: 0;
}

.dtr-table th:first-child,
.dtr-table td:first-child {
    border-left-width: 1px;
}

/* Prevent text from expanding cells */
.dtr-day-cell * {
    max-width: 100%;
    overflow: hidden;
}

/* Ensure sticky columns stay aligned on hover/scroll */
.dtr-sticky-col,
.dtr-sticky-right {
    will-change: auto;
    transform: translateZ(0);
}

/* Prevent text from expanding cells */
.dtr-day-cell > * {
    max-width: 100%;
    overflow: hidden;
}

/* Ensure consistent row heights across all rows */
.dtr-row td {
    height: 3rem;
    max-height: 3rem;
}

/* Responsive - Maintain Readability */
@media (max-width: 768px) {
    .dtr-sticky-name {
        width: 150px !important;
        min-width: 150px !important;
        max-width: 150px !important;
    }
    
    .dtr-day-header,
    .dtr-day-cell {
        width: 52px !important;
        min-width: 52px !important;
        max-width: 52px !important;
    }
    
    .dtr-time-in,
    .dtr-time-out {
        font-size: 0.6rem;
    }
    
    .dtr-sticky-days {
        right: 185px;
        width: 50px !important;
        min-width: 50px !important;
        max-width: 50px !important;
    }
    
    .dtr-sticky-hours {
        right: 120px;
        width: 60px !important;
        min-width: 60px !important;
        max-width: 60px !important;
    }
    
    .dtr-sticky-signature {
        right: 0;
        width: 100px !important;
        min-width: 100px !important;
        max-width: 100px !important;
    }
}

/* Print Styles */
@media print {
    body * {
        visibility: hidden;
    }
    
    #dtr-print-container,
    #dtr-print-container * {
        visibility: visible;
    }
    
    #dtr-print-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        page-break-inside: avoid;
    }
    
    .dtr-print-controls {
        display: none !important;
    }
    
    .dtr-table-wrapper {
        max-height: none;
        overflow: visible;
    }
    
    .dtr-sticky-col,
    .dtr-sticky-right {
        position: static;
        box-shadow: none;
    }
    
    .dtr-table {
        table-layout: fixed;
        border-collapse: collapse;
    }
    
    .dtr-table th,
    .dtr-table td {
        border: 1px solid #000 !important;
        padding: 0.3rem 0.2rem !important;
    }
    
    .dtr-table thead th {
        background: #000 !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .dtr-document-header {
        border-bottom: 2px solid #000 !important;
        page-break-after: avoid;
    }
}
</style>

<script>
// Print DTR Function
window.printDTR = function() {
    window.print();
};

// Export button handlers
document.addEventListener('DOMContentLoaded', function() {
    
    // Enhanced column hover effect - Highlight entire day column
    const dayCells = document.querySelectorAll('.dtr-day-cell[data-day]');
    const dayHeaders = document.querySelectorAll('.dtr-day-header[data-day]');
    
    function highlightColumn(day, highlight) {
        const className = 'dtr-column-hover';
        
        // Highlight all cells in this column
        document.querySelectorAll(`.dtr-day-cell[data-day="${day}"]`).forEach(c => {
            if (highlight) {
                c.classList.add(className);
            } else {
                c.classList.remove(className);
                // Restore original background
                const isWeekend = c.classList.contains('dtr-weekend');
                c.style.backgroundColor = isWeekend ? '#f0f4f8' : '';
            }
        });
        
        // Highlight header
        const header = document.querySelector(`.dtr-day-header[data-day="${day}"]`);
        if (header) {
            if (highlight) {
                header.classList.add(className);
            } else {
                header.classList.remove(className);
                const isWeekend = header.classList.contains('dtr-weekend');
                header.style.backgroundColor = isWeekend ? '#1e3a5f' : '#1f2937';
                header.style.color = '#ffffff';
            }
        }
    }
    
    dayCells.forEach(cell => {
        const day = cell.getAttribute('data-day');
        
        cell.addEventListener('mouseenter', function() {
            highlightColumn(day, true);
        });
        
        cell.addEventListener('mouseleave', function() {
            highlightColumn(day, false);
        });
    });
    
    // Also add hover to headers
    dayHeaders.forEach(header => {
        const day = header.getAttribute('data-day');
        
        header.addEventListener('mouseenter', function() {
            highlightColumn(day, true);
        });
        
        header.addEventListener('mouseleave', function() {
            highlightColumn(day, false);
        });
    });
    
    // Add tooltips for status indicators
    dayCells.forEach(cell => {
        const status = cell.getAttribute('data-status');
        if (status) {
            let tooltip = '';
            switch(status) {
                case 'P': tooltip = 'Present'; break;
                case 'Late': tooltip = 'Late arrival'; break;
                case 'A': tooltip = 'Absent'; break;
                case 'L': tooltip = 'On Leave'; break;
                case 'RD': tooltip = 'Rest Day'; break;
                case 'H': tooltip = 'Holiday'; break;
            }
            if (tooltip) {
                cell.setAttribute('title', tooltip);
            }
        }
    });
});
</script>
