<?php
$page_title = 'Violation History - Golden Z-5 HR System';
$page = 'violation_history';

// Get database connection
$pdo = get_db_connection();

// Get violation ID from URL (optional - if provided, show only that violation's history)
$violation_id = $_GET['violation_id'] ?? null;

// Generate mock violation history data
function generateMockViolationHistory($violation_id = null) {
    $mockData = [];
    $actions = ['INSERT', 'UPDATE', 'UPDATE', 'UPDATE', 'DELETE'];
    $users = [
        ['username' => 'admin', 'first_name' => 'John', 'last_name' => 'Doe'],
        ['username' => 'hr_manager', 'first_name' => 'Jane', 'last_name' => 'Smith'],
        ['username' => 'supervisor', 'first_name' => 'Mike', 'last_name' => 'Johnson'],
    ];
    $violations = [
        ['name' => 'Tardiness', 'reference_no' => 'VT-001', 'description' => 'Employee arriving late to work or assigned post without valid reason'],
        ['name' => 'Absence Without Leave', 'reference_no' => 'VT-002', 'description' => 'Employee fails to report for duty without approved leave or valid excuse'],
        ['name' => 'Insubordination', 'reference_no' => 'VT-003', 'description' => 'Refusal to obey lawful orders or disrespectful behavior towards superiors'],
        ['name' => 'Violation of Company Policy', 'reference_no' => 'VT-004', 'description' => 'Any act that violates established company rules and regulations'],
    ];
    
    $baseDate = new DateTime();
    $baseDate->modify('-30 days');
    
    for ($i = 0; $i < 8; $i++) {
        $user = $users[$i % count($users)];
        $violation = $violations[$i % count($violations)];
        $action = $actions[$i % count($actions)];
        
        // Skip if viewing specific violation and this doesn't match
        if ($violation_id && $i % 2 !== 0) {
            continue;
        }
        
        $date = clone $baseDate;
        $date->modify('+' . ($i * 3) . ' days');
        $date->modify('+' . ($i * 2) . ' hours');
        
        $oldValues = null;
        $newValues = null;
        
        // Determine category based on reference number pattern
        $category = 'Minor';
        $subcategory = null;
        if (strpos($violation['reference_no'], 'MIN-') === false) {
            if (strpos($violation['reference_no'], 'A.') !== false || 
                strpos($violation['reference_no'], 'B.') !== false ||
                strpos($violation['reference_no'], 'C.') !== false ||
                strpos($violation['reference_no'], 'D.') !== false) {
                $category = 'Major';
                $subcategory = substr($violation['reference_no'], 0, 1);
            } else {
                $category = 'Major';
            }
        }
        
        if ($action === 'INSERT') {
            $newValues = json_encode([
                'reference_no' => $violation['reference_no'],
                'description' => $violation['description'],
                'category' => $category,
                'subcategory' => $subcategory,
                'first_offense' => 'Verbal Warning',
                'second_offense' => 'Written Warning',
                'third_offense' => 'Suspension',
                'fourth_offense' => 'Termination',
                'fifth_offense' => null
            ]);
        } elseif ($action === 'UPDATE') {
            $oldValues = json_encode([
                'reference_no' => $violation['reference_no'],
                'description' => $violation['description'],
                'category' => $category,
                'first_offense' => 'Verbal Warning',
                'second_offense' => 'Written Warning',
            ]);
            $newValues = json_encode([
                'reference_no' => $violation['reference_no'],
                'description' => $violation['description'],
                'category' => $category,
                'first_offense' => 'Written Warning',
                'second_offense' => 'Suspension',
            ]);
        } else {
            $oldValues = json_encode([
                'reference_no' => $violation['reference_no'],
                'description' => $violation['description'],
                'category' => $category,
                'first_offense' => 'Verbal Warning',
            ]);
        }
        
        $mockData[] = [
            'id' => $i + 1,
            'user_id' => $i + 1,
            'action' => $action,
            'table_name' => 'violation_types',
            'record_id' => $i + 1,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => $date->format('Y-m-d H:i:s'),
            'username' => $user['username'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'reference_no' => $violation['reference_no'],
            'violation_name' => $violation['name'],
            'violation_description' => $violation['description'],
            'category' => $category,
            'subcategory' => $subcategory,
            'first_offense' => 'Verbal Warning',
            'second_offense' => 'Written Warning',
            'third_offense' => 'Suspension',
            'fourth_offense' => 'Termination',
            'fifth_offense' => null
        ];
    }
    
    return $mockData;
}

// Fetch violation edit history from audit logs
$violation_history = [];
$use_mock_data = false;

try {
    $sql = "
        SELECT 
            al.id,
            al.user_id,
            al.action,
            al.table_name,
            al.record_id,
            al.old_values,
            al.new_values,
            al.created_at,
            u.username,
            u.first_name,
            u.last_name,
            vt.reference_no,
            vt.name as violation_name,
            vt.description as violation_description,
            vt.category,
            vt.subcategory,
            vt.first_offense,
            vt.second_offense,
            vt.third_offense,
            vt.fourth_offense,
            vt.fifth_offense
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN violation_types vt ON al.record_id = vt.id
        WHERE al.table_name = 'violation_types'
        AND al.action IN ('UPDATE', 'INSERT', 'DELETE')
    ";
    
    $params = [];
    if ($violation_id) {
        $sql .= " AND al.record_id = ?";
        $params[] = $violation_id;
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT 500";
    
    $history_stmt = $pdo->prepare($sql);
    $history_stmt->execute($params);
    $violation_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Use mock data if no real data exists (for UI preview)
    if (empty($violation_history)) {
        $use_mock_data = true;
        $violation_history = generateMockViolationHistory($violation_id);
    }
} catch (PDOException $e) {
    error_log("Error fetching violation history: " . $e->getMessage());
    $use_mock_data = true;
    $violation_history = generateMockViolationHistory($violation_id);
}

// Get violation name for title if viewing specific violation
$violation_name = null;
if ($violation_id) {
    try {
        $stmt = $pdo->prepare("SELECT name, reference_no FROM violation_types WHERE id = ?");
        $stmt->execute([$violation_id]);
        $violation = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($violation) {
            $violation_name = $violation['name'];
        }
    } catch (PDOException $e) {
        error_log("Error fetching violation: " . $e->getMessage());
    }
}

// Fields to display in history (Ref No, Description, Severity, Sanctions)
$fields = [
    ['key' => 'reference_no', 'label' => 'Ref #'],
    ['key' => 'description', 'label' => 'Description'],
    ['key' => 'category', 'label' => 'Severity'],
    ['key' => 'subcategory', 'label' => 'Subcategory'],
    ['key' => 'first_offense', 'label' => '1st Offense'],
    ['key' => 'second_offense', 'label' => '2nd Offense'],
    ['key' => 'third_offense', 'label' => '3rd Offense'],
    ['key' => 'fourth_offense', 'label' => '4th Offense'],
    ['key' => 'fifth_offense', 'label' => '5th Offense']
];

// Helper function to escape HTML
function escapeHtml($text) {
    if (!$text) return '';
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Helper function to format date
function formatDateTime($dateString) {
    $date = new DateTime($dateString);
    return [
        'date' => $date->format('M d, Y'),
        'time' => $date->format('h:i A')
    ];
}

// Helper function to get user name
function getUserName($entry) {
    if (!empty($entry['username'])) return $entry['username'];
    if (!empty($entry['first_name']) && !empty($entry['last_name'])) {
        return $entry['first_name'] . ' ' . $entry['last_name'];
    }
    return 'System';
}

// Helper function to get action badge
function getActionBadge($action) {
    switch($action) {
        case 'UPDATE':
            return ['class' => 'badge-updated', 'text' => 'Updated'];
        case 'INSERT':
            return ['class' => 'badge-created', 'text' => 'Created'];
        case 'DELETE':
            return ['class' => 'badge-deleted', 'text' => 'Deleted'];
        default:
            return ['class' => 'badge-secondary', 'text' => $action];
    }
}

// Helper function to format changes
function formatChanges($oldData, $newData, $action, $fields) {
    if (!$oldData && !$newData) return '<span class="text-muted">—</span>';
    
    $html = '<div class="history-changes-cell">';
    
    if ($action === 'INSERT' && $newData) {
        $html .= '<div class="change-summary"><i class="fas fa-plus-circle me-1"></i>Created</div>';
        foreach ($fields as $field) {
            if (!empty($newData[$field['key']])) {
                $html .= '<div class="change-detail"><span class="change-label">' . escapeHtml($field['label']) . ':</span> <span class="change-value">' . escapeHtml($newData[$field['key']]) . '</span></div>';
            }
        }
    } else if ($action === 'DELETE' && $oldData) {
        $html .= '<div class="change-summary"><i class="fas fa-trash me-1"></i>Deleted</div>';
        foreach ($fields as $field) {
            if (!empty($oldData[$field['key']])) {
                $html .= '<div class="change-detail"><span class="change-label">' . escapeHtml($field['label']) . ':</span> <span class="change-value">' . escapeHtml($oldData[$field['key']]) . '</span></div>';
            }
        }
    } else if ($action === 'UPDATE' && $oldData && $newData) {
        $hasChanges = false;
        foreach ($fields as $field) {
            $oldVal = $oldData[$field['key']] ?? null;
            $newVal = $newData[$field['key']] ?? null;
            if ($oldVal !== $newVal && ($oldVal || $newVal)) {
                if (!$hasChanges) {
                    $html .= '<div class="change-summary"><i class="fas fa-edit me-1"></i>Updated</div>';
                    $hasChanges = true;
                }
                $html .= '<div class="change-detail">';
                $html .= '<span class="change-label">' . escapeHtml($field['label']) . ':</span> ';
                if ($oldVal) {
                    $html .= '<span class="change-old-value">' . escapeHtml($oldVal) . '</span> ';
                    $html .= '<i class="fas fa-arrow-right text-muted mx-1" style="font-size: 0.7rem;"></i> ';
                }
                if ($newVal) {
                    $html .= '<span class="change-new-value">' . escapeHtml($newVal) . '</span>';
                }
                $html .= '</div>';
            }
        }
        if (!$hasChanges) {
            $html = '<span class="text-muted">No changes</span>';
        }
    }
    
    $html .= '</div>';
    return $html;
}
?>

<div class="container-fluid hrdash">
    <div class="mb-3">
        <a href="?page=violation_types" class="text-decoration-none" style="color: #64748b; font-size: 0.875rem;">
            <i class="fas fa-arrow-left me-1"></i>Back to Violation Types
        </a>
    </div>
    
    <div class="card hrdash-card hrdash-license">
        <div class="hrdash-license__body">
            <!-- Filter Bar -->
            <div class="card card-modern mb-3" style="border-radius: 8px;">
                <div class="card-body-modern" style="padding: 0.75rem 1rem;">
                    <form id="violation-history-filter-form" class="d-flex flex-wrap gap-3 align-items-end" style="flex-wrap: nowrap;">
                        <div style="flex: 0 0 auto; min-width: 140px;">
                            <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">User</label>
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   id="filter-user" 
                                   placeholder="Type name"
                                   style="padding: 0.375rem 0.5rem; font-size: 0.8125rem;">
                        </div>
                        <div style="flex: 0 0 auto; min-width: 120px;">
                            <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Ref #</label>
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   id="filter-ref" 
                                   placeholder="Type ref"
                                   style="padding: 0.375rem 0.5rem; font-size: 0.8125rem;">
                        </div>
                        <div style="flex: 0 0 auto; min-width: 160px;">
                            <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Date</label>
                            <div class="input-group input-group-sm">
                                <input type="date" 
                                       class="form-control" 
                                       id="filter-date" 
                                       placeholder="dd/mm/yyyy"
                                       style="padding: 0.375rem 0.5rem; font-size: 0.8125rem;">
                                <span class="input-group-text bg-white" style="padding: 0.375rem 0.5rem;">
                                    <i class="fas fa-calendar-alt text-muted"></i>
                                </span>
                            </div>
                        </div>
                        <?php if (!$violation_id): ?>
                        <div style="flex: 0 0 auto; min-width: 160px;">
                            <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Violation</label>
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   id="filter-violation" 
                                   placeholder="Type violation"
                                   style="padding: 0.375rem 0.5rem; font-size: 0.8125rem;">
                        </div>
                        <?php endif; ?>
                        <div style="flex: 0 0 auto; min-width: 120px;">
                            <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Action</label>
                            <select class="form-select form-select-sm" id="filter-action" style="padding: 0.375rem 0.5rem; font-size: 0.8125rem;">
                                <option value="">All</option>
                                <option value="Created">Created</option>
                                <option value="Updated">Updated</option>
                                <option value="Deleted">Deleted</option>
                            </select>
                        </div>
                        <div style="flex: 0 0 auto; margin-left: 0.5rem;">
                            <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500; visibility: hidden;">Button</label>
                            <button type="button" class="btn btn-primary-modern btn-sm" id="filter-search-btn" style="padding: 0.375rem 0.75rem; font-size: 0.8125rem; white-space: nowrap;">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                        </div>
                        <div style="flex: 0 0 auto;">
                            <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500; visibility: hidden;">Reset</label>
                            <button type="button" class="btn btn-outline-modern btn-sm" id="filter-reset-btn" style="padding: 0.375rem 0.75rem; font-size: 0.8125rem; white-space: nowrap;">
                                <i class="fas fa-redo me-1"></i>Reset
                            </button>
                        </div>
                        <div style="flex: 0 0 auto; min-width: 70px; margin-left: 0.5rem; text-align: right;">
                            <div style="font-size: 0.6875rem; color: #64748b; margin-bottom: 0.125rem;">Results</div>
                            <div id="violation-history-count" style="font-size: 1rem; font-weight: 600; color: #1e3a8a;">0</div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- History Table -->
            <div class="table-responsive" style="margin: 0;">
                <table class="table table-hover align-middle mb-0" id="violation-history-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">Date & Time</th>
                            <th style="width: 8%;">Action</th>
                            <th style="width: 12%;">User</th>
                            <th style="width: 8%;">Ref #</th>
                            <th style="width: 10%;">Severity</th>
                            <th style="width: <?php echo $violation_id ? '52%' : '52%'; ?>;">Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($violation_history)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                    <p class="fs-5">No violation history found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($violation_history as $entry): ?>
                                <?php
                                try {
                                    $oldValues = $entry['old_values'] ? json_decode($entry['old_values'], true) : null;
                                    $newValues = $entry['new_values'] ? json_decode($entry['new_values'], true) : null;
                                    $userName = getUserName($entry);
                                    $actionBadge = getActionBadge($entry['action']);
                                    $dateTime = formatDateTime($entry['created_at']);
                                    $refNo = escapeHtml($entry['reference_no'] ?? 'N/A');
                                    
                                    // Determine severity from current entry or from changes
                                    $severity = null;
                                    $category = $entry['category'] ?? null;
                                    $subcategory = $entry['subcategory'] ?? null;
                                    
                                    // Get severity from new values (for INSERT/UPDATE) or old values (for DELETE)
                                    if ($newValues && isset($newValues['category'])) {
                                        $category = $newValues['category'];
                                        $subcategory = $newValues['subcategory'] ?? null;
                                    } elseif ($oldValues && isset($oldValues['category'])) {
                                        $category = $oldValues['category'];
                                        $subcategory = $oldValues['subcategory'] ?? null;
                                    }
                                    
                                    // Determine severity display
                                    if (!empty($subcategory)) {
                                        $severity = 'RA 5487';
                                    } elseif ($category === 'Minor') {
                                        $severity = 'Minor';
                                    } elseif ($category === 'Major') {
                                        $severity = 'Major';
                                    } else {
                                        $severity = '—';
                                    }
                                    
                                    $changes = formatChanges($oldValues, $newValues, $entry['action'], $fields);
                                } catch (Exception $e) {
                                    continue; // Skip invalid entries
                                }
                                ?>
                                <tr class="history-row">
                                    <td>
                                        <div class="history-date"><?php echo $dateTime['date']; ?></div>
                                        <div class="history-time text-muted small"><?php echo $dateTime['time']; ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $actionBadge['class']; ?> px-2 py-1"><?php echo $actionBadge['text']; ?></span>
                                    </td>
                                    <td>
                                        <div class="history-user"><?php echo escapeHtml($userName); ?></div>
                                    </td>
                                    <td>
                                        <span class="text-muted small"><?php echo $refNo; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-severity severity-<?php echo strtolower(str_replace(' ', '-', $severity)); ?>">
                                            <?php echo escapeHtml($severity); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $changes; ?>
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

<style>
.history-date {
    font-weight: 500;
    color: #1e293b;
    font-size: 0.8125rem;
    line-height: 1.3;
}

.history-time {
    font-size: 0.6875rem;
    margin-top: 0.125rem;
    line-height: 1.2;
}

.history-user {
    font-weight: 500;
    color: #374151;
    font-size: 0.8125rem;
    line-height: 1.3;
}

.history-violation-description {
    font-weight: 400;
    color: #1e293b;
    font-size: 0.8125rem;
    line-height: 1.4;
}

.badge-severity {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.severity-minor {
    background-color: #fef3c7;
    color: #d97706;
}

.severity-major {
    background-color: #fee2e2;
    color: #dc2626;
}

.severity-ra-5487 {
    background-color: #e0e7ff;
    color: #6366f1;
}

.severity-— {
    background-color: #f3f4f6;
    color: #6b7280;
}

.history-changes-cell {
    font-size: 0.8125rem;
    line-height: 1.4;
}

.change-summary {
    font-weight: 600;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    font-size: 0.75rem;
    color: #1e293b;
}

.change-detail {
    margin-bottom: 0.25rem;
    padding-left: 0.75rem;
    line-height: 1.4;
}

.change-label {
    font-weight: 500;
    color: #64748b;
    margin-right: 0.375rem;
    font-size: 0.75rem;
}

.change-value {
    color: #1e293b;
    font-size: 0.8125rem;
}

.change-old-value {
    color: #dc2626;
    text-decoration: line-through;
    margin-right: 0.25rem;
    font-size: 0.8125rem;
}

.change-new-value {
    color: #16a34a;
    font-weight: 500;
    font-size: 0.8125rem;
}

#violation-history-table {
    margin-bottom: 0;
}

#violation-history-table thead th {
    background-color: #f8fafc !important;
    font-weight: 600;
    color: #1e293b;
    border-bottom: 2px solid #e2e8f0;
    padding: 0.5rem 0.625rem;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap;
    font-size: 0.75rem;
    line-height: 1.3;
}

#violation-history-table tbody td {
    padding: 0.5rem 0.625rem;
    vertical-align: top;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.8125rem;
    line-height: 1.4;
}

#violation-history-table tbody tr:hover {
    background-color: #f8fafc;
}

#violation-history-table .badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.6875rem;
    line-height: 1.2;
    font-weight: 600;
    border-radius: 4px;
}

/* Action Badge Color Scheme - High Contrast */
#violation-history-table .badge-created {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}

#violation-history-table .badge-updated {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

#violation-history-table .badge-deleted {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

#violation-history-table .badge-secondary {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}

#violation-history-table .small {
    font-size: 0.6875rem;
    line-height: 1.2;
}

#violation-history-table tbody tr {
    margin: 0;
}

#violation-history-table tbody tr:last-child td {
    border-bottom: none;
}

.table-responsive {
    padding: 0;
}

.hrdash-license__body {
    padding: 1rem;
}

/* Compact Filter Bar Styling */
#violation-history-filter-form .form-label {
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
    font-weight: 500;
    color: #374151;
}

#violation-history-filter-form .form-control-sm,
#violation-history-filter-form .form-select-sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.8125rem;
    border-radius: 4px;
    border: 1px solid #d1d5db;
    transition: all 0.2s ease;
}

#violation-history-filter-form .form-control-sm:focus,
#violation-history-filter-form .form-select-sm:focus {
    border-color: #1e3a8a;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    outline: none;
}

#violation-history-filter-form .input-group-sm .input-group-text {
    border: 1px solid #d1d5db;
    border-left: none;
    background-color: #ffffff;
    border-radius: 0 4px 4px 0;
    padding: 0.375rem 0.5rem;
}

#violation-history-filter-form .input-group-sm .form-control {
    border-right: none;
    border-radius: 4px 0 0 4px;
}

#violation-history-filter-form .btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
}

.card-body-modern {
    padding: 0.75rem 1rem !important;
}

#violation-history-filter-form {
    display: flex;
    flex-wrap: nowrap !important;
    gap: 0.5rem;
    align-items: flex-end;
    width: 100%;
    overflow: visible;
}

#violation-history-filter-form > div {
    flex-shrink: 1;
}

#violation-history-filter-form input,
#violation-history-filter-form select {
    width: 100%;
    box-sizing: border-box;
}

#violation-history-count {
    color: #1e3a8a;
    font-size: 1rem;
    font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 991.98px) {
    #violation-history-filter-form .col-lg-2 {
        margin-bottom: 0.5rem;
    }
    
    #violation-history-filter-form .col-lg-auto {
        width: 100%;
    }
    
    #violation-history-filter-form .btn-primary-modern,
    #violation-history-filter-form .btn-outline-modern {
        flex: 1;
    }
}

@media (max-width: 575.98px) {
    #violation-history-filter-form .col-md-6 {
        margin-bottom: 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('violation-history-table');
    const rows = table ? table.querySelectorAll('tbody tr.history-row') : [];
    const filterUser = document.getElementById('filter-user');
    const filterRef = document.getElementById('filter-ref');
    const filterDate = document.getElementById('filter-date');
    const filterViolation = document.getElementById('filter-violation');
    const filterAction = document.getElementById('filter-action');
    const searchBtn = document.getElementById('filter-search-btn');
    const resetBtn = document.getElementById('filter-reset-btn');
    const historyCount = document.getElementById('violation-history-count');
    
    if (!table) return;
    
    function getRowData(row) {
        const cells = row.querySelectorAll('td');
        if (!cells.length) return null;
        
        // Extract data from row
        const dateTimeCell = cells[0];
        const dateTime = dateTimeCell ? dateTimeCell.textContent.trim() : '';
        const dateText = dateTime.split('\n')[0] || dateTime; // Get date part
        
        const actionCell = cells[1];
        const actionBadge = actionCell ? actionCell.querySelector('.badge') : null;
        const action = actionBadge ? actionBadge.textContent.trim() : '';
        
        const user = cells[2] ? cells[2].textContent.trim() : '';
        const ref = cells[3] ? cells[3].textContent.trim() : '';
        
        // Violation column was removed - columns are now: Date, Action, User, Ref #, Minor, Major, RA 5487, Changes
        // Keep violation empty for backward compatibility with filter
        const violation = '';
        
        return { dateText, action, user, ref, violation };
    }
    
    function parseDate(dateString) {
        // Parse date string like "Jan 15, 2024" or "January 15, 2024"
        const date = new Date(dateString);
        return isNaN(date.getTime()) ? null : date;
    }
    
    function formatDateForComparison(dateObj) {
        // Format date as YYYY-MM-DD for comparison
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    function matchesFilter(rowData, filters) {
        if (!rowData) return false;
        
        // User filter
        if (filters.user && !rowData.user.toLowerCase().includes(filters.user.toLowerCase())) {
            return false;
        }
        
        // Ref # filter
        if (filters.ref && !rowData.ref.toLowerCase().includes(filters.ref.toLowerCase())) {
            return false;
        }
        
        // Date filter
        if (filters.date) {
            const rowDate = parseDate(rowData.dateText);
            if (rowDate) {
                const rowDateStr = formatDateForComparison(rowDate);
                if (rowDateStr !== filters.date) {
                    return false;
                }
            } else {
                return false;
            }
        }
        
        // Violation filter
        if (filters.violation && rowData.violation && 
            !rowData.violation.toLowerCase().includes(filters.violation.toLowerCase())) {
            return false;
        }
        
        // Action filter - map filter values to badge text
        if (filters.action) {
            const actionMap = {
                'Created': 'Created',
                'Updated': 'Updated',
                'Deleted': 'Deleted'
            };
            const expectedAction = actionMap[filters.action] || filters.action;
            if (rowData.action !== expectedAction) {
                return false;
            }
        }
        
        return true;
    }
    
    function filterTable() {
        const filters = {
            user: filterUser ? filterUser.value.trim() : '',
            ref: filterRef ? filterRef.value.trim() : '',
            date: filterDate ? filterDate.value : '',
            violation: filterViolation ? filterViolation.value.trim() : '',
            action: filterAction ? filterAction.value : ''
        };
        
        let visibleCount = 0;
        
        rows.forEach(row => {
            const rowData = getRowData(row);
            if (matchesFilter(rowData, filters)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show no results message if needed
        const tbody = table.querySelector('tbody');
        let noResultsRow = tbody.querySelector('tr.no-results');
        
        const hasActiveFilters = Object.values(filters).some(v => v !== '');
        
        if (visibleCount === 0 && hasActiveFilters) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results';
                const colCount = table.querySelectorAll('thead th').length;
                noResultsRow.innerHTML = `
                    <td colspan="${colCount}" class="text-center text-muted py-5">
                        <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                        <p class="fs-5">No results found</p>
                    </td>
                `;
            }
            if (!tbody.querySelector('tr.no-results')) {
                tbody.appendChild(noResultsRow);
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
        
        // Update results count
        if (historyCount) {
            historyCount.textContent = visibleCount.toLocaleString();
        }
    }
    
    // Search button
    if (searchBtn) {
        searchBtn.addEventListener('click', filterTable);
    }
    
    // Reset button
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (filterUser) filterUser.value = '';
            if (filterRef) filterRef.value = '';
            if (filterDate) filterDate.value = '';
            if (filterViolation) filterViolation.value = '';
            if (filterAction) filterAction.value = '';
            filterTable();
        });
    }
    
    // Allow Enter key to trigger search and auto-update on input changes
    const filterInputs = [filterUser, filterRef, filterDate, filterViolation, filterAction];
    filterInputs.forEach(input => {
        if (input) {
            // Enter key to trigger search
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    filterTable();
                }
            });
            
            // Auto-update on input/change (live filtering)
            if (input.tagName === 'INPUT') {
                input.addEventListener('input', filterTable);
            } else if (input.tagName === 'SELECT') {
                input.addEventListener('change', filterTable);
            }
        }
    });
    
    // Initialize count on page load - always show results
    filterTable();
});
</script>
