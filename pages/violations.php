<?php
$page_title = 'Employee Violation - Golden Z-5 HR System';
$page = 'violations';

// Get database connection
$pdo = get_db_connection();

// Handle violation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'delete_violation' && isset($_POST['violation_id'])) {
        // Handle deletion
        try {
            $stmt = $pdo->prepare("DELETE FROM employee_violations WHERE id = ?");
            $stmt->execute([$_POST['violation_id']]);
            redirect_with_message('?page=violations', 'Violation record deleted successfully!', 'success');
        } catch (PDOException $e) {
            error_log("Error deleting violation: " . $e->getMessage());
            redirect_with_message('?page=violations', 'Error deleting violation: ' . $e->getMessage(), 'error');
        }
    }
}

// Get filter parameters
$employee_id = $_GET['employee_id'] ?? '';
$severity = $_GET['severity'] ?? '';
$violation_type_id = $_GET['violation_type'] ?? '';

// Get all employees for filter dropdown
$employees = get_employees();

// Get employee violations from database (or use mock data if table doesn't exist)
$violations = [];
try {
    if (function_exists('get_employee_violations')) {
        $violations = get_employee_violations($employee_id ?: null, $severity ?: null, $violation_type_id ?: null);
    } else {
        // Fallback: try direct query
        $sql = "SELECT ev.*, 
                       e.first_name, e.surname, e.post,
                       e.employee_no, e.email, e.cp_number, e.department, e.status as employee_status,
                       CONCAT(e.surname, ', ', e.first_name) as employee_name,
                       vt.name as violation_type_name,
                       vt.category as violation_category
                FROM employee_violations ev
                LEFT JOIN employees e ON ev.employee_id = e.id
                LEFT JOIN violation_types vt ON ev.violation_type_id = vt.id
                WHERE 1=1";
        $params = [];
        if ($employee_id) {
            $sql .= " AND ev.employee_id = ?";
            $params[] = $employee_id;
        }
        if ($severity) {
            $sql .= " AND ev.severity = ?";
            $params[] = $severity;
        }
        if ($violation_type_id) {
            $sql .= " AND ev.violation_type_id = ?";
            $params[] = $violation_type_id;
        }
        $sql .= " ORDER BY ev.violation_date DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Error fetching violations: " . $e->getMessage());
    // Fallback to mock data if table doesn't exist
    $violations = [];
}

// Get violation types from database
$violation_types_list = [];
try {
    $stmt = $pdo->query("SELECT id, name, category, reference_no FROM violation_types WHERE is_active = 1 ORDER BY category, reference_no");
    $violation_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($violation_types as $vt) {
        $violation_types_list[$vt['id']] = [
            'name' => $vt['name'],
            'category' => $vt['category'],
            'reference_no' => $vt['reference_no']
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching violation types: " . $e->getMessage());
}

// If no violations found and table doesn't exist, show empty state

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
                <h5 class="card-title-modern">Employee Violation</h5>
                <div class="card-subtitle">Employee violations and sanctions</div>
            </div>
            <div class="d-flex gap-2">
                <a href="?page=add_violation" class="btn btn-primary-modern">
                    <i class="fas fa-plus me-2"></i>Add Violation
                </a>
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
                            <?php 
                            $current_category = '';
                            foreach ($violation_types_list as $vt_id => $vt): 
                                if ($current_category !== $vt['category']):
                                    if ($current_category !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($vt['category']) . ' Violations">';
                                    $current_category = $vt['category'];
                                endif;
                            ?>
                                <option value="<?php echo $vt_id; ?>" <?php echo $violation_type_id == $vt_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vt['reference_no'] ?? ''); ?> - <?php echo htmlspecialchars($vt['name']); ?>
                                </option>
                            <?php 
                            endforeach; 
                            if ($current_category !== '') echo '</optgroup>';
                            ?>
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
                                    <td><?php echo $violation['violation_date'] ? date('M d, Y', strtotime($violation['violation_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($violation['employee_name'] ?? 'Unknown Employee'); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($violation['post'] ?? $violation['employee_post'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        $vt_name = $violation['violation_type_name'] ?? 'Unknown';
                                        $vt_ref = '';
                                        if (isset($violation['violation_type_id']) && isset($violation_types_list[$violation['violation_type_id']])) {
                                            $vt_ref = $violation_types_list[$violation['violation_type_id']]['reference_no'] ?? '';
                                            $vt_name = $violation_types_list[$violation['violation_type_id']]['name'] ?? $vt_name;
                                        }
                                        if ($vt_ref) {
                                            echo '<span class="text-muted small">' . htmlspecialchars($vt_ref) . '</span><br>';
                                        }
                                        echo htmlspecialchars($vt_name); 
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $violation['severity'] === 'Major' ? 'danger' : 'warning'; ?>">
                                            <?php echo htmlspecialchars($violation['severity']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($violation['sanction'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $status = $violation['status'] ?? 'Pending';
                                        $status_class = 'bg-secondary';
                                        if ($status === 'Resolved') $status_class = 'bg-success';
                                        elseif ($status === 'Pending') $status_class = 'bg-warning';
                                        elseif ($status === 'Under Review') $status_class = 'bg-info';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary view-violation-btn" 
                                                    data-violation='<?php echo json_encode($violation, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                                                    data-bs-toggle="modal" data-bs-target="#viewViolationModal"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="?page=edit_violation&id=<?php echo $violation['id']; ?>" 
                                               class="btn btn-outline-success" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View violation details
    document.querySelectorAll('.view-violation-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const violation = JSON.parse(this.dataset.violation);
            const violationDate = violation.violation_date ? new Date(violation.violation_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : 'N/A';
            const sanctionDate = violation.sanction_date ? new Date(violation.sanction_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : 'N/A';
            const employeeNo = violation.employee_no || violation.employee_id || 'N/A';
            const employeeEmail = violation.email || 'N/A';
            const employeePhone = violation.cp_number || 'N/A';
            const employeeDepartment = violation.department || 'N/A';
            const employeePost = violation.post || violation.employee_post || 'N/A';
            const employeeStatus = violation.employee_status || 'N/A';
            const content = `
                <div class="row g-3">
                    <div class="col-12">
                        <div class="p-3 border rounded-3 bg-light">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <div class="text-muted small">Employee</div>
                                    <div class="fw-semibold">${violation.employee_name || 'Unknown Employee'}</div>
                                    <div class="text-muted small">${employeePost}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small">Employee #</div>
                                    <div class="fw-semibold">${employeeNo}</div>
                                </div>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-md-4">
                                    <div class="text-muted small">Department</div>
                                    <div class="fw-semibold">${employeeDepartment}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">Email</div>
                                    <div class="fw-semibold">${employeeEmail}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">Contact</div>
                                    <div class="fw-semibold">${employeePhone}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">Employee Status</div>
                                    <div class="fw-semibold">${employeeStatus}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <strong>Employee:</strong><br>
                        ${violation.employee_name || 'Unknown Employee'}<br>
                        <small class="text-muted">${employeePost}</small>
                    </div>
                    <div class="col-md-6">
                        <strong>Violation Date:</strong><br>
                        ${violationDate}
                    </div>
                    <div class="col-md-6">
                        <strong>Violation Type:</strong><br>
                        ${violation.violation_type_name || 'Unknown'}
                    </div>
                    <div class="col-md-6">
                        <strong>Severity:</strong><br>
                        <span class="badge bg-${violation.severity === 'Major' ? 'danger' : 'warning'}">${violation.severity || 'N/A'}</span>
                    </div>
                    <div class="col-12">
                        <strong>Description:</strong><br>
                        <p class="mb-0">${violation.description || 'No description provided'}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Sanction:</strong><br>
                        ${violation.sanction || 'N/A'}
                    </div>
                    <div class="col-md-6">
                        <strong>Sanction Date:</strong><br>
                        ${sanctionDate}
                    </div>
                    <div class="col-md-6">
                        <strong>Reported By:</strong><br>
                        ${violation.reported_by || 'N/A'}
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-${violation.status === 'Resolved' ? 'success' : violation.status === 'Pending' ? 'warning' : 'info'}">${violation.status || 'Pending'}</span>
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

    // Export violations to CSV
    const exportBtn = document.getElementById('exportViolationsBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            const table = document.querySelector('.table.table-hover.align-middle');
            if (!table) return;

            const rows = Array.from(table.querySelectorAll('tbody tr'));
            let csv = 'Date,Employee,Violation Type,Severity,Sanction,Status\n';

            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length < 6) return;

                const date = cells[0].textContent.trim();

                // Employee name (first line / bold text)
                let employee = cells[1].querySelector('.fw-semibold');
                const employeeName = (employee ? employee.textContent : cells[1].textContent).trim();

                const violationType = cells[2].textContent.trim().replace(/\s+/g, ' ');
                const severity = cells[3].textContent.trim();
                const sanction = cells[4].textContent.trim();
                const status = cells[5].textContent.trim();

                const values = [date, employeeName, violationType, severity, sanction, status]
                    .map(value => `"${(value || '').replace(/"/g, '""')}"`)
                    .join(',');

                csv += values + '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'violations_export.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        });
    }
});
</script>
