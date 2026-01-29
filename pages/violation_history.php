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
    <!-- Page Header -->
    <header class="violation-page-header">
        <nav aria-label="Breadcrumb navigation">
            <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="?page=violation_types" itemprop="item" class="breadcrumb-link">
                        <span itemprop="name">Violation Types</span>
                    </a>
                    <meta itemprop="position" content="1" />
                </li>
                <li class="breadcrumb-item active" aria-current="page" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <span itemprop="name">Violation History</span>
                    <meta itemprop="position" content="2" />
                </li>
            </ol>
        </nav>
        <div class="page-header-content">
            <h1 class="page-title">Violation Types Management</h1>
            <p class="page-description">View and manage all violation types in the system</p>
        </div>
    </header>

    <!-- Statistics Overview -->
    <?php
    $total_count = count($violation_types_list);
    $major_count = count(array_filter($violation_types_list, fn($vt) => $vt['category'] === 'Major'));
    $minor_count = count(array_filter($violation_types_list, fn($vt) => $vt['category'] === 'Minor'));
    $active_count = count(array_filter($violation_types_list, fn($vt) => (int)$vt['is_active'] === 1));
    $ra5487_count = count(array_filter($violation_types_list, fn($vt) => (int)$vt['ra5487_compliant'] === 1));
    ?>
    <section class="stats-grid" aria-label="Violation statistics">
        <article class="stat-card stat-card-primary">
            <div class="stat-card-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <div class="stat-card-label">Total Violations</div>
                <div class="stat-card-value" aria-live="polite"><?php echo number_format($total_count); ?></div>
            </div>
        </article>
        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <div class="stat-card-label">Major Violations</div>
                <div class="stat-card-value" aria-live="polite"><?php echo number_format($major_count); ?></div>
            </div>
        </article>
        <article class="stat-card stat-card-warning">
            <div class="stat-card-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <div class="stat-card-label">Minor Violations</div>
                <div class="stat-card-value" aria-live="polite"><?php echo number_format($minor_count); ?></div>
            </div>
        </article>
        <article class="stat-card stat-card-success">
            <div class="stat-card-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M22 4L12 14.01l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <div class="stat-card-label">Active Types</div>
                <div class="stat-card-value" aria-live="polite"><?php echo number_format($active_count); ?></div>
            </div>
        </article>
    </section>

    <!-- Main Content Section -->
    <section class="violation-types-section" aria-labelledby="violation-types-heading">
        <div class="violation-types-card">
            <header class="card-header">
                <div class="card-header-content">
                    <h2 id="violation-types-heading" class="card-title">Violation Types</h2>
                    <p class="card-subtitle">Comprehensive view and management of violation types</p>
                </div>
                <div class="card-header-actions">
                    <button type="button" class="btn btn-secondary btn-sm" id="exportViolationsBtn" aria-label="Export violation types data">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M14 10v2.667A1.333 1.333 0 0112.667 14H3.333A1.333 1.333 0 012 12.667V10M11.333 5.333L8 2M8 2L4.667 5.333M8 2v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Export</span>
                    </button>
                </div>
            </header>
            <div class="card-body">
                <!-- Filter Section -->
                <form method="GET" action="" id="violationTypesFilterForm" class="filter-form" aria-label="Filter violation types">
                    <input type="hidden" name="page" value="violation_history">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label for="violationTypesSearch" class="filter-label">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M10.5 10.5l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                Search
                            </label>
                            <div class="input-wrapper">
                                <input 
                                    type="search" 
                                    id="violationTypesSearch" 
                                    name="search"
                                    class="form-input" 
                                    placeholder="Reference, name, or description&hellip;" 
                                    autocomplete="off"
                                    spellcheck="false"
                                    aria-describedby="search-description">
                                <span id="search-description" class="sr-only">Search violation types by reference number, name, or description</span>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label for="violationTypesCategoryFilter" class="filter-label">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M3 4h10M3 8h10M3 12h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                Category
                            </label>
                            <select name="filter_category" id="violationTypesCategoryFilter" class="form-select" aria-label="Filter by violation category">
                                <option value="" <?php echo $filter_category === '' ? 'selected' : ''; ?>>All Categories</option>
                                <option value="Major" <?php echo $filter_category === 'Major' ? 'selected' : ''; ?>>Major</option>
                                <option value="Minor" <?php echo $filter_category === 'Minor' ? 'selected' : ''; ?>>Minor</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="violationTypesRa5487Filter" class="filter-label">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M8 2v12M2 8h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                RA 5487
                            </label>
                            <select name="filter_ra5487" id="violationTypesRa5487Filter" class="form-select" aria-label="Filter by RA 5487 compliance">
                                <option value="" <?php echo $filter_ra5487 === '' ? 'selected' : ''; ?>>All</option>
                                <option value="1" <?php echo $filter_ra5487 === '1' ? 'selected' : ''; ?>>Compliant</option>
                                <option value="0" <?php echo $filter_ra5487 === '0' ? 'selected' : ''; ?>>Non-Compliant</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="violationTypesActiveFilter" class="filter-label">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M8 4v4l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                Status
                            </label>
                            <select name="filter_active" id="violationTypesActiveFilter" class="form-select" aria-label="Filter by active status">
                                <option value="" <?php echo $filter_active === '' ? 'selected' : ''; ?>>All Status</option>
                                <option value="1" <?php echo $filter_active === '1' ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $filter_active === '0' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="filter-group filter-group-actions">
                            <a href="?page=violation_history" class="btn btn-secondary btn-sm" aria-label="Reset all filters">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M1.333 4v4h4M14.667 4v4h-4M4 1.333L2 4l2 2.667M12 1.333L14 4l-2 2.667M8 10.667a2.667 2.667 0 11-5.333 0 2.667 2.667 0 015.333 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>Reset</span>
                            </a>
                            <div class="results-count" aria-live="polite" aria-atomic="true">
                                <span class="results-label">Results</span>
                                <span id="violation-types-count" class="results-value"><?php echo number_format(count($filtered_types)); ?></span>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Table Section -->
                <div class="table-wrapper">
                    <div class="table-responsive" role="region" aria-label="Violation types table" tabindex="0">
                        <table class="violation-table" id="violationTypesTable">
                            <caption class="sr-only">List of violation types with their categories, offenses, and compliance status</caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="col-ref">Reference</th>
                                    <th scope="col" class="col-name">Name</th>
                                    <th scope="col" class="col-category">Category</th>
                                    <th scope="col" class="col-subcategory">Subcategory</th>
                                    <th scope="col" class="col-description">Description</th>
                                    <th scope="col" class="col-offense">1st</th>
                                    <th scope="col" class="col-offense">2nd</th>
                                    <th scope="col" class="col-offense">3rd</th>
                                    <th scope="col" class="col-offense">4th</th>
                                    <th scope="col" class="col-offense">5th</th>
                                    <th scope="col" class="col-compliance">RA 5487</th>
                                    <th scope="col" class="col-status">Status</th>
                                </tr>
                            </thead>
                            <tbody id="violationTypesTableBody">
                                <?php if (empty($violation_types_list)): ?>
                                    <tr>
                                        <td colspan="12" class="empty-state">
                                            <div class="empty-state-content">
                                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <p class="empty-state-text">No violation types found</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php if (empty($filtered_types)): ?>
                                        <tr>
                                            <td colspan="12" class="empty-state">
                                                <div class="empty-state-content">
                                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                                                        <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                    </svg>
                                                    <p class="empty-state-text">No violation types match the selected filters</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($filtered_types as $vt): ?>
                                        <tr class="violation-row" 
                                            data-reference="<?php echo htmlspecialchars(strtolower($vt['reference_no'] ?? '')); ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($vt['name'] ?? '')); ?>"
                                            data-description="<?php echo htmlspecialchars(strtolower($vt['description'] ?? '')); ?>"
                                            data-category="<?php echo htmlspecialchars($vt['category'] ?? ''); ?>"
                                            data-ra5487="<?php echo (int)$vt['ra5487_compliant']; ?>"
                                            data-active="<?php echo (int)$vt['is_active']; ?>">
                                            <td class="col-ref">
                                                <span class="ref-number"><?php echo htmlspecialchars($vt['reference_no'] ?? 'N/A'); ?></span>
                                            </td>
                                            <td class="col-name">
                                                <span class="violation-name"><?php echo htmlspecialchars($vt['name'] ?? 'N/A'); ?></span>
                                            </td>
                                            <td class="col-category">
                                                <span class="badge badge-category badge-<?php echo strtolower($vt['category'] ?? 'minor'); ?>" aria-label="Category: <?php echo htmlspecialchars($vt['category'] ?? 'N/A'); ?>">
                                                    <?php if (($vt['category'] ?? '') === 'Major'): ?>
                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                            <path d="M6 1L1 11h10L6 1z" fill="currentColor"/>
                                                            <path d="M6 4v3M6 8h.01" stroke="white" stroke-width="1" stroke-linecap="round"/>
                                                        </svg>
                                                    <?php else: ?>
                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                            <circle cx="6" cy="6" r="5" stroke="currentColor" stroke-width="1.5"/>
                                                            <path d="M6 3v3M6 7h.01" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
                                                        </svg>
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($vt['category'] ?? 'N/A'); ?></span>
                                                </span>
                                            </td>
                                            <td class="col-subcategory">
                                                <span class="subcategory-text"><?php echo htmlspecialchars($vt['subcategory'] ?? '—'); ?></span>
                                            </td>
                                            <td class="col-description">
                                                <span class="description-text" title="<?php echo htmlspecialchars($vt['description'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars($vt['description'] ?? '—'); ?>
                                                </span>
                                            </td>
                                            <td class="col-offense">
                                                <span class="offense-text"><?php echo htmlspecialchars($vt['first_offense'] ?? '—'); ?></span>
                                            </td>
                                            <td class="col-offense">
                                                <span class="offense-text"><?php echo htmlspecialchars($vt['second_offense'] ?? '—'); ?></span>
                                            </td>
                                            <td class="col-offense">
                                                <span class="offense-text"><?php echo htmlspecialchars($vt['third_offense'] ?? '—'); ?></span>
                                            </td>
                                            <td class="col-offense">
                                                <span class="offense-text"><?php echo htmlspecialchars($vt['fourth_offense'] ?? '—'); ?></span>
                                            </td>
                                            <td class="col-offense">
                                                <span class="offense-text"><?php echo htmlspecialchars($vt['fifth_offense'] ?? '—'); ?></span>
                                            </td>
                                            <td class="col-compliance">
                                                <?php if ((int)$vt['ra5487_compliant'] === 1): ?>
                                                    <span class="badge badge-status badge-compliant" aria-label="RA 5487 Compliant">
                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                            <circle cx="6" cy="6" r="5" fill="currentColor"/>
                                                            <path d="M4 6l1.5 1.5L8 4" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        <span>Compliant</span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-status badge-non-compliant" aria-label="RA 5487 Non-Compliant">
                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                            <circle cx="6" cy="6" r="5" stroke="currentColor" stroke-width="1.5"/>
                                                            <path d="M4 4l4 4M8 4l-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                        </svg>
                                                        <span>Non-Compliant</span>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="col-status">
                                                <?php if ((int)$vt['is_active'] === 1): ?>
                                                    <span class="badge badge-status badge-active" aria-label="Status: Active">
                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                            <circle cx="6" cy="6" r="5" fill="currentColor"/>
                                                            <path d="M4 6l1.5 1.5L8 4" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        <span>Active</span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-status badge-inactive" aria-label="Status: Inactive">
                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                            <circle cx="6" cy="6" r="5" stroke="currentColor" stroke-width="1.5"/>
                                                            <path d="M4 6h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                        </svg>
                                                        <span>Inactive</span>
                                                    </span>
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
    </section>
</div>

<style>
/* Violation History Page - Design System Compliant */
/* Based on 8pt grid system and Web Interface Guidelines */

/* Design Tokens - 8pt Grid System */
:root {
    --spacing-xs: 4px;   /* 0.5 * 8 */
    --spacing-sm: 8px;   /* 1 * 8 */
    --spacing-md: 16px;  /* 2 * 8 */
    --spacing-lg: 24px;  /* 3 * 8 */
    --spacing-xl: 32px;  /* 4 * 8 */
    --spacing-2xl: 48px; /* 6 * 8 */
    --spacing-3xl: 64px; /* 8 * 8 */
    
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    
    --transition-base: 150ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
    
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    
    --focus-ring: 0 0 0 3px rgba(31, 178, 213, 0.2);
}

/* Respect reduced motion preference */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Screen reader only */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

/* Page Header */
.violation-page-header {
    margin-bottom: var(--spacing-xl);
}

.breadcrumb {
    display: flex;
    flex-wrap: wrap;
    padding: 0;
    margin-bottom: var(--spacing-md);
    list-style: none;
    background: transparent;
    font-size: 0.875rem;
    line-height: 1.5;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "/";
    display: inline-block;
    padding: 0 var(--spacing-sm);
    color: #94a3b8;
}

.breadcrumb-link {
    color: var(--primary-color, #1fb2d5);
    text-decoration: none;
    transition: color var(--transition-base);
}

.breadcrumb-link:hover {
    color: var(--primary-dark, #0e708c);
    text-decoration: underline;
}

.breadcrumb-link:focus-visible {
    outline: 2px solid var(--primary-color, #1fb2d5);
    outline-offset: 2px;
    border-radius: var(--radius-sm);
}

.breadcrumb-item.active {
    color: #64748b;
}

.page-header-content {
    margin-top: var(--spacing-md);
}

.page-title {
    font-size: 1.875rem;
    font-weight: 700;
    line-height: 1.2;
    color: #0f172a;
    margin: 0 0 var(--spacing-sm) 0;
    text-wrap: balance;
}

.page-description {
    font-size: 1rem;
    line-height: 1.5;
    color: #64748b;
    margin: 0;
}

/* Statistics Grid - 8pt spacing */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    position: relative;
    overflow: hidden;
    transition: transform var(--transition-base), box-shadow var(--transition-base), border-color var(--transition-base);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    transition: width var(--transition-slow);
}

.stat-card-primary::before { background: var(--primary-color, #1fb2d5); }
.stat-card-danger::before { background: var(--danger-color, #ef4444); }
.stat-card-warning::before { background: var(--warning-color, #f59e0b); }
.stat-card-success::before { background: var(--success-color, #22c55e); }

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: rgba(31, 178, 213, 0.3);
}

.stat-card:focus-within {
    outline: 2px solid var(--primary-color, #1fb2d5);
    outline-offset: 2px;
}

.stat-card-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform var(--transition-base);
}

.stat-card-icon svg {
    width: 24px;
    height: 24px;
}

.stat-card-primary .stat-card-icon {
    background: rgba(31, 178, 213, 0.1);
    color: var(--primary-color, #1fb2d5);
}

.stat-card-danger .stat-card-icon {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color, #ef4444);
}

.stat-card-warning .stat-card-icon {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-color, #f59e0b);
}

.stat-card-success .stat-card-icon {
    background: rgba(34, 197, 94, 0.1);
    color: var(--success-color, #22c55e);
}

.stat-card:hover .stat-card-icon {
    transform: scale(1.1);
}

.stat-card-content {
    flex: 1;
    min-width: 0;
}

.stat-card-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--spacing-xs);
    line-height: 1.2;
}

.stat-card-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
}

/* Card Section */
.violation-types-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: var(--spacing-lg);
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.card-header-content {
    flex: 1;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.3;
    color: #0f172a;
    margin: 0 0 var(--spacing-xs) 0;
}

.card-subtitle {
    font-size: 0.875rem;
    line-height: 1.5;
    color: #64748b;
    margin: 0;
}

.card-header-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-left: var(--spacing-md);
}

.card-body {
    padding: var(--spacing-lg);
}

/* Filter Form - 8pt spacing */
.filter-form {
    margin-bottom: var(--spacing-lg);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.filter-group-actions {
    display: flex;
    flex-direction: row;
    align-items: flex-end;
    gap: var(--spacing-md);
}

.filter-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.filter-label svg {
    width: 16px;
    height: 16px;
    color: var(--primary-color, #1fb2d5);
    flex-shrink: 0;
}

.input-wrapper {
    position: relative;
}

.form-input,
.form-select {
    width: 100%;
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: 0.875rem;
    line-height: 1.5;
    color: #0f172a;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: var(--radius-md);
    transition: border-color var(--transition-base), box-shadow var(--transition-base);
    touch-action: manipulation;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: var(--primary-color, #1fb2d5);
    box-shadow: var(--focus-ring);
}

.form-input::placeholder {
    color: #94a3b8;
}

.form-input[type="search"] {
    padding-left: var(--spacing-xl);
}

.form-input[type="search"]::-webkit-search-cancel-button {
    appearance: none;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: 0.875rem;
    font-weight: 500;
    line-height: 1.5;
    text-align: center;
    text-decoration: none;
    border: 1px solid transparent;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: background-color var(--transition-base), border-color var(--transition-base), color var(--transition-base), transform var(--transition-base), box-shadow var(--transition-base);
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.btn:focus-visible {
    outline: 2px solid var(--primary-color, #1fb2d5);
    outline-offset: 2px;
}

.btn-secondary {
    background-color: #ffffff;
    border-color: #e2e8f0;
    color: #334155;
}

.btn-secondary:hover {
    background-color: #f8fafc;
    border-color: #cbd5e1;
}

.btn-secondary:active {
    transform: translateY(1px);
}

.btn svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.btn-sm {
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: 0.8125rem;
}

.results-count {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    margin-left: auto;
}

.results-label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--spacing-xs);
}

.results-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color, #1fb2d5);
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

/* Table */
.table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table-responsive {
    position: relative;
}

.table-responsive:focus-visible {
    outline: 2px solid var(--primary-color, #1fb2d5);
    outline-offset: 2px;
    border-radius: var(--radius-md);
}

.violation-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
    line-height: 1.5;
}

.violation-table caption {
    padding: var(--spacing-md);
    text-align: left;
    font-weight: 600;
    color: #334155;
}

.violation-table thead {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
}

.violation-table th {
    padding: var(--spacing-md);
    text-align: left;
    font-size: 0.75rem;
    font-weight: 700;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc;
}

.violation-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background-color var(--transition-base), border-left-color var(--transition-base);
    border-left: 3px solid transparent;
}

.violation-table tbody tr:hover {
    background-color: rgba(31, 178, 213, 0.02);
    border-left-color: var(--primary-color, #1fb2d5);
}

.violation-table tbody tr[data-category="Major"]:hover {
    border-left-color: var(--danger-color, #ef4444);
    background-color: rgba(239, 68, 68, 0.02);
}

.violation-table tbody tr[data-category="Minor"]:hover {
    border-left-color: var(--warning-color, #f59e0b);
    background-color: rgba(245, 158, 11, 0.02);
}

.violation-table td {
    padding: var(--spacing-md);
    vertical-align: middle;
}

.violation-table .col-ref {
    font-family: ui-monospace, monospace;
    font-size: 0.8125rem;
    color: #64748b;
}

.violation-name {
    font-weight: 600;
    color: #0f172a;
}

.subcategory-text,
.offense-text,
.description-text {
    color: #475569;
    font-size: 0.8125rem;
}

.description-text {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1.4;
    border-radius: var(--radius-sm);
    border: 1px solid;
}

.badge svg {
    width: 12px;
    height: 12px;
    flex-shrink: 0;
}

.badge-category.badge-major {
    background-color: rgba(239, 68, 68, 0.1);
    color: var(--danger-color, #ef4444);
    border-color: rgba(239, 68, 68, 0.2);
}

.badge-category.badge-minor {
    background-color: rgba(245, 158, 11, 0.1);
    color: var(--warning-color, #f59e0b);
    border-color: rgba(245, 158, 11, 0.2);
}

.badge-status.badge-compliant,
.badge-status.badge-active {
    background-color: rgba(34, 197, 94, 0.1);
    color: var(--success-color, #22c55e);
    border-color: rgba(34, 197, 94, 0.2);
}

.badge-status.badge-non-compliant,
.badge-status.badge-inactive {
    background-color: rgba(148, 163, 184, 0.1);
    color: #64748b;
    border-color: rgba(148, 163, 184, 0.2);
}

/* Empty State */
.empty-state {
    padding: var(--spacing-3xl) var(--spacing-md);
    text-align: center;
}

.empty-state-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-md);
}

.empty-state-content svg {
    color: #cbd5e1;
    opacity: 0.5;
}

.empty-state-text {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-md);
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-group-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .results-count {
        align-items: flex-start;
        margin-left: 0;
    }
    
    .card-header {
        flex-direction: column;
        gap: var(--spacing-md);
    }
    
    .card-header-actions {
        margin-left: 0;
        width: 100%;
    }
    
    .table-wrapper {
        overflow-x: scroll;
    }
}

/* Animation for rows */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.violation-row {
    animation: fadeIn 200ms ease-out;
}

@media (prefers-reduced-motion: reduce) {
    .violation-row {
        animation: none;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    // Utility: Format number with Intl API
    function formatNumber(num) {
        return new Intl.NumberFormat('en-US').format(num);
    }
    
    // Utility: Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('violationTypesCategoryFilter');
        const ra5487Select = document.getElementById('violationTypesRa5487Filter');
        const activeSelect = document.getElementById('violationTypesActiveFilter');
        const filterForm = document.getElementById('violationTypesFilterForm');
        const searchInput = document.getElementById('violationTypesSearch');
        const tableBody = document.getElementById('violationTypesTableBody');
        const countEl = document.getElementById('violation-types-count');
        const exportBtn = document.getElementById('exportViolationsBtn');
        
        if (!filterForm || !tableBody || !countEl) return;
        
        // Get all violation rows
        const getRows = () => Array.from(tableBody.querySelectorAll('tr.violation-row'));
        
        // Update count with animation (respecting reduced motion)
        function updateCount(count) {
            if (!countEl) return;
            const currentCount = parseInt(countEl.textContent.replace(/,/g, '')) || 0;
            const targetCount = count;
            
            if (currentCount === targetCount) {
                countEl.textContent = formatNumber(targetCount);
                return;
            }
            
            if (prefersReducedMotion) {
                countEl.textContent = formatNumber(targetCount);
                return;
            }
            
            // Animate count change
            const duration = 300;
            const startTime = Date.now();
            const difference = targetCount - currentCount;
            
            function animate() {
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const current = Math.round(currentCount + (difference * easeOutQuart));
                
                countEl.textContent = formatNumber(current);
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    countEl.textContent = formatNumber(targetCount);
                }
            }
            
            animate();
        }
        
        // Filter rows based on search and filters
        function filterRows() {
            const q = (searchInput?.value || '').trim().toLowerCase();
            const categoryFilter = categorySelect?.value || '';
            const ra5487Filter = ra5487Select?.value || '';
            const activeFilter = activeSelect?.value || '';
            
            let visibleCount = 0;
            const rows = getRows();
            
            rows.forEach((row, index) => {
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
                
                const shouldShow = matchesSearch && matchesCategory && matchesRa5487 && matchesActive;
                
                if (shouldShow) {
                    row.style.display = '';
                    if (!prefersReducedMotion) {
                        row.style.opacity = '0';
                        row.style.transform = 'translateY(8px)';
                        requestAnimationFrame(() => {
                            row.style.transition = 'opacity 200ms ease, transform 200ms ease';
                            row.style.opacity = '1';
                            row.style.transform = 'translateY(0)';
                        });
                    }
                    visibleCount++;
                } else {
                    if (!prefersReducedMotion) {
                        row.style.transition = 'opacity 150ms ease';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.style.display = 'none';
                        }, 150);
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
            
            updateCount(visibleCount);
        }
        
        // Server-side filter submission
        function submitFilter() {
            if (filterForm) {
                filterForm.submit();
            }
        }
        
        // Attach event listeners
        if (categorySelect) {
            categorySelect.addEventListener('change', submitFilter);
        }
        
        if (ra5487Select) {
            ra5487Select.addEventListener('change', submitFilter);
        }
        
        if (activeSelect) {
            activeSelect.addEventListener('change', submitFilter);
        }
        
        // Client-side search with debounce
        if (searchInput) {
            const debouncedFilter = debounce(filterRows, 150);
            searchInput.addEventListener('input', debouncedFilter);
            
            // Initial filter if search has value
            if (searchInput.value) {
                filterRows();
            }
        }
        
        // Export button handler
        if (exportBtn) {
            exportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Export functionality placeholder
                console.log('Export functionality to be implemented');
            });
        }
        
        // Keyboard navigation for table
        const tableContainer = document.querySelector('.table-responsive');
        if (tableContainer) {
            tableContainer.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const rows = getRows().filter(r => r.style.display !== 'none');
                    const currentIndex = rows.findIndex(r => r === document.activeElement);
                    let nextIndex;
                    
                    if (e.key === 'ArrowDown') {
                        nextIndex = currentIndex < rows.length - 1 ? currentIndex + 1 : 0;
                    } else {
                        nextIndex = currentIndex > 0 ? currentIndex - 1 : rows.length - 1;
                    }
                    
                    if (rows[nextIndex]) {
                        rows[nextIndex].focus();
                    }
                }
            });
        }
    });
})();
</script>
.stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    transition: width 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    border-color: rgba(31, 178, 213, 0.3);
}

.stat-card:hover::before {
    width: 100%;
    opacity: 0.05;
}

.stat-card-primary::before { background: var(--primary-color, #1fb2d5); }
.stat-card-danger::before { background: var(--danger-color, #ef4444); }
.stat-card-warning::before { background: var(--warning-color, #f59e0b); }
.stat-card-success::before { background: var(--success-color, #22c55e); }

.stat-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.stat-card-primary .stat-card-icon {
    background: linear-gradient(135deg, rgba(31, 178, 213, 0.1) 0%, rgba(31, 178, 213, 0.2) 100%);
    color: var(--primary-color, #1fb2d5);
}

.stat-card-danger .stat-card-icon {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.2) 100%);
    color: var(--danger-color, #ef4444);
}

.stat-card-warning .stat-card-icon {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.2) 100%);
    color: var(--warning-color, #f59e0b);
}

.stat-card-success .stat-card-icon {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.2) 100%);
    color: var(--success-color, #22c55e);
}

.stat-card:hover .stat-card-icon {
    transform: scale(1.1) rotate(5deg);
}

.stat-card-content {
    flex: 1;
    min-width: 0;
}

.stat-card-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}

.stat-card-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

/* Enhanced Card */
.violation-types-card {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    background: #ffffff;
}

.violation-types-card .card-header-modern {
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 2px solid #e2e8f0;
    padding: 1.5rem;
}

.violation-types-card .card-title-modern {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.violation-types-card .card-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 0.25rem;
}

/* Enhanced Filter Section */
.violation-filter-section {
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.filter-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.filter-label i {
    color: var(--primary-color, #1fb2d5);
    font-size: 0.7rem;
}

.input-group-modern {
    position: relative;
}

.input-group-modern .input-group-text {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-right: none;
    color: var(--primary-color, #1fb2d5);
    padding: 0.5rem 0.75rem;
}

.input-group-modern .form-control {
    border-left: none;
    border-color: #e2e8f0;
    transition: all 0.2s ease;
}

.input-group-modern .form-control:focus {
    border-color: var(--primary-color, #1fb2d5);
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

.form-select-sm {
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.form-select-sm:focus {
    border-color: var(--primary-color, #1fb2d5);
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

.filter-results-count {
    text-align: right;
    margin-left: auto;
}

.filter-results-label {
    font-size: 0.6875rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.125rem;
}

.filter-results-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color, #1fb2d5);
    line-height: 1;
}

/* Enhanced Table */
#violationTypesTable {
    margin: 0;
}

#violationTypesTable thead th {
    background: linear-gradient(135deg, #f1f5f9 0%, #f8fafc 100%);
    border-bottom: 2px solid #e2e8f0;
    color: #334155;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 1rem;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
}

#violationTypesTable tbody td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    transition: all 0.2s ease;
}

#violationTypesTable tbody tr {
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

#violationTypesTable tbody tr:hover {
    background: linear-gradient(90deg, rgba(31, 178, 213, 0.03) 0%, rgba(31, 178, 213, 0.01) 100%);
    border-left-color: var(--primary-color, #1fb2d5);
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

#violationTypesTable tbody tr.violation-type-row[data-category="Major"]:hover {
    border-left-color: var(--danger-color, #ef4444);
    background: linear-gradient(90deg, rgba(239, 68, 68, 0.03) 0%, rgba(239, 68, 68, 0.01) 100%);
}

#violationTypesTable tbody tr.violation-type-row[data-category="Minor"]:hover {
    border-left-color: var(--warning-color, #f59e0b);
    background: linear-gradient(90deg, rgba(245, 158, 11, 0.03) 0%, rgba(245, 158, 11, 0.01) 100%);
}

/* Enhanced Badges */
.badge-category {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    border: 1px solid;
    transition: all 0.2s ease;
}

.badge-category.badge-major {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.15) 100%);
    color: var(--danger-color, #ef4444);
    border-color: rgba(239, 68, 68, 0.3);
}

.badge-category.badge-minor {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.15) 100%);
    color: var(--warning-color, #f59e0b);
    border-color: rgba(245, 158, 11, 0.3);
}

.badge-status {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    border: 1px solid;
    transition: all 0.2s ease;
}

.badge-status.badge-compliant,
.badge-status.badge-active {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.15) 100%);
    color: var(--success-color, #22c55e);
    border-color: rgba(34, 197, 94, 0.3);
}

.badge-status.badge-non-compliant,
.badge-status.badge-inactive {
    background: linear-gradient(135deg, rgba(148, 163, 184, 0.1) 0%, rgba(148, 163, 184, 0.15) 100%);
    color: #64748b;
    border-color: rgba(148, 163, 184, 0.3);
}

.badge-category:hover,
.badge-status:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Enhanced Buttons */
.btn-outline-modern {
    border: 1px solid #e2e8f0;
    color: #334155;
    transition: all 0.2s ease;
    border-radius: 8px;
}

.btn-outline-modern:hover {
    background: var(--primary-color, #1fb2d5);
    border-color: var(--primary-color, #1fb2d5);
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.3);
}

/* Breadcrumb Enhancement */
.breadcrumb {
    font-size: 0.875rem;
}

.breadcrumb-item a:hover {
    color: var(--primary-color, #1fb2d5) !important;
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #64748b;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .violation-filter-section .row {
        flex-direction: column;
    }
    
    .filter-results-count {
        text-align: left;
        margin-left: 0;
        margin-top: 1rem;
    }
}

/* Loading Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.violation-type-row {
    animation: fadeIn 0.3s ease-out;
}

/* Empty State Enhancement */
#violationTypesTableBody tr td[colspan] {
    padding: 3rem 1rem;
    text-align: center;
}

#violationTypesTableBody tr td[colspan] i {
    color: #cbd5e1;
    margin-bottom: 1rem;
}

#violationTypesTableBody tr td[colspan] p {
    color: #64748b;
    font-size: 0.875rem;
    margin: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced filter form submission with loading state
    const categorySelect = document.getElementById('violationTypesCategoryFilter');
    const ra5487Select = document.getElementById('violationTypesRa5487Filter');
    const activeSelect = document.getElementById('violationTypesActiveFilter');
    const filterForm = document.getElementById('violationTypesFilterForm');
    
    function submitFilter() {
        // Add loading state
        const submitBtn = filterForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Filtering...';
        }
        filterForm.submit();
    }
    
    if (categorySelect) {
        categorySelect.addEventListener('change', () => {
            submitFilter();
        });
    }
    
    if (ra5487Select) {
        ra5487Select.addEventListener('change', () => {
            submitFilter();
        });
    }
    
    if (activeSelect) {
        activeSelect.addEventListener('change', () => {
            submitFilter();
        });
    }

    // Enhanced client-side search filtering with smooth animations
    function initViolationTypesSearch() {
        const searchInput = document.getElementById('violationTypesSearch');
        const tableBody = document.getElementById('violationTypesTableBody');
        const countEl = document.getElementById('violation-types-count');
        const rows = () => Array.from(tableBody?.querySelectorAll('tr.violation-type-row') || []);

        const updateCount = (count) => {
            if (!countEl) return;
            const currentCount = parseInt(countEl.textContent.replace(/,/g, '')) || 0;
            const targetCount = count;
            
            // Animate count change
            if (currentCount !== targetCount) {
                const duration = 300;
                const startTime = Date.now();
                const difference = targetCount - currentCount;
                
                const animate = () => {
                    const elapsed = Date.now() - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                    const current = Math.round(currentCount + (difference * easeOutQuart));
                    
                    countEl.textContent = current.toLocaleString();
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        countEl.textContent = targetCount.toLocaleString();
                    }
                };
                
                animate();
            }
        };

        const filterRows = () => {
            const q = (searchInput?.value || '').trim().toLowerCase();
            const categoryFilter = categorySelect ? categorySelect.value : '';
            const ra5487Filter = ra5487Select ? ra5487Select.value : '';
            const activeFilter = activeSelect ? activeSelect.value : '';
            
            let visibleCount = 0;
            
            rows().forEach((row, index) => {
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
                
                const shouldShow = matchesSearch && matchesCategory && matchesRa5487 && matchesActive;
                
                if (shouldShow) {
                    row.style.display = '';
                    row.style.opacity = '0';
                    row.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        row.style.opacity = '1';
                        row.style.transform = 'translateY(0)';
                    }, index * 10);
                    visibleCount++;
                } else {
                    row.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-10px)';
                    setTimeout(() => {
                        row.style.display = 'none';
                    }, 200);
                }
            });
            
            updateCount(visibleCount);
        };

        if (searchInput) {
            let searchTimeout;
            
            // Add search icon animation
            const searchIcon = searchInput.parentElement?.querySelector('.input-group-text i');
            
            searchInput.addEventListener('input', () => {
                if (searchIcon) {
                    searchIcon.style.transform = 'rotate(15deg)';
                    setTimeout(() => {
                        searchIcon.style.transform = 'rotate(0deg)';
                        searchIcon.style.transition = 'transform 0.3s ease';
                    }, 100);
                }
                
                window.clearTimeout(searchTimeout);
                searchTimeout = window.setTimeout(() => {
                    filterRows();
                }, 150);
            });
            
            searchInput.addEventListener('focus', () => {
                searchInput.parentElement.style.transform = 'scale(1.02)';
                searchInput.parentElement.style.transition = 'transform 0.2s ease';
            });
            
            searchInput.addEventListener('blur', () => {
                searchInput.parentElement.style.transform = 'scale(1)';
            });
            
            // Trigger initial filter if there's a value
            if (searchInput.value) {
                filterRows();
            }
        }
        
        // Initial count update
        const initialCount = rows().length;
        updateCount(initialCount);
    }
    
    initViolationTypesSearch();
    
    // Add hover effects to stat cards
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Export button handler (placeholder)
    const exportBtn = document.getElementById('exportViolationsBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            // Add export functionality here
            console.log('Export functionality to be implemented');
        });
    }
    
    // Add smooth scroll to table on filter
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer && (categorySelect?.value || ra5487Select?.value || activeSelect?.value)) {
        setTimeout(() => {
            tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
});
</script>
