<?php
$page_title = 'Violations - Golden Z-5 HR System';
$page = 'violations';

// Get database connection
$pdo = get_db_connection();

// Handle violation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_violation') {
        // Handle adding violation (to be implemented with database)
        redirect_with_message('?page=violations', 'Violation record added successfully!', 'success');
    } elseif ($action === 'update_violation') {
        // Handle updating violation (to be implemented with database)
        redirect_with_message('?page=violations', 'Violation record updated successfully!', 'success');
    } elseif ($action === 'delete_violation' && isset($_POST['violation_id'])) {
        // Handle deletion (to be implemented with database)
        redirect_with_message('?page=violations', 'Violation record deleted successfully!', 'success');
    }
}

// Get filter parameters
$employee_id = $_GET['employee_id'] ?? '';
$severity = $_GET['severity'] ?? '';
$violation_type = $_GET['violation_type'] ?? '';

// Get all employees for filter dropdown
$employees = get_employees();

// Mock violation types
$violation_types_list = [
    'AWOL' => 'Major',
    'Tardiness' => 'Minor',
    'Insubordination' => 'Major',
    'Dress Code Violation' => 'Minor',
    'Safety Violation' => 'Major',
    'Unauthorized Leave' => 'Minor',
    'Theft' => 'Major',
    'Harassment' => 'Major',
];

// Mock violation data
$violations = [];
for ($i = 1; $i <= 25; $i++) {
    $type = array_rand($violation_types_list);
    $severity_val = $violation_types_list[$type];
    
    $violations[] = [
        'id' => $i,
        'employee_id' => rand(1, 50),
        'employee_name' => 'Employee ' . $i,
        'employee_post' => 'Post ' . rand(1, 10),
        'violation_type' => $type,
        'severity' => $severity_val,
        'description' => 'Description of the violation and circumstances.',
        'violation_date' => date('Y-m-d', strtotime('-' . rand(1, 365) . ' days')),
        'reported_by' => 'Supervisor ' . rand(1, 5),
        'sanction' => $severity_val === 'Major' ? ['Suspension', 'Final Warning', 'Termination'][rand(0, 2)] : ['Verbal Warning', 'Written Warning', '1-day Suspension'][rand(0, 2)],
        'sanction_date' => date('Y-m-d', strtotime('-' . rand(1, 365) . ' days')),
        'status' => ['Pending', 'Resolved', 'Under Review'][rand(0, 2)],
    ];
}

// Get statistics
$stats = [
    'total' => count($violations),
    'major' => count(array_filter($violations, fn($v) => $v['severity'] === 'Major')),
    'minor' => count(array_filter($violations, fn($v) => $v['severity'] === 'Minor')),
    'pending' => count(array_filter($violations, fn($v) => $v['status'] === 'Pending')),
    'resolved' => count(array_filter($violations, fn($v) => $v['status'] === 'Resolved')),
];
?>

<div class="container-fluid hrdash">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Total Violations</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($stats['total']); ?></div>
                </div>
                <div class="hrdash-stat__meta">All time violations</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Major Violations</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-danger"><?php echo number_format($stats['major']); ?></div>
                </div>
                <div class="hrdash-stat__meta">Serious offenses</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Minor Violations</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-warning"><?php echo number_format($stats['minor']); ?></div>
                </div>
                <div class="hrdash-stat__meta">Minor offenses</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Pending</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($stats['pending']); ?></div>
                </div>
                <div class="hrdash-stat__meta">Under review</div>
            </div>
        </div>
    </div>

    <!-- Violations Table -->
    <div class="card card-modern">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title-modern">Violation Records</h5>
                <div class="card-subtitle">Employee violations and sanctions</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary-modern" data-bs-toggle="modal" data-bs-target="#addViolationModal">
                    <i class="fas fa-plus me-2"></i>Add Violation
                </button>
                <button type="button" class="btn btn-outline-modern" id="exportViolationsBtn">
                    <i class="fas fa-file-export me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-body-modern">
            <form method="GET" action="" class="mb-4">
                <input type="hidden" name="page" value="violations">
                <div class="row g-3 align-items-end">
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
                        <label class="form-label">Severity</label>
                        <select name="severity" class="form-select">
                            <option value="">All</option>
                            <option value="Major" <?php echo $severity === 'Major' ? 'selected' : ''; ?>>Major</option>
                            <option value="Minor" <?php echo $severity === 'Minor' ? 'selected' : ''; ?>>Minor</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Violation Type</label>
                        <select name="violation_type" class="form-select">
                            <option value="">All Types</option>
                            <?php foreach (array_keys($violation_types_list) as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $violation_type === $type ? 'selected' : ''; ?>>
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
                        <button type="button" class="btn btn-outline-modern" onclick="window.location.href='?page=violations'" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Violations Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Violation Type</th>
                            <th>Severity</th>
                            <th>Sanction</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($violations)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No violation records found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($violations as $violation): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($violation['violation_date'])); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($violation['employee_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($violation['employee_post']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($violation['violation_type']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $violation['severity'] === 'Major' ? 'danger' : 'warning'; ?>">
                                            <?php echo htmlspecialchars($violation['severity']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($violation['sanction']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = 'bg-secondary';
                                        if ($violation['status'] === 'Resolved') $status_class = 'bg-success';
                                        elseif ($violation['status'] === 'Pending') $status_class = 'bg-warning';
                                        elseif ($violation['status'] === 'Under Review') $status_class = 'bg-info';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($violation['status']); ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary view-violation-btn" 
                                                    data-violation='<?php echo json_encode($violation); ?>'
                                                    data-bs-toggle="modal" data-bs-target="#viewViolationModal"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success edit-violation-btn" 
                                                    data-violation='<?php echo json_encode($violation); ?>'
                                                    data-bs-toggle="modal" data-bs-target="#editViolationModal"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger delete-violation-btn" 
                                                    data-violation-id="<?php echo $violation['id']; ?>"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

<!-- Add Violation Modal -->
<div class="modal fade" id="addViolationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Violation Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_violation">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo htmlspecialchars(($emp['surname'] ?? '') . ', ' . ($emp['first_name'] ?? '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Violation Date <span class="text-danger">*</span></label>
                            <input type="date" name="violation_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Violation Type <span class="text-danger">*</span></label>
                            <select name="violation_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <?php foreach (array_keys($violation_types_list) as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>">
                                        <?php echo htmlspecialchars($type); ?> (<?php echo $violation_types_list[$type]; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Severity <span class="text-danger">*</span></label>
                            <select name="severity" class="form-select" required>
                                <option value="">Select Severity</option>
                                <option value="Major">Major</option>
                                <option value="Minor">Minor</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Detailed description of the violation..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sanction <span class="text-danger">*</span></label>
                            <select name="sanction" class="form-select" required>
                                <option value="">Select Sanction</option>
                                <option value="Verbal Warning">Verbal Warning</option>
                                <option value="Written Warning">Written Warning</option>
                                <option value="1-day Suspension">1-day Suspension</option>
                                <option value="3-day Suspension">3-day Suspension</option>
                                <option value="Final Warning">Final Warning</option>
                                <option value="Termination">Termination</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reported By <span class="text-danger">*</span></label>
                            <input type="text" name="reported_by" class="form-control" placeholder="Supervisor name" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Violation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Violation Modal -->
<div class="modal fade" id="viewViolationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Violation Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="violationDetailsContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Violation Modal (similar to Add, but with pre-filled data) -->
<div class="modal fade" id="editViolationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Violation Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_violation">
                    <input type="hidden" name="violation_id" id="edit_violation_id">
                    <!-- Similar fields as Add modal -->
                    <p class="text-muted">Edit functionality to be implemented...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Violation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View violation details
    document.querySelectorAll('.view-violation-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const violation = JSON.parse(this.dataset.violation);
            const content = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Employee:</strong><br>
                        ${violation.employee_name}<br>
                        <small class="text-muted">${violation.employee_post}</small>
                    </div>
                    <div class="col-md-6">
                        <strong>Violation Date:</strong><br>
                        ${new Date(violation.violation_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                    </div>
                    <div class="col-md-6">
                        <strong>Violation Type:</strong><br>
                        ${violation.violation_type}
                    </div>
                    <div class="col-md-6">
                        <strong>Severity:</strong><br>
                        <span class="badge bg-${violation.severity === 'Major' ? 'danger' : 'warning'}">${violation.severity}</span>
                    </div>
                    <div class="col-12">
                        <strong>Description:</strong><br>
                        <p class="mb-0">${violation.description}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Sanction:</strong><br>
                        ${violation.sanction}
                    </div>
                    <div class="col-md-6">
                        <strong>Sanction Date:</strong><br>
                        ${new Date(violation.sanction_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                    </div>
                    <div class="col-md-6">
                        <strong>Reported By:</strong><br>
                        ${violation.reported_by}
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-${violation.status === 'Resolved' ? 'success' : violation.status === 'Pending' ? 'warning' : 'info'}">${violation.status}</span>
                    </div>
                </div>
            `;
            document.getElementById('violationDetailsContent').innerHTML = content;
        });
    });

    // Delete violation
    document.querySelectorAll('.delete-violation-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this violation record?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_violation">
                    <input type="hidden" name="violation_id" value="${this.dataset.violationId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>
