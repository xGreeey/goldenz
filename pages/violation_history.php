<?php
$page_title = 'Violation History - Golden Z-5 HR System';
$page = 'violation_history';

// Get database connection
$pdo = get_db_connection();

// Get violation ID from URL (optional - if provided, show only that violation's history)
$violation_id = $_GET['violation_id'] ?? null;

// Fetch violation edit history from audit logs
$violation_history = [];

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
            vt.fifth_offense,
            vt.ra5487_compliant,
            vt.is_active
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
    
} catch (PDOException $e) {
    error_log("Error fetching violation history: " . $e->getMessage());
    $violation_history = [];
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

// Fetch all violation types for the table
$violation_types_list = [];
try {
    $sql = "SELECT 
                id,
                reference_no,
                name,
                category,
                subcategory,
                description,
                first_offense,
                second_offense,
                third_offense,
                fourth_offense,
                fifth_offense,
                ra5487_compliant,
                is_active,
                created_at,
                updated_at
            FROM violation_types
            ORDER BY 
                CASE category
                    WHEN 'Major' THEN 1
                    WHEN 'Minor' THEN 2
                    ELSE 3
                END,
                subcategory,
                reference_no";
    $stmt = $pdo->query($sql);
    $violation_types_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching violation types: " . $e->getMessage());
    $violation_types_list = [];
}

// Get filter parameters
$filter_category = $_GET['filter_category'] ?? '';
$filter_ra5487 = $_GET['filter_ra5487'] ?? '';
$filter_active = $_GET['filter_active'] ?? '';
$search_query = $_GET['search'] ?? '';

// Apply server-side filters
$filtered_types = $violation_types_list ?? [];

if ($filter_category) {
    $filtered_types = array_filter($filtered_types, function($vt) use ($filter_category) {
        return $vt['category'] === $filter_category;
    });
}

if ($filter_ra5487 !== '') {
    $filtered_types = array_filter($filtered_types, function($vt) use ($filter_ra5487) {
        return (int)$vt['ra5487_compliant'] === (int)$filter_ra5487;
    });
}

if ($filter_active !== '') {
    $filtered_types = array_filter($filtered_types, function($vt) use ($filter_active) {
        return (int)$vt['is_active'] === (int)$filter_active;
    });
}

// Note: Search filtering is done client-side for instant feedback
// Server-side search removed - handled by JavaScript

$filtered_types = array_values($filtered_types);

// Fields to display in history (Ref No, Description, Severity, Sanctions)
// Based on violation_types table structure
$fields = [
    ['key' => 'reference_no', 'label' => 'Ref #'],
    ['key' => 'name', 'label' => 'Name'],
    ['key' => 'description', 'label' => 'Description'],
    ['key' => 'category', 'label' => 'Category'],
    ['key' => 'subcategory', 'label' => 'Subcategory'],
    ['key' => 'first_offense', 'label' => '1st Offense'],
    ['key' => 'second_offense', 'label' => '2nd Offense'],
    ['key' => 'third_offense', 'label' => '3rd Offense'],
    ['key' => 'fourth_offense', 'label' => '4th Offense'],
    ['key' => 'fifth_offense', 'label' => '5th Offense'],
    ['key' => 'ra5487_compliant', 'label' => 'RA 5487 Compliant'],
    ['key' => 'is_active', 'label' => 'Active Status']
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

// Helper function to format field value for display
function formatFieldValue($value, $fieldKey) {
    if ($value === null || $value === '') {
        return '—';
    }
    
    // Handle boolean fields
    if ($fieldKey === 'ra5487_compliant' || $fieldKey === 'is_active') {
        return ($value == 1 || $value === true || $value === '1') ? 'Yes' : 'No';
    }
    
    return $value;
}

// Helper function to format changes
function formatChanges($oldData, $newData, $action, $fields) {
    if (!$oldData && !$newData) return '<span class="text-muted">—</span>';
    
    $html = '<div class="history-changes-cell">';
    
    if ($action === 'INSERT' && $newData) {
        $html .= '<div class="change-summary"><i class="fas fa-plus-circle me-1"></i>Created</div>';
        foreach ($fields as $field) {
            $value = $newData[$field['key']] ?? null;
            // Show field if it has a value (including 0 for booleans)
            if ($value !== null && $value !== '') {
                $displayValue = formatFieldValue($value, $field['key']);
                $html .= '<div class="change-detail"><span class="change-label">' . escapeHtml($field['label']) . ':</span> <span class="change-value">' . escapeHtml($displayValue) . '</span></div>';
            }
        }
    } else if ($action === 'DELETE' && $oldData) {
        $html .= '<div class="change-summary"><i class="fas fa-trash me-1"></i>Deleted</div>';
        foreach ($fields as $field) {
            $value = $oldData[$field['key']] ?? null;
            // Show field if it has a value (including 0 for booleans)
            if ($value !== null && $value !== '') {
                $displayValue = formatFieldValue($value, $field['key']);
                $html .= '<div class="change-detail"><span class="change-label">' . escapeHtml($field['label']) . ':</span> <span class="change-value">' . escapeHtml($displayValue) . '</span></div>';
            }
        }
    } else if ($action === 'UPDATE' && $oldData && $newData) {
        $hasChanges = false;
        foreach ($fields as $field) {
            $oldVal = $oldData[$field['key']] ?? null;
            $newVal = $newData[$field['key']] ?? null;
            
            // Normalize values for comparison (handle boolean fields)
            $oldValNormalized = ($field['key'] === 'ra5487_compliant' || $field['key'] === 'is_active') 
                ? (int)$oldVal 
                : $oldVal;
            $newValNormalized = ($field['key'] === 'ra5487_compliant' || $field['key'] === 'is_active') 
                ? (int)$newVal 
                : $newVal;
            
            // Check if value changed
            if ($oldValNormalized !== $newValNormalized) {
                if (!$hasChanges) {
                    $html .= '<div class="change-summary"><i class="fas fa-edit me-1"></i>Updated</div>';
                    $hasChanges = true;
                }
                $html .= '<div class="change-detail">';
                $html .= '<span class="change-label">' . escapeHtml($field['label']) . ':</span> ';
                
                $oldDisplay = formatFieldValue($oldVal, $field['key']);
                $newDisplay = formatFieldValue($newVal, $field['key']);
                
                if ($oldVal !== null && $oldVal !== '') {
                    $html .= '<span class="change-old-value">' . escapeHtml($oldDisplay) . '</span> ';
                    $html .= '<i class="fas fa-arrow-right text-muted mx-1" style="font-size: 0.7rem;"></i> ';
                }
                if ($newVal !== null && $newVal !== '') {
                    $html .= '<span class="change-new-value">' . escapeHtml($newDisplay) . '</span>';
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

    <!-- Violation Types Table Section -->
    <div class="card card-modern mb-4">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title-modern">Violation Types</h5>
                <div class="card-subtitle">View and filter violation types</div>
            </div>
        </div>
        <div class="card-body-modern">
            <!-- Filter Section -->
            <form method="GET" action="" id="violationTypesFilterForm" class="d-flex gap-2 align-items-end" style="flex-wrap: nowrap;">
                <input type="hidden" name="page" value="violation_history">
                <div class="flex-grow-1" style="min-width: 0;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Search</label>
                    <input type="text" id="violationTypesSearch" class="form-control form-control-sm" 
                           placeholder="Search reference, name, or description" 
                           autocomplete="off">
                </div>
                <div style="flex: 0 0 auto; min-width: 160px;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Category</label>
                    <select name="filter_category" id="violationTypesCategoryFilter" class="form-select form-select-sm">
                        <option value="" <?php echo $filter_category === '' ? 'selected' : ''; ?>>All</option>
                        <option value="Major" <?php echo $filter_category === 'Major' ? 'selected' : ''; ?>>Major</option>
                        <option value="Minor" <?php echo $filter_category === 'Minor' ? 'selected' : ''; ?>>Minor</option>
                    </select>
                </div>
                <div style="flex: 0 0 auto; min-width: 160px;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">RA 5487 Compliant</label>
                    <select name="filter_ra5487" id="violationTypesRa5487Filter" class="form-select form-select-sm">
                        <option value="" <?php echo $filter_ra5487 === '' ? 'selected' : ''; ?>>All</option>
                        <option value="1" <?php echo $filter_ra5487 === '1' ? 'selected' : ''; ?>>Yes</option>
                        <option value="0" <?php echo $filter_ra5487 === '0' ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>
                <div style="flex: 0 0 auto; min-width: 160px;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Status</label>
                    <select name="filter_active" id="violationTypesActiveFilter" class="form-select form-select-sm">
                        <option value="" <?php echo $filter_active === '' ? 'selected' : ''; ?>>All</option>
                        <option value="1" <?php echo $filter_active === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $filter_active === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div style="flex: 0 0 auto;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500; visibility: hidden;">Reset</label>
                    <a class="btn btn-outline-modern btn-sm" href="?page=violation_history" title="Reset">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
                <div style="flex: 0 0 30%; min-width: 120px; text-align: right; margin-left: auto;">
                    <div style="font-size: 0.6875rem; color: #64748b; margin-bottom: 0.125rem;">Results</div>
                    <div id="violation-types-count" style="font-size: 1rem; font-weight: 600; color: #1e3a8a;"><?php echo number_format(count($filtered_types)); ?></div>
                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive mt-3">
                <table class="table table-hover align-middle" id="violationTypesTable">
                    <thead>
                        <tr>
                            <th>Reference #</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Description</th>
                            <th>1st Offense</th>
                            <th>2nd Offense</th>
                            <th>3rd Offense</th>
                            <th>4th Offense</th>
                            <th>5th Offense</th>
                            <th>RA 5487</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="violationTypesTableBody">
                        <?php if (empty($violation_types_list)): ?>
                            <tr>
                                <td colspan="12" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No violation types found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php if (empty($filtered_types)): ?>
                                <tr>
                                    <td colspan="12" class="text-center py-5">
                                        <i class="fas fa-filter fa-3x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">No violation types match the selected filters</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_types as $vt): ?>
                                <tr class="violation-type-row" 
                                    data-reference="<?php echo htmlspecialchars(strtolower($vt['reference_no'] ?? '')); ?>"
                                    data-name="<?php echo htmlspecialchars(strtolower($vt['name'] ?? '')); ?>"
                                    data-description="<?php echo htmlspecialchars(strtolower($vt['description'] ?? '')); ?>"
                                    data-category="<?php echo htmlspecialchars($vt['category'] ?? ''); ?>"
                                    data-ra5487="<?php echo (int)$vt['ra5487_compliant']; ?>"
                                    data-active="<?php echo (int)$vt['is_active']; ?>">
                                    <td>
                                        <span class="text-muted small"><?php echo htmlspecialchars($vt['reference_no'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($vt['name'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo ($vt['category'] ?? '') === 'Major' ? 'danger' : 'warning'; ?>-subtle text-<?php echo ($vt['category'] ?? '') === 'Major' ? 'danger' : 'warning'; ?> fw-semibold">
                                            <?php echo htmlspecialchars($vt['category'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small"><?php echo htmlspecialchars($vt['subcategory'] ?? '—'); ?></span>
                                    </td>
                                    <td>
                                        <div class="small text-muted" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($vt['description'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($vt['description'] ?? '—'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small"><?php echo htmlspecialchars($vt['first_offense'] ?? '—'); ?></span>
                                    </td>
                                    <td>
                                        <span class="small"><?php echo htmlspecialchars($vt['second_offense'] ?? '—'); ?></span>
                                    </td>
                                    <td>
                                        <span class="small"><?php echo htmlspecialchars($vt['third_offense'] ?? '—'); ?></span>
                                    </td>
                                    <td>
                                        <span class="small"><?php echo htmlspecialchars($vt['fourth_offense'] ?? '—'); ?></span>
                                    </td>
                                    <td>
                                        <span class="small"><?php echo htmlspecialchars($vt['fifth_offense'] ?? '—'); ?></span>
                                    </td>
                                    <td>
                                        <?php if ((int)$vt['ra5487_compliant'] === 1): ?>
                                            <span class="badge bg-success-subtle text-success fw-semibold">Yes</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary fw-semibold">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$vt['is_active'] === 1): ?>
                                            <span class="badge bg-success-subtle text-success fw-semibold">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary fw-semibold">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.violation-types-table thead th {
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
.violation-types-table tbody td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}
.violation-types-table tbody tr:hover {
    background: #f9fafb;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Server-side filter form submission
    const categorySelect = document.getElementById('violationTypesCategoryFilter');
    const ra5487Select = document.getElementById('violationTypesRa5487Filter');
    const activeSelect = document.getElementById('violationTypesActiveFilter');
    
    if (categorySelect) {
        categorySelect.addEventListener('change', () => {
            document.getElementById('violationTypesFilterForm')?.submit();
        });
    }
    
    if (ra5487Select) {
        ra5487Select.addEventListener('change', () => {
            document.getElementById('violationTypesFilterForm')?.submit();
        });
    }
    
    if (activeSelect) {
        activeSelect.addEventListener('change', () => {
            document.getElementById('violationTypesFilterForm')?.submit();
        });
    }

    // Client-side search filtering
    function initViolationTypesSearch() {
        const searchInput = document.getElementById('violationTypesSearch');
        const tableBody = document.getElementById('violationTypesTableBody');
        const countEl = document.getElementById('violation-types-count');
        const rows = () => Array.from(tableBody?.querySelectorAll('tr.violation-type-row') || []);

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
                    const categoryFilter = categorySelect ? categorySelect.value : '';
                    const ra5487Filter = ra5487Select ? ra5487Select.value : '';
                    const activeFilter = activeSelect ? activeSelect.value : '';
                    
                    rows().forEach(row => {
                        const reference = row.dataset.reference || '';
                        const name = row.dataset.name || '';
                        const description = row.dataset.description || '';
                        const category = row.dataset.category || '';
                        const ra5487 = row.dataset.ra5487 || '';
                        const active = row.dataset.active || '';
                        
                        const matchesSearch = !q || 
                            reference.includes(q) || 
                            name.includes(q) || 
                            description.includes(q);
                        
                        const matchesCategory = !categoryFilter || category === categoryFilter;
                        const matchesRa5487 = !ra5487Filter || ra5487 === ra5487Filter;
                        const matchesActive = !activeFilter || active === activeFilter;
                        
                        if (matchesSearch && matchesCategory && matchesRa5487 && matchesActive) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    updateCount();
                }, 150);
            });
            
            // Trigger initial filter if there's a value
            if (searchInput.value) {
                const q = (searchInput.value || '').trim().toLowerCase();
                const categoryFilter = categorySelect ? categorySelect.value : '';
                const ra5487Filter = ra5487Select ? ra5487Select.value : '';
                const activeFilter = activeSelect ? activeSelect.value : '';
                
                rows().forEach(row => {
                    const reference = row.dataset.reference || '';
                    const name = row.dataset.name || '';
                    const description = row.dataset.description || '';
                    const category = row.dataset.category || '';
                    const ra5487 = row.dataset.ra5487 || '';
                    const active = row.dataset.active || '';
                    
                    const matchesSearch = !q || 
                        reference.includes(q) || 
                        name.includes(q) || 
                        description.includes(q);
                    
                    const matchesCategory = !categoryFilter || category === categoryFilter;
                    const matchesRa5487 = !ra5487Filter || ra5487 === ra5487Filter;
                    const matchesActive = !activeFilter || active === activeFilter;
                    
                    if (matchesSearch && matchesCategory && matchesRa5487 && matchesActive) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        }
        updateCount();
    }
    
    initViolationTypesSearch();
});
</script>
