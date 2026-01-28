<?php
$page_title = 'Leave Requests - Golden Z-5 HR System';
$page = 'leaves';

// Get database connection
$pdo = get_db_connection();

// Handle leave request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $request_id = $_POST['request_id'] ?? '';
    
    if (($action === 'approve' || $action === 'reject') && $request_id) {
        try {
            // Determine which table to use
            $check_table = $pdo->query("SHOW TABLES LIKE 'leave_requests'");
            $table_name = ($check_table->rowCount() > 0) ? 'leave_requests' : 'time_off_requests';
            
            // Get current user ID
            $processed_by = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
            
            if ($action === 'approve') {
                $notes = $_POST['approval_notes'] ?? '';
                
                if ($table_name === 'leave_requests') {
                    $sql = "UPDATE leave_requests 
                            SET status = 'approved', 
                                processed_by = ?, 
                                processed_date = NOW(),
                                notes = ?
                            WHERE id = ?";
                } else {
                    // time_off_requests table
                    $sql = "UPDATE time_off_requests 
                            SET status = 'approved', 
                                approved_by = ?, 
                                approved_at = NOW()
                            WHERE id = ?";
                }
                
                $params = $table_name === 'leave_requests' 
                    ? [$processed_by, $notes, $request_id]
                    : [$processed_by, $request_id];
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                redirect_with_message('?page=leaves', 'Leave request approved successfully!', 'success');
                
            } elseif ($action === 'reject') {
                $notes = $_POST['rejection_notes'] ?? '';
                
                if (empty($notes)) {
                    redirect_with_message('?page=leaves', 'Rejection reason is required.', 'error');
                    exit;
                }
                
                if ($table_name === 'leave_requests') {
                    $sql = "UPDATE leave_requests 
                            SET status = 'rejected', 
                                processed_by = ?, 
                                processed_date = NOW(),
                                notes = ?
                            WHERE id = ?";
                } else {
                    // time_off_requests table
                    $sql = "UPDATE time_off_requests 
                            SET status = 'rejected', 
                                approved_by = ?, 
                                approved_at = NOW(),
                                rejection_reason = ?
                            WHERE id = ?";
                }
                
                $params = $table_name === 'leave_requests' 
                    ? [$processed_by, $notes, $request_id]
                    : [$processed_by, $notes, $request_id];
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                redirect_with_message('?page=leaves', 'Leave request rejected.', 'info');
            }
        } catch (Exception $e) {
            error_log('Error processing leave request: ' . $e->getMessage());
            redirect_with_message('?page=leaves', 'An error occurred while processing the request. Please try again.', 'error');
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'pending';
$employee_id = $_GET['employee_id'] ?? '';
$leave_type = $_GET['leave_type'] ?? '';

// Get all employees for filter dropdown
$employees = get_employees();

// Get leave requests from database
$table_name = 'leave_requests'; // Default table name
try {
    // Check if leave_requests table exists, otherwise try time_off_requests
    $check_table = $pdo->query("SHOW TABLES LIKE 'leave_requests'");
    if ($check_table->rowCount() == 0) {
        // Try time_off_requests as fallback
        $check_table = $pdo->query("SHOW TABLES LIKE 'time_off_requests'");
        if ($check_table->rowCount() > 0) {
            $table_name = 'time_off_requests';
        }
    }
    
    // Use get_leave_requests function if available and table exists, otherwise query directly
    if (function_exists('get_leave_requests') && $table_name === 'leave_requests') {
        $leave_requests_raw = get_leave_requests(
            $status_filter ?: null,
            $employee_id ?: null,
            $leave_type ?: null
        );
    } else {
        // Query directly from database (handles both leave_requests and time_off_requests)
        if ($table_name === 'time_off_requests') {
            // Map time_off_requests columns to leave_requests format
            $sql = "SELECT tor.id, tor.employee_id, tor.request_type as leave_type,
                           tor.start_date, tor.end_date, tor.total_days,
                           tor.reason, tor.status,
                           tor.approved_by as processed_by,
                           tor.approved_at as processed_date,
                           tor.rejection_reason as notes,
                           tor.created_at as request_date,
                           e.first_name, e.surname, e.middle_name, e.post,
                           CONCAT(e.surname, ', ', e.first_name, 
                                  IF(e.middle_name IS NOT NULL AND e.middle_name != '', CONCAT(' ', e.middle_name), '')) as employee_name,
                           u.name as processed_by_name
                    FROM time_off_requests tor
                    LEFT JOIN employees e ON tor.employee_id = e.id
                    LEFT JOIN users u ON tor.approved_by = u.id
                    WHERE 1=1";
        } else {
            $sql = "SELECT lr.*, 
                           e.first_name, e.surname, e.middle_name, e.post,
                           CONCAT(e.surname, ', ', e.first_name, 
                                  IF(e.middle_name IS NOT NULL AND e.middle_name != '', CONCAT(' ', e.middle_name), '')) as employee_name,
                           u.name as processed_by_name
                    FROM leave_requests lr
                    LEFT JOIN employees e ON lr.employee_id = e.id
                    LEFT JOIN users u ON lr.processed_by = u.id
                    WHERE 1=1";
        }
        
        $params = [];
        
        if ($status_filter) {
            $sql .= " AND " . ($table_name === 'time_off_requests' ? 'tor' : 'lr') . ".status = ?";
            $params[] = $status_filter;
        }
        
        if ($employee_id) {
            $sql .= " AND " . ($table_name === 'time_off_requests' ? 'tor' : 'lr') . ".employee_id = ?";
            $params[] = $employee_id;
        }
        
        if ($leave_type) {
            $column = $table_name === 'time_off_requests' ? 'request_type' : 'leave_type';
            $sql .= " AND " . ($table_name === 'time_off_requests' ? 'tor' : 'lr') . ".{$column} = ?";
            $params[] = $leave_type;
        }
        
        $order_column = $table_name === 'time_off_requests' ? 'created_at' : 'request_date';
        $sql .= " ORDER BY " . ($table_name === 'time_off_requests' ? 'tor' : 'lr') . ".{$order_column} DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $leave_requests_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Transform database results to match expected format
    $leave_requests = [];
    foreach ($leave_requests_raw as $req) {
        // Calculate days between start and end date
        $days = 0;
        if (!empty($req['start_date']) && !empty($req['end_date'])) {
            $start = new DateTime($req['start_date']);
            $end = new DateTime($req['end_date']);
            $days = $start->diff($end)->days + 1; // +1 to include both start and end dates
        } elseif (!empty($req['total_days'])) {
            $days = (int)$req['total_days'];
        }
        
        // Build employee name from database fields
        $employee_name = '';
        if (!empty($req['employee_name'])) {
            $employee_name = $req['employee_name'];
        } elseif (!empty($req['surname']) && !empty($req['first_name'])) {
            $employee_name = $req['surname'] . ', ' . $req['first_name'];
            if (!empty($req['middle_name'])) {
                $employee_name .= ' ' . $req['middle_name'];
            }
        }
        
        $leave_requests[] = [
            'id' => $req['id'] ?? null,
            'employee_id' => $req['employee_id'] ?? null,
            'employee_name' => $employee_name,
            'employee_post' => $req['post'] ?? '',
            'leave_type' => $req['leave_type'] ?? '',
            'start_date' => $req['start_date'] ?? '',
            'end_date' => $req['end_date'] ?? '',
            'days' => $days,
            'remaining_leave' => $req['remaining_leave'] ?? null,
            'reason' => $req['reason'] ?? '',
            'status' => $req['status'] ?? 'pending',
            'request_date' => $req['request_date'] ?? $req['created_at'] ?? date('Y-m-d H:i:s'),
            'processed_by' => $req['processed_by'] ?? null,
            'processed_by_name' => $req['processed_by_name'] ?? null,
            'processed_date' => $req['processed_date'] ?? $req['approved_at'] ?? null,
            'notes' => $req['notes'] ?? $req['rejection_reason'] ?? null,
        ];
    }
    
} catch (Exception $e) {
    error_log('Error fetching leave requests: ' . $e->getMessage());
    $leave_requests = [];
}

// Get unique leave types from database for filter dropdown
$leave_types = [];
try {
    // Check which table exists
    $check_table = $pdo->query("SHOW TABLES LIKE 'leave_requests'");
    if ($check_table->rowCount() > 0) {
        $stmt = $pdo->query("SELECT DISTINCT leave_type FROM leave_requests WHERE leave_type IS NOT NULL AND leave_type != '' ORDER BY leave_type");
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $leave_types = $types ?: [];
    } else {
        // Try time_off_requests
        $check_table = $pdo->query("SHOW TABLES LIKE 'time_off_requests'");
        if ($check_table->rowCount() > 0) {
            $stmt = $pdo->query("SELECT DISTINCT request_type FROM time_off_requests WHERE request_type IS NOT NULL AND request_type != '' ORDER BY request_type");
            $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
            // Map time_off_requests types to readable format
            $type_map = [
                'vacation' => 'Vacation Leave',
                'sick' => 'Sick Leave',
                'personal' => 'Personal Leave',
                'emergency' => 'Emergency Leave',
                'maternity' => 'Maternity Leave',
                'paternity' => 'Paternity Leave',
                'bereavement' => 'Bereavement Leave',
                'other' => 'Other'
            ];
            $leave_types = array_map(function($type) use ($type_map) {
                return $type_map[strtolower($type)] ?? ucfirst($type) . ' Leave';
            }, $types);
        }
    }
    
    // Fallback to default types if no types found
    if (empty($leave_types)) {
        $leave_types = ['Sick Leave', 'Vacation Leave', 'Emergency Leave', 'Maternity Leave', 'Paternity Leave'];
    }
} catch (Exception $e) {
    // Fallback to default types if query fails
    $leave_types = ['Sick Leave', 'Vacation Leave', 'Emergency Leave', 'Maternity Leave', 'Paternity Leave'];
}

// Filtered requests (already filtered by database query, but keep for consistency)
$filtered_requests = $leave_requests;

// Get statistics from database
$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'total' => 0,
];

try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM {$table_name} GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($status_counts as $row) {
        $status = strtolower($row['status']);
        if (isset($stats[$status])) {
            $stats[$status] = (int)$row['count'];
        }
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$table_name}");
    $total_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total'] = (int)($total_result['total'] ?? 0);
} catch (Exception $e) {
    error_log('Error fetching leave statistics: ' . $e->getMessage());
}

$days_pending = 0;
try {
    
    if ($table_name === 'time_off_requests') {
        $stmt = $pdo->query("SELECT SUM(total_days) as total_days 
                             FROM {$table_name} 
                             WHERE status = 'pending' 
                             AND total_days IS NOT NULL");
    } else {
        $stmt = $pdo->query("SELECT SUM(DATEDIFF(end_date, start_date) + 1) as total_days 
                             FROM {$table_name} 
                             WHERE status = 'pending' 
                             AND start_date IS NOT NULL 
                             AND end_date IS NOT NULL");
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $days_pending = (int)($result['total_days'] ?? 0);
} catch (Exception $e) {
    error_log('Error calculating pending days: ' . $e->getMessage());
}
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
                            <i class="fas fa-redo"></i>
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

    function initLeaveSearch() {
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
            // Remove any existing listeners by cloning
            const newInput = searchInput.cloneNode(true);
            searchInput.parentNode.replaceChild(newInput, searchInput);
            
            let searchTimeout;
            newInput.addEventListener('input', () => {
                window.clearTimeout(searchTimeout);
                searchTimeout = window.setTimeout(() => {
                    const q = (newInput.value || '').trim().toLowerCase();
                    rows().forEach(row => {
                        const name = (row.querySelector('.employee-name')?.textContent || '').toLowerCase();
                        const post = (row.querySelector('.employee-role')?.textContent || '').toLowerCase();
                        row.style.display = (q === '' || name.includes(q) || post.includes(q)) ? '' : 'none';
                    });
                    updateCount();
                }, 150);
            });
            
            // Trigger initial filter if there's a value
            if (newInput.value) {
                const q = (newInput.value || '').trim().toLowerCase();
                rows().forEach(row => {
                    const name = (row.querySelector('.employee-name')?.textContent || '').toLowerCase();
                    const post = (row.querySelector('.employee-role')?.textContent || '').toLowerCase();
                    row.style.display = (q === '' || name.includes(q) || post.includes(q)) ? '' : 'none';
                });
            }
        }
        updateCount();
    }
    
    initLeaveSearch();

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

// Re-initialize when page content is loaded via AJAX (outside DOMContentLoaded)
document.addEventListener('pageContentLoaded', function(e) {
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'timeoff' || page === 'leaves') {
        setTimeout(function() {
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
                let searchTimeout;
                searchInput.addEventListener('input', () => {
                    window.clearTimeout(searchTimeout);
                    searchTimeout = window.setTimeout(() => {
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
        }, 100);
    }
});

// Also listen for the old event name (backwards compatibility)
document.addEventListener('pageLoaded', function(e) {
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'timeoff' || page === 'leaves') {
        setTimeout(function() {
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
                let searchTimeout;
                searchInput.addEventListener('input', () => {
                    window.clearTimeout(searchTimeout);
                    searchTimeout = window.setTimeout(() => {
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
        }, 100);
    }
});
</script>
