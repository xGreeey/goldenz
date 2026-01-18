<?php
$page_title = 'Hiring Process - Golden Z-5 HR System';
$page = 'hiring';

// Get job posts and applicants
$posts = get_posts();
$applicants = []; // This would come from an applicants table

// Sample applicants data
$sample_applicants = [
    [
        'id' => 1,
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@email.com',
        'phone' => '09123456789',
        'position' => 'Security Guard',
        'status' => 'pending',
        'applied_date' => '2024-01-15',
        'experience' => '2 years',
        'education' => 'High School Graduate'
    ],
    [
        'id' => 2,
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@email.com',
        'phone' => '09123456790',
        'position' => 'Lady Guard',
        'status' => 'interviewed',
        'applied_date' => '2024-01-14',
        'experience' => '3 years',
        'education' => 'College Graduate'
    ],
    [
        'id' => 3,
        'first_name' => 'Pedro',
        'last_name' => 'Garcia',
        'email' => 'pedro.garcia@email.com',
        'phone' => '09123456791',
        'position' => 'Security Officer',
        'status' => 'hired',
        'applied_date' => '2024-01-10',
        'experience' => '5 years',
        'education' => 'Bachelor Degree'
    ]
];

$applicants = $sample_applicants;
?>

<div class="hiring-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>Hiring Process</h1>
            <p class="text-muted">Manage job postings, applications, and recruitment workflow</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-outline-primary" id="exportApplicantsBtn">
                <i class="fas fa-download me-2"></i>Export Applicants
            </button>
            <a href="?page=add_post" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Post New Job
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?php echo count($posts); ?></h3>
                    <p class="text-muted mb-0">Active Job Posts</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-info"><?php echo count($applicants); ?></h3>
                    <p class="text-muted mb-0">Total Applicants</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?php echo count(array_filter($applicants, fn($a) => $a['status'] === 'pending')); ?></h3>
                    <p class="text-muted mb-0">Pending Review</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card">
                <div class="card-body text-center">
                    <h3 class="text-success"><?php echo count(array_filter($applicants, fn($a) => $a['status'] === 'hired')); ?></h3>
                    <p class="text-muted mb-0">Hired This Month</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="page-tabs">
        <button class="tab-button active" data-tab="job-posts">Job Posts</button>
        <button class="tab-button" data-tab="applicants">Applicants</button>
        <button class="tab-button" data-tab="interviews">Interviews</button>
        <button class="tab-button" data-tab="offers">Job Offers</button>
    </div>

    <!-- Job Posts Tab -->
    <div class="tab-content active" id="job-posts">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Active Job Posts</h5>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control" id="searchPosts" placeholder="Search posts..." style="width: 200px;">
                        <select class="form-select" id="filterDepartment" style="width: 150px;">
                            <option value="">All Departments</option>
                            <option value="Security">Security</option>
                            <option value="Administration">Administration</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($posts as $post): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title"><?php echo htmlspecialchars($post['post_title']); ?></h6>
                                    <span class="badge bg-<?php echo $post['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($post['status']); ?>
                                    </span>
                                </div>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($post['location']); ?>
                                </p>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo $post['required_count'] - $post['filled_count']; ?> positions available
                                </p>
                                <p class="card-text small"><?php echo htmlspecialchars(substr($post['description'], 0, 100)); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Posted: <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                    </small>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="viewPost(<?php echo $post['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" onclick="editPost(<?php echo $post['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deletePost(<?php echo $post['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Applicants Tab -->
    <div class="tab-content" id="applicants">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Applicant Management</h5>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control" id="searchApplicants" placeholder="Search applicants..." style="width: 200px;">
                        <select class="form-select" id="filterStatus" style="width: 150px;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="interviewed">Interviewed</option>
                            <option value="hired">Hired</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="applicantsTable">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Position</th>
                                <th>Experience</th>
                                <th>Education</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applicants as $applicant): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">
                                            <?php echo strtoupper(substr($applicant['first_name'], 0, 1) . substr($applicant['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($applicant['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($applicant['position']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($applicant['experience']); ?></td>
                                <td><?php echo htmlspecialchars($applicant['education']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($applicant['applied_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($applicant['status']) {
                                            'pending' => 'warning',
                                            'interviewed' => 'info',
                                            'hired' => 'success',
                                            'rejected' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($applicant['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="viewApplicant(<?php echo $applicant['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" onclick="scheduleInterview(<?php echo $applicant['id']; ?>)">
                                            <i class="fas fa-calendar"></i>
                                        </button>
                                        <button class="btn btn-outline-info" onclick="updateStatus(<?php echo $applicant['id']; ?>)">
                                            <i class="fas fa-edit"></i>
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

    <!-- Interviews Tab -->
    <div class="tab-content" id="interviews">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Interview Schedule</h5>
            </div>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No interviews scheduled</h5>
                    <p class="text-muted">Schedule interviews for pending applicants</p>
                    <button class="btn btn-primary" onclick="scheduleNewInterview()">
                        <i class="fas fa-plus me-2"></i>Schedule Interview
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Offers Tab -->
    <div class="tab-content" id="offers">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Job Offers</h5>
            </div>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No job offers pending</h5>
                    <p class="text-muted">Create job offers for approved candidates</p>
                    <button class="btn btn-primary" onclick="createJobOffer()">
                        <i class="fas fa-plus me-2"></i>Create Job Offer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Applicant Detail Modal -->
<div class="modal fade" id="applicantDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Applicant Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="applicantDetailContent">
                <!-- Dynamic content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="hireApplicantBtn">Hire Candidate</button>
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

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Page Header - Rectangle container with rounded corners */
.hiring-container .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1.5rem 2rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
}

.hiring-container .page-header .page-title h1 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
}

.hiring-container .page-header .page-title .text-muted {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
}

.hiring-container .page-header .page-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.page-tabs {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem 1.5rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
}

.tab-button {
    background: none;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s;
}

.tab-button:hover {
    background-color: #f8f9fa;
}

.tab-button.active {
    background-color: var(--primary-color);
    color: white;
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

/* Dark theme support for Hiring page header */
html[data-theme="dark"] .page-header {
    background-color: #1a1d23 !important;
    border: 1px solid var(--interface-border) !important;
    border-radius: 14px; /* Rounded rectangle */
    padding: 1.5rem 2rem; /* Adjusted padding */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04); /* Added shadow */
    color: var(--interface-text) !important;
    margin-bottom: var(--spacing-xl) !important;
}

html[data-theme="dark"] .page-header h1 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-header .text-muted {
    color: var(--interface-text-muted) !important;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hiringManager = new HiringManager();
    hiringManager.init();
});

class HiringManager {
    constructor() {
        this.currentTab = 'job-posts';
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Tab switching
        document.addEventListener('click', (e) => {
            if (e.target.matches('.tab-button')) {
                this.switchTab(e.target.dataset.tab);
            }
        });

        // Search functionality
        document.getElementById('searchPosts').addEventListener('input', (e) => {
            this.searchPosts(e.target.value);
        });

        document.getElementById('searchApplicants').addEventListener('input', (e) => {
            this.searchApplicants(e.target.value);
        });

        // Filter functionality
        document.getElementById('filterDepartment').addEventListener('change', (e) => {
            this.filterByDepartment(e.target.value);
        });

        document.getElementById('filterStatus').addEventListener('change', (e) => {
            this.filterByStatus(e.target.value);
        });
    }

    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(tabName).classList.add('active');

        this.currentTab = tabName;
    }

    searchPosts(searchTerm) {
        const cards = document.querySelectorAll('#job-posts .card');
        cards.forEach(card => {
            const title = card.querySelector('.card-title').textContent.toLowerCase();
            const location = card.querySelector('.text-muted').textContent.toLowerCase();
            const matches = title.includes(searchTerm.toLowerCase()) || location.includes(searchTerm.toLowerCase());
            card.closest('.col-md-6').style.display = matches ? 'block' : 'none';
        });
    }

    searchApplicants(searchTerm) {
        const rows = document.querySelectorAll('#applicantsTable tbody tr');
        rows.forEach(row => {
            const name = row.querySelector('h6').textContent.toLowerCase();
            const email = row.querySelector('small').textContent.toLowerCase();
            const matches = name.includes(searchTerm.toLowerCase()) || email.includes(searchTerm.toLowerCase());
            row.style.display = matches ? '' : 'none';
        });
    }

    filterByDepartment(department) {
        const cards = document.querySelectorAll('#job-posts .card');
        cards.forEach(card => {
            if (!department) {
                card.closest('.col-md-6').style.display = 'block';
                return;
            }
            
            const location = card.querySelector('.text-muted').textContent;
            const matches = location.includes(department);
            card.closest('.col-md-6').style.display = matches ? 'block' : 'none';
        });
    }

    filterByStatus(status) {
        const rows = document.querySelectorAll('#applicantsTable tbody tr');
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
}

// Global functions for button actions
function viewPost(postId) {
    console.log('Viewing post:', postId);
    // Implementation for viewing post details
}

function editPost(postId) {
    window.location.href = `?page=edit_post&id=${postId}`;
}

function deletePost(postId) {
    if (confirm('Are you sure you want to delete this job post?')) {
        console.log('Deleting post:', postId);
        // Implementation for deleting post
    }
}

function viewApplicant(applicantId) {
    console.log('Viewing applicant:', applicantId);
    // Implementation for viewing applicant details
}

function scheduleInterview(applicantId) {
    console.log('Scheduling interview for:', applicantId);
    // Implementation for scheduling interview
}

function updateStatus(applicantId) {
    console.log('Updating status for:', applicantId);
    // Implementation for updating applicant status
}

function scheduleNewInterview() {
    console.log('Scheduling new interview');
    // Implementation for scheduling new interview
}

function createJobOffer() {
    console.log('Creating job offer');
    // Implementation for creating job offer
}
</script>