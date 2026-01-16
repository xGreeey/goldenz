<?php
$page_title = 'Employee Checklist - Golden Z-5 HR System';
$page = 'checklist';

// Get employees for checklist
$employees = get_employees();
$checklist_items = [
    'documents' => [
        'title' => 'Documentation',
        'items' => [
            'employment_contract' => 'Employment Contract Signed',
            'id_requirements' => 'ID Requirements Submitted',
            'medical_certificate' => 'Medical Certificate',
            'nbi_clearance' => 'NBI Clearance',
            'police_clearance' => 'Police Clearance',
            'barangay_clearance' => 'Barangay Clearance',
            'sss_registration' => 'SSS Registration',
            'pagibig_registration' => 'Pag-IBIG Registration',
            'philhealth_registration' => 'PhilHealth Registration',
            'tin_registration' => 'TIN Registration'
        ]
    ],
    'training' => [
        'title' => 'Training & Certification',
        'items' => [
            'orientation_completed' => 'Company Orientation',
            'safety_training' => 'Safety Training',
            'security_training' => 'Security Training',
            'first_aid_training' => 'First Aid Training',
            'fire_safety_training' => 'Fire Safety Training',
            'customer_service_training' => 'Customer Service Training'
        ]
    ],
    'equipment' => [
        'title' => 'Equipment & Uniform',
        'items' => [
            'uniform_issued' => 'Uniform Issued',
            'id_card_issued' => 'ID Card Issued',
            'equipment_issued' => 'Equipment Issued',
            'radio_issued' => 'Radio Issued',
            'flashlight_issued' => 'Flashlight Issued',
            'handcuffs_issued' => 'Handcuffs Issued (if applicable)'
        ]
    ],
    'compliance' => [
        'title' => 'Compliance & Legal',
        'items' => [
            'background_check' => 'Background Check Completed',
            'drug_test' => 'Drug Test Completed',
            'psychological_test' => 'Psychological Test Completed',
            'license_verification' => 'License Verification',
            'reference_check' => 'Reference Check',
            'probation_period' => 'Probation Period Started'
        ]
    ]
];

// Handle checklist updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $employee_id = $_POST['employee_id'];
    $item_key = $_POST['item_key'];
    $completed = $_POST['completed'] === 'true';
    
    try {
        $pdo = get_db_connection();
        
        if ($action === 'update_checklist') {
            // Check if checklist entry exists
            $stmt = $pdo->prepare("SELECT id FROM employee_checklist WHERE employee_id = ? AND item_key = ?");
            $stmt->execute([$employee_id, $item_key]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing entry
                $stmt = $pdo->prepare("UPDATE employee_checklist SET completed = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$completed ? 1 : 0, $existing['id']]);
            } else {
                // Create new entry
                $stmt = $pdo->prepare("INSERT INTO employee_checklist (employee_id, item_key, completed, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$employee_id, $item_key, $completed ? 1 : 0]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Checklist updated successfully']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating checklist: ' . $e->getMessage()]);
    }
    exit;
}

// Get checklist progress for employees
function get_checklist_progress($employee_id) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT item_key, completed FROM employee_checklist WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        return $results;
    } catch (Exception $e) {
        return [];
    }
}
?>

<div class="checklist-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>Employee Checklist</h1>
            <p class="text-muted">Track employee onboarding progress and compliance requirements</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-outline-primary" id="exportChecklistBtn">
                <i class="fas fa-download me-2"></i>Export Progress
            </button>
            <button class="btn btn-primary" id="addChecklistItemBtn">
                <i class="fas fa-plus me-2"></i>Add Item
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?php echo count($employees); ?></h3>
                    <p class="text-muted mb-0">Total Employees</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-success" id="completedCount">0</h3>
                    <p class="text-muted mb-0">Fully Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-warning" id="inProgressCount">0</h3>
                    <p class="text-muted mb-0">In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-danger" id="notStartedCount">0</h3>
                    <p class="text-muted mb-0">Not Started</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee List with Checklist -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Employee Onboarding Checklist</h5>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control" id="searchEmployees" placeholder="Search employees..." style="width: 200px;">
                    <select class="form-select" id="filterStatus" style="width: 150px;">
                        <option value="">All Status</option>
                        <option value="completed">Completed</option>
                        <option value="in_progress">In Progress</option>
                        <option value="not_started">Not Started</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="checklistTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Progress</th>
                            <th>Documents</th>
                            <th>Training</th>
                            <th>Equipment</th>
                            <th>Compliance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): 
                            $progress = get_checklist_progress($employee['id']);
                            $total_items = 0;
                            $completed_items = 0;
                            
                            foreach ($checklist_items as $category) {
                                foreach ($category['items'] as $key => $title) {
                                    $total_items++;
                                    if (isset($progress[$key]) && $progress[$key]) {
                                        $completed_items++;
                                    }
                                }
                            }
                            
                            $progress_percentage = $total_items > 0 ? round(($completed_items / $total_items) * 100) : 0;
                        ?>
                        <tr data-employee-id="<?php echo $employee['id']; ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3">
                                        <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['surname'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['surname']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($employee['employee_no']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($employee['post']); ?></span>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($employee['employee_type']); ?></small>
                            </td>
                            <td>
                                <div class="progress" style="width: 100px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progress_percentage; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo $progress_percentage; ?>% (<?php echo $completed_items; ?>/<?php echo $total_items; ?>)</small>
                            </td>
                            <td>
                                <div class="checklist-category">
                                    <?php 
                                    $doc_completed = 0;
                                    $doc_total = count($checklist_items['documents']['items']);
                                    foreach ($checklist_items['documents']['items'] as $key => $title) {
                                        if (isset($progress[$key]) && $progress[$key]) $doc_completed++;
                                    }
                                    ?>
                                    <small><?php echo $doc_completed; ?>/<?php echo $doc_total; ?> completed</small>
                                </div>
                            </td>
                            <td>
                                <div class="checklist-category">
                                    <?php 
                                    $train_completed = 0;
                                    $train_total = count($checklist_items['training']['items']);
                                    foreach ($checklist_items['training']['items'] as $key => $title) {
                                        if (isset($progress[$key]) && $progress[$key]) $train_completed++;
                                    }
                                    ?>
                                    <small><?php echo $train_completed; ?>/<?php echo $train_total; ?> completed</small>
                                </div>
                            </td>
                            <td>
                                <div class="checklist-category">
                                    <?php 
                                    $equip_completed = 0;
                                    $equip_total = count($checklist_items['equipment']['items']);
                                    foreach ($checklist_items['equipment']['items'] as $key => $title) {
                                        if (isset($progress[$key]) && $progress[$key]) $equip_completed++;
                                    }
                                    ?>
                                    <small><?php echo $equip_completed; ?>/<?php echo $equip_total; ?> completed</small>
                                </div>
                            </td>
                            <td>
                                <div class="checklist-category">
                                    <?php 
                                    $comp_completed = 0;
                                    $comp_total = count($checklist_items['compliance']['items']);
                                    foreach ($checklist_items['compliance']['items'] as $key => $title) {
                                        if (isset($progress[$key]) && $progress[$key]) $comp_completed++;
                                    }
                                    ?>
                                    <small><?php echo $comp_completed; ?>/<?php echo $comp_total; ?> completed</small>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary view-checklist-btn" data-employee-id="<?php echo $employee['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Checklist Detail Modal -->
<div class="modal fade" id="checklistDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Checklist Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="checklistDetailContent">
                <!-- Dynamic content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveChecklistBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<style>
.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.checklist-category {
    font-size: 0.875rem;
}

.checklist-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.checklist-item:last-child {
    border-bottom: none;
}

.checklist-item input[type="checkbox"] {
    margin-right: 0.75rem;
}

.checklist-item label {
    margin: 0;
    flex: 1;
    cursor: pointer;
}

.checklist-item.completed label {
    text-decoration: line-through;
    color: #6c757d;
}

.progress {
    height: 8px;
}

.progress-bar {
    background-color: var(--primary-color);
}

/* Card styling to match HR admin dashboard */
.card-modern,
.card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    background: #ffffff;
    overflow: hidden;
    transition: all 0.3s ease;
    outline: none !important;
}

.card-modern:hover,
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0 !important;
    outline: none !important;
}

.card-modern:focus,
.card:focus,
.card-modern:focus-visible,
.card:focus-visible {
    outline: none !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0 !important;
}

/* Summary cards with enhanced shadows */
.summary-card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
    outline: none !important;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0 !important;
    outline: none !important;
}

.summary-card:focus,
.summary-card:focus-visible {
    outline: none !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0 !important;
}

.card-body-modern,
.card-body {
    padding: 1.5rem;
}

.card-header-modern {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.card-title-modern {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}

/* Dark theme support for Checklist page */
html[data-theme="dark"] .checklist-container {
    background-color: transparent;
    color: var(--interface-text);
}

html[data-theme="dark"] .page-header h1 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-header .text-muted {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .summary-card {
    background-color: #1a1d23 !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .summary-card .card-body {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .summary-card .text-muted {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .card {
    background-color: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-header {
    background-color: #1a1d23 !important;
    border-bottom-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-header h5 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-body {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .form-control,
html[data-theme="dark"] .form-select {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table thead {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table thead th {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .table tbody {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table tbody tr {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .table tbody tr:hover {
    background-color: var(--interface-hover) !important;
}

html[data-theme="dark"] .table td {
    background-color: transparent !important;
    color: var(--interface-text) !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .table td h6 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table td .text-muted {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .table td small {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .checklist-category {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .checklist-category small {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .modal-content {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .modal-header {
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .modal-title {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .modal-footer {
    border-top-color: var(--interface-border) !important;
}

html[data-theme="dark"] .checklist-item {
    border-bottom-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .checklist-item label {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .checklist-item.completed label {
    color: var(--interface-text-muted) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checklistManager = new ChecklistManager();
    checklistManager.init();
});

class ChecklistManager {
    constructor() {
        this.currentEmployeeId = null;
        this.checklistItems = <?php echo json_encode($checklist_items); ?>;
    }

    init() {
        this.bindEvents();
        this.updateStatistics();
    }

    bindEvents() {
        // View checklist details
        document.addEventListener('click', (e) => {
            if (e.target.closest('.view-checklist-btn')) {
                const employeeId = e.target.closest('.view-checklist-btn').dataset.employeeId;
                this.showChecklistDetails(employeeId);
            }
        });

        // Search and filter
        document.getElementById('searchEmployees').addEventListener('input', (e) => {
            this.filterEmployees(e.target.value);
        });

        document.getElementById('filterStatus').addEventListener('change', (e) => {
            this.filterByStatus(e.target.value);
        });

        // Export functionality
        document.getElementById('exportChecklistBtn').addEventListener('click', () => {
            this.exportChecklist();
        });
    }

    showChecklistDetails(employeeId) {
        this.currentEmployeeId = employeeId;
        
        // Get employee data
        const employeeRow = document.querySelector(`tr[data-employee-id="${employeeId}"]`);
        const employeeName = employeeRow.querySelector('h6').textContent;
        const employeePosition = employeeRow.querySelector('.badge').textContent;
        
        // Build checklist content
        let content = `
            <div class="employee-info mb-4">
                <h6>${employeeName}</h6>
                <p class="text-muted">${employeePosition}</p>
            </div>
        `;
        
        Object.keys(this.checklistItems).forEach(categoryKey => {
            const category = this.checklistItems[categoryKey];
            content += `
                <div class="checklist-category-section mb-4">
                    <h6 class="text-primary">${category.title}</h6>
                    <div class="checklist-items">
            `;
            
            Object.keys(category.items).forEach(itemKey => {
                const itemTitle = category.items[itemKey];
                content += `
                    <div class="checklist-item">
                        <input type="checkbox" id="${itemKey}" data-item-key="${itemKey}" class="checklist-checkbox">
                        <label for="${itemKey}">${itemTitle}</label>
                    </div>
                `;
            });
            
            content += `
                    </div>
                </div>
            `;
        });
        
        document.getElementById('checklistDetailContent').innerHTML = content;
        
        // Load current progress
        this.loadEmployeeProgress(employeeId);
        
        // Show modal
        new bootstrap.Modal(document.getElementById('checklistDetailModal')).show();
    }

    loadEmployeeProgress(employeeId) {
        fetch('?page=checklist', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_progress&employee_id=${employeeId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Object.keys(data.progress).forEach(itemKey => {
                    const checkbox = document.getElementById(itemKey);
                    if (checkbox) {
                        checkbox.checked = data.progress[itemKey];
                        this.updateItemStatus(checkbox);
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error loading progress:', error);
        });
    }

    updateItemStatus(checkbox) {
        const item = checkbox.closest('.checklist-item');
        if (checkbox.checked) {
            item.classList.add('completed');
        } else {
            item.classList.remove('completed');
        }
    }

    filterEmployees(searchTerm) {
        const rows = document.querySelectorAll('#checklistTable tbody tr');
        rows.forEach(row => {
            const name = row.querySelector('h6').textContent.toLowerCase();
            const employeeNo = row.querySelector('small').textContent.toLowerCase();
            const matches = name.includes(searchTerm.toLowerCase()) || employeeNo.includes(searchTerm.toLowerCase());
            row.style.display = matches ? '' : 'none';
        });
    }

    filterByStatus(status) {
        const rows = document.querySelectorAll('#checklistTable tbody tr');
        rows.forEach(row => {
            const progressBar = row.querySelector('.progress-bar');
            const progressText = row.querySelector('small').textContent;
            const percentage = parseInt(progressText.match(/(\d+)%/)[1]);
            
            let show = true;
            if (status === 'completed' && percentage < 100) show = false;
            if (status === 'in_progress' && (percentage === 0 || percentage === 100)) show = false;
            if (status === 'not_started' && percentage > 0) show = false;
            
            row.style.display = show ? '' : 'none';
        });
    }

    updateStatistics() {
        const rows = document.querySelectorAll('#checklistTable tbody tr');
        let completed = 0;
        let inProgress = 0;
        let notStarted = 0;
        
        rows.forEach(row => {
            const progressText = row.querySelector('small').textContent;
            const percentage = parseInt(progressText.match(/(\d+)%/)[1]);
            
            if (percentage === 100) completed++;
            else if (percentage > 0) inProgress++;
            else notStarted++;
        });
        
        document.getElementById('completedCount').textContent = completed;
        document.getElementById('inProgressCount').textContent = inProgress;
        document.getElementById('notStartedCount').textContent = notStarted;
    }

    exportChecklist() {
        // Implementation for exporting checklist data
        console.log('Exporting checklist data...');
    }
}
</script>