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

// Expiring licenses list (next 90 days) - only active employees with valid dates
$expiring = [];
$expStmt = $pdo->prepare("SELECT id, first_name, surname, post, license_no, license_exp_date 
                          FROM employees 
                          WHERE status = 'Active'
                                AND license_no IS NOT NULL 
                                AND license_no != ''
                                AND license_exp_date IS NOT NULL 
                                AND license_exp_date != '' 
                                AND license_exp_date != '0000-00-00'
                                AND license_exp_date >= CURDATE() 
                                AND license_exp_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                          ORDER BY license_exp_date ASC 
                          LIMIT 8");
$expStmt->execute();
$expiring = $expStmt->fetchAll(PDO::FETCH_ASSOC);

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
        return ['class' => 'text-danger fw-bold', 'badge' => 'bg-danger', 'text' => 'Expires in ' . $days_until_exp . ' days', 'icon' => 'fa-exclamation-circle'];
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
?>

<div class="container-fluid dashboard-modern">
    <!-- Page Header -->
    <div class="page-header-modern mb-5">
        <div class="page-title-modern">
            <h1 class="page-title-main">Dashboard</h1>
            <p class="page-subtitle"><?php echo $greeting; ?>! Welcome back.</p>
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
    padding: 2rem 2.5rem;
    max-width: 100%;
    overflow-x: hidden;
    background: #f8fafc;
    min-height: 100vh;
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
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    color: #ffffff;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.25);
}

.btn-primary-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.35);
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
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
    color: #1e293b;
    margin: 0;
    line-height: 1;
    letter-spacing: -0.02em;
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
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
    transition: all 0.2s ease;
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
    background: #f8fafc;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.post-item-modern:hover {
    background: #f1f5f9;
    transform: translateX(2px);
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
    background-color: #f8fafc;
    color: #64748b;
    position: sticky;
    top: 0;
    z-index: 10;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
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
        padding: 1.5rem 1rem;
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
