<?php
$page_title = 'Violation Types & Sanctions - Golden Z-5 HR System';
$page = 'violation_types';

// Get database connection
$pdo = get_db_connection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_type') {
        // Handle adding violation type (to be implemented with database)
        redirect_with_message('?page=violation_types', 'Violation type added successfully!', 'success');
    } elseif ($action === 'update_type') {
        // Handle updating violation type (to be implemented with database)
        redirect_with_message('?page=violation_types', 'Violation type updated successfully!', 'success');
    } elseif ($action === 'delete_type' && isset($_POST['type_id'])) {
        // Handle deletion (to be implemented with database)
        redirect_with_message('?page=violation_types', 'Violation type deleted successfully!', 'success');
    }
}

// Mock violation types data
$violation_types = [
    [
        'id' => 1,
        'name' => 'AWOL (Absence Without Official Leave)',
        'category' => 'Major',
        'description' => 'Employee is absent from work without prior notification or approval.',
        'sanctions' => ['1st Offense: Written Warning', '2nd Offense: 3-day Suspension', '3rd Offense: Termination'],
        'created_at' => '2024-01-15',
    ],
    [
        'id' => 2,
        'name' => 'Tardiness',
        'category' => 'Minor',
        'description' => 'Arriving late to work or designated post beyond grace period.',
        'sanctions' => ['1st Offense: Verbal Warning', '2nd Offense: Written Warning', '3rd Offense: 1-day Suspension'],
        'created_at' => '2024-01-15',
    ],
    [
        'id' => 3,
        'name' => 'Insubordination',
        'category' => 'Major',
        'description' => 'Refusal to follow lawful and reasonable directives from supervisors.',
        'sanctions' => ['1st Offense: Final Warning', '2nd Offense: Termination'],
        'created_at' => '2024-01-15',
    ],
    [
        'id' => 4,
        'name' => 'Dress Code Violation',
        'category' => 'Minor',
        'description' => 'Not adhering to the company uniform or dress code policy.',
        'sanctions' => ['1st Offense: Verbal Warning', '2nd Offense: Written Warning', '3rd Offense: 1-day Suspension'],
        'created_at' => '2024-01-15',
    ],
    [
        'id' => 5,
        'name' => 'Safety Violation',
        'category' => 'Major',
        'description' => 'Engaging in activities that compromise workplace safety or security protocols.',
        'sanctions' => ['1st Offense: Written Warning + Safety Training', '2nd Offense: 5-day Suspension', '3rd Offense: Termination'],
        'created_at' => '2024-01-15',
    ],
    [
        'id' => 6,
        'name' => 'Unauthorized Leave',
        'category' => 'Minor',
        'description' => 'Taking leave without proper approval or documentation.',
        'sanctions' => ['1st Offense: Written Warning', '2nd Offense: 2-day Suspension', '3rd Offense: Final Warning'],
        'created_at' => '2024-01-15',
    ],
    [
        'id' => 7,
        'name' => 'Theft',
        'category' => 'Major',
        'description' => 'Stealing company or client property, regardless of value.',
        'sanctions' => ['1st Offense: Immediate Termination + Legal Action'],
        'created_at' => '2024-01-15',
    ],
    [
        'id' => 8,
        'name' => 'Harassment',
        'category' => 'Major',
        'description' => 'Any form of harassment including sexual, verbal, or physical harassment.',
        'sanctions' => ['1st Offense: Final Warning + Counseling', '2nd Offense: Termination + Legal Action'],
        'created_at' => '2024-01-15',
    ],
];

// Separate by category
$major_violations = array_filter($violation_types, fn($v) => $v['category'] === 'Major');
$minor_violations = array_filter($violation_types, fn($v) => $v['category'] === 'Minor');
?>

<div class="container-fluid hrdash">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Total Violation Types</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo count($violation_types); ?></div>
                </div>
                <div class="hrdash-stat__meta">Defined violation categories</div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Major Violations</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-danger"><?php echo count($major_violations); ?></div>
                </div>
                <div class="hrdash-stat__meta">Serious offense categories</div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Minor Violations</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-warning"><?php echo count($minor_violations); ?></div>
                </div>
                <div class="hrdash-stat__meta">Minor offense categories</div>
            </div>
        </div>
    </div>

    <!-- Major Violations Table -->
    <div class="card card-modern mb-4">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title-modern">Major Violations</h5>
                <div class="card-subtitle">Serious offenses with severe sanctions</div>
            </div>
            <div>
                <button type="button" class="btn btn-primary-modern" data-bs-toggle="modal" data-bs-target="#addViolationTypeModal" data-category="Major">
                    <i class="fas fa-plus me-2"></i>Add Major Violation
                </button>
            </div>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Violation Type</th>
                            <th style="width: 35%;">Description</th>
                            <th style="width: 30%;">Sanctions</th>
                            <th style="width: 10%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($major_violations as $type): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($type['name']); ?></div>
                                    <span class="badge bg-danger">Major</span>
                                </td>
                                <td class="text-muted"><?php echo htmlspecialchars($type['description']); ?></td>
                                <td>
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($type['sanctions'] as $sanction): ?>
                                            <li><small><?php echo htmlspecialchars($sanction); ?></small></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-success" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
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

    <!-- Minor Violations Table -->
    <div class="card card-modern">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title-modern">Minor Violations</h5>
                <div class="card-subtitle">Less serious offenses with progressive discipline</div>
            </div>
            <div>
                <button type="button" class="btn btn-primary-modern" data-bs-toggle="modal" data-bs-target="#addViolationTypeModal" data-category="Minor">
                    <i class="fas fa-plus me-2"></i>Add Minor Violation
                </button>
            </div>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Violation Type</th>
                            <th style="width: 35%;">Description</th>
                            <th style="width: 30%;">Sanctions</th>
                            <th style="width: 10%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($minor_violations as $type): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($type['name']); ?></div>
                                    <span class="badge bg-warning text-dark">Minor</span>
                                </td>
                                <td class="text-muted"><?php echo htmlspecialchars($type['description']); ?></td>
                                <td>
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($type['sanctions'] as $sanction): ?>
                                            <li><small><?php echo htmlspecialchars($sanction); ?></small></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-success" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
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

<!-- Add Violation Type Modal -->
<div class="modal fade" id="addViolationTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Violation Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_type">
                    
                    <div class="mb-3">
                        <label class="form-label">Violation Name <span class="text-danger">*</span></label>
                        <input type="text" name="violation_name" class="form-control" placeholder="e.g., Repeated Tardiness" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">Select Category</option>
                            <option value="Major">Major</option>
                            <option value="Minor">Minor</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe the violation..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sanctions (Progressive Discipline) <span class="text-danger">*</span></label>
                        <div id="sanctionsContainer">
                            <div class="input-group mb-2">
                                <span class="input-group-text">1st Offense</span>
                                <input type="text" name="sanctions[]" class="form-control" placeholder="e.g., Verbal Warning" required>
                            </div>
                            <div class="input-group mb-2">
                                <span class="input-group-text">2nd Offense</span>
                                <input type="text" name="sanctions[]" class="form-control" placeholder="e.g., Written Warning" required>
                            </div>
                            <div class="input-group mb-2">
                                <span class="input-group-text">3rd Offense</span>
                                <input type="text" name="sanctions[]" class="form-control" placeholder="e.g., 1-day Suspension">
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addSanctionBtn">
                            <i class="fas fa-plus me-1"></i>Add More Offenses
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Violation Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add more sanction fields
document.getElementById('addSanctionBtn')?.addEventListener('click', function() {
    const container = document.getElementById('sanctionsContainer');
    const count = container.querySelectorAll('.input-group').length + 1;
    const html = `
        <div class="input-group mb-2">
            <span class="input-group-text">${count}${count === 1 ? 'st' : count === 2 ? 'nd' : count === 3 ? 'rd' : 'th'} Offense</span>
            <input type="text" name="sanctions[]" class="form-control" placeholder="Sanction for ${count}${count === 1 ? 'st' : count === 2 ? 'nd' : count === 3 ? 'rd' : 'th'} offense">
            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
});
</script>
