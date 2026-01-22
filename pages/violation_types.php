<?php
$page_title = 'Violation Types & Sanctions - Golden Z-5 HR System';
$page = 'violation_types';

// Get database connection
$pdo = get_db_connection();

// Fetch all violation types from database
try {
    $stmt = $pdo->query("
        SELECT 
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
            created_at
        FROM violation_types
        WHERE is_active = 1
        ORDER BY 
            CASE category
                WHEN 'Major' THEN 1
                WHEN 'Minor' THEN 2
                ELSE 3
            END,
            subcategory,
            CAST(SUBSTRING_INDEX(reference_no, '.', 1) AS UNSIGNED),
            reference_no
    ");
    $all_violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching violation types: " . $e->getMessage());
    $all_violations = [];
}

// Separate violations by category and type
$major_violations = [];
$minor_violations = [];
$ra5487_offenses = [];

foreach ($all_violations as $violation) {
    if (!empty($violation['subcategory'])) {
        // RA 5487 Offenses (A, B, C, D)
        $ra5487_offenses[] = $violation;
    } elseif ($violation['category'] === 'Major') {
        // Major Violations (reference 1-28)
        $major_violations[] = $violation;
    } elseif ($violation['category'] === 'Minor') {
        // Minor Violations (reference MIN-1 to MIN-30)
        $minor_violations[] = $violation;
    }
}

// Group RA 5487 offenses by subcategory
$ra5487_by_category = [];
foreach ($ra5487_offenses as $offense) {
    $subcat = $offense['subcategory'];
    if (!isset($ra5487_by_category[$subcat])) {
        $ra5487_by_category[$subcat] = [];
    }
    $ra5487_by_category[$subcat][] = $offense;
}

// Function to categorize violations by topic
function get_violation_category($name, $description) {
    $name_lower = strtolower($name);
    $desc_lower = strtolower($description ?? '');
    $combined = $name_lower . ' ' . $desc_lower;
    
    // Uniform and Appearance
    if (preg_match('/\b(uniform|dress|appearance|id card|badge|patches|insignia|paraphernalia|neat)\b/i', $combined)) {
        return 'Uniform & Appearance';
    }
    
    // Attendance and Punctuality
    if (preg_match('/\b(attendance|tardiness|late|absent|awol|leave|return to work|report for duty|duty assignment)\b/i', $combined)) {
        return 'Attendance & Punctuality';
    }
    
    // Safety and Security
    if (preg_match('/\b(safety|security|fire|alarm|extinguisher|weapon|firearm|ammunition|threat|violence|endanger|protect)\b/i', $combined)) {
        return 'Safety & Security';
    }
    
    // Property and Equipment
    if (preg_match('/\b(property|equipment|telephone|company.*property|client.*property|selling|disposing|theft|stealing|malversation|funds)\b/i', $combined)) {
        return 'Property & Equipment';
    }
    
    // Conduct and Behavior
    if (preg_match('/\b(conduct|behavior|disrespect|disobey|insubordination|fighting|quarrel|assault|provoke|threaten|intimidate|coerce|disturb)\b/i', $combined)) {
        return 'Conduct & Behavior';
    }
    
    // Duty Performance
    if (preg_match('/\b(duty|performance|neglect|sleeping|post|guard|relief|relieved|formation|mounting|meeting|training)\b/i', $combined)) {
        return 'Duty Performance';
    }
    
    // Communication and Reporting
    if (preg_match('/\b(communication|report|notify|conversation|confidential|information|disclosure|reveal)\b/i', $combined)) {
        return 'Communication & Reporting';
    }
    
    // Substance Abuse
    if (preg_match('/\b(drug|alcohol|drink|drunken|intoxicating|prohibited|substance)\b/i', $combined)) {
        return 'Substance Abuse';
    }
    
    // Ethics and Integrity
    if (preg_match('/\b(ethics|integrity|honest|dishonest|fraud|bribery|kickback|compromise|criminal|lawless)\b/i', $combined)) {
        return 'Ethics & Integrity';
    }
    
    // General Orders and Regulations
    if (preg_match('/\b(order|regulation|rule|policy|law|ra 5487|general order|creed|code)\b/i', $combined)) {
        return 'Orders & Regulations';
    }
    
    // Default category
    return 'General Violation';
}

// Statistics
$total_violations = count($all_violations);
$total_major = count($major_violations);
$total_minor = count($minor_violations);
$total_ra5487 = count($ra5487_offenses);

// Handle add violation form submission (AJAX)
$add_violation_errors = [];
$add_violation_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_violation') {
    // Validate required fields
    if (empty(trim($_POST['name'] ?? ''))) {
        $add_violation_errors[] = 'Violation name is required.';
    }
    
    if (empty($_POST['category'] ?? '') || !in_array($_POST['category'], ['Major', 'Minor'])) {
        $add_violation_errors[] = 'Category is required and must be Major or Minor.';
    }
    
    // Auto-generate reference number if not provided
    $reference_no = trim($_POST['reference_no'] ?? '');
    if (empty($reference_no)) {
        $category = $_POST['category'];
        $subcategory = trim($_POST['subcategory'] ?? '');
        
        if (!empty($subcategory)) {
            try {
                $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(reference_no, '.', -1) AS UNSIGNED)) AS max_num 
                                       FROM violation_types 
                                       WHERE subcategory = ?");
                $stmt->execute([$subcategory]);
                $row = $stmt->fetch();
                $maxNum = $row['max_num'] ?? 0;
                $reference_no = $subcategory . '.' . ($maxNum + 1);
            } catch (Exception $e) {
                $reference_no = $subcategory . '.1';
            }
        } else {
            $prefix = $category === 'Major' ? 'MAJ' : 'MIN';
            try {
                $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(reference_no, '-', -1) AS UNSIGNED)) AS max_num 
                                       FROM violation_types 
                                       WHERE category = ? AND subcategory IS NULL");
                $stmt->execute([$category]);
                $row = $stmt->fetch();
                $maxNum = $row['max_num'] ?? 0;
                $reference_no = $prefix . '-' . ($maxNum + 1);
            } catch (Exception $e) {
                $reference_no = $prefix . '-1';
            }
        }
    }
    
    // Check if reference number already exists
    if (!empty($reference_no)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM violation_types WHERE reference_no = ?");
            $stmt->execute([$reference_no]);
            if ($stmt->fetch()) {
                $add_violation_errors[] = 'Reference number already exists. Please provide a different one.';
            }
        } catch (Exception $e) {
            // Ignore check if query fails
        }
    }
    
    // If no errors, insert the violation
    if (empty($add_violation_errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO violation_types (
                    reference_no, name, category, subcategory, description,
                    first_offense, second_offense, third_offense, fourth_offense, fifth_offense,
                    ra5487_compliant, is_active
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?
                )
            ");
            
            $ra5487_compliant = isset($_POST['ra5487_compliant']) ? 1 : 0;
            $is_active = isset($_POST['is_active']) ? 1 : 1;
            
            $stmt->execute([
                $reference_no,
                trim($_POST['name']),
                $_POST['category'],
                !empty(trim($_POST['subcategory'] ?? '')) ? trim($_POST['subcategory']) : null,
                !empty(trim($_POST['description'] ?? '')) ? trim($_POST['description']) : null,
                !empty(trim($_POST['first_offense'] ?? '')) ? trim($_POST['first_offense']) : null,
                !empty(trim($_POST['second_offense'] ?? '')) ? trim($_POST['second_offense']) : null,
                !empty(trim($_POST['third_offense'] ?? '')) ? trim($_POST['third_offense']) : null,
                !empty(trim($_POST['fourth_offense'] ?? '')) ? trim($_POST['fourth_offense']) : null,
                !empty(trim($_POST['fifth_offense'] ?? '')) ? trim($_POST['fifth_offense']) : null,
                $ra5487_compliant,
                $is_active
            ]);
            
            $add_violation_success = true;
            
            // If AJAX request, return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Violation type added successfully!']);
                exit;
            }
            
            // Regular form submission - redirect
            header('Location: ?page=violation_types&success=created');
            exit;
        } catch (PDOException $e) {
            $add_violation_errors[] = 'Error saving violation: ' . $e->getMessage();
            error_log("Error adding violation: " . $e->getMessage());
            
            // If AJAX request, return JSON error
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $add_violation_errors]);
                exit;
            }
        }
    } else {
        // If AJAX request, return JSON errors
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $add_violation_errors]);
            exit;
        }
    }
}

// Fetch violation edit history from audit logs
$violation_history = [];
try {
    $history_stmt = $pdo->query("
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
        ORDER BY al.created_at DESC
        LIMIT 100
    ");
    $violation_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching violation history: " . $e->getMessage());
    $violation_history = [];
}

// MOCK DATA FOR UI CONFIGURATION - Remove this when ready to use real data
// Uncomment the line below to use mock data instead of real audit logs
$use_mock_data = true; // Set to false to use real data

if ($use_mock_data && empty($violation_history)) {
    $violation_history = [
        [
            'id' => 1,
            'user_id' => 1,
            'action' => 'UPDATE',
            'table_name' => 'violation_types',
            'record_id' => 10,
            'old_values' => json_encode([
                'name' => 'Sleeping on post during office or working hours',
                'first_offense' => '15 days suspension',
                'second_offense' => '30 days suspension',
                'third_offense' => 'Dismissal'
            ]),
            'new_values' => json_encode([
                'name' => 'Sleeping on post during office or working hours',
                'first_offense' => '30 days suspension',
                'second_offense' => 'Dismissal',
                'third_offense' => 'Dismissal'
            ]),
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'username' => 'admin',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'reference_no' => 'MAJ-15',
            'violation_name' => 'Sleeping on post during office or working hours'
        ],
        [
            'id' => 2,
            'user_id' => 2,
            'action' => 'UPDATE',
            'table_name' => 'violation_types',
            'record_id' => 5,
            'old_values' => json_encode([
                'name' => 'Insubordination, disrespect, disobedience',
                'first_offense' => '7 days suspension',
                'second_offense' => '15 days suspension'
            ]),
            'new_values' => json_encode([
                'name' => 'Insubordination, disrespect, disobedience, or willfully and intentionally refusing to obey superior\'s legal order to perform task',
                'first_offense' => '15 days suspension',
                'second_offense' => '30 days suspension',
                'third_offense' => 'Dismissal'
            ]),
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'username' => 'hr_manager',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'reference_no' => 'MIN-1',
            'violation_name' => 'Insubordination, disrespect, disobedience'
        ],
        [
            'id' => 3,
            'user_id' => 1,
            'action' => 'INSERT',
            'table_name' => 'violation_types',
            'record_id' => 45,
            'old_values' => null,
            'new_values' => json_encode([
                'reference_no' => 'B.16',
                'name' => 'He shall maintain proper grooming standards at all times',
                'first_offense' => '7 days suspension',
                'second_offense' => '15 days suspension',
                'third_offense' => '30 days suspension',
                'fourth_offense' => 'Dismissal'
            ]),
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
            'username' => 'admin',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'reference_no' => 'B.16',
            'violation_name' => 'He shall maintain proper grooming standards at all times'
        ],
        [
            'id' => 4,
            'user_id' => 3,
            'action' => 'UPDATE',
            'table_name' => 'violation_types',
            'record_id' => 22,
            'old_values' => json_encode([
                'name' => 'Unauthorized use of company\'s telephone',
                'first_offense' => '15 days suspension',
                'second_offense' => '30 days suspension'
            ]),
            'new_values' => json_encode([
                'name' => 'Unauthorized use of company\'s telephone for national and overseas long distance call and other personal calls',
                'first_offense' => '15 days suspension & Payment for the entire billing acquired',
                'second_offense' => '30 days suspension & Payment for the entire billing acquired',
                'third_offense' => 'Payment for the entire billing acquired & Dismissal'
            ]),
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'username' => 'supervisor',
            'first_name' => 'Michael',
            'last_name' => 'Johnson',
            'reference_no' => 'MIN-22',
            'violation_name' => 'Unauthorized use of company\'s telephone'
        ],
        [
            'id' => 5,
            'user_id' => 1,
            'action' => 'UPDATE',
            'table_name' => 'violation_types',
            'record_id' => 8,
            'old_values' => json_encode([
                'name' => 'Challenging or assaulting co-employees',
                'first_offense' => 'Dismissal'
            ]),
            'new_values' => json_encode([
                'name' => 'Challenging or assaulting co-employees, clients, clients authorized representatives, client\'s children and legally adopted children and relatives and or company\'s officers',
                'first_offense' => 'Dismissal'
            ]),
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
            'username' => 'admin',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'reference_no' => 'MAJ-8',
            'violation_name' => 'Challenging or assaulting co-employees'
        ],
        [
            'id' => 6,
            'user_id' => 2,
            'action' => 'DELETE',
            'table_name' => 'violation_types',
            'record_id' => 50,
            'old_values' => json_encode([
                'reference_no' => 'C.13',
                'name' => 'He shall not engage in any unauthorized activities',
                'first_offense' => '15 days suspension',
                'second_offense' => 'Dismissal'
            ]),
            'new_values' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 weeks')),
            'username' => 'hr_manager',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'reference_no' => 'C.13',
            'violation_name' => 'He shall not engage in any unauthorized activities'
        ],
        [
            'id' => 7,
            'user_id' => 1,
            'action' => 'UPDATE',
            'table_name' => 'violation_types',
            'record_id' => 12,
            'old_values' => json_encode([
                'name' => 'Leaving or abandoning the place of works',
                'first_offense' => '15 days suspension',
                'second_offense' => 'Dismissal'
            ]),
            'new_values' => json_encode([
                'name' => 'Leaving or abandoning the place of works without the authorization from the immediate superior',
                'first_offense' => '30 days suspension',
                'second_offense' => 'Dismissal'
            ]),
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'username' => 'admin',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'reference_no' => 'MAJ-12',
            'violation_name' => 'Leaving or abandoning the place of works'
        ],
        [
            'id' => 8,
            'user_id' => 3,
            'action' => 'INSERT',
            'table_name' => 'violation_types',
            'record_id' => 46,
            'old_values' => null,
            'new_values' => json_encode([
                'reference_no' => 'D.12',
                'name' => 'To maintain constant vigilance and report any suspicious activities immediately',
                'first_offense' => '7 days suspension',
                'second_offense' => '15 days suspension',
                'third_offense' => '30 days suspension',
                'fourth_offense' => 'Dismissal'
            ]),
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'username' => 'supervisor',
            'first_name' => 'Michael',
            'last_name' => 'Johnson',
            'reference_no' => 'D.12',
            'violation_name' => 'To maintain constant vigilance and report any suspicious activities immediately'
        ]
    ];
}

// Get list of edited violation IDs for icon display
$edited_violation_ids = [];
foreach ($violation_history as $history) {
    if ($history['record_id'] && $history['action'] === 'UPDATE') {
        $edited_violation_ids[$history['record_id']] = true;
    }
}
?>

<div class="container-fluid hrdash">
    <!-- Statistics Cards - Ordered by Escalation: Minor → Major → RA 5487 -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Total Violations</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo $total_violations; ?></div>
                </div>
                <div class="hrdash-stat__meta">RA 5487 Compliant</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Minor Violations</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-warning"><?php echo $total_minor; ?></div>
                </div>
                <div class="hrdash-stat__meta">Progressive discipline (1st level)</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Major Violations</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-danger"><?php echo $total_major; ?></div>
                </div>
                <div class="hrdash-stat__meta">Serious offenses (2nd level)</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">RA 5487 Offenses</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-info"><?php echo $total_ra5487; ?></div>
                </div>
                <div class="hrdash-stat__meta">Compliance violations (3rd level)</div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Container - Above Violation Types Card -->
    <div class="card mb-4" style="border: 1px solid #e5e7eb; background: #ffffff;">
        <div class="card-body p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="violation-search" class="form-label small text-muted mb-1">Search All Violations</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           id="violation-search" 
                           placeholder="Search by reference, name, or category...">
                </div>
                <div class="col-md-3">
                    <label for="violation-category-filter" class="form-label small text-muted mb-1">Filter by Category</label>
                    <select class="form-select form-select-sm" id="violation-category-filter">
                        <option value="">All Categories</option>
                        <option value="Uniform & Appearance">Uniform & Appearance</option>
                        <option value="Attendance & Punctuality">Attendance & Punctuality</option>
                        <option value="Safety & Security">Safety & Security</option>
                        <option value="Property & Equipment">Property & Equipment</option>
                        <option value="Conduct & Behavior">Conduct & Behavior</option>
                        <option value="Duty Performance">Duty Performance</option>
                        <option value="Communication & Reporting">Communication & Reporting</option>
                        <option value="Substance Abuse">Substance Abuse</option>
                        <option value="Ethics & Integrity">Ethics & Integrity</option>
                        <option value="Orders & Regulations">Orders & Regulations</option>
                        <option value="General Violation">General Violation</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="clear-filters">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </button>
                </div>
                <div class="col-md-2 text-end">
                    <div class="small text-muted mb-1">Results</div>
                    <div class="fw-bold" id="violation-count">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Violation Types Watchlist with Tabs -->
    <div class="card hrdash-card hrdash-license">
        <div class="hrdash-card__header hrdash-card__header--split">
            <div>
                <h5 class="hrdash-card__title">Violation Types & Sanctions</h5>
                <div class="hrdash-card__subtitle">Escalation: Minor → Major → RA 5487 (Progressive sanctions)</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="?page=violation_history" class="hrdash-welcome__icon-btn violation-history-icon-btn-custom" title="Violation History" aria-label="Violation History">
                    <i class="fas fa-history"></i>
                </a>
                <div class="hrdash-segment" role="tablist" aria-label="Violation types">
                    <button class="hrdash-segment__btn active" type="button" data-target="minor" data-tab="minor">
                        Minor
                    </button>
                    <button class="hrdash-segment__btn" type="button" data-target="major" data-tab="major">
                        Major
                    </button>
                    <button class="hrdash-segment__btn" type="button" data-target="ra5487" data-tab="ra5487">
                        RA 5487
                    </button>
                </div>
                <button type="button" class="btn btn-primary-modern" id="add-violation-btn" data-bs-toggle="modal" data-bs-target="#addViolationModal" title="Add New Violation" aria-label="Add New Violation">
                    <i class="fas fa-plus me-2"></i>Add Violation
                </button>
            </div>
        </div>

        <div class="hrdash-license__body">

            <!-- Minor Violations Tab Content (First - Least Severe) -->
            <div class="violation-tab-content" id="minor-content" style="display: block;">
                <div class="table-responsive violation-table-wrapper">
                    <table class="table table-hover align-middle mb-0 violation-table">
                        <thead>
                            <tr>
                                <th style="width: 10%; min-width: 80px;">Ref #</th>
                                <th style="width: 41%;">Description</th>
                                <th style="width: 10%;">1st Offense</th>
                                <th style="width: 10%;">2nd Offense</th>
                                <th style="width: 10%;">3rd Offense</th>
                                <th style="width: 10%;">4th Offense</th>
                                <th style="width: 9%;">5th Offense</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($minor_violations)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox me-2"></i>No minor violations found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($minor_violations as $type): ?>
                                    <tr>
                                        <td class="ref-no-cell">
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="text-muted small"><?php echo htmlspecialchars($type['reference_no'] ?? 'N/A'); ?></span>
                                                <?php if (isset($edited_violation_ids[$type['id']])): ?>
                                                    <a href="?page=violation_history&violation_id=<?php echo $type['id']; ?>" 
                                                       class="text-primary small" 
                                                       style="text-decoration: none; font-size: 0.7rem;" 
                                                       title="View violation history">
                                                        <i class="fas fa-history"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-muted small mb-1"><?php echo htmlspecialchars(get_violation_category($type['name'], $type['description'])); ?></div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($type['name']); ?></div>
                                        </td>
                                        <td class="small">
                                            <?php if (!empty($type['first_offense'])): ?>
                                                <span class="badge violation-badge violation-badge--minor violation-badge--1st"><?php echo htmlspecialchars($type['first_offense']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <?php if (!empty($type['second_offense'])): ?>
                                                <span class="badge violation-badge violation-badge--minor violation-badge--2nd"><?php echo htmlspecialchars($type['second_offense']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <?php if (!empty($type['third_offense'])): ?>
                                                <span class="badge violation-badge violation-badge--minor violation-badge--3rd"><?php echo htmlspecialchars($type['third_offense']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <?php if (!empty($type['fourth_offense'])): ?>
                                                <span class="badge violation-badge violation-badge--minor violation-badge--4th"><?php echo htmlspecialchars($type['fourth_offense']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <?php if (!empty($type['fifth_offense'])): ?>
                                                <span class="badge violation-badge violation-badge--minor violation-badge--5th"><?php echo htmlspecialchars($type['fifth_offense']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Major Violations Tab Content (Second - More Severe) -->
            <div class="violation-tab-content" id="major-content" style="display: none;">
                <div class="table-responsive violation-table-wrapper">
                    <table class="table table-hover align-middle mb-0 violation-table">
                        <thead>
                            <tr>
                                <th style="width: 10%; min-width: 80px;">Ref #</th>
                                <th style="width: 41%;">Description</th>
                                <th style="width: 10%;">1st Offense</th>
                                <th style="width: 10%;">2nd Offense</th>
                                <th style="width: 10%;">3rd Offense</th>
                                <th style="width: 10%;">4th Offense</th>
                                <th style="width: 9%;">5th Offense</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($major_violations)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox me-2"></i>No major violations found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($major_violations as $type): ?>
                                    <tr>
                                        <td class="ref-no-cell">
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="text-muted small"><?php echo htmlspecialchars($type['reference_no'] ?? 'N/A'); ?></span>
                                                <?php if (isset($edited_violation_ids[$type['id']])): ?>
                                                    <a href="?page=violation_history&violation_id=<?php echo $type['id']; ?>" 
                                                       class="text-primary small" 
                                                       style="text-decoration: none; font-size: 0.7rem;" 
                                                       title="View violation history">
                                                        <i class="fas fa-history"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-muted small mb-1"><?php echo htmlspecialchars(get_violation_category($type['name'], $type['description'])); ?></div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($type['name']); ?></div>
                                        </td>
                                    <td class="small">
                                        <?php if (!empty($type['first_offense'])): ?>
                                            <span class="badge violation-badge violation-badge--1st"><?php echo htmlspecialchars($type['first_offense']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php if (!empty($type['second_offense'])): ?>
                                            <span class="badge violation-badge violation-badge--2nd"><?php echo htmlspecialchars($type['second_offense']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php if (!empty($type['third_offense'])): ?>
                                            <span class="badge violation-badge violation-badge--3rd"><?php echo htmlspecialchars($type['third_offense']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php if (!empty($type['fourth_offense'])): ?>
                                            <span class="badge violation-badge violation-badge--4th"><?php echo htmlspecialchars($type['fourth_offense']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php if (!empty($type['fifth_offense'])): ?>
                                            <span class="badge violation-badge violation-badge--5th"><?php echo htmlspecialchars($type['fifth_offense']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RA 5487 Offenses Tab Content (Third - Compliance/Legal Framework) -->
            <div class="violation-tab-content" id="ra5487-content" style="display: none;">
                <?php if (empty($ra5487_offenses)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-2x mb-3"></i>
                        <p>No RA 5487 offenses found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ra5487_by_category as $subcategory => $offenses): ?>
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3" style="color: #000000;">
                                <i class="fas fa-file-alt me-2"></i><?php echo htmlspecialchars($subcategory); ?>
                            </h6>
                            <div class="table-responsive violation-table-wrapper">
                                <table class="table table-hover align-middle mb-4 violation-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 10%; min-width: 80px;">Ref #</th>
                                            <th style="width: 41%;">Description</th>
                                            <th style="width: 10%;">1st Offense</th>
                                            <th style="width: 10%;">2nd Offense</th>
                                            <th style="width: 10%;">3rd Offense</th>
                                            <th style="width: 10%;">4th Offense</th>
                                            <th style="width: 9%;">5th Offense</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($offenses as $offense): ?>
                                            <tr>
                                                <td class="ref-no-cell">
                                                    <div class="d-flex align-items-center gap-1">
                                                        <span class="text-muted small"><?php echo htmlspecialchars($offense['reference_no'] ?? 'N/A'); ?></span>
                                                        <?php if (isset($edited_violation_ids[$offense['id']])): ?>
                                                            <a href="?page=violation_history&violation_id=<?php echo $offense['id']; ?>" 
                                                               class="text-primary small" 
                                                               style="text-decoration: none; font-size: 0.7rem;" 
                                                               title="View violation history">
                                                                <i class="fas fa-history"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-muted small mb-1"><?php echo htmlspecialchars(get_violation_category($offense['name'], $offense['description'])); ?></div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($offense['name']); ?></div>
                                                </td>
                                                <td class="small">
                                                    <?php if (!empty($offense['first_offense'])): ?>
                                                        <span class="badge violation-badge violation-badge--ra5487 violation-badge--1st"><?php echo htmlspecialchars($offense['first_offense']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small">
                                                    <?php if (!empty($offense['second_offense'])): ?>
                                                        <span class="badge violation-badge violation-badge--ra5487 violation-badge--2nd"><?php echo htmlspecialchars($offense['second_offense']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small">
                                                    <?php if (!empty($offense['third_offense'])): ?>
                                                        <span class="badge violation-badge violation-badge--ra5487 violation-badge--3rd"><?php echo htmlspecialchars($offense['third_offense']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small">
                                                    <?php if (!empty($offense['fourth_offense'])): ?>
                                                        <span class="badge violation-badge violation-badge--ra5487 violation-badge--4th"><?php echo htmlspecialchars($offense['fourth_offense']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small">
                                                    <?php if (!empty($offense['fifth_offense'])): ?>
                                                        <span class="badge violation-badge violation-badge--ra5487 violation-badge--5th"><?php echo htmlspecialchars($offense['fifth_offense']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Tab Segment Styles - Exact Match from Dashboard */
.hrdash-segment {
    display: inline-flex;
    border: none;
    border-radius: 999px;
    overflow: hidden;
    background: #f1f5f9;
    padding: 0.25rem;
    gap: 0.25rem;
    flex-wrap: wrap;
}

/* Remove background from history icon button */
.violation-history-icon-btn-custom {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}

.violation-history-icon-btn-custom:hover {
    background: transparent !important;
}

.violation-history-icon-btn-custom i {
    color: #64748b !important;
}

.violation-history-icon-btn-custom:hover i {
    color: #475569 !important;
}

.hrdash-segment__btn {
    border: 0;
    background: transparent;
    padding: 0.5rem 1rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: #64748b;
    border-radius: 999px;
    transition: all 0.2s ease;
    white-space: nowrap;
    cursor: pointer;
}

.hrdash-segment__btn:hover {
    color: #475569;
}

.hrdash-segment__btn.active {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%);
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(30, 58, 138, 0.2);
}

/* Add Violation Button - Using btn-primary-modern class (matching Add Employee button) */
.btn-primary-modern {
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    color: #ffffff;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.25);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.btn-primary-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.35);
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: #ffffff;
}

.btn-primary-modern:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.25);
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
}

.btn-primary-modern:focus {
    outline: none;
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.25), 0 0 0 3px rgba(31, 178, 213, 0.2);
}

.hrdash-card__header--split {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    flex-wrap: wrap;
    gap: 1rem;
}

.hrdash-card__title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 0.25rem 0;
}

.hrdash-card__subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

.hrdash-license__body {
    padding: 1.5rem;
    overflow-x: auto;
}

.violation-tab-content {
    animation: fadeIn 0.3s ease-in;
    width: 100%;
}

.violation-table-wrapper {
    width: 100%;
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
    position: relative;
}

.violation-table-wrapper table {
    overflow: visible;
}

/* Search and Filter Container Styles */
#violation-search,
#violation-category-filter {
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    transition: border-color 0.2s ease;
}

#violation-search:focus,
#violation-category-filter:focus {
    border-color: #1e3a8a;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    outline: none;
}

#violation-count {
    color: #1e3a8a;
    font-size: 1.25rem;
}

#clear-filters:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

/* Responsive Search/Filter */
@media (max-width: 768px) {
    .card-body .row {
        margin: 0;
    }
    
    .card-body .col-md-4,
    .card-body .col-md-3,
    .card-body .col-md-2 {
        margin-bottom: 0.75rem;
    }
    
    #violation-count {
        font-size: 1rem;
    }
}

.violation-table {
    width: 100%;
    min-width: 800px;
    margin-bottom: 0;
}

.violation-table th {
    white-space: nowrap;
    vertical-align: middle;
    background-color: #f8fafc;
    font-weight: 600;
}

.violation-table td {
    vertical-align: middle;
    word-wrap: break-word;
    overflow: visible;
}

.violation-table td:first-child,
.violation-table td.ref-no-cell {
    white-space: nowrap;
    min-width: 100px;
    width: 10%;
    overflow: visible;
    text-overflow: clip;
    padding: 0.75rem 0.5rem;
}

.violation-table th:first-child {
    min-width: 100px;
    width: 10%;
}

.violation-table td.small {
    padding: 1rem 0.5rem;
    white-space: normal;
    min-width: 120px;
    overflow: visible;
    min-height: 3.5rem;
}

/* Ensure badges in table cells maintain horizontal shape */
.violation-table td.small .violation-badge {
    max-width: 300px;
    width: fit-content;
    display: inline-flex;
    padding: 0.5rem 0.95rem;
    font-size: 0.65rem;
    max-height: none;
    overflow: visible;
}


@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Violation Badge Styles - Plain Text (No Background Colors) */
.violation-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1rem;
    border-radius: 0;
    font-size: 0.68rem;
    font-weight: 500;
    line-height: 1.3;
    border: none;
    background-color: transparent;
    min-width: 100px;
    max-width: 280px;
    min-height: 1.75rem;
    text-align: center;
    overflow-wrap: break-word;
    word-break: break-word;
    white-space: normal;
    hyphens: auto;
    word-spacing: 0.03em;
    box-sizing: border-box;
    vertical-align: middle;
    transition: none;
    width: fit-content;
    overflow: visible;
    color: #374151;
}

.violation-badge:hover {
    transform: none;
    box-shadow: none;
}

/* All violation badges - plain text, no colors */
.violation-badge--1st,
.violation-badge--2nd,
.violation-badge--3rd,
.violation-badge--4th,
.violation-badge--5th,
.violation-badge--minor.violation-badge--1st,
.violation-badge--minor.violation-badge--2nd,
.violation-badge--minor.violation-badge--3rd,
.violation-badge--minor.violation-badge--4th,
.violation-badge--minor.violation-badge--5th,
.violation-badge--ra5487.violation-badge--1st,
.violation-badge--ra5487.violation-badge--2nd,
.violation-badge--ra5487.violation-badge--3rd,
.violation-badge--ra5487.violation-badge--4th,
.violation-badge--ra5487.violation-badge--5th {
    background-color: transparent;
    color: #374151;
    border: none;
}

/* Statistics Cards Responsive */
@media (max-width: 1200px) {
    .hrdash-stat {
        margin-bottom: 1rem;
    }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .hrdash-license__body {
        padding: 1rem;
    }
    
    .hrdash-card__header--split {
        padding: 1rem;
    }
    
    .violation-table {
        min-width: 750px;
        font-size: 0.875rem;
    }
    
    .violation-badge {
        font-size: 0.65rem;
        padding: 0.4rem 0.85rem;
        min-width: 90px;
        max-width: 250px;
        line-height: 1.3;
    }
}

@media (max-width: 992px) {
    .hrdash-card__header--split {
        flex-direction: column;
        align-items: stretch;
    }
    
    .hrdash-segment {
        width: 100%;
        justify-content: center;
        margin-top: 0.5rem;
    }
    
    .hrdash-segment__btn {
        flex: 1;
        min-width: 0;
    }
    
    #add-violation-btn {
        width: 100%;
        margin-top: 0.5rem;
        justify-content: center;
    }
    
    .violation-tab-content {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .violation-table {
        min-width: 800px;
        font-size: 0.813rem;
    }
    
    .violation-table th,
    .violation-table td {
        padding: 0.5rem 0.375rem;
    }
}

@media (max-width: 768px) {
    .hrdash-license__body {
        padding: 0.75rem;
    }
    
    .hrdash-card__header--split {
        padding: 0.75rem;
    }
    
    .hrdash-card__title {
        font-size: 1rem;
    }
    
    .hrdash-card__subtitle {
        font-size: 0.813rem;
    }
    
    .hrdash-segment__btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.813rem;
    }
    
    .violation-table {
        min-width: 700px;
        font-size: 0.75rem;
    }
    
    .violation-table th,
    .violation-table td {
        padding: 0.375rem 0.25rem;
    }
    
    .violation-badge {
        font-size: 0.63rem;
        padding: 0.35rem 0.75rem;
        min-width: 80px;
        max-width: 220px;
        line-height: 1.3;
    }
    
    .violation-table .badge {
        font-size: 0.7rem;
    }
}

@media (max-width: 576px) {
    .hrdash-license__body {
        padding: 0.5rem;
    }
    
    .hrdash-card__header--split {
        padding: 0.5rem;
    }
    
    .hrdash-segment {
        flex-direction: column;
        width: 100%;
    }
    
    .hrdash-segment__btn {
        width: 100%;
        border-radius: 0.375rem;
    }
    
    #add-violation-btn {
        width: 100%;
        margin-top: 0.5rem;
        justify-content: center;
    }
    
    .hrdash-card__header--split {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .hrdash-card__header--split > div:last-child {
        width: 100%;
    }
    
    .violation-table {
        min-width: 600px;
    }
    
    .violation-table th {
        font-size: 0.7rem;
        padding: 0.375rem 0.25rem;
    }
    
    .violation-table td {
        font-size: 0.7rem;
        padding: 0.375rem 0.25rem;
    }
    
    .violation-badge {
        font-size: 0.6rem;
        padding: 0.35rem 0.6rem;
        min-width: 70px;
        max-width: 200px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        line-height: 1.3;
    }
}

/* Landscape Orientation */
@media (orientation: landscape) and (max-height: 600px) {
    .hrdash-license__body {
        padding: 0.75rem;
    }
    
    .violation-table {
        font-size: 0.75rem;
        min-width: 750px;
    }
    
    .violation-badge {
        font-size: 0.63rem;
        padding: 0.4rem 0.8rem;
        min-width: 85px;
        max-width: 240px;
        line-height: 1.3;
    }
    
    .violation-table th,
    .violation-table td {
        padding: 0.375rem 0.25rem;
    }
}

/* Print Styles */
@media print {
    .hrdash-segment {
        display: none;
    }
    
    .violation-tab-content {
        display: block !important;
        page-break-inside: avoid;
    }
    
    .violation-badge {
        border: 1px solid #000;
        background: #ffffff !important;
        color: #000 !important;
    }
}
</style>

<script>
// ============================================================================
// VIOLATION HISTORY FUNCTIONALITY MOVED TO SEPARATE PAGE (violation_history.php)
// ============================================================================

// Add Violation Form Handler
document.addEventListener('DOMContentLoaded', function() {
    const addViolationForm = document.getElementById('addViolationForm');
    if (addViolationForm) {
        addViolationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(addViolationForm);
            const errorsDiv = document.getElementById('addViolationErrors');
            const errorsList = document.getElementById('addViolationErrorsList');
            
            // Hide previous errors
            errorsDiv.style.display = 'none';
            errorsList.innerHTML = '';
            
            // Submit via AJAX
            fetch('?page=violation_types', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addViolationModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Show success message and reload page
                    alert('Violation type added successfully!');
                    window.location.reload();
                } else {
                    // Show errors
                    if (data.errors && data.errors.length > 0) {
                        errorsList.innerHTML = data.errors.map(error => `<li>${error}</li>`).join('');
                        errorsDiv.style.display = 'block';
                    } else {
                        alert('Error: ' + (data.message || 'Failed to add violation type'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the form. Please try again.');
            });
        });
    }
    
    // Reset form when modal is closed
    const addViolationModal = document.getElementById('addViolationModal');
    if (addViolationModal) {
        addViolationModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('addViolationForm');
            if (form) {
                form.reset();
                document.getElementById('addViolationErrors').style.display = 'none';
            }
        });
    }
});

// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.hrdash-segment__btn');
    const tabContents = document.querySelectorAll('.violation-tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            // Show selected tab content
            const targetContent = document.getElementById(targetTab + '-content');
            if (targetContent) {
                targetContent.style.display = 'block';
                // Update count after tab switch (filters remain applied)
                setTimeout(() => {
                    updateViolationCount();
                }, 100);
            }
        });
    });

    // Search and Filter Functionality
    const searchInput = document.getElementById('violation-search');
    const categoryFilter = document.getElementById('violation-category-filter');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const violationCount = document.getElementById('violation-count');

    function filterViolations() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedCategory = categoryFilter.value;
        const allTabs = document.querySelectorAll('.violation-tab-content');
        let totalVisibleCount = 0;
        
        allTabs.forEach(tab => {
            const rows = tab.querySelectorAll('tbody tr');
            let tabVisibleCount = 0;
            
            rows.forEach(row => {
                if (row.classList.contains('no-results-row')) {
                    row.style.display = 'none';
                    return;
                }
                
                // Skip empty state rows
                if (row.querySelector('td[colspan]')) {
                    return;
                }
                
                const refNo = row.querySelector('td:first-child')?.textContent?.toLowerCase().trim() || '';
                const categoryElement = row.querySelector('td:nth-child(2) .text-muted.small.mb-1');
                const category = categoryElement?.textContent?.trim() || '';
                const violationName = row.querySelector('td:nth-child(2) .fw-semibold')?.textContent?.toLowerCase().trim() || '';
                
                const matchesSearch = !searchTerm || 
                    refNo.includes(searchTerm) || 
                    violationName.includes(searchTerm) || 
                    category.toLowerCase().includes(searchTerm);
                
                const matchesCategory = !selectedCategory || category === selectedCategory;
                
                if (matchesSearch && matchesCategory) {
                    row.style.display = '';
                    tabVisibleCount++;
                    totalVisibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show no results message if needed for this tab
            const tbody = tab.querySelector('tbody');
            if (tabVisibleCount === 0 && (searchTerm || selectedCategory)) {
                let noResultsRow = tbody.querySelector('.no-results-row');
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'no-results-row';
                    noResultsRow.innerHTML = `
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-search me-2"></i>No violations found matching your search criteria
                        </td>
                    `;
                    tbody.appendChild(noResultsRow);
                }
                noResultsRow.style.display = '';
            } else {
                const noResultsRow = tbody.querySelector('.no-results-row');
                if (noResultsRow) {
                    noResultsRow.style.display = 'none';
                }
            }
        });
        
        violationCount.textContent = totalVisibleCount;
    }

    function updateViolationCount() {
        const allTabs = document.querySelectorAll('.violation-tab-content');
        let totalCount = 0;
        
        allTabs.forEach(tab => {
            const allRows = tab.querySelectorAll('tbody tr:not(.no-results-row)');
            allRows.forEach(row => {
                // Skip empty state rows
                if (!row.querySelector('td[colspan]') && row.style.display !== 'none') {
                    totalCount++;
                }
            });
        });
        
        violationCount.textContent = totalCount;
    }

    function clearFilters() {
        searchInput.value = '';
        categoryFilter.value = '';
        const allTabs = document.querySelectorAll('.violation-tab-content');
        
        allTabs.forEach(tab => {
            const rows = tab.querySelectorAll('tbody tr:not(.no-results-row)');
            rows.forEach(row => {
                // Skip empty state rows
                if (!row.querySelector('td[colspan]')) {
                    row.style.display = '';
                }
            });
            const noResultsRow = tab.querySelector('.no-results-row');
            if (noResultsRow) {
                noResultsRow.remove();
            }
        });
        
        updateViolationCount();
    }

    // Event listeners
    searchInput.addEventListener('input', filterViolations);
    searchInput.addEventListener('keyup', filterViolations);
    categoryFilter.addEventListener('change', filterViolations);
    clearFiltersBtn.addEventListener('click', clearFilters);

    // Initialize count on page load
    setTimeout(() => {
        updateViolationCount();
    }, 100);
    
    // Violation history functionality moved to separate page (violation_history.php)


});
</script>

<!-- Add Violation Modal -->
<div class="modal fade" id="addViolationModal" tabindex="-1" aria-labelledby="addViolationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl add-violation-modal-dialog">
        <div class="modal-content add-violation-modal-content">
            <div class="modal-header add-violation-modal-header">
                <h5 class="modal-title" id="addViolationModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Add New Violation Type
                </h5>
                <button type="button" class="btn-close add-violation-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="addViolationForm" class="add-violation-form">
                <input type="hidden" name="action" value="add_violation">
                <div class="modal-body add-violation-modal-body">
                    <div class="row g-3">
                        <!-- Left Column: Form Fields -->
                        <div class="col-lg-7">
                            <div class="add-violation-form-section">
                                <h6 class="form-section-title">
                                    <i class="fas fa-edit me-2"></i>Violation Details
                                </h6>
                                
                                <div id="addViolationErrors" class="alert alert-danger" style="display: none;">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>Error:</strong>
                                    <ul class="mb-0 mt-2" id="addViolationErrorsList"></ul>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Violation Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" required 
                                           placeholder="e.g., Sleeping on post during office or working hours">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>Enter the full name of the violation
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <option value="Major">Major</option>
                                        <option value="Minor">Minor</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>Select whether this is a Major or Minor violation
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" name="reference_no" class="form-control" 
                                           placeholder="Auto-generated if left blank (e.g., MAJ-29, MIN-31, A.2)">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>Leave blank to auto-generate based on category
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Subcategory (RA 5487)</label>
                                    <input type="text" name="subcategory" class="form-control" 
                                           placeholder="e.g., A. Security Guard Creed, B. Code of Conduct">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>For RA 5487 violations only (A, B, C, D)
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3" 
                                              placeholder="Provide detailed description of the violation..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">1st Offense</label>
                                    <input type="text" name="first_offense" class="form-control" 
                                           placeholder="e.g., 15 days suspension">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">2nd Offense</label>
                                    <input type="text" name="second_offense" class="form-control" 
                                           placeholder="e.g., 30 days suspension">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">3rd Offense</label>
                                    <input type="text" name="third_offense" class="form-control" 
                                           placeholder="e.g., Dismissal">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">4th Offense</label>
                                    <input type="text" name="fourth_offense" class="form-control" 
                                           placeholder="e.g., Dismissal">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">5th Offense</label>
                                    <input type="text" name="fifth_offense" class="form-control" 
                                           placeholder="e.g., Dismissal">
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="ra5487_compliant" id="ra5487_compliant" value="1">
                                        <label class="form-check-label" for="ra5487_compliant">
                                            RA 5487 Compliant
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>Mark if this violation complies with RA 5487
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>Uncheck to create as inactive
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column: Reference List -->
                        <div class="col-lg-5">
                            <div class="add-violation-reference-section">
                                <h6 class="form-section-title">
                                    <i class="fas fa-list me-2"></i>Violation Types Reference
                                </h6>
                                <div class="violation-reference-list">
                                    <?php if (!empty($major_violations)): ?>
                                        <div class="reference-category major">
                                            <div class="reference-category-header">
                                                <i class="fas fa-exclamation-triangle me-2"></i>Major Violations
                                            </div>
                                            <div class="reference-items">
                                                <?php foreach (array_slice($major_violations, 0, 10) as $violation): ?>
                                                    <div class="reference-item">
                                                        <div class="reference-code"><?php echo htmlspecialchars($violation['reference_no']); ?></div>
                                                        <div class="reference-name"><?php echo htmlspecialchars($violation['name']); ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($minor_violations)): ?>
                                        <div class="reference-category minor">
                                            <div class="reference-category-header">
                                                <i class="fas fa-exclamation-circle me-2"></i>Minor Violations
                                            </div>
                                            <div class="reference-items">
                                                <?php foreach (array_slice($minor_violations, 0, 10) as $violation): ?>
                                                    <div class="reference-item">
                                                        <div class="reference-code"><?php echo htmlspecialchars($violation['reference_no']); ?></div>
                                                        <div class="reference-name"><?php echo htmlspecialchars($violation['name']); ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer add-violation-modal-footer">
                    <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary-modern">
                        <i class="fas fa-save me-2"></i>Save Violation Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* ============================================================================
   ADD VIOLATION MODAL STYLES
   ============================================================================ */
.add-violation-modal-dialog {
    margin: 0 auto !important;
    width: min(1200px, 95vw) !important;
    max-width: 1200px !important;
    max-height: 90vh !important;
}

.add-violation-modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    background: #ffffff;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.add-violation-modal-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%);
    color: #ffffff;
    border-bottom: none;
    padding: 1rem 1.5rem;
    flex-shrink: 0;
}

.add-violation-modal-header .modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
}

.add-violation-modal-header .modal-title i {
    color: #fbbf24;
}

.add-violation-close-btn {
    background: transparent !important;
    border: none !important;
    opacity: 0.8 !important;
    filter: brightness(0) invert(1);
}

.add-violation-close-btn:hover {
    opacity: 1 !important;
}

.add-violation-modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
    max-height: calc(90vh - 140px);
}

.add-violation-form-section,
.add-violation-reference-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1.25rem;
    height: 100%;
}

.form-section-title {
    font-size: 0.938rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
}

.form-section-title i {
    color: #64748b;
    font-size: 0.875rem;
}

.add-violation-reference-section {
    max-height: calc(90vh - 200px);
    overflow-y: auto;
}

.violation-reference-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.reference-category {
    margin-bottom: 1rem;
}

.reference-category-header {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: #ffffff;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
}

.reference-category.minor .reference-category-header {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.reference-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.reference-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 0.75rem;
    transition: all 0.2s ease;
}

.reference-item:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.reference-code {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.reference-name {
    font-size: 0.813rem;
    color: #1e293b;
    line-height: 1.4;
}

.add-violation-modal-footer {
    border-top: 1px solid #e2e8f0;
    padding: 1rem 1.5rem;
    flex-shrink: 0;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.add-violation-form .form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.add-violation-form .form-control,
.add-violation-form .form-select {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.add-violation-form .form-control:focus,
.add-violation-form .form-select:focus {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
    outline: none;
}

.add-violation-form .form-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.add-violation-form .form-text i {
    color: #9ca3af;
}

/* Custom scrollbar for reference section */
.add-violation-reference-section::-webkit-scrollbar {
    width: 6px;
}

.add-violation-reference-section::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.add-violation-reference-section::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.add-violation-reference-section::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Modal backdrop for add violation */
#addViolationModal + .modal-backdrop,
body.modal-open #addViolationModal + .modal-backdrop {
    background-color: #343C42 !important;
    backdrop-filter: blur(4px);
    opacity: 1 !important;
    z-index: 9998 !important;
}

#addViolationModal.modal {
    z-index: 9999 !important;
}

/* Responsive */
@media (max-width: 992px) {
    .add-violation-modal-body .row > div {
        margin-bottom: 1.5rem;
    }
    
    .add-violation-reference-section {
        max-height: 400px;
    }
}
</style>
