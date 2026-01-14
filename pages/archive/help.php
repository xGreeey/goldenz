<?php
$page_title = 'Help & Support - Golden Z-5 HR System';
$page = 'help';

// Sample help data
$help_articles = [
    [
        'id' => 1,
        'title' => 'Getting Started with Employee Management',
        'description' => 'Learn how to add, edit, and manage employee records',
        'category' => 'Employee Management',
        'views' => 156,
        'last_updated' => '2025-01-10',
        'status' => 'published'
    ],
    [
        'id' => 2,
        'title' => 'Setting Up Time Off Policies',
        'description' => 'Configure time off policies and approval workflows',
        'category' => 'Time Management',
        'views' => 89,
        'last_updated' => '2025-01-08',
        'status' => 'published'
    ],
    [
        'id' => 3,
        'title' => 'Troubleshooting Common Issues',
        'description' => 'Solutions for frequently encountered problems',
        'category' => 'Troubleshooting',
        'views' => 234,
        'last_updated' => '2025-01-05',
        'status' => 'published'
    ],
    [
        'id' => 4,
        'title' => 'API Integration Guide',
        'description' => 'How to integrate with external systems using our API',
        'category' => 'Integration',
        'views' => 67,
        'last_updated' => '2025-01-03',
        'status' => 'draft'
    ]
];

$total_articles = count($help_articles);
$published = count(array_filter($help_articles, function($a) { return $a['status'] === 'published'; }));
$drafts = count(array_filter($help_articles, function($a) { return $a['status'] === 'draft'; }));
$total_views = array_sum(array_column($help_articles, 'views'));
?>

<div class="help-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>Help & Support</h1>
        </div>
        <div class="page-actions">
            <button class="btn btn-outline-secondary">
                <i class="fas fa-envelope"></i>
            </button>
            <button class="btn btn-outline-secondary">
                <i class="fas fa-bell"></i>
            </button>
            <button class="btn btn-outline-primary" id="exportBtn">
                <i class="fas fa-download me-2"></i>Export PDF
            </button>
            <button class="btn btn-primary" id="addNewBtn">
                <i class="fas fa-plus me-2"></i>Add article
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-content">
                <div class="card-label">Total articles</div>
                <div class="card-number"><?php echo $total_articles; ?></div>
                <div class="card-trend positive">
                    <i class="fas fa-arrow-up"></i> +2
                </div>
            </div>
            <div class="card-icon">
                <i class="fas fa-book"></i>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-content">
                <div class="card-label">Published</div>
                <div class="card-number"><?php echo $published; ?></div>
                <div class="card-trend positive">
                    <i class="fas fa-arrow-up"></i> +3
                </div>
            </div>
            <div class="card-icon">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-content">
                <div class="card-label">Drafts</div>
                <div class="card-number"><?php echo $drafts; ?></div>
                <div class="card-trend negative">
                    <i class="fas fa-arrow-down"></i> -1
                </div>
            </div>
            <div class="card-icon">
                <i class="fas fa-edit"></i>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-content">
                <div class="card-label">Total views</div>
                <div class="card-number"><?php echo $total_views; ?></div>
                <div class="card-trend positive">
                    <i class="fas fa-arrow-up"></i> +45
                </div>
            </div>
            <div class="card-icon">
                <i class="fas fa-eye"></i>
            </div>
        </div>
    </div>

    <!-- Quick Help Section -->
    <div class="quick-help-section">
        <h3>Quick Help</h3>
        <div class="quick-help-grid">
            <div class="quick-help-card">
                <i class="fas fa-phone"></i>
                <h4>Contact Support</h4>
                <p>Get help from our support team</p>
                <button class="btn btn-outline-primary btn-sm">Contact</button>
            </div>
            <div class="quick-help-card">
                <i class="fas fa-video"></i>
                <h4>Video Tutorials</h4>
                <p>Watch step-by-step tutorials</p>
                <button class="btn btn-outline-primary btn-sm">Watch</button>
            </div>
            <div class="quick-help-card">
                <i class="fas fa-comments"></i>
                <h4>Live Chat</h4>
                <p>Chat with support in real-time</p>
                <button class="btn btn-outline-primary btn-sm">Start Chat</button>
            </div>
            <div class="quick-help-card">
                <i class="fas fa-bug"></i>
                <h4>Report Issue</h4>
                <p>Report bugs or technical issues</p>
                <button class="btn btn-outline-primary btn-sm">Report</button>
            </div>
        </div>
    </div>

    <!-- Table Controls -->
    <div class="table-controls">
        <div class="search-control">
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search help articles..." id="helpSearch">
            </div>
        </div>
        <div class="control-buttons">
            <button class="btn btn-outline-secondary btn-sm" id="filterBtn">
                <i class="fas fa-filter me-1"></i>Filter
            </button>
            <button class="btn btn-outline-secondary btn-sm" id="sortBtn">
                <i class="fas fa-sort me-1"></i>Sort
            </button>
        </div>
    </div>

    <!-- Help Articles Table -->
    <div class="table-container">
        <table class="help-table">
            <thead>
                <tr>
                    <th width="50">
                        <input type="checkbox" id="selectAllArticles" class="form-check-input">
                    </th>
                    <th>Article title</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Views</th>
                    <th>Status</th>
                    <th>Last updated</th>
                    <th width="50"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($help_articles as $article): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input article-checkbox" value="<?php echo $article['id']; ?>">
                    </td>
                    <td>
                        <div class="article-info">
                            <div class="article-title"><?php echo htmlspecialchars($article['title']); ?></div>
                            <div class="article-id">ID: ART<?php echo str_pad($article['id'], 3, '0', STR_PAD_LEFT); ?></div>
                        </div>
                    </td>
                    <td>
                        <div class="description-text"><?php echo htmlspecialchars($article['description']); ?></div>
                    </td>
                    <td>
                        <span class="category-badge"><?php echo htmlspecialchars($article['category']); ?></span>
                    </td>
                    <td>
                        <div class="views-info">
                            <span class="views-count"><?php echo $article['views']; ?></span>
                            <span class="views-label">views</span>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $article['status']; ?>">
                            <i class="fas fa-<?php echo $article['status'] === 'published' ? 'check' : 'edit'; ?> me-1"></i>
                            <?php echo ucfirst($article['status']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="last-updated"><?php echo date('M d, Y', strtotime($article['last_updated'])); ?></span>
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="viewArticle(<?php echo $article['id']; ?>)">
                                    <i class="fas fa-eye me-2"></i>View
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="editArticle(<?php echo $article['id']; ?>)">
                                    <i class="fas fa-edit me-2"></i>Edit
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="publishArticle(<?php echo $article['id']; ?>)">
                                    <i class="fas fa-upload me-2"></i>Publish
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteArticle(<?php echo $article['id']; ?>)">
                                    <i class="fas fa-trash me-2"></i>Delete
                                </a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-container">
        <div class="pagination-info">
            <select class="form-select form-select-sm" id="perPageSelect">
                <option value="10" selected>10 records</option>
                <option value="25">25 records</option>
                <option value="50">50 records</option>
            </select>
        </div>
        <div class="pagination-controls">
            <button class="btn btn-outline-secondary btn-sm" disabled>
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="btn btn-primary btn-sm">1</button>
            <button class="btn btn-outline-secondary btn-sm">2</button>
            <button class="btn btn-outline-secondary btn-sm">3</button>
            <span class="pagination-ellipsis">...</span>
            <button class="btn btn-outline-secondary btn-sm">10</button>
            <button class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div class="pagination-info">
            <span>1 - 4 of 4</span>
        </div>
    </div>
</div>

<style>
.help-container {
    padding: var(--spacing-xl);
    background-color: var(--interface-bg);
    min-height: 100vh;
    max-width: 1400px;
    margin: 0 auto;
}

.quick-help-section {
    background: white;
    border: 1px solid var(--interface-border);
    border-radius: 0;
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-lg);
}

.quick-help-section h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--interface-text);
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-sm);
    border-bottom: 1px solid var(--interface-border-light);
}

.quick-help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
}

.quick-help-card {
    text-align: center;
    padding: var(--spacing-lg);
    border: 1px solid var(--interface-border-light);
    border-radius: 0;
    transition: all 0.2s ease;
}

.quick-help-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.quick-help-card i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: var(--spacing-md);
}

.quick-help-card h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--interface-text);
    margin-bottom: var(--spacing-sm);
}

.quick-help-card p {
    color: var(--muted-color);
    margin-bottom: var(--spacing-md);
    font-size: 0.875rem;
}

.article-info {
    display: flex;
    flex-direction: column;
}

.article-title {
    font-weight: 700;
    color: var(--interface-text);
    margin-bottom: 2px;
    font-size: 1rem;
}

.article-id {
    font-size: 0.75rem;
    color: var(--muted-color);
    margin-top: 2px;
}

.category-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 0;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    background-color: #e8f5e8;
    color: #2e7d32;
}

.views-info {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.views-count {
    font-weight: 700;
    color: var(--interface-text);
    font-size: 1.1rem;
}

.views-label {
    font-size: 0.75rem;
    color: var(--muted-color);
    text-transform: uppercase;
}

.last-updated {
    font-size: 0.875rem;
    color: var(--interface-text);
    font-weight: 500;
}

.status-badge.draft {
    background-color: #f3f4f6;
    color: #6b7280;
}

.help-table {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
}

.help-table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid var(--interface-border);
    padding: var(--spacing-md);
    font-weight: 600;
    color: var(--interface-text);
    text-align: left;
}

.help-table td {
    padding: var(--spacing-md);
    border-bottom: 1px solid var(--interface-border);
    vertical-align: middle;
}

.help-table tbody tr:nth-child(even) {
    background-color: rgba(248, 249, 250, 0.5);
}

@media (max-width: 768px) {
    .quick-help-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('helpSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.help-table tbody tr');
            
            rows.forEach(row => {
                const title = row.querySelector('.article-title').textContent.toLowerCase();
                const description = row.querySelector('.description-text').textContent.toLowerCase();
                const category = row.querySelector('.category-badge').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm) || category.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Select all functionality
    const selectAll = document.getElementById('selectAllArticles');
    const checkboxes = document.querySelectorAll('.article-checkbox');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Export functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        alert('Export PDF functionality will be implemented soon.');
    });
    
    // Add new functionality
    document.getElementById('addNewBtn').addEventListener('click', function() {
        alert('Add new article functionality will be implemented soon.');
    });
});

function viewArticle(id) {
    alert('View article: ' + id);
}

function editArticle(id) {
    alert('Edit article: ' + id);
}

function publishArticle(id) {
    if (confirm('Are you sure you want to publish this article?')) {
        alert('Publish article: ' + id);
    }
}

function deleteArticle(id) {
    if (confirm('Are you sure you want to delete this article?')) {
        alert('Delete article: ' + id);
    }
}
</script>
