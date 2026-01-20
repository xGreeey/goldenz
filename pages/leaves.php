<?php
$page_title = 'Leave Requests - Golden Z-5 HR System';
$page = 'leaves';

// Get database connection
$pdo = get_db_connection();

// Handle leave request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $request_id = $_POST['request_id'] ?? '';
    
    if ($action === 'approve' && $request_id) {
        $notes = $_POST['approval_notes'] ?? '';
        // Handle approval (to be implemented with database)
        redirect_with_message('?page=leaves', 'Leave request approved successfully!', 'success');
    } elseif ($action === 'reject' && $request_id) {
        $notes = $_POST['rejection_notes'] ?? '';
        // Handle rejection (to be implemented with database)
        redirect_with_message('?page=leaves', 'Leave request rejected.', 'info');
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'pending';
$employee_id = $_GET['employee_id'] ?? '';
$leave_type = $_GET['leave_type'] ?? '';

// Get all employees for filter dropdown
$employees = get_employees();

// Temporary: Mock data for leave requests (to be replaced with database)
$leave_requests = [];
$statuses = ['pending', 'approved', 'rejected'];
$leave_types = ['Sick Leave', 'Vacation Leave', 'Emergency Leave', 'Maternity Leave', 'Paternity Leave'];

for ($i = 1; $i <= 20; $i++) {
    $status = $statuses[array_rand($statuses)];
    $type = $leave_types[array_rand($leave_types)];
    $start_date = date('Y-m-d', strtotime('+' . rand(-30, 60) . ' days'));
    $end_date = date('Y-m-d', strtotime($start_date . ' +' . rand(1, 5) . ' days'));
    
    $leave_requests[] = [
        'id' => $i,
        'employee_id' => rand(1, 50),
        'employee_name' => 'Employee ' . $i,
        'employee_post' => 'Post ' . rand(1, 10),
        'leave_type' => $type,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'days' => (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1,
        'reason' => 'Personal matters that require attention.',
        'status' => $status,
        'request_date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
        'processed_by' => $status !== 'pending' ? 'HR Admin' : null,
        'processed_date' => $status !== 'pending' ? date('Y-m-d H:i:s', strtotime('-' . rand(1, 10) . ' days')) : null,
        'notes' => $status === 'rejected' ? 'Insufficient leave balance' : ($status === 'approved' ? 'Approved' : null),
    ];
}

// Apply filters
$filtered_requests = array_filter($leave_requests, function($req) use ($status_filter, $employee_id, $leave_type) {
    if ($status_filter && $req['status'] !== $status_filter) return false;
    if ($employee_id && $req['employee_id'] != $employee_id) return false;
    if ($leave_type && $req['leave_type'] !== $leave_type) return false;
    return true;
});

// Get statistics
$stats = [
    'pending' => count(array_filter($leave_requests, fn($r) => $r['status'] === 'pending')),
    'approved' => count(array_filter($leave_requests, fn($r) => $r['status'] === 'approved')),
    'rejected' => count(array_filter($leave_requests, fn($r) => $r['status'] === 'rejected')),
    'total' => count($leave_requests),
];

$days_pending = array_sum(array_map(fn($r) => $r['status'] === 'pending' ? $r['days'] : 0, $leave_requests));
?>

<div class="container-fluid hrdash">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Pending Requests</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($stats['pending']); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--negative">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $days_pending; ?> days</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Awaiting approval</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Approved</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($stats['approved']); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Approved leave requests</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Rejected</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($stats['rejected']); ?></div>
                </div>
                <div class="hrdash-stat__meta">Rejected requests</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Total Requests</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($stats['total']); ?></div>
                </div>
                <div class="hrdash-stat__meta">All time requests</div>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card card-modern">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title-modern">Leave Requests Inbox</h5>
                <div class="card-subtitle">Manage employee leave requests</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-modern" id="exportRequestsBtn">
                    <i class="fas fa-file-export me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Filters and Status Tabs -->
        <div class="card-body-modern">
            <!-- Status Tabs -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="?page=leaves&status=pending">
                        Pending <span class="badge bg-warning ms-1"><?php echo $stats['pending']; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" href="?page=leaves&status=approved">
                        Approved <span class="badge bg-success ms-1"><?php echo $stats['approved']; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" href="?page=leaves&status=rejected">
                        Rejected <span class="badge bg-danger ms-1"><?php echo $stats['rejected']; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === '' ? 'active' : ''; ?>" href="?page=leaves">
                        All <span class="badge bg-secondary ms-1"><?php echo $stats['total']; ?></span>
                    </a>
                </li>
            </ul>

            <!-- Filters -->
            <form method="GET" action="" class="mb-4">
                <input type="hidden" name="page" value="leaves">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
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
                    <div class="col-md-3">
                        <label class="form-label">Leave Type</label>
                        <select name="leave_type" class="form-select">
                            <option value="">All Types</option>
                            <?php foreach ($leave_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $leave_type === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
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
                        <button type="button" class="btn btn-outline-modern" onclick="window.location.href='?page=leaves&status=<?php echo htmlspecialchars($status_filter); ?>'" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Requests Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Dates</th>
                            <th>Days</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filtered_requests)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No leave requests found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filtered_requests as $request): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($request['employee_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($request['employee_post']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($request['leave_type']); ?></span>
                                    </td>
                                    <td>
                                        <div><?php echo date('M d, Y', strtotime($request['start_date'])); ?></div>
                                        <small class="text-muted">to <?php echo date('M d, Y', strtotime($request['end_date'])); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?php echo $request['days']; ?> day<?php echo $request['days'] > 1 ? 's' : ''; ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($request['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary view-details-btn" 
                                                    data-request='<?php echo json_encode($request); ?>'
                                                    data-bs-toggle="modal" data-bs-target="#requestDetailsModal"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-outline-success approve-btn" 
                                                        data-request-id="<?php echo $request['id']; ?>"
                                                        data-bs-toggle="modal" data-bs-target="#approveModal"
                                                        title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger reject-btn" 
                                                        data-request-id="<?php echo $request['id']; ?>"
                                                        data-bs-toggle="modal" data-bs-target="#rejectModal"
                                                        title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
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

<!-- Request Details Modal -->
<div class="modal fade" id="requestDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Leave Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="requestDetailsContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="request_id" id="approve_request_id">
                    <div class="mb-3">
                        <label class="form-label">Approval Notes (Optional)</label>
                        <textarea name="approval_notes" class="form-control" rows="3" placeholder="Add any notes or comments..."></textarea>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Are you sure you want to approve this leave request?
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="request_id" id="reject_request_id">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_notes" class="form-control" rows="3" placeholder="Provide a reason for rejection..." required></textarea>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        The employee will be notified of this rejection.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle view details button
document.addEventListener('DOMContentLoaded', function() {
    // View details
    document.querySelectorAll('.view-details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const request = JSON.parse(this.dataset.request);
            const content = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Employee:</strong><br>
                        ${request.employee_name}<br>
                        <small class="text-muted">${request.employee_post}</small>
                    </div>
                    <div class="col-md-6">
                        <strong>Leave Type:</strong><br>
                        <span class="badge bg-info">${request.leave_type}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Start Date:</strong><br>
                        ${new Date(request.start_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                    </div>
                    <div class="col-md-6">
                        <strong>End Date:</strong><br>
                        ${new Date(request.end_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                    </div>
                    <div class="col-md-6">
                        <strong>Duration:</strong><br>
                        ${request.days} day${request.days > 1 ? 's' : ''}
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-${request.status === 'pending' ? 'warning' : request.status === 'approved' ? 'success' : 'danger'}">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span>
                    </div>
                    <div class="col-12">
                        <strong>Reason:</strong><br>
                        <p class="mb-0">${request.reason}</p>
                    </div>
                    ${request.processed_by ? `
                    <div class="col-md-6">
                        <strong>Processed By:</strong><br>
                        ${request.processed_by}
                    </div>
                    <div class="col-md-6">
                        <strong>Processed Date:</strong><br>
                        ${new Date(request.processed_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                    </div>
                    ` : ''}
                    ${request.notes ? `
                    <div class="col-12">
                        <strong>Notes:</strong><br>
                        <p class="mb-0">${request.notes}</p>
                    </div>
                    ` : ''}
                </div>
            `;
            document.getElementById('requestDetailsContent').innerHTML = content;
        });
    });

    // Approve button
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('approve_request_id').value = this.dataset.requestId;
        });
    });

    // Reject button
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('reject_request_id').value = this.dataset.requestId;
        });
    });
});
</script>
