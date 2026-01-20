<?php
$page_title = 'Attendance - Golden Z-5 HR System';
$page = 'attendance';

// Get database connection
$pdo = get_db_connection();

// Handle manual adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'adjust_attendance') {
        $attendance_id = $_POST['attendance_id'] ?? '';
        $time_in = $_POST['time_in'] ?? '';
        $time_out = $_POST['time_out'] ?? '';
        $reason = $_POST['adjustment_reason'] ?? '';
        // Handle adjustment (to be implemented with database and audit log)
        redirect_with_message('?page=attendance', 'Attendance adjusted successfully!', 'success');
    }
}

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$employee_id = $_GET['employee_id'] ?? '';
$status = $_GET['status'] ?? '';

// Get all employees for filter dropdown
$employees = get_employees();

// Mock attendance data
$attendance_records = [];
$statuses = ['Present', 'Late', 'Absent', 'Half-Day', 'On Leave'];

for ($i = 1; $i <= 30; $i++) {
    $status_val = $statuses[array_rand($statuses)];
    $time_in = null;
    $time_out = null;
    
    if ($status_val === 'Present') {
        $time_in = date('H:i:s', strtotime('08:' . rand(0, 30) . ':00'));
        $time_out = date('H:i:s', strtotime('17:' . rand(0, 30) . ':00'));
    } elseif ($status_val === 'Late') {
        $time_in = date('H:i:s', strtotime('09:' . rand(0, 59) . ':00'));
        $time_out = date('H:i:s', strtotime('17:' . rand(0, 30) . ':00'));
    } elseif ($status_val === 'Half-Day') {
        $time_in = date('H:i:s', strtotime('08:' . rand(0, 30) . ':00'));
        $time_out = date('H:i:s', strtotime('12:' . rand(0, 30) . ':00'));
    }
    
    $attendance_records[] = [
        'id' => $i,
        'employee_id' => rand(1, 50),
        'employee_name' => 'Employee ' . $i,
        'employee_post' => 'Post ' . rand(1, 10),
        'date' => date('Y-m-d', strtotime($date_from . ' +' . rand(0, 7) . ' days')),
        'time_in' => $time_in,
        'time_out' => $time_out,
        'status' => $status_val,
        'hours_worked' => $time_in && $time_out ? round((strtotime($time_out) - strtotime($time_in)) / 3600, 2) : 0,
        'is_adjusted' => rand(0, 10) > 8,
        'adjustment_reason' => rand(0, 10) > 8 ? 'Manual time correction due to system error' : null,
    ];
}

// Get statistics
$total_present = count(array_filter($attendance_records, fn($r) => $r['status'] === 'Present'));
$total_late = count(array_filter($attendance_records, fn($r) => $r['status'] === 'Late'));
$total_absent = count(array_filter($attendance_records, fn($r) => $r['status'] === 'Absent'));
$avg_hours = array_sum(array_column($attendance_records, 'hours_worked')) / max(count($attendance_records), 1);
?>

<div class="container-fluid hrdash">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Present</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($total_present); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Employees present today</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Late</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-warning"><?php echo number_format($total_late); ?></div>
                </div>
                <div class="hrdash-stat__meta">Late arrivals</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Absent</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-danger"><?php echo number_format($total_absent); ?></div>
                </div>
                <div class="hrdash-stat__meta">Absent today</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Avg Hours</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($avg_hours, 1); ?>h</div>
                </div>
                <div class="hrdash-stat__meta">Average hours worked</div>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card card-modern">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title-modern">Daily Attendance</h5>
                <div class="card-subtitle">Track employee attendance and work hours</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-modern" id="exportAttendanceBtn">
                    <i class="fas fa-file-export me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-body-modern">
            <form method="GET" action="" class="mb-4">
                <input type="hidden" name="page" value="attendance">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
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
                            <option value="">All Status</option>
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $status === $s ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary-modern">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-modern" onclick="window.location.href='?page=attendance'" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Attendance Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Post</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendance_records)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No attendance records found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($record['employee_name']); ?></div>
                                        <?php if ($record['is_adjusted']): ?>
                                            <small class="text-warning"><i class="fas fa-edit"></i> Adjusted</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted"><?php echo htmlspecialchars($record['employee_post']); ?></td>
                                    <td><?php echo $record['time_in'] ? date('g:i A', strtotime($record['time_in'])) : '-'; ?></td>
                                    <td><?php echo $record['time_out'] ? date('g:i A', strtotime($record['time_out'])) : '-'; ?></td>
                                    <td><?php echo $record['hours_worked'] > 0 ? number_format($record['hours_worked'], 1) . 'h' : '-'; ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'bg-secondary';
                                        if ($record['status'] === 'Present') $badge_class = 'bg-success';
                                        elseif ($record['status'] === 'Late') $badge_class = 'bg-warning';
                                        elseif ($record['status'] === 'Absent') $badge_class = 'bg-danger';
                                        elseif ($record['status'] === 'On Leave') $badge_class = 'bg-info';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($record['status']); ?></span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary adjust-btn" 
                                                data-record='<?php echo json_encode($record); ?>'
                                                data-bs-toggle="modal" data-bs-target="#adjustAttendanceModal"
                                                title="Adjust Time">
                                            <i class="fas fa-edit"></i>
                                        </button>
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

<!-- Adjust Attendance Modal -->
<div class="modal fade" id="adjustAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="adjust_attendance">
                    <input type="hidden" name="attendance_id" id="adjust_attendance_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" id="adjust_employee_name" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" id="adjust_date" class="form-control" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Time In</label>
                            <input type="time" name="time_in" id="adjust_time_in" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Time Out</label>
                            <input type="time" name="time_out" id="adjust_time_out" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Adjustment <span class="text-danger">*</span></label>
                        <textarea name="adjustment_reason" class="form-control" rows="3" placeholder="Provide a detailed reason for this adjustment..." required></textarea>
                        <small class="text-muted">This will be logged for audit purposes.</small>
                    </div>
                    
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Manual adjustments are logged and audited. Ensure the reason is valid and documented.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle adjust button
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.adjust-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const record = JSON.parse(this.dataset.record);
            document.getElementById('adjust_attendance_id').value = record.id;
            document.getElementById('adjust_employee_name').value = record.employee_name;
            document.getElementById('adjust_date').value = new Date(record.date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'});
            document.getElementById('adjust_time_in').value = record.time_in || '';
            document.getElementById('adjust_time_out').value = record.time_out || '';
        });
    });
});
</script>
