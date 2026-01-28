<?php
$page_title = '201 Files - Golden Z-5 HR System';
$page = 'documents';

// Get database connection
$pdo = get_db_connection();

// File operations are now handled via API endpoints (api/employee_files.php)
// No server-side form handling needed here

// Get filter parameters
$search = $_GET['search'] ?? '';
$selected_category = $_GET['category'] ?? '';
$selected_tag = $_GET['tag'] ?? '';

// Get all employees for filter dropdown
$employees = get_employees();

// Available tags (document types)
$available_tags = [
    ['name' => 'Urgent', 'color' => '#ef4444'],
    ['name' => 'NBI Clearance', 'color' => '#8b5cf6'],
    ['name' => 'Birth Certificate', 'color' => '#06b6d4'],
    ['name' => 'TOR', 'color' => '#10b981'],
    ['name' => 'Contract', 'color' => '#f59e0b'],
    ['name' => 'Medical', 'color' => '#ec4899'],
    ['name' => 'Personal Records', 'color' => '#c8f4e0'],
    ['name' => 'Employment Contract', 'color' => '#d4e8ff'],
    ['name' => 'Government ID', 'color' => '#fff4cc'],
    ['name' => 'Certification', 'color' => '#ffe4d9'],
];

// Helper function to format file size
function format_file_size($bytes) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

// Helper function to get tag color
function get_tag_color($tag_name, $available_tags) {
    foreach ($available_tags as $tag) {
        if (strcasecmp($tag['name'], $tag_name) === 0) {
            return $tag['color'];
        }
    }
    return '#6b7280'; // Default gray color
}

// Fetch documents from new secure employee_files table
$employee_folders = [];
$all_documents = [];

try {
    // Check if employee_files table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'employee_files'");
    $table_exists = $table_check->rowCount() > 0;
    
    if ($table_exists) {
        // Fetch all documents from secure employee_files table
        $documents_sql = "SELECT ef.*, 
                                 e.id as employee_id,
                                 e.surname, 
                                 e.first_name,
                                 e.middle_name,
                                 CONCAT(e.surname, ', ', e.first_name) as employee_name,
                                 u.name as uploaded_by_name
                          FROM employee_files ef
                          LEFT JOIN employees e ON ef.employee_id = e.id
                          LEFT JOIN users u ON ef.uploaded_by = u.id
                          WHERE ef.deleted_at IS NULL
                          ORDER BY ef.created_at DESC";
        
        $documents_stmt = $pdo->prepare($documents_sql);
        $documents_stmt->execute();
        $all_documents = $documents_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log('Error fetching documents: ' . $e->getMessage());
    $all_documents = [];
}

// Process employees and create folders
foreach ($employees as $employee) {
    $employee_id = $employee['id'];
    $employee_name = trim(($employee['surname'] ?? '') . ', ' . ($employee['first_name'] ?? ''));
    if (empty($employee_name) || $employee_name === ', ') {
        $employee_name = 'Employee #' . $employee_id;
    }
    
    // Get documents for this employee
    $employee_docs = [];
    foreach ($all_documents as $doc) {
        if (isset($doc['employee_id']) && $doc['employee_id'] == $employee_id) {
            $file_size = isset($doc['size_bytes']) ? (int)$doc['size_bytes'] : 0;
            $document_type = $doc['category'] ?? 'Other';
            
            $employee_docs[] = [
                'id' => $doc['id'] ?? null,
                'name' => $doc['original_filename'] ?? 'Unknown',
                'tag' => $document_type,
                'tag_color' => get_tag_color($document_type, $available_tags),
                'size' => format_file_size($file_size),
                'modified' => isset($doc['created_at']) ? date('d.m.Y', strtotime($doc['created_at'])) : 'N/A',
                'employee' => $employee_name,
                'employee_id' => $employee_id,
                'file_id' => $doc['id'] ?? null,
                'uploaded_by' => $doc['uploaded_by_name'] ?? 'Unknown'
            ];
        }
    }
    
    // Create folder for this employee (even if no documents)
    $employee_folders[$employee_id] = [
        'employee_name' => $employee_name,
        'employee_id' => $employee_id,
        'documents' => $employee_docs
    ];
}

// Apply search and tag filters
if (!empty($search) || !empty($selected_tag)) {
    $filtered_folders = [];
    foreach ($employee_folders as $folder) {
        $matches_search = true;
        $matches_tag = true;
        
        // Search filter - check employee name
        if (!empty($search)) {
            $search_lower = strtolower($search);
            $employee_name_lower = strtolower($folder['employee_name']);
            $matches_search = strpos($employee_name_lower, $search_lower) !== false;
            
            // Also check document names if search doesn't match employee name
            if (!$matches_search) {
                foreach ($folder['documents'] as $doc) {
                    if (strpos(strtolower($doc['name']), $search_lower) !== false) {
                        $matches_search = true;
                        break;
                    }
                }
            }
        }
        
        // Tag filter - check if any document matches the tag
        if (!empty($selected_tag)) {
            $matches_tag = false;
            foreach ($folder['documents'] as $doc) {
                if (strcasecmp($doc['tag'], $selected_tag) === 0) {
                    $matches_tag = true;
                    break;
                }
            }
        }
        
        // If tag filter is active and folder has no matching documents, exclude it
        // But if search matches, include it even if tag doesn't match
        if ($matches_search && ($matches_tag || empty($selected_tag) || !empty($search))) {
            // If tag filter is active, filter documents within the folder
            if (!empty($selected_tag) && $matches_tag) {
                $filtered_docs = [];
                foreach ($folder['documents'] as $doc) {
                    if (strcasecmp($doc['tag'], $selected_tag) === 0) {
                        $filtered_docs[] = $doc;
                    }
                }
                $folder['documents'] = $filtered_docs;
            }
            $filtered_folders[] = $folder;
        }
    }
    $employee_folders = $filtered_folders;
}

// Sort folders by employee name
usort($employee_folders, function($a, $b) {
    return strcasecmp($a['employee_name'], $b['employee_name']);
});

// Calculate category stats from actual documents
$category_stats = [
    'Personal Records' => ['count' => 0, 'size' => 0],
    'Employment Contracts' => ['count' => 0, 'size' => 0],
    'Government IDs' => ['count' => 0, 'size' => 0],
    'Certifications' => ['count' => 0, 'size' => 0],
];

foreach ($all_documents as $doc) {
    $doc_type = strtolower($doc['document_type'] ?? '');
    $file_size = isset($doc['file_size']) ? (int)$doc['file_size'] : 0;
    
    if (stripos($doc_type, 'personal') !== false || stripos($doc_type, 'record') !== false) {
        $category_stats['Personal Records']['count']++;
        $category_stats['Personal Records']['size'] += $file_size;
    } elseif (stripos($doc_type, 'contract') !== false || stripos($doc_type, 'employment') !== false) {
        $category_stats['Employment Contracts']['count']++;
        $category_stats['Employment Contracts']['size'] += $file_size;
    } elseif (stripos($doc_type, 'government') !== false || stripos($doc_type, 'id') !== false || stripos($doc_type, 'nbi') !== false) {
        $category_stats['Government IDs']['count']++;
        $category_stats['Government IDs']['size'] += $file_size;
    } elseif (stripos($doc_type, 'certif') !== false || stripos($doc_type, 'tor') !== false) {
        $category_stats['Certifications']['count']++;
        $category_stats['Certifications']['size'] += $file_size;
    }
}

// Document categories with stats
$categories = [
    [
        'name' => 'Personal Records',
        'file_count' => $category_stats['Personal Records']['count'],
        'size' => format_file_size($category_stats['Personal Records']['size']),
        'color' => '#c8f4e0',
        'icon' => 'fa-id-card'
    ],
    [
        'name' => 'Employment Contracts',
        'file_count' => $category_stats['Employment Contracts']['count'],
        'size' => format_file_size($category_stats['Employment Contracts']['size']),
        'color' => '#d4e8ff',
        'icon' => 'fa-file-contract'
    ],
    [
        'name' => 'Government IDs',
        'file_count' => $category_stats['Government IDs']['count'],
        'size' => format_file_size($category_stats['Government IDs']['size']),
        'color' => '#fff4cc',
        'icon' => 'fa-id-badge'
    ],
    [
        'name' => 'Certifications',
        'file_count' => $category_stats['Certifications']['count'],
        'size' => format_file_size($category_stats['Certifications']['size']),
        'color' => '#ffe4d9',
        'icon' => 'fa-certificate'
    ],
];
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
        <div class="col-xl-12">
            <!-- All Files Section -->
            <div class="card card-modern mb-4">
                <div class="card-body-modern">
                    <!-- Header with actions -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title-modern mb-1">Employee Document Folders</h5>
                            <div class="card-subtitle">201 Files Management - One folder per employee</div>
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

                    <!-- Search and Filter Bar -->
                    <div class="d-flex gap-2 mb-4 flex-wrap">
                        <div class="flex-grow-1" style="min-width: 200px;">
                            <input type="text" id="documentSearch" class="form-control form-control-sm" placeholder="Search folders or files..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div style="flex: 0 0 auto; min-width: 180px;">
                            <select id="tagFilter" class="form-select form-select-sm">
                                <option value="">All Tags</option>
                                <?php foreach ($available_tags as $tag): ?>
                                    <option value="<?php echo htmlspecialchars($tag['name']); ?>" <?php echo $selected_tag === $tag['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Employee Folders List -->
                    <div class="employee-folders-container">
                        <?php if (empty($employee_folders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No employee folders found</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($employee_folders as $folder): ?>
                                <div class="employee-folder mb-3" data-folder-id="<?php echo $folder['employee_id']; ?>" data-employee-name="<?php echo htmlspecialchars($folder['employee_name']); ?>">
                                    <!-- Folder Header -->
                                    <div class="folder-header" onclick="toggleFolder(<?php echo $folder['employee_id']; ?>)">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center flex-grow-1">
                                                <i class="fas fa-chevron-right folder-chevron me-3" id="chevron-<?php echo $folder['employee_id']; ?>"></i>
                                                <i class="fas fa-folder folder-icon me-3"></i>
                                                <div class="flex-grow-1">
                                                    <div class="folder-name fw-semibold">
                                                        <?php 
                                                        // Format: "ID - Employee Name" if ID exists, otherwise just name
                                                        $folder_label = $folder['employee_id'] ? $folder['employee_id'] . ' - ' . $folder['employee_name'] : $folder['employee_name'];
                                                        echo htmlspecialchars($folder_label);
                                                        ?>
                                                    </div>
                                                    <div class="folder-meta text-muted small mt-1">
                                                        <span class="file-count-badge"><?php echo count($folder['documents']); ?></span> document<?php echo count($folder['documents']) !== 1 ? 's' : ''; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-modern ms-3" onclick="event.stopPropagation(); uploadToFolder(<?php echo $folder['employee_id']; ?>, '<?php echo htmlspecialchars($folder['employee_name']); ?>');" title="Upload to this folder">
                                                <i class="fas fa-upload me-1"></i>Upload
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Folder Content (Documents) -->
                                    <div class="folder-content" id="folder-content-<?php echo $folder['employee_id']; ?>" style="display: none;">
                                        <?php if (empty($folder['documents'])): ?>
                                            <div class="text-center py-3 text-muted small">
                                                <i class="fas fa-inbox me-2"></i>No documents in this folder
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless documents-table-folder">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 40px;">
                                                                <input type="checkbox" class="form-check-input folder-select-all" data-folder-id="<?php echo $folder['employee_id']; ?>">
                                                            </th>
                                                            <th>File Name</th>
                                                            <th>Type</th>
                                                            <th>Size</th>
                                                            <th>Last Modified</th>
                                                            <th style="width: 50px;"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($folder['documents'] as $doc): ?>
                                                            <tr class="document-row">
                                                                <td>
                                                                    <input type="checkbox" class="form-check-input document-checkbox" data-document-id="<?php echo $doc['id']; ?>">
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="file-icon me-2">
                                                                            <?php
                                                                            $ext = strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION));
                                                                            if (in_array($ext, ['pdf'])) {
                                                                                echo '<i class="fas fa-file-pdf text-danger"></i>';
                                                                            } elseif (in_array($ext, ['doc', 'docx'])) {
                                                                                echo '<i class="fas fa-file-word text-primary"></i>';
                                                                            } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                                                                echo '<i class="fas fa-file-image text-success"></i>';
                                                                            } else {
                                                                                echo '<i class="fas fa-file text-muted"></i>';
                                                                            }
                                                                            ?>
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
                                                                <td class="text-muted small"><?php echo htmlspecialchars($doc['size']); ?></td>
                                                                <td class="text-muted small"><?php echo htmlspecialchars($doc['modified']); ?></td>
                                                                <td>
                                                                    <div class="dropdown">
                                                                        <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown">
                                                                            <i class="fas fa-ellipsis-vertical"></i>
                                                                        </button>
                                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                                            <?php if (!empty($doc['file_id'])): ?>
                                                                                <li><a class="dropdown-item" href="#" onclick="downloadFile(<?php echo $doc['file_id']; ?>); return false;"><i class="fas fa-download me-2"></i>Download</a></li>
                                                                            <?php else: ?>
                                                                                <li><a class="dropdown-item" href="#" onclick="alert('File not available'); return false;"><i class="fas fa-download me-2"></i>Download</a></li>
                                                                            <?php endif; ?>
                                                                            <li><hr class="dropdown-divider"></li>
                                                                            <li><a class="dropdown-item text-danger" href="#" onclick="if(confirm('Are you sure you want to delete this document?')) { deleteFile(<?php echo $doc['file_id'] ?? $doc['id']; ?>); } return false;"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                                                        </ul>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
            <form id="uploadDocumentForm" enctype="multipart/form-data" data-no-transition="true" onsubmit="return false;">
                <div class="modal-body">
                    <div id="uploadError" class="alert alert-danger" style="display: none;"></div>
                    <div id="uploadSuccess" class="alert alert-success" style="display: none;"></div>
                    <div class="mb-3">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" id="uploadEmployeeId" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo htmlspecialchars(($emp['surname'] ?? '') . ', ' . ($emp['first_name'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Document will be stored securely in this employee's dedicated folder</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Document Category <span class="text-danger">*</span></label>
                        <select name="category" id="uploadCategory" class="form-select" required>
                            <option value="">Select Category</option>
                            <option value="Personal Records">Personal Records</option>
                            <option value="Contracts">Contracts</option>
                            <option value="Government IDs">Government IDs</option>
                            <option value="Certifications">Certifications</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="uploadFile" class="form-control" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small class="text-muted">Max file size: 20MB. Allowed formats: PDF, DOC, DOCX, JPG, PNG</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-modern" id="uploadSubmitBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Upload to Employee Folder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Document Storage Styles - Enterprise Navy & Gold Theme */

/* Employee Folder Styles */
.employee-folder {
    border: 1px solid #E5E9F0;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
    overflow: hidden;
}

.employee-folder:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    border-color: rgba(255, 193, 7, 0.2);
}

.folder-header {
    padding: 1rem 1.25rem;
    cursor: pointer;
    user-select: none;
    background: #ffffff;
    transition: background-color 0.2s ease;
    border-bottom: 1px solid transparent;
}

.folder-header:hover {
    background-color: #F8FAFC;
}

.folder-chevron {
    transition: transform 0.2s ease;
    color: #64748b;
    font-size: 0.75rem;
    width: 16px;
    display: inline-block;
    text-align: center;
}

.folder-chevron.expanded {
    transform: rotate(90deg);
}

.folder-icon {
    font-size: 1.5rem;
    color: #FFC107;
}

.folder-name {
    font-size: 0.95rem;
    color: #0f172a;
    font-weight: 600;
}

.folder-meta {
    font-size: 0.8125rem;
    margin-top: 0.25rem;
    color: #64748b;
}

.folder-content {
    padding: 0 1.25rem 1rem 1.25rem;
    border-top: 1px solid #E5E9F0;
    margin-top: 0;
    padding-top: 1rem;
    background: #F8FAFC;
}

/* Documents Table Inside Folders */
.documents-table-folder {
    font-size: 0.875rem;
    background: #ffffff;
    border-radius: 8px;
    overflow: hidden;
}

.documents-table-folder thead th {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.75rem 0.75rem;
    border-bottom: 1px solid #E5E9F0;
    background: #F8FAFC;
    color: #0f172a;
}

.documents-table-folder tbody td {
    padding: 0.875rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #F1F5F9;
}

.documents-table-folder .document-row:hover {
    background-color: rgba(255, 193, 7, 0.05);
}

.documents-table-folder .document-row:last-child td {
    border-bottom: none;
}

.file-icon {
    font-size: 1.1rem;
}

.file-name {
    font-weight: 500;
    color: #0f172a;
}

.tag-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
</style>

<script>
(function() {
'use strict';

// Toggle folder expand/collapse
function toggleFolder(employeeId) {
    const content = document.getElementById('folder-content-' + employeeId);
    const chevron = document.getElementById('chevron-' + employeeId);
    
    if (content && chevron) {
        if (content.style.display === 'none' || !content.style.display) {
            content.style.display = 'block';
            chevron.classList.add('expanded');
        } else {
            content.style.display = 'none';
            chevron.classList.remove('expanded');
        }
    }
}

// Upload to specific folder
function uploadToFolder(employeeId, employeeName) {
    const modal = new bootstrap.Modal(document.getElementById('uploadDocumentModal'));
    const employeeSelect = document.getElementById('uploadEmployeeId');
    if (employeeSelect) {
        employeeSelect.value = employeeId;
    }
    modal.show();
}

// Search functionality
function initDocumentSearch() {
    const searchInput = document.getElementById('documentSearch');
    const tagFilter = document.getElementById('tagFilter');
    
    function submitSearch() {
        const search = searchInput ? searchInput.value.trim() : '';
        const tag = tagFilter ? tagFilter.value : '';
        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (tag) params.set('tag', tag);
        window.location.href = '?page=documents' + (params.toString() ? '&' + params.toString() : '');
    }
    
    if (searchInput) {
        // Remove any existing listeners by cloning
        const newInput = searchInput.cloneNode(true);
        searchInput.parentNode.replaceChild(newInput, searchInput);
        
        let searchTimeout;
        
        // Add input event listener with debounce for live search
        newInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                submitSearch();
            }, 500);
        });
        
        // Also trigger on Enter key for immediate search
        newInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                e.preventDefault();
                submitSearch();
            }
        });
    }
    
    if (tagFilter) {
        // Remove any existing listeners by cloning
        const newTagFilter = tagFilter.cloneNode(true);
        tagFilter.parentNode.replaceChild(newTagFilter, tagFilter);
        
        newTagFilter.addEventListener('change', function() {
            submitSearch();
        });
    }
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDocumentSearch);
} else {
    initDocumentSearch();
}

// Re-initialize when page content is loaded via AJAX
document.addEventListener('pageContentLoaded', function(e) {
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'documents') {
        setTimeout(initDocumentSearch, 100);
    }
});

// Also listen for the old event name (backwards compatibility)
document.addEventListener('pageLoaded', function(e) {
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'documents') {
        setTimeout(initDocumentSearch, 100);
    }
});

// Initialize folder select all checkboxes
function initFolderCheckboxes() {
    document.querySelectorAll('.folder-select-all').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const folderId = this.getAttribute('data-folder-id');
            const folderContent = document.getElementById('folder-content-' + folderId);
            if (folderContent) {
                const checkboxes = folderContent.querySelectorAll('.document-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
            }
        });
    });
}

// Initialize folder checkboxes on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFolderCheckboxes);
} else {
    initFolderCheckboxes();
}

// Re-initialize folder checkboxes on AJAX page load
document.addEventListener('pageContentLoaded', function(e) {
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'documents') {
        setTimeout(initFolderCheckboxes, 100);
    }
});

document.addEventListener('pageLoaded', function(e) {
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'documents') {
        setTimeout(initFolderCheckboxes, 100);
    }
});

// Initialize upload form handler
function initUploadForm() {
    const uploadForm = document.getElementById('uploadDocumentForm');
    if (!uploadForm) {
        console.warn('Upload form not found');
        return;
    }
    
    // Check if already initialized (prevent duplicate listeners)
    if (uploadForm.dataset.initialized === 'true') {
        console.log('Upload form already initialized');
        return;
    }
    
    // Mark as initialized
    uploadForm.dataset.initialized = 'true';
    
    // Attach event listener to form
    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation(); // Prevent other handlers
        
        console.log('Upload form submitted');
        
        const form = e.target;
        const employeeId = form.querySelector('#uploadEmployeeId')?.value || document.getElementById('uploadEmployeeId')?.value;
        const category = form.querySelector('#uploadCategory')?.value || document.getElementById('uploadCategory')?.value;
        const fileInput = form.querySelector('#uploadFile') || document.getElementById('uploadFile');
        const submitBtn = form.querySelector('#uploadSubmitBtn') || document.getElementById('uploadSubmitBtn');
        const errorDiv = form.querySelector('#uploadError') || document.getElementById('uploadError');
        const successDiv = form.querySelector('#uploadSuccess') || document.getElementById('uploadSuccess');
        
        if (!employeeId || !category || !fileInput?.files[0]) {
            const errorMsg = 'Please fill in all required fields';
            if (errorDiv) {
                errorDiv.textContent = errorMsg;
                errorDiv.style.display = 'block';
            }
            if (successDiv) successDiv.style.display = 'none';
            console.error('Validation failed:', { employeeId, category, hasFile: !!fileInput?.files[0] });
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('employee_id', employeeId);
        formData.append('category', category);
        formData.append('file', fileInput.files[0]);
        
        if (submitBtn) {
            submitBtn.disabled = true;
            const spinner = submitBtn.querySelector('.spinner-border');
            if (spinner) spinner.classList.remove('d-none');
        }
        if (errorDiv) errorDiv.style.display = 'none';
        if (successDiv) successDiv.style.display = 'none';
        
        try {
            console.log('Sending upload request...');
            // Use absolute path to ensure it works with SPA navigation
            const apiUrl = '/api/employee_files.php?action=upload';
            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            console.log('Response status:', response.status);
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
            }
            
            console.log('Response data:', data);
            
            if (data.success) {
                if (successDiv) {
                    successDiv.textContent = 'File uploaded successfully!';
                    successDiv.style.display = 'block';
                }
                form.reset();
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('uploadDocumentModal'));
                    if (modal) modal.hide();
                    // Reload page to show new file
                    window.location.reload();
                }, 1500);
            } else {
                const errorMsg = data.error || 'Upload failed';
                if (errorDiv) {
                    errorDiv.textContent = errorMsg;
                    errorDiv.style.display = 'block';
                }
                console.error('Upload failed:', errorMsg);
            }
        } catch (error) {
            console.error('Upload error:', error);
            const errorMsg = 'Network error: ' + error.message;
            if (errorDiv) {
                errorDiv.textContent = errorMsg;
                errorDiv.style.display = 'block';
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                const spinner = submitBtn.querySelector('.spinner-border');
                if (spinner) spinner.classList.add('d-none');
            }
        }
    });
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUploadForm);
} else {
    initUploadForm();
}

// Re-initialize when page content is loaded via AJAX
document.addEventListener('pageContentLoaded', function(e) {
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'documents') {
        setTimeout(initUploadForm, 100);
    }
});

// Also listen for the old event name (backwards compatibility)
document.addEventListener('pageLoaded', function(e) {
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'documents') {
        setTimeout(initUploadForm, 100);
    }
});

// Download file via secure API
function downloadFile(fileId) {
    if (!fileId) {
        alert('Invalid file ID');
        return;
    }
    
    // Open download endpoint in new window/tab (use absolute path)
    window.open(`/api/employee_files.php?action=download&file_id=${fileId}`, '_blank');
}

// Delete file via secure API
async function deleteFile(fileId) {
    if (!fileId) {
        alert('Invalid file ID');
        return;
    }
    
    if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        return;
    }
    
    try {
        // Use absolute path to ensure it works with SPA navigation
        const response = await fetch(`/api/employee_files.php?action=delete&file_id=${fileId}`, {
            method: 'POST',
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('File deleted successfully');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to delete file'));
        }
    } catch (error) {
        alert('Network error: ' + error.message);
    }
}

// Export documents to CSV
function exportDocuments() {
    alert('Export functionality will be implemented soon.');
    // TODO: Implement CSV export
}

})(); // End IIFE
</script>
