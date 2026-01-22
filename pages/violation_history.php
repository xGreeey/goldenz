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
            vt.name as violation_name
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

// Fields to display in history
$fields = [
    ['key' => 'name', 'label' => 'Name'],
    ['key' => 'first_offense', 'label' => '1st Offense'],
    ['key' => 'second_offense', 'label' => '2nd Offense'],
    ['key' => 'third_offense', 'label' => '3rd Offense'],
    ['key' => 'fourth_offense', 'label' => '4th Offense'],
    ['key' => 'fifth_offense', 'label' => '5th Offense'],
    ['key' => 'description', 'label' => 'Description']
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
            return ['class' => 'bg-warning', 'text' => 'Updated'];
        case 'INSERT':
            return ['class' => 'bg-success', 'text' => 'Created'];
        case 'DELETE':
            return ['class' => 'bg-danger', 'text' => 'Deleted'];
        default:
            return ['class' => 'bg-secondary', 'text' => $action];
    }
}

// Helper function to format changes
function formatChanges($oldData, $newData, $action, $fields) {
    if (!$oldData && !$newData) return '<span class="text-muted">—</span>';
    
    $html = '<div class="history-changes-cell">';
    
    if ($action === 'INSERT' && $newData) {
        $html .= '<div class="change-summary text-success"><i class="fas fa-plus-circle me-1"></i>Created</div>';
        foreach ($fields as $field) {
            if (!empty($newData[$field['key']])) {
                $html .= '<div class="change-detail"><span class="change-label">' . escapeHtml($field['label']) . ':</span> <span class="change-value">' . escapeHtml($newData[$field['key']]) . '</span></div>';
            }
        }
    } else if ($action === 'DELETE' && $oldData) {
        $html .= '<div class="change-summary text-danger"><i class="fas fa-trash me-1"></i>Deleted</div>';
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
                    $html .= '<div class="change-summary text-warning"><i class="fas fa-edit me-1"></i>Updated</div>';
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
    <div class="card hrdash-card hrdash-license">
        <div class="hrdash-card__header">
            <div>
                <h5 class="hrdash-card__title">
                    <?php if ($violation_id && $violation_name): ?>
                        Violation History: <?php echo escapeHtml($violation_name); ?>
                    <?php else: ?>
                        All Violation History
                    <?php endif; ?>
                </h5>
                <div class="hrdash-card__subtitle">
                    <?php echo $violation_id ? 'History of changes for this violation type' : 'Complete history of all violation type changes'; ?>
                </div>
            </div>
            <div>
                <a href="?page=violation_types" class="btn btn-outline-modern">
                    <i class="fas fa-arrow-left me-2"></i>Back to Violation Types
                </a>
            </div>
        </div>

        <div class="hrdash-license__body">
            <!-- Search Bar -->
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" 
                           class="form-control border-start-0" 
                           id="violation-history-search" 
                           placeholder="Search history by date, user, reference, violation, or changes...">
                    <button type="button" class="btn btn-outline-secondary border-start-0" id="clear-history-search" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- History Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="violation-history-table">
                    <thead>
                        <tr>
                            <th style="width: 12%;">Date & Time</th>
                            <th style="width: 10%;">Action</th>
                            <th style="width: 15%;">User</th>
                            <th style="width: 10%;">Ref #</th>
                            <?php if (!$violation_id): ?>
                                <th style="width: 20%;">Violation</th>
                            <?php endif; ?>
                            <th style="width: <?php echo $violation_id ? '53%' : '33%'; ?>;">Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($violation_history)): ?>
                            <tr>
                                <td colspan="<?php echo $violation_id ? '5' : '6'; ?>" class="text-center text-muted py-5">
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
                                    $violationName = escapeHtml($entry['violation_name'] ?? 'Unknown Violation');
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
                                    <?php if (!$violation_id): ?>
                                        <td>
                                            <div class="history-violation-name"><?php echo $violationName; ?></div>
                                        </td>
                                    <?php endif; ?>
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
}

.history-time {
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.history-user {
    font-weight: 500;
    color: #374151;
}

.history-violation-name {
    font-weight: 500;
    color: #1e293b;
    font-size: 0.875rem;
}

.history-changes-cell {
    font-size: 0.875rem;
}

.change-summary {
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.change-detail {
    margin-bottom: 0.5rem;
    padding-left: 1rem;
    line-height: 1.5;
}

.change-label {
    font-weight: 500;
    color: #64748b;
    margin-right: 0.5rem;
}

.change-value {
    color: #1e293b;
}

.change-old-value {
    color: #dc2626;
    text-decoration: line-through;
    margin-right: 0.25rem;
}

.change-new-value {
    color: #16a34a;
    font-weight: 500;
}

#violation-history-search {
    border: 1px solid #d1d5db;
}

#violation-history-search:focus {
    border-color: #1e3a8a;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    outline: none;
}

.table th {
    background-color: #f8fafc;
    font-weight: 600;
    color: #1e293b;
    border-bottom: 2px solid #e2e8f0;
    padding: 0.75rem;
}

.table td {
    padding: 0.75rem;
    vertical-align: top;
    border-bottom: 1px solid #e5e7eb;
}

.table tbody tr:hover {
    background-color: #f8fafc;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('violation-history-search');
    const clearBtn = document.getElementById('clear-history-search');
    const table = document.getElementById('violation-history-table');
    const rows = table.querySelectorAll('tbody tr.history-row');
    
    if (!searchInput || !table) return;
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide clear button
        if (searchTerm.length > 0) {
            clearBtn.style.display = 'block';
        } else {
            clearBtn.style.display = 'none';
        }
        
        // Show no results message if needed
        const tbody = table.querySelector('tbody');
        let noResultsRow = tbody.querySelector('tr.no-results');
        
        if (visibleCount === 0 && searchTerm.length > 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results';
                noResultsRow.innerHTML = `
                    <td colspan="${table.querySelectorAll('thead th').length}" class="text-center text-muted py-5">
                        <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                        <p class="fs-5">No results found for "${escapeHtml(searchTerm)}"</p>
                    </td>
                `;
            }
            tbody.appendChild(noResultsRow);
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    }
    
    searchInput.addEventListener('input', filterTable);
    
    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        filterTable();
        searchInput.focus();
    });
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
