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

// Export is handled in header.php before any HTML output
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
                <button type="button" class="btn btn-outline-modern" id="exportViolationsBtn" data-bs-toggle="modal" data-bs-target="#exportViolationsModal" aria-label="Export violations">
                    <i class="fas fa-file-export me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-body-modern">
            <?php if (isset($_SESSION['export_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['export_error']); unset($_SESSION['export_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
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

<!-- Export Violations Modal -->
<div class="modal fade" id="exportViolationsModal" tabindex="-1" aria-labelledby="exportViolationsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportViolationsModalLabel">Export Violations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="exportViolationsForm" aria-label="Export violations form">
                <div class="modal-body">
                    <!-- Export Scope -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-3">Export Scope</label>
                        <div class="export-scope-options">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="export_scope" id="export_filtered" value="filtered" checked>
                                <label class="form-check-label" for="export_filtered">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span>
                                            <strong>Export Filtered Results</strong>
                                            <small class="d-block text-muted">Export violations matching current filters</small>
                                        </span>
                                        <span class="badge bg-primary" id="filtered-count"><?php echo number_format(count($violations)); ?></span>
                                    </div>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="export_scope" id="export_all" value="all">
                                <label class="form-check-label" for="export_all">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span>
                                            <strong>Export All Violations</strong>
                                            <small class="d-block text-muted">Export all violation records regardless of filters</small>
                                        </span>
                                        <span class="badge bg-secondary" id="all-count"><?php 
                                            try {
                                                $stmt = $pdo->query("SELECT COUNT(*) as total FROM employee_violations");
                                                $total = $stmt->fetch(PDO::FETCH_ASSOC);
                                                echo number_format($total['total'] ?? 0);
                                            } catch (Exception $e) {
                                                echo number_format(count($violations));
                                            }
                                        ?></span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Export Format -->
                    <div class="mb-4">
                        <label for="export_format" class="form-label fw-semibold mb-2">Export Format</label>
                        <select name="export_format" id="export_format" class="form-select">
                            <option value="csv">CSV (Comma Separated Values)</option>
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="pdf">PDF Document</option>
                        </select>
                        <small class="form-text text-muted">Choose the file format for export</small>
                    </div>

                    <!-- Column Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-2">Include Columns</label>
                        <div class="export-columns" role="group" aria-label="Select export columns">
                            <div class="export-columns-header">
                                <div class="export-columns-hint">Tip: choose the fields you want in the file.</div>
                                <div class="export-columns-actions" aria-label="Column selection actions">
                                    <button type="button" class="btn btn-sm btn-link p-0" id="selectAllColumns">Select all</button>
                                    <span class="export-columns-divider" aria-hidden="true">|</span>
                                    <button type="button" class="btn btn-sm btn-link p-0" id="deselectAllColumns">Deselect all</button>
                                </div>
                            </div>
                            <div class="row g-2 export-columns-grid">
                                <div class="col-md-6">
                                    <div class="form-check export-column-item">
                                        <input class="form-check-input" type="checkbox" name="export_columns[]" id="col_date" value="date" checked>
                                        <label class="form-check-label" for="col_date">Violation Date</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check export-column-item">
                                        <input class="form-check-input" type="checkbox" name="export_columns[]" id="col_employee" value="employee" checked>
                                        <label class="form-check-label" for="col_employee">Employee Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check export-column-item">
                                        <input class="form-check-input" type="checkbox" name="export_columns[]" id="col_employee_no" value="employee_no">
                                        <label class="form-check-label" for="col_employee_no">Employee Number</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check export-column-item">
                                        <input class="form-check-input" type="checkbox" name="export_columns[]" id="col_post" value="post">
                                        <label class="form-check-label" for="col_post">Post/Position</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check export-column-item">
                                        <input class="form-check-input" type="checkbox" name="export_columns[]" id="col_violation_type" value="violation_type" checked>
                                        <label class="form-check-label" for="col_violation_type">Violation Type</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check export-column-item">
                                        <input class="form-check-input" type="checkbox" name="export_columns[]" id="col_severity" value="severity" checked>
                                        <label class="form-check-label" for="col_severity">Severity</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check export-column-item">
                                        <input class="form-check-input" type="checkbox" name="export_columns[]" id="col_description" value="description">
                                        <label class="form-check-label" for="col_description">Description</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check export-column-item">
                                        <input class="form-check-input" type="checkbox" name="export_columns[]" id="col_sanction" value="sanction" checked>
                                        <label class="form-check-label" for="col_sanction">Sanction</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check export-column-item">
                                        <input class="form-check-input" type="checkbox" name="export_columns[]" id="col_sanction_date" value="sanction_date">
                                        <label class="form-check-label" for="col_sanction_date">Sanction Date</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check export-column-item">
                                        <input class="form-check-input" type="checkbox" name="export_columns[]" id="col_reported_by" value="reported_by">
                                        <label class="form-check-label" for="col_reported_by">Reported By</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check export-column-item">
                                        <input class="form-check-input" type="checkbox" name="export_columns[]" id="col_status" value="status" checked>
                                        <label class="form-check-label" for="col_status">Status</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Preview -->
                    <div class="alert alert-info">
                        <div class="d-flex align-items-start">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-2 flex-shrink-0 mt-1">
                                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M10 6v4M10 14h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <div>
                                <strong>Export Preview:</strong>
                                <div id="export-preview" class="mt-1">
                                    <span id="export-count"><?php echo number_format(count($violations)); ?></span> violation(s) will be exported in <span id="export-format-preview">CSV</span> format
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="confirmExportBtn">
                        <i class="fas fa-file-export me-2"></i>Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Export Modal Styles - Design System Compliant */
:root {
    --spacing-xs: 4px;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --radius-md: 8px;
    --primary-color: #1fb2d5;
    --transition-base: 150ms cubic-bezier(0.4, 0, 0.2, 1);
}

.export-scope-options .form-check {
    padding: var(--spacing-md);
    border: 2px solid #e2e8f0;
    border-radius: var(--radius-md);
    transition: border-color var(--transition-base), background-color var(--transition-base), box-shadow var(--transition-base);
    cursor: pointer;
    margin-bottom: var(--spacing-sm);
    background-color: #ffffff;
}

.export-scope-options .form-check:last-child {
    margin-bottom: 0;
}

.export-scope-options .form-check:hover {
    border-color: var(--primary-color);
    background-color: rgba(31, 178, 213, 0.02);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.export-scope-options .form-check-input:checked ~ .form-check-label {
    color: var(--primary-color);
}

.export-scope-options .form-check-label {
    width: 100%;
    cursor: pointer;
    user-select: none;
}

.export-scope-options .form-check-label strong {
    display: block;
    margin-bottom: var(--spacing-xs);
    font-weight: 600;
}

.export-scope-options .form-check-label small {
    display: block;
    color: #64748b;
    font-size: 0.8125rem;
}

.export-scope-options .badge {
    font-size: 0.875rem;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: 6px;
}

.export-columns {
    border: 1px solid #e2e8f0;
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    background-color: #f8fafc;
    max-height: 300px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f8fafc;
}

.export-columns-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-md);
    padding-bottom: var(--spacing-sm);
    margin-bottom: var(--spacing-sm);
    border-bottom: 1px solid #e2e8f0;
}

.export-columns-hint {
    font-size: 0.8125rem;
    color: #64748b;
}

.export-columns-actions {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    white-space: nowrap;
}

.export-columns-divider {
    color: #cbd5e1;
}

.export-columns-grid {
    margin-top: 0;
}

.export-columns::-webkit-scrollbar {
    width: 8px;
}

.export-columns::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 4px;
}

.export-columns::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.export-columns::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.export-columns .export-column-item {
    padding: 10px 12px;
    margin-bottom: 0;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #ffffff;
    transition: border-color var(--transition-base), background-color var(--transition-base), box-shadow var(--transition-base);
}

.export-columns .export-column-item:hover {
    border-color: rgba(31, 178, 213, 0.55);
    background-color: rgba(31, 178, 213, 0.03);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.export-columns .export-column-item:has(.form-check-input:focus-visible) {
    border-color: rgba(31, 178, 213, 0.9);
    box-shadow: 0 0 0 4px rgba(31, 178, 213, 0.18);
}

.export-columns .export-column-item .form-check-input {
    margin-top: 0.2rem;
}

.export-columns .form-check-label {
    font-size: 0.875rem;
    cursor: pointer;
    user-select: none;
    transition: color var(--transition-base);
}

.export-columns .form-check-input:checked ~ .form-check-label {
    color: var(--primary-color);
    font-weight: 500;
}

#selectAllColumns,
#deselectAllColumns {
    font-size: 0.8125rem;
    color: var(--primary-color);
    text-decoration: none;
    transition: color var(--transition-base);
}

#selectAllColumns:hover,
#deselectAllColumns:hover {
    color: var(--primary-dark, #0e708c);
    text-decoration: underline;
}

#export-preview {
    font-size: 0.875rem;
    line-height: 1.5;
}

#export-preview strong {
    font-weight: 600;
    color: #334155;
}

#export-count {
    font-weight: 700;
    color: var(--primary-color);
}

#export-format-preview {
    font-weight: 600;
    color: #334155;
}

.modal-body .alert-info {
    background-color: rgba(31, 178, 213, 0.1);
    border-color: rgba(31, 178, 213, 0.2);
    color: #0e708c;
}

.modal-body .alert-info svg {
    color: var(--primary-color);
}

@media (prefers-reduced-motion: reduce) {
    .export-scope-options .form-check,
    .export-columns .form-check-label,
    #selectAllColumns,
    #deselectAllColumns {
        transition: none;
    }
}
</style>

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

    // Export violations with filter options
    const exportForm = document.getElementById('exportViolationsForm');
    const exportModal = document.getElementById('exportViolationsModal');
    
    if (exportForm) {
        // Update preview when options change
        const updatePreview = () => {
            const scope = document.querySelector('input[name="export_scope"]:checked')?.value || 'filtered';
            const format = document.getElementById('export_format')?.value || 'csv';
            const count = scope === 'all' 
                ? document.getElementById('all-count')?.textContent || '0'
                : document.getElementById('filtered-count')?.textContent || '0';
            
            const formatNames = {
                'csv': 'CSV',
                'excel': 'Excel',
                'pdf': 'PDF'
            };
            
            const previewEl = document.getElementById('export-preview');
            const countEl = document.getElementById('export-count');
            const formatEl = document.getElementById('export-format-preview');
            
            if (previewEl && countEl && formatEl) {
                countEl.textContent = parseInt(count.replace(/,/g, '')).toLocaleString();
                formatEl.textContent = formatNames[format] || format.toUpperCase();
            }
        };
        
        // Column selection helpers
        const selectAllBtn = document.getElementById('selectAllColumns');
        const deselectAllBtn = document.getElementById('deselectAllColumns');
        
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('input[name="export_columns[]"]').forEach(cb => {
                    cb.checked = true;
                });
            });
        }
        
        if (deselectAllBtn) {
            deselectAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('input[name="export_columns[]"]').forEach(cb => {
                    cb.checked = false;
                });
            });
        }
        
        // Update preview on change
        document.querySelectorAll('input[name="export_scope"], #export_format').forEach(el => {
            el.addEventListener('change', updatePreview);
        });
        
        // Handle form submission
        exportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const scope = document.querySelector('input[name="export_scope"]:checked')?.value || 'filtered';
            const format = document.getElementById('export_format')?.value || 'csv';
            const selectedColumns = Array.from(document.querySelectorAll('input[name="export_columns[]"]:checked')).map(cb => cb.value);
            
            if (selectedColumns.length === 0) {
                alert('Please select at least one column to export.');
                return;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('confirmExportBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exporting&hellip;';
            
            // Prepare export data
            const exportData = {
                scope: scope,
                format: format,
                columns: selectedColumns,
                filters: {
                    employee_id: '<?php echo htmlspecialchars($employee_id ?? ''); ?>',
                    severity: '<?php echo htmlspecialchars($severity ?? ''); ?>',
                    violation_type: '<?php echo htmlspecialchars($violation_type_id ?? ''); ?>'
                }
            };
            
            // Create form to submit export request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?page=violations&action=export';
            form.style.display = 'none';
            
            Object.keys(exportData).forEach(key => {
                if (key === 'columns') {
                    exportData[key].forEach(col => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `export_columns[]`;
                        input.value = col;
                        form.appendChild(input);
                    });
                } else if (key === 'filters') {
                    Object.keys(exportData[key]).forEach(filterKey => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `export_filters[${filterKey}]`;
                        input.value = exportData[key][filterKey];
                        form.appendChild(input);
                    });
                } else {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `export_${key}`;
                    input.value = exportData[key];
                    form.appendChild(input);
                }
            });
            
            document.body.appendChild(form);
            
            // Close modal before submitting (for better UX)
            const modalElement = document.getElementById('exportViolationsModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
            
            // Small delay to allow modal to close, then submit
            setTimeout(() => {
                form.submit();
            }, 300);
            
            // Reset button after delay
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 2000);
        });
    }
});
</script>
