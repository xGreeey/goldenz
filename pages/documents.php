<?php
$page_title = '201 Files - Golden Z-5 HR System';
$page = 'documents';

// Get database connection
$pdo = get_db_connection();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'upload_document' && isset($_FILES['document_file'])) {
        // Handle document upload (to be implemented with proper file handling)
        redirect_with_message('?page=documents', 'Document uploaded successfully!', 'success');
    } elseif ($action === 'delete_document' && isset($_POST['document_id'])) {
        // Handle document deletion (to be implemented)
        redirect_with_message('?page=documents', 'Document deleted successfully!', 'success');
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$selected_category = $_GET['category'] ?? '';
$selected_tag = $_GET['tag'] ?? '';

// Get all employees for filter dropdown
$employees = get_employees();

// Document categories with stats
$categories = [
    [
        'name' => 'Personal Records',
        'file_count' => 127,
        'size' => '45.8 mb',
        'color' => '#c8f4e0',
        'icon' => 'fa-id-card'
    ],
    [
        'name' => 'Employment Contracts',
        'file_count' => 89,
        'size' => '12.3 mb',
        'color' => '#d4e8ff',
        'icon' => 'fa-file-contract'
    ],
    [
        'name' => 'Government IDs',
        'file_count' => 156,
        'size' => '67.2 mb',
        'color' => '#fff4cc',
        'icon' => 'fa-id-badge'
    ],
    [
        'name' => 'Certifications',
        'file_count' => 94,
        'size' => '28.9 mb',
        'color' => '#ffe4d9',
        'icon' => 'fa-certificate'
    ],
];

// Available tags
$available_tags = [
    ['name' => 'Urgent', 'color' => '#ef4444'],
    ['name' => 'NBI Clearance', 'color' => '#8b5cf6'],
    ['name' => 'Birth Certificate', 'color' => '#06b6d4'],
    ['name' => 'TOR', 'color' => '#10b981'],
    ['name' => 'Contract', 'color' => '#f59e0b'],
    ['name' => 'Medical', 'color' => '#ec4899'],
];

// Temporary: Mock data for documents
$documents = [
    ['id' => 1, 'name' => 'John_Doe_NBI.pdf', 'tag' => 'NBI Clearance', 'tag_color' => '#8b5cf6', 'size' => '1.23 mb', 'modified' => '24.02.2022', 'employee' => 'John Doe'],
    ['id' => 2, 'name' => 'Maria_Santos_Birth_Cert.pdf', 'tag' => 'Birth Certificate', 'tag_color' => '#06b6d4', 'size' => '890 kb', 'modified' => '23.02.2022', 'employee' => 'Maria Santos'],
    ['id' => 3, 'name' => 'Pedro_Cruz_Contract_2024.pdf', 'tag' => 'Contract', 'tag_color' => '#f59e0b', 'size' => '2.45 mb', 'modified' => '22.02.2022', 'employee' => 'Pedro Cruz'],
    ['id' => 4, 'name' => 'Ana_Reyes_Medical_Cert.pdf', 'tag' => 'Medical', 'tag_color' => '#ec4899', 'size' => '567 kb', 'modified' => '21.02.2022', 'employee' => 'Ana Reyes'],
    ['id' => 5, 'name' => 'Luis_Bautista_TOR.pdf', 'tag' => 'TOR', 'tag_color' => '#10b981', 'size' => '3.12 mb', 'modified' => '20.02.2022', 'employee' => 'Luis Bautista'],
    ['id' => 6, 'name' => 'Elena_Garcia_NBI.pdf', 'tag' => 'NBI Clearance', 'tag_color' => '#8b5cf6', 'size' => '1.05 mb', 'modified' => '19.02.2022', 'employee' => 'Elena Garcia'],
    ['id' => 7, 'name' => 'Carlos_Mendoza_Contract.pdf', 'tag' => 'Contract', 'tag_color' => '#f59e0b', 'size' => '1.89 mb', 'modified' => '18.02.2022', 'employee' => 'Carlos Mendoza'],
    ['id' => 8, 'name' => 'Sofia_Torres_Birth_Cert.pdf', 'tag' => 'Birth Certificate', 'tag_color' => '#06b6d4', 'size' => '756 kb', 'modified' => '17.02.2022', 'employee' => 'Sofia Torres'],
    ['id' => 9, 'name' => 'Miguel_Ramos_Medical.pdf', 'tag' => 'Medical', 'tag_color' => '#ec4899', 'size' => '425 kb', 'modified' => '16.02.2022', 'employee' => 'Miguel Ramos'],
    ['id' => 10, 'name' => 'Isabel_Flores_TOR.pdf', 'tag' => 'TOR', 'tag_color' => '#10b981', 'size' => '2.67 mb', 'modified' => '15.02.2022', 'employee' => 'Isabel Flores'],
];

// Storage breakdown
$storage_breakdown = [
    ['type' => 'Documents', 'size' => '89.2', 'unit' => 'mb', 'icon' => 'fa-file-pdf', 'color' => '#3b82f6'],
    ['type' => 'Images', 'size' => '34.8', 'unit' => 'mb', 'icon' => 'fa-image', 'color' => '#8b5cf6'],
    ['type' => 'Scans', 'size' => '28.4', 'unit' => 'mb', 'icon' => 'fa-file-image', 'color' => '#06b6d4'],
    ['type' => 'Other', 'size' => '1.7', 'unit' => 'mb', 'icon' => 'fa-folder', 'color' => '#6b7280'],
];

$total_used = 154.1; // mb
$total_space = 500; // mb
$percentage_used = ($total_used / $total_space) * 100;
?>

<div class="container-fluid hrdash">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <?php foreach ($categories as $index => $category): ?>
            <div class="col-xl-3 col-md-6">
                <div class="card hrdash-stat <?php echo $index === 0 ? 'hrdash-stat--primary' : ''; ?>">
                    <div class="hrdash-stat__header">
                        <div class="hrdash-stat__label">
                            <i class="fas <?php echo $category['icon']; ?> me-2"></i>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </div>
                    </div>
                    <div class="hrdash-stat__content">
                        <div class="hrdash-stat__value"><?php echo number_format($category['file_count']); ?></div>
                    </div>
                    <div class="hrdash-stat__meta"><?php echo $category['file_count']; ?> files • <?php echo $category['size']; ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-xl-9">
            <!-- All Files Section -->
            <div class="card card-modern mb-4">
                <div class="card-body-modern">
                    <!-- Header with actions -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title-modern mb-1">All Documents</h5>
                            <div class="card-subtitle">201 Files Management</div>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-outline-modern" onclick="exportDocuments()" title="Export document list">
                                <i class="fas fa-file-export me-2"></i>Export
                            </button>
                            <button type="button" class="btn btn-primary-modern" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                                <i class="fas fa-upload me-2"></i>Upload
                            </button>
                        </div>
                    </div>

                    <!-- Documents Table -->
                    <div class="table-responsive">
                        <table class="table table-borderless documents-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" id="selectAll">
                                    </th>
                                    <th>Name</th>
                                    <th>Tag</th>
                                    <th>Size</th>
                                    <th>Last Modified</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documents)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No documents found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr class="document-row">
                                            <td>
                                                <input type="checkbox" class="form-check-input">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="file-icon me-2">
                                                        <i class="fas fa-file-pdf text-danger"></i>
                                                    </div>
                                                    <span class="file-name"><?php echo htmlspecialchars($doc['name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($doc['tag']): ?>
                                                    <span class="tag-badge" style="background-color: <?php echo $doc['tag_color']; ?>;">
                                                        <?php echo htmlspecialchars($doc['tag']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">no tag</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted"><?php echo htmlspecialchars($doc['size']); ?></td>
                                            <td class="text-muted"><?php echo htmlspecialchars($doc['modified']); ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i>View</a></li>
                                                        <li><a class="dropdown-item" href="#"><i class="fas fa-download me-2"></i>Download</a></li>
                                                        <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Rename</a></li>
                                                        <li><a class="dropdown-item" href="#"><i class="fas fa-share me-2"></i>Share</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                                    </ul>
                                                </div>
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

        <!-- Right Sidebar - Space Usage -->
        <div class="col-xl-3">
            <div class="card card-modern mb-4">
                <div class="card-body-modern">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="mb-0 fw-bold">Storage Space</h6>
                    </div>

                    <!-- Storage Gauge -->
                    <div class="storage-gauge-container mb-4">
                        <div class="storage-gauge">
                            <svg viewBox="0 0 200 200" class="gauge-svg">
                                <!-- Background circle -->
                                <circle cx="100" cy="100" r="80" fill="none" stroke="#f3f4f6" stroke-width="20"/>
                                <!-- Progress circle -->
                                <circle cx="100" cy="100" r="80" fill="none" stroke="#3b82f6" stroke-width="20"
                                        stroke-dasharray="<?php echo $percentage_used * 5.026; ?> 502.6"
                                        stroke-linecap="round"
                                        transform="rotate(-90 100 100)"/>
                            </svg>
                            <div class="gauge-center">
                                <div class="gauge-value"><?php echo number_format($total_used, 1); ?> mb</div>
                                <div class="gauge-label">of <?php echo $total_space; ?> mb</div>
                            </div>
                        </div>
                    </div>

                    <!-- Storage Breakdown -->
                    <div class="storage-breakdown">
                        <?php foreach ($storage_breakdown as $item): ?>
                            <div class="breakdown-item">
                                <div class="breakdown-icon" style="background-color: <?php echo $item['color']; ?>20;">
                                    <i class="fas <?php echo $item['icon']; ?>" style="color: <?php echo $item['color']; ?>;"></i>
                                </div>
                                <div class="breakdown-info">
                                    <div class="breakdown-type"><?php echo $item['type']; ?></div>
                                    <div class="breakdown-size"><?php echo $item['size'] . ' ' . $item['unit']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="d-grid gap-2 mt-4">
                        <button type="button" class="btn btn-primary-modern" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                            <i class="fas fa-upload me-2"></i>Upload Document
                        </button>
                        <button type="button" class="btn btn-outline-modern" onclick="window.location.href='?page=employees'">
                            <i class="fas fa-users me-2"></i>View Employees
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="upload_document">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo htmlspecialchars(($emp['surname'] ?? '') . ', ' . ($emp['first_name'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Document Type (Tag)</label>
                        <select name="document_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <?php foreach ($available_tags as $tag): ?>
                                <option value="<?php echo htmlspecialchars($tag['name']); ?>">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" name="document_file" class="form-control" required>
                        <small class="text-muted">Max file size: 5MB. Allowed formats: PDF, DOC, DOCX, JPG, PNG</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Document Storage Styles - Golden Z-5 Branding */

/* Documents Table - Matches Golden Z-5 table styles */
.documents-table thead th {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem 0.75rem;
    border-bottom: 2px solid rgba(0, 0, 0, 0.05);
}

.documents-table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

.document-row:hover {
    background-color: rgba(59, 130, 246, 0.02);
}

.file-icon {
    font-size: 1.1rem;
}

.file-name {
    font-weight: 500;
}

.tag-badge {
    display: inline-block;
    padding: 0.35rem 0.85rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Storage Gauge - Golden Z-5 Style */
.storage-gauge {
    position: relative;
    width: 200px;
    height: 200px;
    margin: 0 auto;
}

.gauge-svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 4px 6px rgba(59, 130, 246, 0.1));
}

.gauge-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.gauge-value {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1.2;
}

.gauge-label {
    font-size: 0.85rem;
    opacity: 0.7;
    margin-top: 0.5rem;
}

/* Storage Breakdown */
.storage-breakdown {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    margin-top: 2rem;
}

.breakdown-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.breakdown-item:hover {
    background-color: rgba(59, 130, 246, 0.03);
}

.breakdown-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.breakdown-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.breakdown-type {
    font-weight: 600;
}

.breakdown-size {
    font-weight: 600;
    color: #3b82f6;
}
</style>

<script>
// Export documents to CSV
function exportDocuments() {
    alert('Export functionality will be implemented soon.');
    // TODO: Implement CSV export
}

// Select all checkboxes
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.documents-table tbody input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
});
</script>
