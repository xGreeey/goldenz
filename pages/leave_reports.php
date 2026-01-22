<?php
$page_title = 'Leave Reports - Golden Z-5 HR System';
$page = 'leave_reports';

// Get database connection
$pdo = get_db_connection();

// Get filter parameters
$month = $_GET['month'] ?? date('Y-m');
$department = $_GET['department'] ?? '';

// Mock departments
$departments = ['Security', 'Administration', 'Operations', 'HR', 'Accounting'];

// Mock report data
$monthly_stats = [
    'total_requests' => rand(50, 150),
    'approved' => rand(40, 120),
    'rejected' => rand(5, 20),
    'pending' => rand(2, 10),
    'total_days_taken' => rand(200, 500),
];

$by_type = [
    'Sick Leave' => rand(20, 60),
    'Vacation Leave' => rand(30, 80),
    'Emergency Leave' => rand(10, 30),
    'Maternity Leave' => rand(2, 10),
    'Paternity Leave' => rand(1, 5),
];

$by_department = [];
foreach ($departments as $dept) {
    $by_department[$dept] = rand(10, 50);
}
?>

<div class="container-fluid hrdash">
    <!-- Filters -->
    <div class="card card-modern mb-4">
        <div class="card-body-modern">
            <form method="GET" action="">
                <input type="hidden" name="page" value="leave_reports">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($month); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department === $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary-modern">
                            <i class="fas fa-chart-bar me-2"></i>Generate Report
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-modern" onclick="window.location.href='?page=leave_reports'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Total Requests</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($monthly_stats['total_requests']); ?></div>
                </div>
                <div class="hrdash-stat__meta">For <?php echo date('F Y', strtotime($month . '-01')); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Approved</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($monthly_stats['approved']); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <span><?php echo round(($monthly_stats['approved'] / $monthly_stats['total_requests']) * 100); ?>%</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Approval rate</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Total Days Taken</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($monthly_stats['total_days_taken']); ?></div>
                </div>
                <div class="hrdash-stat__meta">Leave days utilized</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Pending</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($monthly_stats['pending']); ?></div>
                </div>
                <div class="hrdash-stat__meta">Awaiting approval</div>
            </div>
        </div>
    </div>

    <!-- Reports -->
    <div class="row g-4">
        <!-- By Leave Type -->
        <div class="col-md-6">
            <div class="card card-modern">
                <div class="card-header-modern">
                    <div>
                        <h5 class="card-title-modern">Requests by Leave Type</h5>
                        <div class="card-subtitle">Breakdown of leave types</div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th class="text-end">Requests</th>
                                    <th class="text-end">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($by_type as $type => $count): 
                                    $total = max(1, $monthly_stats['total_requests']);
                                    $percentage = round(($count / $total) * 100);
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($type); ?></td>
                                        <td class="text-end fw-semibold"><?php echo number_format($count); ?></td>
                                        <td class="text-end">
                                            <div class="progress" style="height: 8px; width: 100px; float: right;">
                                                <div class="progress-bar bg-primary" style="width: <?php echo htmlspecialchars($percentage . '%'); ?>"></div>
                                            </div>
                                            <span class="ms-2"><?php echo $percentage; ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- By Department -->
        <div class="col-md-6">
            <div class="card card-modern">
                <div class="card-header-modern">
                    <div>
                        <h5 class="card-title-modern">Requests by Department</h5>
                        <div class="card-subtitle">Distribution across departments</div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th class="text-end">Requests</th>
                                    <th class="text-end">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($by_department as $dept => $count): 
                                    $total = max(1, $monthly_stats['total_requests']);
                                    $percentage = round(($count / $total) * 100);
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($dept); ?></td>
                                        <td class="text-end fw-semibold"><?php echo number_format($count); ?></td>
                                        <td class="text-end">
                                            <div class="progress" style="height: 8px; width: 100px; float: right;">
                                                <div class="progress-bar bg-success" style="width: <?php echo htmlspecialchars($percentage . '%'); ?>"></div>
                                            </div>
                                            <span class="ms-2"><?php echo $percentage; ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <button type="button" class="btn btn-primary-modern" onclick="window.print()">
            <i class="fas fa-print me-2"></i>Print Report
        </button>
        <button type="button" class="btn btn-outline-modern">
            <i class="fas fa-file-pdf me-2"></i>Export to PDF
        </button>
    </div>
</div>
