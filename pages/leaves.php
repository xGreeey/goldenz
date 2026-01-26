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
$employee_roles = ['Head of Department', 'Software Architect', 'Senior Developer', 'QA Engineer', 'Developer', 'Project Manager'];

// Generate more realistic employee names and data
$employee_names = [
    ['Alexey', 'Sergeevich', 'Berestov'],
    ['Maria', 'Vladimirovna', 'Rodionova'],
    ['Dmitry', 'Alexeevich', 'Korneev'],
    ['Elena', 'Viktorovna', 'Vorontsova'],
    ['Andrey', 'Igorevich', 'Streltsov'],
    ['Anna', 'Sergeevna', 'Demidova'],
    ['Mikhail', 'Nikolaevich', 'Lazarev'],
    ['Sergey', 'Vladimirovich', 'Saveliev'],
    ['Olga', 'Alexandrovna', 'Orlova'],
    ['Alexander', 'Pavlovich', 'Krylov'],
];

for ($i = 0; $i < 10; $i++) {
    $status = 'pending'; // Focus on pending requests for inbox
    $type = $leave_types[array_rand($leave_types)];
    $start_date = date('Y-m-d', strtotime('+' . rand(1, 90) . ' days'));
    $end_date = date('Y-m-d', strtotime($start_date . ' +' . rand(4, 14) . ' days'));
    $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
    $remaining_leave = rand(5, 28);
    
    $name_parts = $employee_names[$i % count($employee_names)];
    $full_name = $name_parts[0] . ' ' . $name_parts[1] . ' ' . $name_parts[2];
    
    $leave_requests[] = [
        'id' => $i + 1,
        'employee_id' => $i + 1,
        'employee_name' => $full_name,
        'employee_post' => $employee_roles[array_rand($employee_roles)],
        'leave_type' => $type,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'days' => $days,
        'remaining_leave' => $remaining_leave,
        'reason' => 'Personal matters that require attention.',
        'status' => $status,
        'request_date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
        'processed_by' => null,
        'processed_date' => null,
        'notes' => null,
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

<style>
/* Leave Requests (standard branding) - keep minimal, use existing system tokens */
.leaves-table thead th {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    color: #374151;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    padding: 0.75rem 1rem;
    white-space: nowrap;
}
.leaves-table tbody td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}
.leaves-table tbody tr:hover {
    background: #f9fafb;
}
.badge-leave-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-weight: 600; }
.badge-leave-approved { background: #dcfce7; color: #166534; border: 1px solid #86efac; font-weight: 600; }
.badge-leave-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; font-weight: 600; }

/* Fix View button icon visibility - Enterprise Theme */
.view-details-btn {
    min-width: 36px;
    height: 36px;
    padding: 0.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    overflow: visible !important;
    position: relative;
}

.view-details-btn i,
.view-details-btn .fas,
.view-details-btn .fa-eye {
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    color: #0f172a !important;
    font-size: 0.875rem !important;
    line-height: 1 !important;
    width: auto !important;
    height: auto !important;
    margin: 0 !important;
    text-rendering: auto !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
}

.view-details-btn:hover i,
.view-details-btn:hover .fas,
.view-details-btn:hover .fa-eye {
    color: #0f172a !important;
    opacity: 1 !important;
}

.view-details-btn:active i,
.view-details-btn:active .fas,
.view-details-btn:active .fa-eye,
.view-details-btn:focus i,
.view-details-btn:focus .fas,
.view-details-btn:focus .fa-eye {
    color: #0f172a !important;
    opacity: 1 !important;
}

.view-details-btn:disabled i,
.view-details-btn:disabled .fas,
.view-details-btn:disabled .fa-eye {
    color: #94a3b8 !important;
    opacity: 0.6 !important;
}
</style>

<div class="container-fluid hrdash">
    <!-- Header -->
    <div class="card card-modern mb-4">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title-modern">Leave Requests Inbox</h5>
                <div class="card-subtitle">Manage employee leave requests</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-modern" id="exportRequestsBtn" title="Export">
                    <i class="fas fa-file-export me-2"></i>Export
                </button>
            </div>
        </div>
        <div class="card-body-modern">
            <!-- Standard Filter Bar -->
            <form method="GET" action="" id="leaveFilterForm" class="d-flex gap-2 align-items-end" style="flex-wrap: nowrap;">
                <input type="hidden" name="page" value="leaves">
                <div class="flex-grow-1" style="min-width: 0;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Search</label>
                    <input type="text" id="leaveSearch" class="form-control form-control-sm" placeholder="search employee or post" autocomplete="off">
                </div>
                <div style="flex: 0 0 auto; min-width: 160px;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Status</label>
                    <select name="status" id="leaveStatusFilter" class="form-select form-select-sm">
                        <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>All</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                <div style="flex: 0 0 auto; min-width: 180px;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Leave Type</label>
                    <select name="leave_type" id="leaveTypeFilter" class="form-select form-select-sm">
                        <option value="">All</option>
                            <?php foreach ($leave_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $leave_type === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <div style="flex: 0 0 auto;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500; visibility: hidden;">Reset</label>
                    <a class="btn btn-outline-modern btn-sm" href="?page=leaves" title="Reset">
                            <i class="fas fa-times"></i>
                    </a>
                    </div>
                <div style="flex: 0 0 30%; min-width: 120px; text-align: right; margin-left: auto;">
                    <div style="font-size: 0.6875rem; color: #64748b; margin-bottom: 0.125rem;">Results</div>
                    <div id="leave-count" style="font-size: 1rem; font-weight: 600; color: #1e3a8a;"><?php echo number_format(count($filtered_requests)); ?></div>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-hover align-middle leaves-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Dates</th>
                            <th class="text-center">Days</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leaveTableBody">
                        <?php if (empty($filtered_requests)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No leave requests found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filtered_requests as $request): ?>
                                <tr class="leave-row">
                                    <td>
                                        <div class="fw-semibold employee-name"><?php echo htmlspecialchars($request['employee_name']); ?></div>
                                        <small class="text-muted employee-role"><?php echo htmlspecialchars($request['employee_post']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info fw-semibold"><?php echo htmlspecialchars($request['leave_type']); ?></span>
                                    </td>
                                    <td>
                                        <div><?php echo date('M d, Y', strtotime($request['start_date'])); ?> — <?php echo date('M d, Y', strtotime($request['end_date'])); ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary fw-semibold"><?php echo (int)$request['days']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <span class="badge badge-leave-pending">Pending</span>
                                        <?php elseif ($request['status'] === 'approved'): ?>
                                            <span class="badge badge-leave-approved">Approved</span>
                                        <?php else: ?>
                                            <span class="badge badge-leave-rejected">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button type="button"
                                                    class="btn btn-outline-modern btn-sm view-details-btn"
                                                    data-request='<?php echo json_encode($request); ?>'
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#requestDetailsModal"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <button type="button"
                                                        class="btn btn-primary-modern btn-sm approve-btn"
                                                        data-request-id="<?php echo $request['id']; ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#approveModal"
                                                        title="Approve">
                                                    <i class="fas fa-check me-1"></i>Approve
                                                </button>
                                                <button type="button"
                                                        class="btn btn-outline-modern btn-sm reject-btn"
                                                        style="border-color:#ef4444;color:#ef4444;"
                                                        data-request-id="<?php echo $request['id']; ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rejectModal"
                                                        title="Reject">
                                                    <i class="fas fa-times me-1"></i>Reject
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
document.addEventListener('DOMContentLoaded', function() {
    // Standard: server-side filters for status + leave_type, client-side search for name/post
    const statusSelect = document.getElementById('leaveStatusFilter');
    const typeSelect = document.getElementById('leaveTypeFilter');
    if (statusSelect) {
        statusSelect.addEventListener('change', () => {
            document.getElementById('leaveFilterForm')?.submit();
        });
    }
    if (typeSelect) {
        typeSelect.addEventListener('change', () => {
            document.getElementById('leaveFilterForm')?.submit();
        });
    }

    const searchInput = document.getElementById('leaveSearch');
    const tableBody = document.getElementById('leaveTableBody');
    const countEl = document.getElementById('leave-count');
    const rows = () => Array.from(tableBody?.querySelectorAll('tr.leave-row') || []);

    const updateCount = () => {
        if (!countEl) return;
        const visible = rows().filter(r => r.style.display !== 'none').length;
        countEl.textContent = visible.toLocaleString();
    };

    if (searchInput) {
        let t = null;
        searchInput.addEventListener('input', () => {
            window.clearTimeout(t);
            t = window.setTimeout(() => {
                const q = (searchInput.value || '').trim().toLowerCase();
                rows().forEach(row => {
                    const name = (row.querySelector('.employee-name')?.textContent || '').toLowerCase();
                    const post = (row.querySelector('.employee-role')?.textContent || '').toLowerCase();
                    row.style.display = (q === '' || name.includes(q) || post.includes(q)) ? '' : 'none';
                });
                updateCount();
            }, 150);
        });
    }
    updateCount();

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
