<?php
$page_title = 'Integrations - Golden Z-5 HR System';
$page = 'integrations';

// Sample integrations data
$integrations = [
    [
        'id' => 1,
        'name' => 'Payroll System',
        'description' => 'Connect with external payroll processing system',
        'status' => 'connected',
        'last_sync' => '2025-01-15 14:30',
        'type' => 'Payroll',
        'provider' => 'PayrollPro'
    ],
    [
        'id' => 2,
        'name' => 'Email Service',
        'description' => 'Send automated emails and notifications',
        'status' => 'connected',
        'last_sync' => '2025-01-15 14:25',
        'type' => 'Communication',
        'provider' => 'SendGrid'
    ],
    [
        'id' => 3,
        'name' => 'Time Tracking',
        'description' => 'Sync employee time tracking data',
        'status' => 'disconnected',
        'last_sync' => '2025-01-10 09:15',
        'type' => 'Time Management',
        'provider' => 'TimeTracker'
    ],
    [
        'id' => 4,
        'name' => 'Document Storage',
        'description' => 'Store and manage employee documents',
        'status' => 'pending',
        'last_sync' => null,
        'type' => 'Storage',
        'provider' => 'CloudDocs'
    ]
];

$total_integrations = count($integrations);
$connected = count(array_filter($integrations, function($i) { return $i['status'] === 'connected'; }));
$disconnected = count(array_filter($integrations, function($i) { return $i['status'] === 'disconnected'; }));
$pending = count(array_filter($integrations, function($i) { return $i['status'] === 'pending'; }));
?>

<div class="integrations-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>Integrations</h1>
        </div>
        <div class="page-actions">
            <button class="btn btn-outline-secondary">
                <i class="fas fa-envelope"></i>
            </button>
            <button class="btn btn-outline-secondary">
                <i class="fas fa-bell"></i>
            </button>
            <button class="btn btn-outline-primary" id="exportBtn">
                <i class="fas fa-download me-2"></i>Export CSV
            </button>
            <button class="btn btn-primary" id="addNewBtn">
                <i class="fas fa-plus me-2"></i>Add integration
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-content">
                <div class="card-label">Total integrations</div>
                <div class="card-number"><?php echo $total_integrations; ?></div>
                <div class="card-trend positive">
                    <i class="fas fa-arrow-up"></i> +1
                </div>
            </div>
            <div class="card-icon">
                <i class="fas fa-plug"></i>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-content">
                <div class="card-label">Connected</div>
                <div class="card-number"><?php echo $connected; ?></div>
                <div class="card-trend positive">
                    <i class="fas fa-arrow-up"></i> +2
                </div>
            </div>
            <div class="card-icon">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-content">
                <div class="card-label">Disconnected</div>
                <div class="card-number"><?php echo $disconnected; ?></div>
                <div class="card-trend negative">
                    <i class="fas fa-arrow-down"></i> -1
                </div>
            </div>
            <div class="card-icon">
                <i class="fas fa-circle-xmark"></i>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-content">
                <div class="card-label">Pending setup</div>
                <div class="card-number"><?php echo $pending; ?></div>
                <div class="card-trend neutral">
                    <i class="fas fa-minus"></i> 0
                </div>
            </div>
            <div class="card-icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    <!-- Table Controls -->
    <div class="table-controls">
        <div class="search-control">
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search integrations..." id="integrationSearch">
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

    <!-- Integrations Table -->
    <div class="table-container">
        <table class="integrations-table">
            <thead>
                <tr>
                    <th width="50">
                        <input type="checkbox" id="selectAllIntegrations" class="form-check-input">
                    </th>
                    <th>Integration name</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Last sync</th>
                    <th width="50"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($integrations as $integration): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input integration-checkbox" value="<?php echo $integration['id']; ?>">
                    </td>
                    <td>
                        <div class="integration-info">
                            <div class="integration-name"><?php echo htmlspecialchars($integration['name']); ?></div>
                            <div class="integration-id">ID: INT<?php echo str_pad($integration['id'], 3, '0', STR_PAD_LEFT); ?></div>
                        </div>
                    </td>
                    <td>
                        <div class="description-text"><?php echo htmlspecialchars($integration['description']); ?></div>
                    </td>
                    <td>
                        <span class="type-badge"><?php echo htmlspecialchars($integration['type']); ?></span>
                    </td>
                    <td>
                        <span class="provider-text"><?php echo htmlspecialchars($integration['provider']); ?></span>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $integration['status']; ?>">
                            <i class="fas fa-<?php echo $integration['status'] === 'connected' ? 'check' : ($integration['status'] === 'disconnected' ? 'times' : 'clock'); ?> me-1"></i>
                            <?php echo ucfirst($integration['status']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="last-sync">
                            <?php echo $integration['last_sync'] ? date('M d, Y H:i', strtotime($integration['last_sync'])) : 'Never'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="viewIntegration(<?php echo $integration['id']; ?>)">
                                    <i class="fas fa-eye me-2"></i>View
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="editIntegration(<?php echo $integration['id']; ?>)">
                                    <i class="fas fa-edit me-2"></i>Edit
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="syncIntegration(<?php echo $integration['id']; ?>)">
                                    <i class="fas fa-sync me-2"></i>Sync Now
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteIntegration(<?php echo $integration['id']; ?>)">
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
.integrations-container {
    padding: var(--spacing-xl);
    background-color: var(--interface-bg);
    min-height: 100vh;
    max-width: 1400px;
    margin: 0 auto;
}

.integration-info {
    display: flex;
    flex-direction: column;
}

.integration-name {
    font-weight: 700;
    color: var(--interface-text);
    margin-bottom: 2px;
    font-size: 1rem;
}

.integration-id {
    font-size: 0.75rem;
    color: var(--muted-color);
    margin-top: 2px;
}

.type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 0;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.provider-text {
    font-size: 0.875rem;
    color: var(--interface-text);
    font-weight: 500;
}

.last-sync {
    font-size: 0.875rem;
    color: var(--muted-color);
    font-weight: 500;
}

.status-badge.disconnected {
    background-color: #ffebee;
    color: #c62828;
}

.status-badge.pending {
    background-color: #fff3e0;
    color: #ef6c00;
}

.integrations-table {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
}

.integrations-table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid var(--interface-border);
    padding: var(--spacing-md);
    font-weight: 600;
    color: var(--interface-text);
    text-align: left;
}

.integrations-table td {
    padding: var(--spacing-md);
    border-bottom: 1px solid var(--interface-border);
    vertical-align: middle;
}

.integrations-table tbody tr:nth-child(even) {
    background-color: rgba(248, 249, 250, 0.5);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('integrationSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.integrations-table tbody tr');
            
            rows.forEach(row => {
                const name = row.querySelector('.integration-name').textContent.toLowerCase();
                const description = row.querySelector('.description-text').textContent.toLowerCase();
                const provider = row.querySelector('.provider-text').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || description.includes(searchTerm) || provider.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Select all functionality
    const selectAll = document.getElementById('selectAllIntegrations');
    const checkboxes = document.querySelectorAll('.integration-checkbox');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Export functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        alert('Export functionality will be implemented soon.');
    });
    
    // Add new functionality
    document.getElementById('addNewBtn').addEventListener('click', function() {
        alert('Add new integration functionality will be implemented soon.');
    });
});

function viewIntegration(id) {
    alert('View integration: ' + id);
}

function editIntegration(id) {
    alert('Edit integration: ' + id);
}

function syncIntegration(id) {
    if (confirm('Are you sure you want to sync this integration now?')) {
        alert('Syncing integration: ' + id);
    }
}

function deleteIntegration(id) {
    if (confirm('Are you sure you want to delete this integration?')) {
        alert('Delete integration: ' + id);
    }
}
</script>
