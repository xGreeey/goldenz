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
</div>
