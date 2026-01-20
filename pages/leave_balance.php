<?php
$page_title = 'Leave Balance - Golden Z-5 HR System';
$page = 'leave_balance';

// Get database connection
$pdo = get_db_connection();

// Get all employees
$employees = get_employees();

// Mock leave balance data
$leave_balances = [];
foreach ($employees as $emp) {
    $leave_balances[] = [
        'employee_id' => $emp['id'],
        'employee_name' => trim(($emp['surname'] ?? '') . ', ' . ($emp['first_name'] ?? '')),
        'employee_post' => $emp['post'] ?? 'Unassigned',
        'sick_leave' => rand(5, 15),
        'vacation_leave' => rand(10, 20),
        'emergency_leave' => rand(3, 5),
        'used_sick' => rand(0, 5),
        'used_vacation' => rand(0, 10),
        'used_emergency' => rand(0, 3),
    ];
}

// Limit to first 50 for display
$leave_balances = array_slice($leave_balances, 0, 50);
?>

<div class="container-fluid hrdash">
    <!-- Page Header -->
    <div class="card card-modern mb-4">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title-modern">Leave Balance</h5>
                <div class="card-subtitle">View employee leave balances</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-modern" id="exportBalanceBtn">
                    <i class="fas fa-file-export me-2"></i>Export
                </button>
            </div>
        </div>

        <div class="card-body-modern">
            <!-- Leave Balance Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Post</th>
                            <th class="text-center">Sick Leave</th>
                            <th class="text-center">Vacation Leave</th>
                            <th class="text-center">Emergency Leave</th>
                            <th class="text-center">Total Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leave_balances as $balance): 
                            $available_sick = $balance['sick_leave'] - $balance['used_sick'];
                            $available_vacation = $balance['vacation_leave'] - $balance['used_vacation'];
                            $available_emergency = $balance['emergency_leave'] - $balance['used_emergency'];
                            $total_available = $available_sick + $available_vacation + $available_emergency;
                        ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($balance['employee_name']); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($balance['employee_post']); ?></td>
                                <td class="text-center">
                                    <div>
                                        <span class="badge bg-<?php echo $available_sick > 5 ? 'success' : ($available_sick > 2 ? 'warning' : 'danger'); ?>">
                                            <?php echo $available_sick; ?> / <?php echo $balance['sick_leave']; ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">Used: <?php echo $balance['used_sick']; ?></small>
                                </td>
                                <td class="text-center">
                                    <div>
                                        <span class="badge bg-<?php echo $available_vacation > 10 ? 'success' : ($available_vacation > 5 ? 'warning' : 'danger'); ?>">
                                            <?php echo $available_vacation; ?> / <?php echo $balance['vacation_leave']; ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">Used: <?php echo $balance['used_vacation']; ?></small>
                                </td>
                                <td class="text-center">
                                    <div>
                                        <span class="badge bg-<?php echo $available_emergency > 2 ? 'success' : ($available_emergency > 1 ? 'warning' : 'danger'); ?>">
                                            <?php echo $available_emergency; ?> / <?php echo $balance['emergency_leave']; ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">Used: <?php echo $balance['used_emergency']; ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary fs-6"><?php echo $total_available; ?> days</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
