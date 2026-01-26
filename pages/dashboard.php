<?php
$page_title = 'Dashboard - Golden Z-5 HR System';
$page = 'dashboard';

// Data prep
$pdo = get_db_connection();

// Totals
$stats = get_dashboard_stats();

// Job level (full-time / part-time) with safe fallback if column not present - only active employees
$job_levels = ['full_time' => 0, 'part_time' => 0];
try {
    $jobStmt = $pdo->query("SELECT employment_type, COUNT(*) as total 
                            FROM employees 
                            WHERE status = 'Active' 
                            GROUP BY employment_type");
    while ($row = $jobStmt->fetch(PDO::FETCH_ASSOC)) {
        $type = strtolower($row['employment_type'] ?? '');
        if ($type === 'full-time' || $type === 'fulltime' || $type === 'full_time') {
            $job_levels['full_time'] += (int)$row['total'];
        } elseif ($type === 'part-time' || $type === 'parttime' || $type === 'part_time') {
            $job_levels['part_time'] += (int)$row['total'];
        }
    }
} catch (Exception $e) {
    // Fallback: use active employees count when column missing
    $job_levels['full_time'] = $stats['active_employees'] ?? 0;
}

// Guard types (employee_type) - only active employees
$guard_types = [];
$gtStmt = $pdo->query("SELECT COALESCE(employee_type, 'N/A') as type, COUNT(*) as total 
                       FROM employees 
                       WHERE status = 'Active' 
                       GROUP BY employee_type 
                       ORDER BY total DESC");
while ($row = $gtStmt->fetch(PDO::FETCH_ASSOC)) {
    $guard_types[] = $row;
}

// Map abbreviations to full guard type names for clarity
$guard_type_labels = [
    'SG' => 'Security Guard',
    'LG' => 'Lady Guard',
    'SO' => 'Security Officer',
    'NA' => 'Not Specified',
    'N/A' => 'Not Specified',
];

// Posts overview (counts per post) - only active employees
$posts = [];
$postStmt = $pdo->query("SELECT COALESCE(post, 'Unassigned') as post_name, COUNT(*) as total 
                        FROM employees 
                        WHERE status = 'Active' 
                        GROUP BY post 
                        ORDER BY total DESC, post_name ASC 
                        LIMIT 8");
while ($row = $postStmt->fetch(PDO::FETCH_ASSOC)) {
    $posts[] = $row;
}

// Helper function to parse license expiration date
function parseLicenseExpDate($dateValue) {
    if (empty($dateValue) || $dateValue === '0000-00-00' || $dateValue === null) {
        return null;
    }
    
    // Convert to string and trim
    $dateStr = trim((string)$dateValue);
    
    // Remove any time portion if present (e.g., "2025-01-15 00:00:00")
    if (strpos($dateStr, ' ') !== false) {
        $dateStr = substr($dateStr, 0, strpos($dateStr, ' '));
    }
    
    // Handle year-only format (e.g., "2025")
    if (preg_match('/^\d{4}$/', $dateStr)) {
        // Treat year-only as December 31 of that year
        return $dateStr . '-12-31';
    }
    
    // Handle various date formats
    // Try MySQL date format first (YYYY-MM-DD)
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
    }
    
    // Try with slashes (YYYY/MM/DD)
    if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $dateStr)) {
        $timestamp = strtotime(str_replace('/', '-', $dateStr));
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
    }
    
    // Try general date parsing
    $timestamp = strtotime($dateStr);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return null;
}

// Expiring licenses list (next 90 days) - all employees with valid dates
$expiring = [];
// Expired licenses list (past) - all employees with valid dates
$expired_licenses = [];

try {
    // Fetch ALL employees - we'll filter in PHP to catch all date formats
    $stmt = $pdo->query("SELECT id, first_name, surname, post, license_no, license_exp_date
                         FROM employees");
    $all_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse dates and filter in PHP
    $today = date('Y-m-d');
    $future_date = date('Y-m-d', strtotime('+90 days'));
    
    foreach ($all_employees as $employee) {
        // Get the raw license expiration date
        $rawDate = $employee['license_exp_date'] ?? null;
        
        // Skip if no license expiration date at all
        if (empty($rawDate) || 
            $rawDate === '0000-00-00' || 
            $rawDate === null ||
            trim((string)$rawDate) === '') {
            continue;
        }
        
        // Parse the date
        $parsedDate = parseLicenseExpDate($rawDate);
        
        // If we couldn't parse it, skip
        if (!$parsedDate) {
            continue;
        }
        
        // Add parsed date to employee array
        $employee['parsed_license_exp_date'] = $parsedDate;
        
        // Categorize as expired or expiring
        if ($parsedDate < $today) {
            // Expired
            $expired_licenses[] = $employee;
        } elseif ($parsedDate >= $today && $parsedDate <= $future_date) {
            // Expiring (within 90 days)
            $expiring[] = $employee;
        }
    }
    
    // Sort expiring by expiration date (ascending - soonest first)
    usort($expiring, function($a, $b) {
        $dateA = $a['parsed_license_exp_date'] ?? '';
        $dateB = $b['parsed_license_exp_date'] ?? '';
        return strcmp($dateA, $dateB);
    });
    
    // Sort expired by expiration date (descending - most recently expired first)
    usort($expired_licenses, function($a, $b) {
        $dateA = $a['parsed_license_exp_date'] ?? '';
        $dateB = $b['parsed_license_exp_date'] ?? '';
        return strcmp($dateB, $dateA);
    });
    
    // Limit to 8 results each
    $expiring = array_slice($expiring, 0, 8);
    $expired_licenses = array_slice($expired_licenses, 0, 8);
    
} catch (Exception $e) {
    error_log("Error fetching license data: " . $e->getMessage());
    $expiring = [];
    $expired_licenses = [];
}

// Expired/expiring counts are already in $stats

// Time-based greeting
$hourNow = (int) date('G');
if ($hourNow < 12) {
    $greeting = 'Good morning';
} elseif ($hourNow < 18) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

// Helper functions from employees.php
function getEmploymentStatus($date_hired) {
    if (!$date_hired || $date_hired === '0000-00-00') {
        return 'N/A';
    }
    $hired_date = strtotime($date_hired);
    $six_months_ago = strtotime('-6 months');
    return $hired_date >= $six_months_ago ? 'Probationary' : 'Regular';
}

function getLicenseExpirationIndicator($exp_date) {
    if (!$exp_date || $exp_date === '0000-00-00' || $exp_date === '') {
        return ['class' => 'text-muted', 'badge' => '', 'text' => 'N/A'];
    }
    
    $exp_timestamp = strtotime($exp_date);
    if ($exp_timestamp === false) {
        return ['class' => 'text-muted', 'badge' => '', 'text' => htmlspecialchars($exp_date)];
    }
    
    $now = strtotime('today');
    $days_until_exp = floor(($exp_timestamp - $now) / (60 * 60 * 24));
    
    if ($days_until_exp < 0) {
        return ['class' => 'text-danger fw-bold', 'badge' => 'bg-danger', 'text' => 'Expired (' . abs($days_until_exp) . ' days ago)', 'icon' => 'fa-exclamation-triangle'];
    } elseif ($days_until_exp <= 30) {
        return ['class' => 'text-danger fw-bold', 'badge' => 'bg-danger', 'text' => 'Expires in ' . $days_until_exp . ' days', 'icon' => 'fa-circle-exclamation'];
    } elseif ($days_until_exp <= 90) {
        return ['class' => 'text-warning fw-bold', 'badge' => 'bg-warning text-dark', 'text' => 'Expires in ' . $days_until_exp . ' days', 'icon' => 'fa-clock'];
    } else {
        return ['class' => 'text-success', 'badge' => '', 'text' => date('M d, Y', $exp_timestamp), 'icon' => ''];
    }
}

function formatLicenseExpiration($exp_date) {
    if (!$exp_date || $exp_date === '0000-00-00' || $exp_date === '') {
        return null;
    }
    
    $exp_timestamp = strtotime($exp_date);
    if ($exp_timestamp === false) {
        return null;
    }
    
    $formatted_date = date('F j, Y', $exp_timestamp);
    $now = strtotime('today');
    $days_until_exp = floor(($exp_timestamp - $now) / (60 * 60 * 24));
    
    if ($days_until_exp < 0) {
        $status_text = 'Expired (' . abs($days_until_exp) . ' days ago)';
    } elseif ($days_until_exp <= 30) {
        $status_text = 'Expires in ' . $days_until_exp . ' days';
    } elseif ($days_until_exp <= 90) {
        $status_text = 'Expires in ' . $days_until_exp . ' days';
    } else {
        $status_text = 'Valid';
    }
    
    return [
        'text' => $formatted_date,
        'status_text' => $status_text,
        'days' => $days_until_exp
    ];
}

function formatRLMExpiration($exp_date) {
    if (!$exp_date || $exp_date === '0000-00-00' || $exp_date === '') {
        return null;
    }
    
    $exp_timestamp = strtotime($exp_date);
    if ($exp_timestamp === false) {
        return null;
    }
    
    $formatted_date = date('F j, Y', $exp_timestamp);
    $now = strtotime('today');
    $days_until_exp = floor(($exp_timestamp - $now) / (60 * 60 * 24));
    
    if ($days_until_exp < 0) {
        $status_text = 'Expired (' . abs($days_until_exp) . ' days ago)';
    } elseif ($days_until_exp <= 30) {
        $status_text = 'Expires in ' . $days_until_exp . ' days';
    } elseif ($days_until_exp <= 90) {
        $status_text = 'Expires in ' . $days_until_exp . ' days';
    } else {
        $status_text = 'Valid';
    }
    
    return [
        'text' => $formatted_date,
        'status_text' => $status_text,
        'days' => $days_until_exp
    ];
}

function getEmployeeTypeLabel($type) {
    $types = [
        'SG' => 'Security Guard',
        'LG' => 'Lady Guard',
        'SO' => 'Security Officer'
    ];
    return $types[$type] ?? $type;
}

// Get employees for the table (limit to active employees, or all if needed)
$all_employees = get_employees();
$display_employees = array_slice($all_employees, 0, 10); // Show first 10 employees

// Get today's schedule (alerts for today)
$today_schedule = [];
try {
    $todayDate = date('Y-m-d');
    $scheduleStmt = $pdo->prepare("SELECT ea.id, ea.title, ea.description, ea.priority, ea.status, 
                                           ea.created_at, e.first_name, e.surname, e.post
                                    FROM employee_alerts ea
                                    LEFT JOIN employees e ON ea.employee_id = e.id
                                    WHERE DATE(ea.created_at) = ?
                                        OR (ea.status = 'active' AND ea.priority IN ('Urgent', 'High'))
                                    ORDER BY 
                                        FIELD(ea.priority, 'Urgent', 'High', 'Medium', 'Low'),
                                        ea.created_at DESC
                                    LIMIT 10");
    $scheduleStmt->execute([$todayDate]);
    $today_schedule = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching today's schedule: " . $e->getMessage());
    $today_schedule = [];
}
?>

<?php if (($_SESSION['user_role'] ?? '') === 'hr_admin'): ?>
<div class="container-fluid hrdash">

    <!-- HR Admin Stats Bar - Priority Metrics -->
    <div class="row g-5 mb-5" id="hr-dashboard-stats">
        <!-- Active Employees - Most Important for HR -->
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Active Employees</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($stats['active_employees'] ?? 0); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-user-check"></i>
                        <span><?php echo ($stats['total_employees'] ?? 0) > 0 ? round(($stats['active_employees'] / max(1, $stats['total_employees'])) * 100) : 0; ?>%</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Currently active and on roster.</div>
            </div>
        </div>
        
        <!-- Expiring Licenses - Critical for HR Compliance -->
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Expiring Licenses</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($stats['expiring_licenses'] ?? 0); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--negative">
                        <i class="fas fa-clock"></i>
                        <span>30 days</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Licenses expiring in the next 30 days - requires immediate attention.</div>
            </div>
        </div>
        
        <!-- Expired Licenses - Urgent Action Required -->
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Expired Licenses</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($stats['expired_licenses'] ?? 0); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--negative">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Urgent</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Licenses that have expired - immediate follow-up required.</div>
            </div>
        </div>
        
        <!-- New Hires This Month - Recruitment Activity -->
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">New Hires</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($stats['new_hires'] ?? 0); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-user-plus"></i>
                        <span>This Month</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">New employees hired in the current month.</div>
            </div>
        </div>
    </div>

    <div class="row g-5">
        <!-- License table (moved to main left position) -->
        <div class="col-xl-8 d-flex">
            <div class="card hrdash-card hrdash-license h-100 d-flex flex-column">
                <div class="hrdash-card__header hrdash-card__header--split">
                    <div>
                        <h5 class="hrdash-card__title">License Watchlist</h5>
                        <div class="hrdash-card__subtitle">Expiring and expired licenses</div>
                    </div>
                    <div class="hrdash-segment" role="tablist" aria-label="License list">
                        <button class="hrdash-segment__btn active" type="button" data-target="expiring">Expiring</button>
                        <button class="hrdash-segment__btn" type="button" data-target="expired">Expired</button>
                    </div>
                </div>

                <div class="hrdash-license__body flex-grow-1 d-flex flex-column">
                    <div class="table-responsive flex-grow-1">
                        <table class="table hrdash-table mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Post</th>
                                <th>License #</th>
                                <th>Expiry</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody data-pane="expiring">
                            <?php if (empty($expiring)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No expiring licenses found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($expiring as $e): ?>
                                    <?php
                                    $expDateValue = $e['parsed_license_exp_date'] ?? ($e['license_exp_date'] ?? '');
                                    $ind = getLicenseExpirationIndicator($expDateValue);
                                    $expDateTimestamp = $expDateValue ? strtotime($expDateValue) : false;
                                    $expDateDisplay = $expDateTimestamp ? date('M d, Y', $expDateTimestamp) : ($expDateValue ?: 'N/A');
                                    $licenseNo = trim((string)($e['license_no'] ?? ''));
                                    if ($licenseNo === '') {
                                        $licenseNo = 'N/A';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="?page=view_employee&id=<?php echo (int)($e['id'] ?? 0); ?>" class="text-decoration-none fw-semibold">
                                                <?php echo htmlspecialchars(($e['surname'] ?? '') . ', ' . ($e['first_name'] ?? '')); ?>
                                            </a>
                                        </td>
                                        <td class="text-muted"><?php echo htmlspecialchars($e['post'] ?? 'Unassigned'); ?></td>
                                        <td><code class="license-code"><?php echo htmlspecialchars($licenseNo); ?></code></td>
                                        <td><?php echo htmlspecialchars($expDateDisplay); ?></td>
                                        <td><span class="<?php echo htmlspecialchars($ind['class'] ?? ''); ?>"><?php echo htmlspecialchars($ind['text'] ?? ''); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tbody data-pane="expired" style="display:none;">
                            <?php if (empty($expired_licenses)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No expired licenses found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($expired_licenses as $e): ?>
                                    <?php
                                    $expDateValue = $e['parsed_license_exp_date'] ?? ($e['license_exp_date'] ?? '');
                                    $ind = getLicenseExpirationIndicator($expDateValue);
                                    $expDateTimestamp = $expDateValue ? strtotime($expDateValue) : false;
                                    $expDateDisplay = $expDateTimestamp ? date('M d, Y', $expDateTimestamp) : ($expDateValue ?: 'N/A');
                                    $licenseNo = trim((string)($e['license_no'] ?? ''));
                                    if ($licenseNo === '') {
                                        $licenseNo = 'N/A';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="?page=view_employee&id=<?php echo (int)($e['id'] ?? 0); ?>" class="text-decoration-none fw-semibold">
                                                <?php echo htmlspecialchars(($e['surname'] ?? '') . ', ' . ($e['first_name'] ?? '')); ?>
                                            </a>
                                        </td>
                                        <td class="text-muted"><?php echo htmlspecialchars($e['post'] ?? 'Unassigned'); ?></td>
                                        <td><code class="license-code"><?php echo htmlspecialchars($licenseNo); ?></code></td>
                                        <td><?php echo htmlspecialchars($expDateDisplay); ?></td>
                                        <td><span class="<?php echo htmlspecialchars($ind['class'] ?? ''); ?>"><?php echo htmlspecialchars($ind['text'] ?? ''); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column: Today's schedule and Shortcuts -->
        <div class="col-xl-4 d-flex flex-column gap-4">
            <!-- Today's schedule -->
            <div class="card hrdash-card hrdash-schedule d-flex flex-column">
                <div class="hrdash-card__header">
                    <div>
                        <h5 class="hrdash-card__title">Today's Schedule</h5>
                    </div>
                </div>
                <div class="hrdash-schedule__body flex-grow-1">
                    <!-- Date selector -->
                    <div class="hrdash-schedule__date-selector">
                        <?php
                        $today = new DateTime();
                        $scheduleCount = 0; // Placeholder - will be calculated from events
                        // Show 2 days before, today in center, and 2 days after (total 5 days)
                        for ($i = -2; $i <= 2; $i++):
                            $date = clone $today;
                            $date->modify("$i days");
                            $isToday = $i === 0;
                            $dayNum = $date->format('j');
                            $dayAbbr = $date->format('D');
                            $dayFull = $date->format('l');
                        ?>
                            <button class="hrdash-schedule__day-btn <?php echo $isToday ? 'active' : ''; ?>" 
                                    type="button" 
                                    data-date="<?php echo $date->format('Y-m-d'); ?>"
                                    title="<?php echo $dayFull . ', ' . $date->format('F j, Y'); ?>">
                                <span class="hrdash-schedule__day-num"><?php echo $dayNum; ?></span>
                                <span class="hrdash-schedule__day-abbr"><?php echo $dayAbbr; ?></span>
                            </button>
                        <?php endfor; ?>
                    </div>

                    <!-- Date display and schedule count -->
                    <div class="hrdash-schedule__date-info">
                        <div class="hrdash-schedule__date-full"><?php echo $today->format('F d, Y'); ?></div>
                        <a href="?page=alerts" class="hrdash-schedule__count-link"><?php echo count($today_schedule); ?> Schedule<?php echo count($today_schedule) != 1 ? 's' : ''; ?></a>
                    </div>

                    <!-- Timeline -->
                    <div class="hrdash-schedule__timeline">
                        <?php if (empty($today_schedule)): ?>
                            <div class="hrdash-schedule__empty">
                                <i class="fas fa-calendar-check"></i>
                                <div>No alerts for today</div>
                                <small class="text-muted">All clear! No urgent items require attention.</small>
                            </div>
                        <?php else: ?>
                            <div class="hrdash-schedule__events">
                                <?php foreach ($today_schedule as $event): 
                                    $priorityClass = '';
                                    $priorityIcon = 'fa-circle-info';
                                    switch(strtolower($event['priority'] ?? '')) {
                                        case 'urgent':
                                            $priorityClass = 'event--urgent';
                                            $priorityIcon = 'fa-exclamation-triangle';
                                            break;
                                        case 'high':
                                            $priorityClass = 'event--high';
                                            $priorityIcon = 'fa-exclamation-circle';
                                            break;
                                        case 'medium':
                                            $priorityClass = 'event--medium';
                                            $priorityIcon = 'fa-circle-exclamation';
                                            break;
                                        default:
                                            $priorityClass = 'event--low';
                                            $priorityIcon = 'fa-circle-info';
                                    }
                                    $eventTime = date('g:i A', strtotime($event['created_at']));
                                    $employeeName = trim(($event['first_name'] ?? '') . ' ' . ($event['surname'] ?? ''));
                                ?>
                                    <div class="hrdash-schedule__event <?php echo $priorityClass; ?>">
                                        <div class="event__time"><?php echo $eventTime; ?></div>
                                        <div class="event__content">
                                            <div class="event__header">
                                                <i class="fas <?php echo $priorityIcon; ?> event__icon"></i>
                                                <span class="event__title"><?php echo htmlspecialchars($event['title']); ?></span>
                                            </div>
                                            <?php if (!empty($employeeName)): ?>
                                                <div class="event__meta">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo htmlspecialchars($employeeName); ?>
                                                    <?php if (!empty($event['post'])): ?>
                                                        <span class="text-muted">· <?php echo htmlspecialchars($event['post']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Shortcuts -->
            <div class="card hrdash-card hrdash-shortcuts-card">
                <div class="hrdash-card__header">
                    <div>
                        <h5 class="hrdash-card__title">Quick Actions</h5>
                        <div class="hrdash-card__subtitle">Frequently used shortcuts</div>
                    </div>
                </div>
                <div class="hrdash-shortcuts-grid">
                    <a class="hrdash-shortcut-btn" href="?page=add_employee" title="Add New Employee">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Employee</span>
                    </a>
                    <a class="hrdash-shortcut-btn" href="?page=employees" title="View All Employees">
                        <i class="fas fa-users"></i>
                        <span>Employees</span>
                    </a>
                    <a class="hrdash-shortcut-btn" href="?page=add_alert" title="Add New Alert">
                        <i class="fas fa-bell-plus"></i>
                        <span>Add Alert</span>
                    </a>
                    <a class="hrdash-shortcut-btn" href="?page=alerts" title="View All Alerts">
                        <i class="fas fa-bell"></i>
                        <span>Alerts</span>
                    </a>
                    <a class="hrdash-shortcut-btn" href="?page=posts" title="Manage Posts">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Posts</span>
                    </a>
                    <a class="hrdash-shortcut-btn" href="?page=tasks" title="View Tasks">
                        <i class="fas fa-tasks"></i>
                        <span>Tasks</span>
                    </a>
                    <a class="hrdash-shortcut-btn" href="?page=teams" title="View Teams">
                        <i class="fas fa-user-friends"></i>
                        <span>Teams</span>
                    </a>
                    <a class="hrdash-shortcut-btn" href="?page=system_logs" title="System Logs">
                        <i class="fas fa-file-alt"></i>
                        <span>System Logs</span>
                    </a>
                    <a class="hrdash-shortcut-btn" href="?page=hr-admin-settings" title="Settings">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a class="hrdash-shortcut-btn" href="?page=help" title="Help & Support">
                        <i class="fas fa-question-circle"></i>
                        <span>Help</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* HR-Admin dashboard inspired layout (scoped by container class) */
.hrdash {
    background: #fafbfc;
    min-height: 100vh;
    margin-left: 0;
    margin-right: 0;
    padding: 2rem 0;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    position: relative;
}

/* Remove watermark for cleaner look */
.hrdash::before {
    display: none;
}
.hrdash-welcome {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    margin: 0 !important;
    /* Fixed height and padding - never changes */
    height: 88px !important;
    min-height: 88px !important;
    max-height: 88px !important;
    padding: 1.25rem 1.5rem !important; /* Fixed padding - never changes */
    background: #f1f5f9 !important;
    border-bottom: 1px solid #e2e8f0 !important;
    width: 100% !important;
    box-sizing: border-box !important;
    overflow: hidden !important; /* Prevent content overflow from changing height */
    flex-wrap: nowrap !important; /* Prevent wrapping that could change height */
}
.hrdash-welcome__left {
    flex: 1;
    min-width: 0; /* Allow shrinking if needed */
    overflow: hidden; /* Prevent content overflow */
}
.hrdash-welcome__title {
    font-size: 2.25rem;
    font-weight: 700;
    letter-spacing: -0.04em;
    margin: 0 0 0.5rem 0 !important; /* Fixed margin - never changes */
    color: #0a0e27;
    line-height: 1.1 !important; /* Fixed line-height to prevent height changes */
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    white-space: nowrap !important; /* Prevent text wrapping */
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}
.hrdash-welcome__time {
    font-weight: 600;
    color: #64748b;
    font-size: 0.9375rem;
    margin: 0 !important; /* Fixed margin - never changes */
    margin-right: 1rem !important;
    display: inline-flex;
    align-items: center;
    padding: 0 !important; /* Fixed padding - never changes */
    letter-spacing: 0.02em;
    white-space: nowrap !important; /* Prevent text wrapping */
    flex-shrink: 0 !important;
}
.hrdash-welcome__subtitle {
    margin: 0 !important; /* Fixed margin - never changes */
    color: #64748b;
    font-size: 0.9375rem;
    line-height: 1.4 !important; /* Fixed line-height */
    white-space: nowrap !important; /* Prevent text wrapping */
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}
.hrdash-welcome__actions {
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
    flex-shrink: 0 !important;
    min-width: 0 !important;
}
.hrdash-welcome__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #0f172a;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}
.hrdash-welcome__btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.hrdash-welcome__btn--primary {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
}
.hrdash-welcome__btn--primary:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
}
.hrdash-welcome__btn i {
    font-size: 0.875rem;
}
.hrdash-welcome__icon-btn {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    border: 1px solid #e8ecf1;
    background: #ffffff;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    position: relative;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.hrdash-welcome__icon-btn:hover {
    background: #fafbfc;
    border-color: #d1d9e6;
    color: #0a0e27;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}
.hrdash-welcome__icon-btn:focus,
.hrdash-welcome__icon-btn:active,
.hrdash-welcome__icon-btn:focus-visible,
.hrdash-welcome__icon-btn.show,
.hrdash-welcome__icon-btn[aria-expanded="true"] {
    outline: none !important;
    box-shadow: none !important;
    border: none !important;
    background: #e4e6eb;
    color: #1e293b;
}
.hrdash-welcome__icon-btn i {
    font-size: 1.125rem;
    color: #64748b;
    display: inline-block;
    line-height: 1;
}

.hrdash-welcome__icon-btn:hover i {
    color: #1e293b;
}
.hrdash-welcome__badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #dc2626;
    color: #ffffff;
    font-size: 0.625rem;
    font-weight: 700;
    padding: 0.125rem 0.375rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    line-height: 1.4;
}
.hrdash-welcome__profile-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: opacity 0.2s ease;
}
.hrdash-welcome__profile-btn:hover {
    opacity: 0.8;
}
.hrdash-welcome__avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    border: 2px solid rgba(255, 255, 255, 0.1);
    transition: all 0.25s ease;
}
.hrdash-welcome__profile-btn:hover .hrdash-welcome__avatar {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
}
.hrdash-welcome__avatar-img {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.2);
    background: none;
    display: inline-block;
}
.hrdash-welcome__chevron {
    font-size: 0.75rem;
    color: #64748b;
    margin-left: 0.25rem;
}
.hrdash-welcome__user-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0a0e27;
    margin-right: 0.75rem;
    white-space: nowrap;
    letter-spacing: -0.01em;
}

/* Profile & Settings Modal Styles */
.profile-avatar-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.25);
}

.profile-avatar-text {
    font-size: 2rem;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.02em;
}

#profileSettingsModal .nav-tabs {
    border-bottom: 2px solid #e2e8f0;
}

#profileSettingsModal .nav-tabs .nav-link {
    color: #64748b;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

#profileSettingsModal .nav-tabs .nav-link:hover {
    color: #1fb2d5;
    border-bottom-color: #cbd5e1;
}

#profileSettingsModal .nav-tabs .nav-link.active {
    color: #1fb2d5;
    border-bottom-color: #1fb2d5;
    background: transparent;
}

#profileSettingsModal .form-label {
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

#profileSettingsModal .form-control,
#profileSettingsModal .form-select {
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

#profileSettingsModal .form-control:focus,
#profileSettingsModal .form-select:focus {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
    outline: none;
}

#profileSettingsModal .form-check-input:checked {
    background-color: #1fb2d5;
    border-color: #1fb2d5;
}

#profileSettingsModal .form-check-input:focus {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}
@media (max-width: 768px) {
    .hrdash-welcome {
        /* Maintain fixed height on mobile - never changes */
        height: 88px !important;
        min-height: 88px !important;
        max-height: 88px !important;
        flex-direction: row !important; /* Keep horizontal to maintain height */
        align-items: center !important;
        gap: 0.75rem !important;
        padding: 1.25rem 1rem !important; /* Fixed padding - reduced horizontal on mobile */
        flex-wrap: nowrap !important; /* Prevent wrapping */
        overflow: hidden !important; /* Prevent content overflow */
    }
    .hrdash-welcome__actions {
        flex-shrink: 0 !important;
        flex-wrap: nowrap !important;
    }
    .hrdash-welcome__btn {
        flex: 0 0 auto !important;
        min-width: auto !important;
    }
}
.hrdash-stat {
    border: 1px solid #e8ecf1;
    border-radius: 20px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 0 0 1px rgba(15, 23, 42, 0.02);
    padding: 2rem 1.75rem;
    background: #ffffff;
    position: relative;
    overflow: hidden;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    min-height: 160px;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.hrdash-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(15, 23, 42, 0.04);
    border-color: #d1d9e6;
}
/* Primary card with black gradient */
.hrdash-stat--primary {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    border: none;
    color: #ffffff;
}
.hrdash-stat--primary .hrdash-stat__label,
.hrdash-stat--primary .hrdash-stat__value,
.hrdash-stat--primary .hrdash-stat__meta {
    color: #ffffff;
}
.hrdash-stat--primary .hrdash-stat__action {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.3);
}
.hrdash-stat--primary .hrdash-stat__action:hover {
    background: rgba(255, 255, 255, 0.3);
}
.hrdash-stat--primary .hrdash-stat__trend {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
}
.hrdash-stat__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 0;
}
.hrdash-stat__label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748b;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    opacity: 0.85;
}
.hrdash-stat--primary .hrdash-stat__label {
    color: rgba(255, 255, 255, 0.9);
    opacity: 1;
}
.hrdash-stat__action {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #0f172a;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
    flex-shrink: 0;
}
.hrdash-stat__action:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.hrdash-stat__action i {
    font-size: 0.75rem;
}
.hrdash-stat__content {
    display: flex;
    align-items: baseline;
    gap: 1rem;
    margin-bottom: 0;
    flex: 1;
}
.hrdash-stat__value {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.05em;
    color: #000000;
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
}
.hrdash-stat--primary .hrdash-stat__value {
    color: #ffffff;
}
.hrdash-stat__trend {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}
.hrdash-stat__trend i {
    font-size: 0.625rem;
}
.hrdash-stat__trend--positive {
    background: #d1fae5;
    color: #059669;
}
.hrdash-stat__trend--negative {
    background: #fee2e2;
    color: #dc2626;
}
.hrdash-stat--primary .hrdash-stat__trend {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    backdrop-filter: blur(8px);
}
.hrdash-stat__meta {
    margin: 0;
    color: #64748b;
    font-size: 0.8125rem;
    line-height: 1.6;
    opacity: 0.8;
    margin-top: auto;
    padding-top: 0.5rem;
}
.hrdash-stat--primary .hrdash-stat__meta {
    color: rgba(255, 255, 255, 0.85);
    opacity: 1;
}

.hrdash-card {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.hrdash-card__header {
    padding: 1.75rem 2rem;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.5rem;
    border-bottom: 1px solid #f1f4f8;
    background: #ffffff;
    flex-shrink: 0;
    min-height: auto;
}
.hrdash-card__header > div:first-child {
    flex: 1;
    padding-top: 0;
}
.hrdash-card__header--split {
    align-items: flex-start;
}
.hrdash-card__header--split > div:first-child {
    padding-top: 0;
}
.hrdash-license__body {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    min-height: 320px;
    height: 100%;
}
.hrdash-card__title {
    margin: 0;
    font-weight: 800;
    color: #0f172a;
}
.hrdash-card__subtitle {
    font-size: 0.875rem;
    color: #64748b;
}
.hrdash-table {
    background: #ffffff;
    margin-bottom: 0;
    width: 100%;
    max-width: 100%;
    table-layout: auto;
}

.hrdash-table th,
.hrdash-table td {
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.hrdash-table thead {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc;
}
.hrdash-table tbody tr:hover {
    background: #f8fafc;
}
.hrdash-license__body .table-responsive {
    overflow-x: auto;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overflow-x: hidden;
    flex: 1;
    min-height: 0;
    max-height: 100%;
    border: none;
    border-radius: 0;
    background: transparent;
    padding: 0;
}

/* License code styling */
.license-code {
    background: #f1f4f8;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    font-family: 'Courier New', monospace;
}
/* Profile & Settings Modal Styles */
.profile-avatar-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.25);
}

.profile-avatar-text {
    font-size: 2rem;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.02em;
}

#profileSettingsModal .nav-tabs {
    border-bottom: 2px solid #e2e8f0;
}

#profileSettingsModal .nav-tabs .nav-link {
    color: #64748b;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

#profileSettingsModal .nav-tabs .nav-link:hover {
    color: #1fb2d5;
    border-bottom-color: #cbd5e1;
}

#profileSettingsModal .nav-tabs .nav-link.active {
    color: #1fb2d5;
    border-bottom-color: #1fb2d5;
    background: transparent;
}

#profileSettingsModal .form-label {
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

#profileSettingsModal .form-control,
#profileSettingsModal .form-select {
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

#profileSettingsModal .form-control:focus,
#profileSettingsModal .form-select:focus {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
    outline: none;
}

#profileSettingsModal .form-check-input:checked {
    background-color: #1fb2d5;
    border-color: #1fb2d5;
}

#profileSettingsModal .form-check-input:focus {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

.hrdash-schedule__body {
    padding: 1.25rem;
    background: #ffffff;
    min-height: 320px;
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}
.hrdash-schedule__date-selector {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}
.hrdash-schedule__day-btn {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.375rem;
    padding: 0.875rem 0.625rem;
    border: 1px solid #e8ecf1;
    border-radius: 12px;
    background: #ffffff;
    color: #64748b;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: inherit;
}
.hrdash-schedule__day-btn:hover {
    background: #fafbfc;
    border-color: #d1d9e6;
    color: #334155;
}
.hrdash-schedule__day-btn.active {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    border-color: transparent;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
}
.hrdash-schedule__day-num {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1;
}
.hrdash-schedule__day-abbr {
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.hrdash-schedule__date-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}
.hrdash-schedule__date-full {
    font-size: 1rem;
    font-weight: 600;
    color: #0a0e27;
    letter-spacing: -0.01em;
}
.hrdash-schedule__count-link {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #2563eb;
    text-decoration: none;
    transition: color 0.2s ease;
}
.hrdash-schedule__count-link:hover {
    color: #1d4ed8;
    text-decoration: underline;
}
.hrdash-schedule__timeline {
    flex: 1;
    overflow-y: auto;
    min-height: 200px;
}
.hrdash-schedule__empty {
    height: 100%;
    min-height: 200px;
    border: 1px dashed #cbd5e1;
    border-radius: 14px;
    display: grid;
    place-content: center;
    gap: 0.35rem;
    text-align: center;
    color: #64748b;
    background: #f8fafc;
}
.hrdash-schedule__empty i {
    font-size: 1.25rem;
    color: #10b981;
}
.hrdash-schedule__events {
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}
.hrdash-schedule__event {
    display: flex;
    gap: 1rem;
    padding: 1rem 1.125rem;
    border-radius: 12px;
    border-left: 3px solid #cbd5e1;
    background: #fafbfc;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.hrdash-schedule__event:hover {
    background: #f1f4f8;
    transform: translateX(3px);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}
.hrdash-schedule__event.event--urgent {
    border-left-color: #dc2626;
    background: #fef2f2;
}
.hrdash-schedule__event.event--high {
    border-left-color: #f59e0b;
    background: #fffbeb;
}
.hrdash-schedule__event.event--medium {
    border-left-color: #3b82f6;
    background: #eff6ff;
}
.hrdash-schedule__event.event--low {
    border-left-color: #10b981;
    background: #f0fdf4;
}
.event__time {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    min-width: 60px;
    flex-shrink: 0;
}
.event__content {
    flex: 1;
    min-width: 0;
}
.event__header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}
.event__icon {
    font-size: 0.875rem;
    flex-shrink: 0;
}
.event--urgent .event__icon {
    color: #dc2626;
}
.event--high .event__icon {
    color: #f59e0b;
}
.event--medium .event__icon {
    color: #3b82f6;
}
.event--low .event__icon {
    color: #10b981;
}
.event__title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0a0e27;
    line-height: 1.5;
    letter-spacing: -0.01em;
}
.event__meta {
    font-size: 0.75rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}
.event__meta i {
    font-size: 0.625rem;
}
.hrdash-segment {
    display: inline-flex;
    border: none;
    border-radius: 12px;
    overflow: hidden;
    background: #f1f4f8;
    padding: 0.375rem;
    gap: 0.375rem;
}
.hrdash-segment__btn {
    border: 0;
    background: transparent;
    padding: 0.625rem 1.25rem;
    font-weight: 600;
    font-size: 0.8125rem;
    color: #64748b;
    border-radius: 8px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}
.hrdash-segment__btn:hover {
    color: #334155;
    background: rgba(255, 255, 255, 0.5);
}
.hrdash-segment__btn.active {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.15);
}
.hrdash-table thead th {
    background: #fafbfc;
    color: #64748b;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 1px solid #f1f4f8;
    padding: 1rem 1.5rem;
    font-weight: 600;
}
.hrdash-table tbody td {
    padding: 1.125rem 1.5rem;
    border-bottom: 1px solid #f8fafc;
    color: #334155;
    font-size: 0.875rem;
}
.hrdash-table tbody tr:last-child td {
    border-bottom: none;
}
.hrdash-table tbody tr:hover {
    background: #fafbfc;
}
/* Shortcuts Grid */
.hrdash-shortcuts-card .hrdash-card__header {
    min-height: auto;
}
.hrdash-shortcuts-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.875rem;
    padding: 1.75rem 2rem;
    background: #ffffff;
}
.hrdash-shortcut-btn {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 0.75rem;
    padding: 1rem 1.125rem;
    border-radius: 12px;
    border: 1px solid #e8ecf1;
    background: #ffffff;
    color: #0a0e27;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    min-height: 48px;
}
.hrdash-shortcut-btn:hover {
    background: #fafbfc;
    border-color: #d1d9e6;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    color: #0a0e27;
}
.hrdash-shortcut-btn i {
    font-size: 1.125rem;
    color: #475569;
    width: 22px;
    text-align: center;
    flex-shrink: 0;
    transition: color 0.25s ease;
}
.hrdash-shortcut-btn:hover i {
    color: #0a0e27;
}
.hrdash-shortcut-btn span {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
/* Ensure consistent card alignment */
.row.g-5 > [class*="col-"] {
    display: flex;
}
.row.g-5 > [class*="col-"] > .card {
    width: 100%;
}

/* Additional spacing utilities for dashboard */
.hrdash .row {
    margin-left: 0;
    margin-right: 0;
}

.hrdash .row > [class*="col-"] {
    padding-left: 1.25rem;
    padding-right: 1.25rem;
}

/* Container max-width for better readability on large screens */
@media (min-width: 1400px) {
    .hrdash {
        max-width: 1600px;
        margin-left: auto;
        margin-right: auto;
    }
}

/* Smooth scroll behavior */
.hrdash-schedule__timeline,
.hrdash-license__body .table-responsive {
    scroll-behavior: smooth;
}

/* Improved focus states for accessibility */
.hrdash-shortcut-btn:focus,
.hrdash-schedule__day-btn:focus,
.hrdash-segment__btn:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Subtle entrance animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hrdash-stat,
.hrdash-card {
    animation: fadeInUp 0.4s ease-out backwards;
}

.hrdash-stat:nth-child(1) { animation-delay: 0.05s; }
.hrdash-stat:nth-child(2) { animation-delay: 0.1s; }
.hrdash-stat:nth-child(3) { animation-delay: 0.15s; }
.hrdash-stat:nth-child(4) { animation-delay: 0.2s; }

/* Improved table spacing */
.hrdash-license__body .table-responsive {
    padding: 0;
}

/* Better visual hierarchy for empty states */
.hrdash-schedule__empty i {
    font-size: 2rem;
    opacity: 0.4;
    margin-bottom: 0.5rem;
}

.hrdash-schedule__empty div {
    font-weight: 500;
    color: #475569;
}

.hrdash-schedule__empty small {
    font-size: 0.8125rem;
    opacity: 0.7;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .hrdash {
        padding: 1.5rem 0;
    }
    .hrdash-welcome {
        margin-bottom: 2rem;
        padding: 2rem 0;
    }
    .hrdash-welcome__title {
        font-size: 1.875rem;
    }
    .hrdash-stat {
        padding: 1.5rem 1.5rem;
        min-height: 140px;
    }
    .hrdash-stat__value {
        font-size: 2.5rem;
    }
    .hrdash-schedule__body,
    .hrdash-license__body {
        min-height: 300px;
    }
    .hrdash-shortcuts-grid {
        grid-template-columns: 1fr;
        padding: 1.5rem 1.5rem;
    }
    .row.g-5 {
        --bs-gutter-y: 1.5rem;
    }
    /* Stack cards on mobile */
    .row.g-5 > [class*="col-"] {
        display: block;
    }
}
@media (max-width: 576px) {
    .hrdash-shortcut-btn {
        padding: 0.75rem 0.875rem;
        font-size: 0.8125rem;
    }
    .hrdash-shortcut-btn i {
        font-size: 0.875rem;
    }
}
</style>

<script>
// HR dashboard: All interactive functionality
(function() {
    function initDashboard() {
        // License watchlist toggle
        function initLicenseWatchlist() {
            const seg = document.querySelector('.hrdash-segment');
            if (!seg) {
                return;
            }
            
            const buttons = seg.querySelectorAll('.hrdash-segment__btn');
            const panes = document.querySelectorAll('tbody[data-pane]');
            
            if (buttons.length === 0 || panes.length === 0) {
                return;
            }
            
            buttons.forEach((btn) => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Remove active class from all buttons
                    buttons.forEach(b => b.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get target pane
                    const target = this.getAttribute('data-target');
                    
                    // Show/hide panes
                    panes.forEach(p => {
                        const paneTarget = p.getAttribute('data-pane');
                        if (paneTarget === target) {
                            p.style.display = '';
                        } else {
                            p.style.display = 'none';
                        }
                    });
                });
            });
        }
        
        // Schedule day selector
        function initScheduleSelector() {
            const dayButtons = document.querySelectorAll('.hrdash-schedule__day-btn');
            const dateFullEl = document.querySelector('.hrdash-schedule__date-full');
            
            if (dayButtons.length > 0 && dateFullEl) {
                dayButtons.forEach((btn) => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Update active state
                        dayButtons.forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                        
                        // Update date display
                        const dateStr = this.getAttribute('data-date');
                        if (dateStr) {
                            const date = new Date(dateStr + 'T00:00:00');
                            const options = { year: 'numeric', month: 'long', day: 'numeric' };
                            dateFullEl.textContent = date.toLocaleDateString('en-US', options);
                            
                            // Update schedule count link if needed
                            const countLink = document.querySelector('.hrdash-schedule__count-link');
                            if (countLink) {
                                // You can add logic here to update schedule count based on selected date
                                // For now, just keep the existing count
                            }
                        }
                    });
                });
            }
        }
        
        // Update current time display
        function initTimeDisplay() {
            const timeEl = document.getElementById('current-time-dashboard');
            if (timeEl) {
                function updateTime() {
                    const now = new Date();
                    let hours = now.getHours();
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const ampm = hours >= 12 ? 'pm' : 'am';
                    hours = hours % 12;
                    hours = hours ? hours : 12; // the hour '0' should be '12'
                    timeEl.textContent = `${hours}:${minutes} ${ampm}`;
                }
                updateTime(); // Set initial time
                setInterval(updateTime, 60000); // Update every minute
            }
        }
        
        // Shortcut buttons functionality
        function initShortcuts() {
            // Add shortcut button
            const addShortcutBtn = document.querySelector('.hrdash-shortcut-btn--add');
            if (addShortcutBtn) {
                addShortcutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Show a modal or prompt to add new shortcuts
                    // For now, just show an alert
                    alert('Shortcut management coming soon!');
                });
            }
            
            // Ensure all shortcut links work
            const shortcutLinks = document.querySelectorAll('.hrdash-shortcut-btn[href]');
            shortcutLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Let the link navigate normally
                    // Add any additional logic here if needed
                });
            });
        }
        
        // Profile dropdown (Bootstrap handles this, but ensure it's initialized)
        function initProfileDropdown() {
            // Bootstrap dropdown should work automatically if Bootstrap JS is loaded
            // Just ensure the dropdown toggle works
            const profileBtn = document.querySelector('.hrdash-welcome__profile-btn');
            if (profileBtn) {
                // Bootstrap will handle the dropdown, but we can add custom logic here if needed
            }
        }
        
        // Initialize all features
        initLicenseWatchlist();
        initScheduleSelector();
        initTimeDisplay();
        initShortcuts();
        initProfileDropdown();
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboard);
    } else {
        initDashboard();
    }
})();
</script>

<?php else: ?>
<div class="container-fluid dashboard-modern">
    <!-- Page Header -->
    <div class="page-header-modern mb-5">
        <div class="page-title-modern">
            <h1 class="page-title-main">Dashboard</h1>
            <p class="page-subtitle"><?php echo $greeting; ?>! Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>!</p>
        </div>
    </div>

    <!-- Top stats -->
    <div class="row g-4 mb-5">
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card-modern h-100">
                <div class="card-body-modern">
                    <div class="stat-header">
                        <span class="stat-label">Total Employees</span>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $stats['total_employees'] ?? 0; ?></h3>
                        <span class="badge badge-success-modern">+<?php echo max(1, ($stats['active_employees'] ?? 0) > 0 ? 1 : 0); ?>%</span>
                    </div>
                    <small class="stat-footer">vs last period</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card-modern h-100">
                <div class="card-body-modern">
                    <div class="stat-header">
                        <span class="stat-label">Active</span>
                        <i class="fas fa-user-check stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo number_format($stats['active_employees'] ?? 0); ?></h3>
                        <span class="badge badge-primary-modern"><?php echo ($stats['total_employees'] ?? 0) > 0 ? round(($stats['active_employees']/$stats['total_employees'])*100) : 0; ?>%</span>
                    </div>
                    <small class="stat-footer">Currently on roster</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card-modern h-100">
                <div class="card-body-modern">
                    <div class="stat-header">
                        <span class="stat-label">Expiring Licenses</span>
                        <i class="fas fa-clock stat-icon text-warning"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number text-warning"><?php echo number_format($stats['expiring_licenses'] ?? 0); ?></h3>
                        <span class="badge badge-warning-modern">Next 30 days</span>
                    </div>
                    <small class="stat-footer">Requires renewal</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card-modern h-100">
                <div class="card-body-modern">
                    <div class="stat-header">
                        <span class="stat-label">Expired Licenses</span>
                        <i class="fas fa-exclamation-triangle stat-icon text-danger"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number text-danger"><?php echo number_format($stats['expired_licenses'] ?? 0); ?></h3>
                        <span class="badge badge-danger-modern">Urgent</span>
                    </div>
                    <small class="stat-footer">Immediate follow-up</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Job level + guard types + license ticker -->
        <div class="col-xl-8">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-4">
                        <div>
                            <h5 class="card-title-modern">Workforce Snapshot</h5>
                            <small class="card-subtitle">Job level + guard types</small>
                        </div>
                        <span class="badge badge-live">Live</span>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mini-card-modern">
                                <div class="mini-card-header">
                                    <span class="mini-card-label">Job Level</span>
                                </div>
                                <div class="mini-card-content">
                                    <div class="progress-item">
                                        <div class="progress-label">
                                            <span>Full-Time</span>
                                            <span class="progress-value"><?php echo $job_levels['full_time']; ?></span>
                                        </div>
                                        <div class="progress progress-modern">
                                            <div class="progress-bar progress-bar-primary" role="progressbar" style="width: <?php echo ($stats['total_employees'] ?? 1) > 0 ? ($job_levels['full_time'] / max(1,$stats['total_employees']))*100 : 0; ?>%;"></div>
                                        </div>
                                    </div>
                                    <div class="progress-item">
                                        <div class="progress-label">
                                            <span>Part-Time</span>
                                            <span class="progress-value"><?php echo $job_levels['part_time']; ?></span>
                                        </div>
                                        <div class="progress progress-modern">
                                            <div class="progress-bar progress-bar-info" role="progressbar" style="width: <?php echo ($stats['total_employees'] ?? 1) > 0 ? ($job_levels['part_time'] / max(1,$stats['total_employees']))*100 : 0; ?>%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mini-card-modern">
                                <div class="mini-card-header">
                                    <span class="mini-card-label">Security Guard Types</span>
                                </div>
                                <?php if (empty($guard_types)): ?>
                                    <p class="text-muted small mb-0">No data yet.</p>
                                <?php else: ?>
                                    <div class="guard-types-list">
                                        <?php foreach ($guard_types as $gt): ?>
                                            <?php
                                                $rawType = strtoupper(trim($gt['type']));
                                                $label = $guard_type_labels[$rawType] ?? ($gt['type'] ?: 'Not Specified');
                                            ?>
                                            <div class="guard-type-item">
                                                <span class="guard-type-name"><?php echo htmlspecialchars($label); ?></span>
                                                <span class="badge badge-primary-modern"><?php echo (int)$gt['total']; ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="section-header-modern mb-4">
                            <div>
                                <h6 class="section-title">Employee Management</h6>
                                <small class="section-subtitle"><?php echo count($display_employees); ?> employee<?php echo count($display_employees) !== 1 ? 's' : ''; ?> displayed</small>
                            </div>
                            <a href="?page=employees" class="btn btn-link-modern">
                                <i class="fas fa-external-link-alt me-2"></i>View all
                            </a>
                        </div>
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-sm align-middle mb-0 table-hover">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Name</th>
                                        <th>Post</th>
                                        <th>License Expiry</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($display_employees)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-5">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                    <h5 class="text-muted">No employees found</h5>
                                                    <p class="text-muted mb-0">No employees are currently in the system.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($display_employees as $employee): 
                                            $full_name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['surname'] ?? ''));
                                            $license_formatted = !empty($employee['license_exp_date']) ? formatLicenseExpiration($employee['license_exp_date']) : null;
                                        ?>
                                        <tr class="employee-row" data-employee-id="<?php echo $employee['id']; ?>">
                                            <!-- Name -->
                                            <td class="align-middle text-center">
                                                <span class="fw-semibold"><?php echo htmlspecialchars($full_name); ?></span>
                                            </td>
                                            <!-- Post -->
                                            <td class="align-middle text-center">
                                                <span><?php echo htmlspecialchars($employee['post'] ?? 'Unassigned'); ?></span>
                                            </td>
                                            <!-- License Expiry -->
                                            <td class="align-middle text-center">
                                                <div class="license-expiry-cell">
                                                    <?php if (!empty($employee['license_no'])): ?>
                                                        <div class="license-number">
                                                            <code class="license-code"><?php echo htmlspecialchars($employee['license_no']); ?></code>
                                                        </div>
                                                        <?php if ($license_formatted): ?>
                                                            <?php
                                                            $exp_date = $employee['license_exp_date'];
                                                            $exp_timestamp = strtotime($exp_date);
                                                            $now = strtotime('today');
                                                            $days_until_exp = floor(($exp_timestamp - $now) / (60 * 60 * 24));
                                                            $badge_class = ($days_until_exp < 0) ? 'badge-danger-modern' : (($days_until_exp <= 30) ? 'badge-warning-modern' : 'badge-success-modern');
                                                            ?>
                                                            <div class="license-expiry-date">
                                                                <span class="badge <?php echo $badge_class; ?>">
                                                                    <i class="fas fa-calendar-alt me-1"></i><?php echo date('M d, Y', $exp_timestamp); ?>
                                                                </span>
                                                            </div>
                                                        <?php elseif (!empty($employee['license_exp_date']) && $employee['license_exp_date'] !== '0000-00-00'): ?>
                                                            <div class="license-expiry-date">
                                                                <span class="badge badge-success-modern">
                                                                    <i class="fas fa-calendar-alt me-1"></i><?php echo date('M d, Y', strtotime($employee['license_exp_date'])); ?>
                                                                </span>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="license-expiry-date">
                                                                <span class="text-muted small">No expiry date</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-danger fw-bold">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>No License
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
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

        <!-- Posts overview -->
        <div class="col-xl-4">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-4">
                        <div>
                            <h5 class="card-title-modern">Posts Overview</h5>
                            <small class="card-subtitle">Headcount per post</small>
                        </div>
                        <a href="?page=posts" class="btn btn-link-modern">
                            <i class="fas fa-cog me-2"></i>Manage
                        </a>
                    </div>
                    <div class="posts-list-modern">
                        <?php if (empty($posts)): ?>
                            <div class="text-muted small">No posts recorded.</div>
                        <?php else: ?>
                            <?php foreach ($posts as $p): ?>
                                <div class="post-item-modern">
                                    <span class="post-name"><?php echo htmlspecialchars($p['post_name']); ?></span>
                                    <span class="badge badge-primary-modern"><?php echo (int)$p['total']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile & Settings Modal -->
<div class="modal fade" id="profileSettingsModal" tabindex="-1" aria-labelledby="profileSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileSettingsModalLabel">
                    <i class="fas fa-user me-2" id="modalIcon"></i>
                    <span id="modalTitle">Profile</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-4" id="profileSettingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-pane" type="button" role="tab" aria-controls="profile-pane" aria-selected="true">
                            <i class="fas fa-user me-2"></i>Profile
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings-pane" type="button" role="tab" aria-controls="settings-pane" aria-selected="false">
                            <i class="fas fa-cog me-2"></i>Settings
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="profileSettingsTabContent">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile-pane" role="tabpanel" aria-labelledby="profile-tab">
                        <div class="row g-4">
                            <div class="col-md-4 text-center">
                                <div class="mb-3">
                                    <div class="profile-avatar-large mx-auto mb-3">
                                        <span class="profile-avatar-text"><?php echo htmlspecialchars($initials); ?></span>
                                    </div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($displayName); ?></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $_SESSION['user_role'] ?? 'HR Admin'))); ?></p>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <form id="profileForm">
                                    <div class="mb-3">
                                        <label for="profileName" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="profileName" value="<?php echo htmlspecialchars($displayName); ?>" placeholder="Enter your full name">
                                    </div>
                                    <div class="mb-3">
                                        <label for="profileEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="profileEmail" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" placeholder="Enter your email">
                                    </div>
                                    <div class="mb-3">
                                        <label for="profilePhone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="profilePhone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" placeholder="Enter your phone number">
                                    </div>
                                    <div class="mb-3">
                                        <label for="profileDepartment" class="form-label">Department</label>
                                        <input type="text" class="form-control" id="profileDepartment" value="Human Resources" readonly>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary-modern">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                        <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div class="tab-pane fade" id="settings-pane" role="tabpanel" aria-labelledby="settings-tab">
                        <form id="settingsForm">
                            <div class="mb-4">
                                <h6 class="mb-3"><i class="fas fa-bell me-2"></i>Notifications</h6>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifyEmail" checked>
                                    <label class="form-check-label" for="notifyEmail">
                                        Email Notifications
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifyLicense" checked>
                                    <label class="form-check-label" for="notifyLicense">
                                        License Expiry Alerts
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifyTasks" checked>
                                    <label class="form-check-label" for="notifyTasks">
                                        Task Assignments
                                    </label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6 class="mb-3"><i class="fas fa-palette me-2"></i>Appearance</h6>
                                <div class="mb-3">
                                    <label for="themeSelect" class="form-label">Theme</label>
                                    <select class="form-select theme-select" id="themeSelect">
                                        <option value="light" selected>Light</option>
                                        <option value="dark">Dark</option>
                                        <option value="auto">Auto (System)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="timeFormat" class="form-label">Time Format</label>
                                    <select class="form-select" id="timeFormat">
                                        <option value="12" selected>12-hour (AM/PM)</option>
                                        <option value="24">24-hour</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6 class="mb-3"><i class="fas fa-shield-alt me-2"></i>Security</h6>
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="currentPassword" placeholder="Enter current password">
                                </div>
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="newPassword" placeholder="Enter new password">
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm new password">
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary-modern">
                                    <i class="fas fa-save me-2"></i>Save Settings
                                </button>
                                <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   MODERN DASHBOARD STYLES
   ============================================ */

/* Hide the main header with black background */
.main-content .header {
    display: none !important;
}

/* Dashboard Container */
.dashboard-modern {
    /* Use portal-wide spacing system (font-override.css) instead of page-local padding */
    padding: 0;
    max-width: 100%;
    overflow-x: hidden;
    /* Default for non HR-Admin portals */
    background: #ffffff;
    min-height: 100vh;
}

/* HR Admin only: keep the light separated background */
body.portal-hr-admin .dashboard-modern {
    background: #f8fafc;
}

/* Page Header */
.page-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}

.page-title-modern {
    flex: 1;
}

.page-title-main {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
    letter-spacing: -0.02em;
}

.page-subtitle {
    font-size: 0.9375rem;
    color: #64748b;
    margin: 0;
    font-weight: 400;
}

.page-actions-modern {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

/* Buttons */
.btn-outline-modern {
    border: 1.5px solid #e2e8f0;
    color: #475569;
    background: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.btn-outline-modern:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.btn-primary-modern {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: #ffffff;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.25);
}

.btn-primary-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.35);
    background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
}

.btn-link-modern {
    color: #1fb2d5;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
}

.btn-link-modern:hover {
    color: #0ea5e9;
    background: #f0f9ff;
}

/* Stat Cards */
.stat-card-modern {
    border: none;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    background: #ffffff;
    transition: all 0.3s ease;
    overflow: hidden;
}

.stat-card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
}

.card-body-modern {
    padding: 1.5rem;
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-label {
    font-size: 0.8125rem;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-icon {
    font-size: 1.125rem;
    color: #cbd5e1;
    opacity: 0.6;
}

.stat-content {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.stat-number {
    font-size: 2.25rem;
    font-weight: 700;
    color: #000000;
    margin: 0;
    line-height: 1;
    letter-spacing: -0.02em;
    /* Number rendering fix - ensures digits display correctly on Windows 10/11 */
    font-family: 'Segoe UI', Arial, Helvetica, sans-serif !important;
    font-variant-numeric: tabular-nums !important;
    font-feature-settings: 'tnum' !important;
    -webkit-font-feature-settings: 'tnum' !important;
    -moz-font-feature-settings: 'tnum' !important;
    text-rendering: optimizeLegibility !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
}

.stat-footer {
    font-size: 0.8125rem;
    color: #94a3b8;
    display: block;
    margin-top: 0.5rem;
}

/* Badges */
.badge-success-modern,
.badge-primary-modern,
.badge-warning-modern,
.badge-danger-modern {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    letter-spacing: 0.01em;
}

.badge-success-modern {
    background: #dcfce7;
    color: #16a34a;
}

.badge-primary-modern {
    background: #dbeafe;
    color: #2563eb;
}

.badge-warning-modern {
    background: #fef3c7;
    color: #d97706;
}

.badge-danger-modern {
    background: #fee2e2;
    color: #dc2626;
}

.badge-live {
    background: #f0fdf4;
    color: #22c55e;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
}

/* Main Cards */
.card-modern {
    border: none;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    background: #ffffff;
    overflow: hidden;
}

.card-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.card-title-modern {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
    letter-spacing: -0.01em;
}

.card-subtitle {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0;
}

/* Mini Cards */
.mini-card-modern {
    /* Default for non HR-Admin portals */
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
    transition: all 0.2s ease;
}

body.portal-hr-admin .mini-card-modern {
    background: #f8fafc;
}

.mini-card-modern:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.mini-card-header {
    margin-bottom: 1rem;
}

.mini-card-label {
    font-size: 0.8125rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.mini-card-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.progress-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8125rem;
    color: #475569;
}

.progress-value {
    font-weight: 600;
    color: #1e293b;
}

.progress-modern {
    height: 8px;
    background: #e2e8f0;
    border-radius: 6px;
    overflow: hidden;
}

.progress-bar-primary {
    background: linear-gradient(90deg, #1fb2d5 0%, #0ea5e9 100%);
    border-radius: 6px;
}

.progress-bar-info {
    background: linear-gradient(90deg, #06b6d4 0%, #0891b2 100%);
    border-radius: 6px;
}

/* Guard Types List */
.guard-types-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.guard-type-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.guard-type-item:last-child {
    border-bottom: none;
}

.guard-type-name {
    font-weight: 500;
    color: #1e293b;
    font-size: 0.875rem;
}

/* Section Header */
.section-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
}

.section-subtitle {
    font-size: 0.8125rem;
    color: #64748b;
}

/* Posts List */
.posts-list-modern {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.post-item-modern {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem 1rem;
    /* Default for non HR-Admin portals */
    background: #ffffff;
    border-radius: 8px;
    transition: all 0.2s ease;
}

body.portal-hr-admin .post-item-modern {
    background: #f8fafc;
}

.post-item-modern:hover {
    /* Default for non HR-Admin portals */
    background: #ffffff;
    transform: translateX(2px);
}

body.portal-hr-admin .post-item-modern:hover {
    background: #f1f5f9;
}

.post-name {
    font-weight: 500;
    color: #1e293b;
    font-size: 0.875rem;
}

/* Table Styling */
.table-responsive {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    background: #ffffff;
}

.table-responsive::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.table {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: auto;
    width: 100%;
}

.table thead th {
    border-bottom: 2px solid #e2e8f0;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem;
    /* Default for non HR-Admin portals */
    background-color: #ffffff;
    color: #64748b;
    position: sticky;
    top: 0;
    z-index: 10;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
}

body.portal-hr-admin .table thead th {
    background-color: #f8fafc;
}

/* Ensure headers align with their corresponding data columns */
.table thead th:first-child {
    text-align: center;
    padding-left: 1rem;
    padding-right: 1rem;
}

.table thead th:nth-child(2) {
    text-align: center;
    padding-left: 1rem;
    padding-right: 1rem;
}

.table thead th:nth-child(3) {
    text-align: center;
    padding-left: 1rem;
    padding-right: 1rem;
}

.table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.15s ease;
}

.table tbody tr:hover {
    /* Default for non HR-Admin portals */
    background-color: #ffffff;
}

body.portal-hr-admin .table tbody tr:hover {
    background-color: #f8fafc;
}

/* Ensure all rows align properly */
.table thead tr,
.table tbody tr {
    display: table-row;
    vertical-align: middle;
}

/* Ensure consistent cell alignment across all rows */
.table thead th,
.table tbody td {
    display: table-cell;
    vertical-align: middle;
}

.table tbody tr:last-child {
    border-bottom: none;
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    color: #475569;
    font-size: 0.875rem;
    text-align: center;
    line-height: 1.5;
}

/* Ensure Name column alignment matches header */
.table tbody td:first-child {
    vertical-align: middle;
    text-align: center;
    padding-left: 1rem;
    padding-right: 1rem;
}

/* Ensure Post column alignment matches header */
.table tbody td:nth-child(2) {
    vertical-align: middle;
    line-height: 1.5;
    text-align: center;
    padding-left: 1rem;
    padding-right: 1rem;
}

/* Ensure License Expiry column alignment matches header */
.table tbody td:nth-child(3) {
    vertical-align: middle;
    text-align: center;
    padding-left: 1rem;
    padding-right: 1rem;
}

/* Ensure consistent column widths and alignment - headers and cells must match */
.table thead th:first-child,
.table tbody td:first-child {
    min-width: 250px;
    width: 35%;
    text-align: center;
}

.table thead th:nth-child(2),
.table tbody td:nth-child(2) {
    min-width: 180px;
    width: 25%;
    text-align: center;
}

.table thead th:nth-child(3),
.table tbody td:nth-child(3) {
    min-width: 250px;
    width: 40%;
    text-align: center;
}

/* License Expiry Cell Styling */
.license-expiry-cell {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    justify-content: center;
    align-items: center;
    width: 100%;
}

.license-number {
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1.5;
    width: 100%;
}

.license-expiry-date {
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1.5;
    width: 100%;
}

/* Ensure all table cells have consistent vertical alignment */
.table tbody td {
    vertical-align: middle !important;
}

.table tbody td.align-middle {
    vertical-align: middle !important;
}

/* Center align text content in cells */
.table tbody td.text-center {
    text-align: center !important;
}

/* Ensure badges are centered */
.license-expiry-cell .badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* Center all content in license expiry cell */
.license-expiry-cell > span {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
}

/* License code styling */
.license-code {
    background: #f0f9ff;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
    color: #0284c7;
    font-weight: 500;
    border: 1px solid #bae6fd;
    display: inline-block;
    text-align: center;
    margin: 0 auto;
}

/* Employee Info Styles */
.employee-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.employee-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.2);
}

.employee-details {
    flex: 1;
    min-width: 0;
}

.employee-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
    line-height: 1.3;
    font-size: 0.9375rem;
}

.employee-email {
    color: #64748b;
    font-size: 0.8125rem;
    line-height: 1.3;
    margin-top: 0.125rem;
}

.employee-number {
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.employee-row {
    transition: all 0.2s ease;
}

.employee-row:hover {
    background-color: #f8fafc;
    transform: translateX(2px);
}

.license-info {
    min-width: 180px;
    line-height: 1.6;
}

.license-info > div {
    margin-bottom: 0.5rem;
}

.license-info > div:last-child {
    margin-bottom: 0;
}

.license-info small {
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 0.25rem;
    color: #6c757d;
}

.license-info strong {
    font-size: 0.875rem;
    font-weight: 600;
    display: block;
    margin-bottom: 0.25rem;
}

.employment-details {
    min-width: 150px;
    line-height: 1.6;
}

.employment-details > div {
    margin-bottom: 0.5rem;
}

.employment-details > div:last-child {
    margin-bottom: 0;
}

.employment-details small {
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 0.25rem;
    color: #6c757d;
}

.employment-details span:not(.badge) {
    font-size: 0.875rem;
    display: block;
}

.status-badge {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-badge.active {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #dc2626;
}

.status-badge.terminated {
    background: #fee2e2;
    color: #dc2626;
}

.status-badge.suspended {
    background: #fef3c7;
    color: #d97706;
}


/* Responsive */
@media (max-width: 768px) {
    .dashboard-modern {
        padding: 0;
    }
    
    .page-header-modern {
        flex-direction: column;
        gap: 1rem;
    }
    
    .page-actions-modern {
        width: 100%;
        justify-content: flex-start;
    }
    
    .stat-number {
        font-size: 1.75rem;
    }
    
    .table thead th,
    .table tbody td {
        padding: 0.75rem 0.5rem;
        font-size: 0.8125rem;
    }
}
</style>

<script>
// Handle employee row clicks to navigate to employee view page
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.employee-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking interactive elements
            if (e.target.closest('a') || e.target.closest('button') || e.target.closest('input')) {
                return;
            }
            
            const employeeId = this.dataset.employeeId;
            if (employeeId) {
                window.location.href = `?page=view_employee&id=${employeeId}`;
            }
        });
    });
});
</script>

<?php endif; ?>
