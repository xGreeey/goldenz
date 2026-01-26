<?php
// Get post statistics
$post_stats = get_post_statistics();

// Get filter parameters
$filters = [
    'department' => $_GET['department'] ?? '',
    'employee_type' => $_GET['employee_type'] ?? '',
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Get posts with filters
$posts = get_posts($filters);
?>

<div class="container-fluid hrdash">

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Total Posts</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($post_stats['total_posts']); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>5%</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">All posts tracked in the system.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Required Positions</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($post_stats['total_required']); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-arrow-up"></i>
                        <span><?php echo $post_stats['total_filled']; ?> filled</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Total staffing requirements across all posts.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Vacant Positions</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($post_stats['total_vacant']); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--negative">
                        <i class="fas fa-arrow-down"></i>
                        <span>2%</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Open positions that need to be filled.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Urgent Posts</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($post_stats['urgent_posts']); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--negative">
                        <i class="fas fa-arrow-down"></i>
                        <span>3%</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">High priority posts requiring immediate attention.</div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card card-modern mb-4">
        <div class="card-body-modern">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search Posts</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" 
                               id="searchInput" 
                               class="form-control"
                               placeholder="Search posts, locations, or descriptions..." 
                               value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Department</label>
                    <select class="form-select" id="departmentFilter">
                        <option value="">All Departments</option>
                        <option value="Security" <?php echo $filters['department'] === 'Security' ? 'selected' : ''; ?>>Security</option>
                        <option value="Administration" <?php echo $filters['department'] === 'Administration' ? 'selected' : ''; ?>>Administration</option>
                        <option value="Operations" <?php echo $filters['department'] === 'Operations' ? 'selected' : ''; ?>>Operations</option>
                        <option value="Management" <?php echo $filters['department'] === 'Management' ? 'selected' : ''; ?>>Management</option>
                        <option value="Support" <?php echo $filters['department'] === 'Support' ? 'selected' : ''; ?>>Support</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Employee Type</label>
                    <select class="form-select" id="employeeTypeFilter">
                        <option value="">All Types</option>
                        <option value="SG" <?php echo $filters['employee_type'] === 'SG' ? 'selected' : ''; ?>>Security Guard (SG)</option>
                        <option value="LG" <?php echo $filters['employee_type'] === 'LG' ? 'selected' : ''; ?>>Lady Guard (LG)</option>
                        <option value="SO" <?php echo $filters['employee_type'] === 'SO' ? 'selected' : ''; ?>>Security Officer (SO)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Active" <?php echo $filters['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $filters['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="Filled" <?php echo $filters['status'] === 'Filled' ? 'selected' : ''; ?>>Filled</option>
                        <option value="Suspended" <?php echo $filters['status'] === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Priority</label>
                    <select class="form-select" id="priorityFilter">
                        <option value="">All Priorities</option>
                        <option value="Urgent" <?php echo $filters['priority'] === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                        <option value="High" <?php echo $filters['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                        <option value="Medium" <?php echo $filters['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="Low" <?php echo $filters['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-modern" onclick="resetFilters()" title="Clear Filters">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Post List -->
    <div class="card card-modern mb-4">
        <div class="card-body-modern">
            <div class="card-header-modern mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title-modern">Post List</h5>
                    <small class="card-subtitle">Viewing <?php echo count($posts); ?> posts</small>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-modern" onclick="exportToCSV()" title="Export post list">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                    <a href="?page=post_assignments" class="btn btn-outline-modern" title="View post assignments">
                        <i class="fas fa-users-cog me-2"></i>Assignments
                    </a>
                    <a href="?page=add_post" class="btn btn-primary-modern">
                        <span class="hr-icon hr-icon-plus me-2"></span>Add Post
                    </a>
                </div>
            </div>
            <div class="table-container">
                <table class="table posts-table" id="postsTable">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="selectAll" class="form-check-input">
                    </th>
                    <th class="sortable" data-sort="post_details">
                        Post Details
                        <i class="fas fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="department">
                        Department
                        <i class="fas fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="employee_type">
                        Employee Type
                        <i class="fas fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="location">
                        Location
                        <i class="fas fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="positions">
                        Positions
                        <i class="fas fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="priority">
                        Priority
                        <i class="fas fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="status">
                        Status
                        <i class="fas fa-sort"></i>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-inbox fa-2x mb-3"></i>
                                <p>No posts found. <a href="?page=add_post">Create your first post</a></p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <tr data-post-id="<?php echo $post['id']; ?>">
                            <td>
                                <input type="checkbox" class="form-check-input post-checkbox" value="<?php echo $post['id']; ?>">
                            </td>
                            <td>
                                <div class="post-info">
                                    <div class="post-title">
                                        <strong><?php echo htmlspecialchars($post['post_title']); ?></strong>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($post['post_code']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-department"><?php echo htmlspecialchars($post['department']); ?></span>
                            </td>
                            <td>
                                <span class="badge badge-employee-type">
                                    <?php 
                                    $type_labels = ['SG' => 'Security Guard', 'LG' => 'Lady Guard', 'SO' => 'Security Officer'];
                                    echo $type_labels[$post['employee_type']] ?? $post['employee_type'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="location-info">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($post['location']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="position-info">
                                    <div class="d-flex align-items-center">
                                        <?php
                                        $requiredCount = (int)($post['required_count'] ?? 0);
                                        $currentEmployees = (int)($post['current_employees'] ?? 0);
                                        $remainingVacancies = (int)($post['remaining_vacancies'] ?? max(0, $requiredCount - $currentEmployees));
                                        $fillPercent = $requiredCount > 0 ? min(100, ($currentEmployees / $requiredCount) * 100) : 0;
                                        ?>
                                        <span class="me-2"><?php echo $currentEmployees; ?>/<?php echo $requiredCount; ?></span>
                                        <div class="progress" style="width: 60px; height: 6px;">
                                            <div class="progress-bar <?php echo ($requiredCount > 0 && $currentEmployees >= $requiredCount) ? 'bg-success' : 'bg-warning'; ?>" 
                                                 style="width: <?php echo $fillPercent; ?>%">
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $remainingVacancies; ?> remaining
                                    </small>
                                </div>
                            </td>
                            <td>
                                <span class="priority-badge priority-<?php echo strtolower($post['priority']); ?>">
                                    <?php echo htmlspecialchars($post['priority']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($post['status']); ?>">
                                    <?php echo htmlspecialchars($post['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="post-actions">
                                    <a href="?page=edit_post&id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Post">
                                        <span class="hr-icon hr-icon-edit"></span>
                                    </a>
                                    <a href="?page=post_assignments&post_id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-info" title="View Assignments">
                                        <span class="hr-icon hr-icon-view"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-container mt-4">
                <div class="pagination-info">
                    <span>Showing <strong><?php echo count($posts); ?></strong> of <strong><?php echo count($posts); ?></strong> posts</span>
                </div>
                <div class="pagination-controls">
                    <!-- Pagination controls would go here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update time display every minute for posts page (HR Admin only)
<?php if (($_SESSION['user_role'] ?? '') === 'hr_admin'): ?>
(function() {
    function updateTime() {
        const timeElement = document.getElementById('current-time-posts');
        if (timeElement) {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const displayHours = hours % 12 || 12;
            const displayMinutes = minutes < 10 ? '0' + minutes : minutes;
            timeElement.textContent = displayHours + ':' + displayMinutes + ' ' + ampm.toUpperCase();
        }
    }
    
    // Update immediately
    updateTime();
    
    // Update every minute
    setInterval(updateTime, 60000);
})();
<?php endif; ?>
</script>

<style>
/* ============================================
   MODERN POSTS PAGE STYLES
   ============================================ */

/* Hide the main header with black background */
.main-content .header {
    display: none !important;
}

/* Container */
.posts-modern {
    /* Use portal-wide spacing system (font-override.css) instead of page-local padding */
    padding: 0;
    max-width: 100%;
    overflow-x: hidden;
    min-height: 100vh;
    background: #ffffff; /* default for non HR-Admin portals */
}

/* HR-Admin: use light separated background */
body.portal-hr-admin .posts-modern {
    background: #f8fafc;
}

/* Page Header */
.page-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}

.page-title-modern {
    flex: 1;
}

.page-title-main {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
    letter-spacing: -0.02em;
}

.page-subtitle {
    font-size: 0.9375rem;
    color: #64748b;
    margin: 0;
    font-weight: 400;
}

.page-actions-modern {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

/* Buttons */
.btn-primary-modern {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: #ffffff;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.25);
}

.btn-primary-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.35);
    background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
}

.btn-outline-modern {
    border: 1.5px solid #e2e8f0;
    color: #475569;
    background: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.btn-outline-modern:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.btn-outline-secondary-modern {
    border: 1.5px solid #e2e8f0;
    color: #64748b;
    background: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.btn-outline-secondary-modern:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #475569;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Summary Cards */
.summary-cards-modern {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.stat-card-modern {
    border: none;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    background: #ffffff;
    transition: all 0.3s ease;
    overflow: hidden;
}

.stat-card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
}

.card-body-modern {
    padding: 1.5rem;
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-label {
    font-size: 0.8125rem;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-icon {
    font-size: 1.125rem;
    color: #cbd5e1;
    opacity: 0.6;
}

.stat-content {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.stat-number {
    font-size: 2.25rem;
    font-weight: 700;
    color: #000000;
    margin: 0;
    line-height: 1;
    letter-spacing: -0.02em;
    /* Number rendering fix - ensures digits display correctly on Windows 10/11 */
    font-family: 'Segoe UI', Arial, Helvetica, sans-serif !important;
    font-variant-numeric: tabular-nums !important;
    font-feature-settings: 'tnum' !important;
    -webkit-font-feature-settings: 'tnum' !important;
    -moz-font-feature-settings: 'tnum' !important;
    text-rendering: optimizeLegibility !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
}

.stat-footer {
    font-size: 0.8125rem;
    color: #94a3b8;
    display: block;
    margin-top: 0.5rem;
}

/* Badges */
.badge-success-modern,
.badge-primary-modern,
.badge-warning-modern,
.badge-danger-modern {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    letter-spacing: 0.01em;
}

.badge-success-modern {
    background: #dcfce7;
    color: #16a34a;
}

.badge-primary-modern {
    background: #dbeafe;
    color: #2563eb;
}

.badge-warning-modern {
    background: #fef3c7;
    color: #d97706;
}

.badge-danger-modern {
    background: #fee2e2;
    color: #dc2626;
}

/* Filters Section */
.filters-modern {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.search-control-modern {
    width: 100%;
}

.search-input-modern {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 1rem;
    color: #94a3b8;
    font-size: 0.875rem;
    z-index: 2;
}

.search-field {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #475569;
    background: #ffffff;
    transition: all 0.2s ease;
}

.search-field:focus {
    outline: none;
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

.filter-controls-modern {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.form-select-modern {
    flex: 1;
    min-width: 150px;
    padding: 0.625rem 0.875rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #475569;
    background: #ffffff;
    transition: all 0.2s ease;
    cursor: pointer;
}

.form-select-modern:focus {
    outline: none;
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

.control-buttons-modern {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

/* Table Container */
.table-container {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    overflow-x: hidden;
    overflow-y: visible;
    margin-bottom: 2rem;
    width: 100%;
    max-width: 100%;
}

/* Posts Table Styling */
.posts-table {
    width: 100%;
    max-width: 100%;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    background: #ffffff;
    font-size: 0.9375rem;
    table-layout: auto;
    min-width: 900px; /* Minimum width for readability */
}

.posts-table thead {
    background: #f8fafc;
}

.posts-table th {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 0.625rem 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-align: left;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: normal;
    word-wrap: break-word;
}

.posts-table th:first-child {
    width: 50px;
    text-align: center;
    padding: 0.625rem 0.75rem;
}

.posts-table th:last-child {
    width: 120px;
    text-align: center;
    padding: 0.625rem 0.75rem;
}

.posts-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s ease;
    background-color: #ffffff;
}

.posts-table tbody tr:hover {
    background-color: #f8fafc;
    transform: translateX(2px);
}

.posts-table tbody tr:last-child {
    border-bottom: none;
}

.posts-table td {
    padding: 0.625rem 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    color: #475569;
    font-size: 0.875rem;
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.posts-table td:first-child {
    padding: 0.625rem 0.75rem;
    text-align: center;
}

.posts-table td:last-child {
    padding: 0.625rem 0.75rem;
    text-align: center;
}

.post-info .post-title {
    font-weight: 600;
    color: #1e293b;
}

.post-info .post-title strong {
    color: #1e293b;
    font-size: 0.9375rem;
    display: block;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.post-info .post-title small {
    color: #64748b;
    font-size: 0.8125rem;
}

.location-info {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    color: #475569;
}

.location-info i {
    color: #94a3b8;
    margin-right: 0.5rem;
}

.position-info {
    color: #475569;
}

.position-info .progress {
    background-color: #e2e8f0;
    height: 8px;
    border-radius: 6px;
    overflow: hidden;
}

.position-info .progress-bar {
    border-radius: 6px;
    transition: width 0.3s ease;
}

.position-info small {
    color: #64748b;
    font-size: 0.8125rem;
    display: block;
    margin-top: 0.375rem;
}

/* Badge Styles */
.badge-department {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    background: #dbeafe;
    color: #2563eb;
    border: none;
    display: inline-block;
}

.badge-employee-type {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    background: #e0f2fe;
    color: #0284c7;
    border: none;
    display: inline-block;
}

.priority-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    border: none;
    display: inline-block;
}

.priority-badge.priority-urgent {
    background: #fee2e2;
    color: #dc2626;
}

.priority-badge.priority-high {
    background: #fef3c7;
    color: #d97706;
}

.priority-badge.priority-medium {
    background: #dbeafe;
    color: #2563eb;
}

.priority-badge.priority-low {
    background: #f1f5f9;
    color: #64748b;
}

.status-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-block;
}

.status-badge.status-active {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.status-inactive {
    background: #f1f5f9;
    color: #64748b;
}

.status-badge.status-filled {
    background: #dbeafe;
    color: #2563eb;
}

.status-badge.status-suspended {
    background: #fef3c7;
    color: #d97706;
}

/* Post Actions - Table Actions */
.posts-table .post-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.posts-table .post-actions .btn {
    min-width: 36px;
    padding: 0.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
    background: #ffffff;
}

.posts-table .post-actions .btn i {
    font-size: 0.875rem;
}

.posts-table .post-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.posts-table .post-actions .btn:active {
    transform: translateY(0);
}

.posts-table .post-actions .btn-outline-primary {
    color: #1fb2d5;
    border-color: #1fb2d5;
}

.posts-table .post-actions .btn-outline-primary:hover {
    background: #1fb2d5;
    color: #ffffff;
    border-color: #1fb2d5;
}

.posts-table .post-actions .btn-outline-info {
    color: #06b6d4;
    border-color: #06b6d4;
}

.posts-table .post-actions .btn-outline-info:hover {
    background: #06b6d4;
    color: #ffffff;
    border-color: #06b6d4;
}

/* Pagination */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    margin-top: 2rem;
}

.pagination-info {
    color: #64748b;
    font-size: 0.875rem;
}

.pagination-info strong {
    color: #1e293b;
    font-weight: 600;
}

/* Form Controls */
.form-check-input {
    width: 1.125rem;
    height: 1.125rem;
    border: 1.5px solid #cbd5e1;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.form-check-input:checked {
    background-color: #1fb2d5;
    border-color: #1fb2d5;
}

.form-check-input:focus {
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .posts-modern {
        padding: 1.5rem 1rem;
    }
    
    .page-header-modern {
        flex-direction: column;
        gap: 1rem;
    }
    
    .page-actions-modern {
        width: 100%;
        justify-content: flex-start;
    }
    
    .summary-cards-modern {
        grid-template-columns: 1fr;
    }
    
    .stat-number {
        font-size: 1.75rem;
    }
    
    .filters-modern {
        padding: 1rem;
    }
    
    .filter-controls-modern {
        flex-direction: column;
    }
    
    .form-select-modern {
        width: 100%;
    }
    
    .control-buttons-modern {
        flex-direction: column;
        width: 100%;
    }
    
    .control-buttons-modern .btn {
        width: 100%;
    }
    
    .posts-table {
        font-size: 0.8125rem;
    }
    
    .posts-table th,
    .posts-table td {
        padding: 0.75rem 0.5rem;
    }
}

/* Dark theme support for Posts page */
html[data-theme="dark"] .hrdash {
    background: var(--interface-bg) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-title-main {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-subtitle-modern {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .hrdash-stat {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .hrdash-stat__label {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .hrdash-stat__value {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .hrdash-stat__meta {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .filters-modern {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .search-input-modern {
    background: transparent !important;
}

html[data-theme="dark"] .search-field {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .search-field::placeholder {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .search-icon {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .form-select-modern {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .btn-outline-modern,
html[data-theme="dark"] .btn-outline-secondary-modern {
    background-color: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .btn-outline-modern:hover,
html[data-theme="dark"] .btn-outline-secondary-modern:hover {
    background-color: var(--interface-hover) !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table-container {
    background: #1a1d23 !important;
    border: 1px solid var(--interface-border) !important;
    border-color: var(--interface-border) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2), 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

html[data-theme="dark"] .posts-table {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
    border: none !important;
    border-collapse: collapse !important;
}

html[data-theme="dark"] .posts-table * {
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .posts-table thead {
    background: #1a1d23 !important;
    border: none !important;
}

html[data-theme="dark"] .posts-table th {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-bottom: 2px solid var(--interface-border) !important;
    border-right: none !important;
    border-left: none !important;
    border-top: none !important;
}

html[data-theme="dark"] .posts-table th:first-child {
    border-left: none !important;
}

html[data-theme="dark"] .posts-table th:last-child {
    border-right: none !important;
}

html[data-theme="dark"] .posts-table tbody tr {
    background-color: #1a1d23 !important;
    border-bottom: 1px solid var(--interface-border) !important;
    border-left: none !important;
    border-right: none !important;
    border-top: none !important;
}

html[data-theme="dark"] .posts-table tbody tr:hover {
    background-color: var(--interface-hover) !important;
}

html[data-theme="dark"] .posts-table tbody tr:last-child {
    border-bottom: none !important;
}

html[data-theme="dark"] .posts-table td {
    background-color: transparent !important;
    color: var(--interface-text) !important;
    border-bottom: 1px solid var(--interface-border) !important;
    border-left: none !important;
    border-right: none !important;
    border-top: none !important;
}

html[data-theme="dark"] .posts-table td:first-child {
    border-left: none !important;
}

html[data-theme="dark"] .posts-table td:last-child {
    border-right: none !important;
}

html[data-theme="dark"] .post-title strong {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .post-title small {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .location-info {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .location-info i {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .position-info {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .position-info small {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .position-info .progress {
    background-color: rgba(255, 255, 255, 0.1) !important;
}

html[data-theme="dark"] .posts-table .post-actions .btn {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .posts-table .post-actions .btn-outline-primary {
    color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
}

html[data-theme="dark"] .posts-table .post-actions .btn-outline-primary:hover {
    background: var(--primary-color) !important;
    color: white !important;
}

html[data-theme="dark"] .posts-table .post-actions .btn-outline-info {
    color: #06b6d4 !important;
    border-color: #06b6d4 !important;
}

html[data-theme="dark"] .posts-table .post-actions .btn-outline-info:hover {
    background: #06b6d4 !important;
    color: white !important;
}

html[data-theme="dark"] .pagination-container {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .pagination-info {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .pagination-info strong {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .form-check-input {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .form-check-input:checked {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
}

</style>

<script>
// Posts Management JavaScript
class PostsManager {
    constructor() {
        this.initializeTable();
        this.bindEvents();
    }

    initializeTable() {
        // Initialize any table-specific functionality
        console.log('Posts table initialized');
    }

    bindEvents() {
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, 300);
            });
        }

        // Filter functionality
        const filters = ['departmentFilter', 'employeeTypeFilter', 'statusFilter', 'priorityFilter'];
        filters.forEach(filterId => {
            const filter = document.getElementById(filterId);
            if (filter) {
                filter.addEventListener('change', () => {
                    this.applyFilters();
                });
            }
        });

        // Select all functionality
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                const checkboxes = document.querySelectorAll('.post-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = e.target.checked;
                });
            });
        }
    }

    performSearch(query) {
        const url = new URL(window.location);
        if (query.trim()) {
            url.searchParams.set('search', query);
        } else {
            url.searchParams.delete('search');
        }
        window.location.href = url.toString();
    }

    applyFilters() {
        const url = new URL(window.location);
        
        const filters = {
            department: document.getElementById('departmentFilter')?.value || '',
            employee_type: document.getElementById('employeeTypeFilter')?.value || '',
            status: document.getElementById('statusFilter')?.value || '',
            priority: document.getElementById('priorityFilter')?.value || ''
        };

        // Clear existing filter params
        ['department', 'employee_type', 'status', 'priority'].forEach(param => {
            url.searchParams.delete(param);
        });

        // Add new filter params
        Object.entries(filters).forEach(([key, value]) => {
            if (value) {
                url.searchParams.set(key, value);
            }
        });

        window.location.href = url.toString();
    }
}

// Global functions
function exportToCSV() {
    const table = document.getElementById('postsTable');
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    
    let csv = 'Post Title,Post Code,Department,Employee Type,Location,Required,Filled,Priority,Status\n';
    
    rows.forEach(row => {
        if (row.querySelector('.post-checkbox')) {
            const cells = row.querySelectorAll('td');
            const postTitle = cells[1].querySelector('.post-title strong').textContent;
            const postCode = cells[1].querySelector('.post-title small').textContent;
            const department = cells[2].textContent.trim();
            const employeeType = cells[3].textContent.trim();
            const location = cells[4].textContent.trim();
            const positions = cells[5].textContent.trim();
            const priority = cells[6].textContent.trim();
            const status = cells[7].textContent.trim();
            
            csv += `"${postTitle}","${postCode}","${department}","${employeeType}","${location}","${positions}","${priority}","${status}"\n`;
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'posts_export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function resetFilters() {
    window.location.href = '?page=posts';
}

function deletePost(postId) {
    if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=posts';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'post_id';
        idInput.value = postId;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}


// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
    new PostsManager();
});
</script>

<?php
// Handle form submissions
if ($_POST['action'] ?? '' === 'delete') {
    $post_id = $_POST['post_id'] ?? 0;
    if ($post_id && delete_post($post_id)) {
        echo '<script>alert("Post deleted successfully"); window.location.href = "?page=posts";</script>';
    } else {
        echo '<script>alert("Error deleting post");</script>';
    }
}
?>

</div> <!-- /.container-fluid -->
