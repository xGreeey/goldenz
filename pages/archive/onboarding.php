<?php
$page_title = 'Employee Onboarding - Golden Z-5 HR System';
$page = 'onboarding';

// Get employees in onboarding process
$employees = get_employees();
$onboarding_employees = array_filter($employees, function($emp) {
    return $emp['status'] === 'Active' && strtotime($emp['date_hired']) > strtotime('-6 months');
});

// Onboarding checklist items
$onboarding_steps = [
    'pre_employment' => [
        'title' => 'Pre-Employment',
        'items' => [
            'background_check' => 'Background Check Completed',
            'medical_exam' => 'Medical Examination',
            'drug_test' => 'Drug Test',
            'reference_check' => 'Reference Check',
            'document_verification' => 'Document Verification'
        ]
    ],
    'first_day' => [
        'title' => 'First Day',
        'items' => [
            'office_tour' => 'Office Tour',
            'introduction_team' => 'Team Introduction',
            'workstation_setup' => 'Workstation Setup',
            'system_access' => 'System Access Provided',
            'uniform_issuance' => 'Uniform Issuance'
        ]
    ],
    'first_week' => [
        'title' => 'First Week',
        'items' => [
            'orientation_training' => 'Company Orientation',
            'safety_training' => 'Safety Training',
            'security_training' => 'Security Training',
            'policy_review' => 'Policy Review',
            'benefits_enrollment' => 'Benefits Enrollment'
        ]
    ],
    'first_month' => [
        'title' => 'First Month',
        'items' => [
            'job_training' => 'Job-Specific Training',
            'mentor_assignment' => 'Mentor Assignment',
            'performance_review' => 'First Performance Review',
            'feedback_session' => 'Feedback Session',
            'goal_setting' => 'Goal Setting'
        ]
    ],
    'probation_period' => [
        'title' => 'Probation Period (6 months)',
        'items' => [
            'monthly_reviews' => 'Monthly Performance Reviews',
            'skill_assessment' => 'Skill Assessment',
            'compliance_training' => 'Compliance Training',
            'final_evaluation' => 'Final Evaluation',
            'permanent_status' => 'Permanent Status Decision'
        ]
    ]
];
?>

<div class="onboarding-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>Employee Onboarding</h1>
            <p class="text-muted">Track and manage new employee onboarding process</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-outline-primary" id="exportOnboardingBtn">
                <i class="fas fa-download me-2"></i>Export Progress
            </button>
            <button class="btn btn-primary" id="addOnboardingBtn">
                <i class="fas fa-plus me-2"></i>Start Onboarding
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?php echo count($onboarding_employees); ?></h3>
                    <p class="text-muted mb-0">In Onboarding</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-success" id="completedOnboarding">0</h3>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-warning" id="inProgressOnboarding">0</h3>
                    <p class="text-muted mb-0">In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-info" id="newHires">0</h3>
                    <p class="text-muted mb-0">New This Month</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Onboarding Progress Overview -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Onboarding Progress Overview</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($onboarding_steps as $step_key => $step): ?>
                <div class="col-md-2 mb-3">
                    <div class="onboarding-step">
                        <div class="step-icon">
                            <i class="fas fa-<?php echo match($step_key) {
                                'pre_employment' => 'clipboard-check',
                                'first_day' => 'calendar-day',
                                'first_week' => 'calendar-week',
                                'first_month' => 'calendar-alt',
                                'probation_period' => 'trophy',
                                default => 'check-circle'
                            }; ?>"></i>
                        </div>
                        <h6 class="step-title"><?php echo $step['title']; ?></h6>
                        <div class="step-progress">
                            <div class="progress">
                                <div class="progress-bar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted">0/<?php echo count($step['items']); ?> completed</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Employee Onboarding List -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Employee Onboarding Status</h5>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control" id="searchEmployees" placeholder="Search employees..." style="width: 200px;">
                    <select class="form-select" id="filterStatus" style="width: 150px;">
                        <option value="">All Status</option>
                        <option value="pre_employment">Pre-Employment</option>
                        <option value="first_day">First Day</option>
                        <option value="first_week">First Week</option>
                        <option value="first_month">First Month</option>
                        <option value="probation_period">Probation Period</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="onboardingTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Hire Date</th>
                            <th>Current Stage</th>
                            <th>Progress</th>
                            <th>Days in Process</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($onboarding_employees as $employee): 
                            $hire_date = new DateTime($employee['date_hired']);
                            $days_in_process = $hire_date->diff(new DateTime())->days;
                            $current_stage = $this->determineCurrentStage($days_in_process);
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
                            <td><?php echo $hire_date->format('M d, Y'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo match($current_stage) {
                                    'pre_employment' => 'secondary',
                                    'first_day' => 'info',
                                    'first_week' => 'warning',
                                    'first_month' => 'primary',
                                    'probation_period' => 'success',
                                    'completed' => 'success',
                                    default => 'secondary'
                                }; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $current_stage)); ?>
                                </span>
                            </td>
                            <td>
                                <div class="progress" style="width: 100px;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted">0%</small>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $days_in_process > 180 ? 'danger' : ($days_in_process > 90 ? 'warning' : 'success'); ?>">
                                    <?php echo $days_in_process; ?> days
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary view-onboarding-btn" data-employee-id="<?php echo $employee['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-success update-progress-btn" data-employee-id="<?php echo $employee['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-info send-reminder-btn" data-employee-id="<?php echo $employee['id']; ?>">
                                        <i class="fas fa-bell"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Onboarding Detail Modal -->
<div class="modal fade" id="onboardingDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Onboarding Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="onboardingDetailContent">
                <!-- Dynamic content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveOnboardingBtn">Save Progress</button>
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

.onboarding-step {
    text-align: center;
    padding: 1rem;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    background: #f8f9fa;
}

.step-icon {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.step-title {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.step-progress .progress {
    height: 6px;
    margin-bottom: 0.25rem;
}

.progress-bar {
    background-color: var(--primary-color);
}

.onboarding-checklist {
    max-height: 400px;
    overflow-y: auto;
}

.checklist-section {
    margin-bottom: 1.5rem;
}

.checklist-section h6 {
    color: var(--primary-color);
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}

.checklist-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
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

.card-body {
    padding: 1.5rem;
}

.card-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.card-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const onboardingManager = new OnboardingManager();
    onboardingManager.init();
});

class OnboardingManager {
    constructor() {
        this.onboardingSteps = <?php echo json_encode($onboarding_steps); ?>;
        this.currentEmployeeId = null;
    }

    init() {
        this.bindEvents();
        this.updateStatistics();
    }

    bindEvents() {
        // View onboarding details
        document.addEventListener('click', (e) => {
            if (e.target.closest('.view-onboarding-btn')) {
                const employeeId = e.target.closest('.view-onboarding-btn').dataset.employeeId;
                this.showOnboardingDetails(employeeId);
            }
        });

        // Search and filter
        document.getElementById('searchEmployees').addEventListener('input', (e) => {
            this.searchEmployees(e.target.value);
        });

        document.getElementById('filterStatus').addEventListener('change', (e) => {
            this.filterByStatus(e.target.value);
        });

        // Export functionality
        document.getElementById('exportOnboardingBtn').addEventListener('click', () => {
            this.exportOnboarding();
        });
    }

    showOnboardingDetails(employeeId) {
        this.currentEmployeeId = employeeId;
        
        // Get employee data
        const employeeRow = document.querySelector(`tr[data-employee-id="${employeeId}"]`);
        const employeeName = employeeRow.querySelector('h6').textContent;
        const employeePosition = employeeRow.querySelector('.badge').textContent;
        
        // Build onboarding content
        let content = `
            <div class="employee-info mb-4">
                <h6>${employeeName}</h6>
                <p class="text-muted">${employeePosition}</p>
            </div>
            
            <div class="onboarding-checklist">
        `;
        
        Object.keys(this.onboardingSteps).forEach(stepKey => {
            const step = this.onboardingSteps[stepKey];
            content += `
                <div class="checklist-section">
                    <h6>${step.title}</h6>
                    <div class="checklist-items">
            `;
            
            Object.keys(step.items).forEach(itemKey => {
                const itemTitle = step.items[itemKey];
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
        
        content += `</div>`;
        
        document.getElementById('onboardingDetailContent').innerHTML = content;
        
        // Load current progress
        this.loadEmployeeProgress(employeeId);
        
        // Show modal
        new bootstrap.Modal(document.getElementById('onboardingDetailModal')).show();
    }

    loadEmployeeProgress(employeeId) {
        // Simulate loading progress data
        // In a real implementation, this would fetch from the server
        console.log('Loading progress for employee:', employeeId);
    }

    searchEmployees(searchTerm) {
        const rows = document.querySelectorAll('#onboardingTable tbody tr');
        rows.forEach(row => {
            const name = row.querySelector('h6').textContent.toLowerCase();
            const employeeNo = row.querySelector('small').textContent.toLowerCase();
            const matches = name.includes(searchTerm.toLowerCase()) || employeeNo.includes(searchTerm.toLowerCase());
            row.style.display = matches ? '' : 'none';
        });
    }

    filterByStatus(status) {
        const rows = document.querySelectorAll('#onboardingTable tbody tr');
        rows.forEach(row => {
            if (!status) {
                row.style.display = '';
                return;
            }
            
            const statusBadge = row.querySelector('.badge').textContent.toLowerCase();
            const matches = statusBadge.includes(status.toLowerCase());
            row.style.display = matches ? '' : 'none';
        });
    }

    updateStatistics() {
        const rows = document.querySelectorAll('#onboardingTable tbody tr');
        let completed = 0;
        let inProgress = 0;
        let newHires = 0;
        
        rows.forEach(row => {
            const daysBadge = row.querySelectorAll('.badge')[1];
            const days = parseInt(daysBadge.textContent);
            
            if (days < 30) newHires++;
            if (days > 180) completed++;
            else if (days > 0) inProgress++;
        });
        
        document.getElementById('completedOnboarding').textContent = completed;
        document.getElementById('inProgressOnboarding').textContent = inProgress;
        document.getElementById('newHires').textContent = newHires;
    }

    exportOnboarding() {
        console.log('Exporting onboarding data...');
    }
}

// Helper function to determine current stage
function determineCurrentStage(daysInProcess) {
    if (daysInProcess < 1) return 'pre_employment';
    if (daysInProcess < 7) return 'first_day';
    if (daysInProcess < 30) return 'first_week';
    if (daysInProcess < 90) return 'first_month';
    if (daysInProcess < 180) return 'probation_period';
    return 'completed';
}
</script>