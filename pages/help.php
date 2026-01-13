<?php
/**
 * Super Admin Help & Support - Ticket Management
 * Review and reply to support tickets
 */
$page_title = 'Help & Support - Ticket Management - Golden Z-5 HR System';
$page = 'help';

// Enforce Super Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../landing/index.php');
    exit;
}

$current_user = [
    'id' => $_SESSION['user_id'] ?? null,
    'name' => $_SESSION['user_name'] ?? 'Super Admin',
    'role' => $_SESSION['user_role'] ?? 'super_admin'
];

// Handle form submissions
$message = '';
$message_type = '';

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reply' && !empty($_POST['ticket_id']) && !empty($_POST['message'])) {
        $result = add_ticket_reply($_POST['ticket_id'], [
            'user_id' => $current_user['id'],
            'user_name' => $current_user['name'],
            'user_role' => $current_user['role'],
            'message' => trim($_POST['message']),
            'is_internal' => isset($_POST['is_internal']) ? 1 : 0
        ]);
        
        if ($result['success']) {
            $message = 'Reply sent successfully';
            $message_type = 'success';
        } else {
            $message = $result['message'];
            $message_type = 'danger';
        }
    }
    
    if ($_POST['action'] === 'update_status' && !empty($_POST['ticket_id']) && !empty($_POST['status'])) {
        $result = update_ticket_status($_POST['ticket_id'], $_POST['status'], $current_user);
        if ($result['success']) {
            $message = 'Ticket status updated';
            $message_type = 'success';
        } else {
            $message = $result['message'];
            $message_type = 'danger';
        }
    }
}

// Get filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'category' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Check if viewing a specific ticket
$view_ticket_id = isset($_GET['view']) ? (int)$_GET['view'] : null;
$viewing_ticket = null;

if ($view_ticket_id) {
    $viewing_ticket = get_ticket_by_id($view_ticket_id);
}

// Get tickets list
$tickets_data = get_support_tickets($filters, 50, 0);
$tickets = $tickets_data['tickets'];
$total_tickets = $tickets_data['total'];

// Get statistics
$stats = get_ticket_stats();

// Priority colors
$priority_colors = [
    'urgent' => 'danger',
    'high' => 'warning',
    'medium' => 'info',
    'low' => 'secondary'
];

// Status colors
$status_colors = [
    'open' => 'primary',
    'in_progress' => 'info',
    'pending_user' => 'warning',
    'resolved' => 'success',
    'closed' => 'secondary'
];

// Category labels
$category_labels = [
    'system_issue' => 'System Issue',
    'access_request' => 'Access Request',
    'data_issue' => 'Data Issue',
    'feature_request' => 'Feature Request',
    'general_inquiry' => 'General Inquiry',
    'bug_report' => 'Bug Report'
];
?>

<div class="container-fluid ticket-management">
    <!-- Page Header -->
    <div class="page-header-modern mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="page-title-modern">
                <h1 class="page-title-main">
                    <i class="fas fa-headset me-2"></i>Help & Support
                </h1>
                <p class="page-subtitle">Review and respond to support tickets</p>
            </div>
            <?php if ($viewing_ticket): ?>
            <a href="?page=help" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Tickets
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($viewing_ticket): ?>
    <!-- Single Ticket View -->
    <div class="row g-4">
        <!-- Ticket Details -->
        <div class="col-lg-8">
            <div class="card card-modern">
                <div class="card-body-modern">
                    <!-- Ticket Header -->
                    <div class="d-flex justify-content-between align-items-start mb-4 pb-3 border-bottom">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-<?php echo $priority_colors[$viewing_ticket['priority']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst($viewing_ticket['priority']); ?>
                                </span>
                                <span class="badge bg-<?php echo $status_colors[$viewing_ticket['status']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $viewing_ticket['status'])); ?>
                                </span>
                                <span class="text-muted small">
                                    <?php echo htmlspecialchars($category_labels[$viewing_ticket['category']] ?? $viewing_ticket['category']); ?>
                                </span>
                            </div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($viewing_ticket['subject']); ?></h4>
                            <small class="text-muted">
                                <strong><?php echo htmlspecialchars($viewing_ticket['ticket_no']); ?></strong>
                                &middot; Created <?php echo date('M d, Y \a\t h:i A', strtotime($viewing_ticket['created_at'])); ?>
                            </small>
                        </div>
                    </div>

                    <!-- Submitter Info -->
                    <div class="bg-light rounded p-3 mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Submitted By</small>
                                <strong><?php echo htmlspecialchars($viewing_ticket['user_name']); ?></strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Email</small>
                                <?php echo htmlspecialchars($viewing_ticket['user_email'] ?? 'N/A'); ?>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Role</small>
                                <?php echo ucfirst(str_replace('_', ' ', $viewing_ticket['user_role'] ?? 'N/A')); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Original Description -->
                    <div class="ticket-message mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-circle bg-primary text-white">
                                <?php echo strtoupper(substr($viewing_ticket['user_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars($viewing_ticket['user_name']); ?></strong>
                                <small class="text-muted ms-2"><?php echo date('M d, Y h:i A', strtotime($viewing_ticket['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="message-content bg-light rounded p-3">
                            <?php echo nl2br(htmlspecialchars($viewing_ticket['description'])); ?>
                        </div>
                    </div>

                    <!-- Replies -->
                    <?php if (!empty($viewing_ticket['replies'])): ?>
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-comments me-2"></i>Conversation (<?php echo count($viewing_ticket['replies']); ?>)
                    </h6>
                    <?php foreach ($viewing_ticket['replies'] as $reply): ?>
                    <div class="ticket-message mb-3 <?php echo $reply['is_internal'] ? 'internal-note' : ''; ?>">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-circle <?php echo ($reply['user_role'] === 'super_admin') ? 'bg-success' : 'bg-secondary'; ?> text-white">
                                <?php echo strtoupper(substr($reply['user_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars($reply['user_name']); ?></strong>
                                <?php if ($reply['user_role']): ?>
                                <span class="badge bg-light text-dark ms-1"><?php echo ucfirst(str_replace('_', ' ', $reply['user_role'])); ?></span>
                                <?php endif; ?>
                                <?php if ($reply['is_internal']): ?>
                                <span class="badge bg-warning text-dark ms-1"><i class="fas fa-lock me-1"></i>Internal</span>
                                <?php endif; ?>
                                <small class="text-muted ms-2"><?php echo date('M d, Y h:i A', strtotime($reply['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="message-content <?php echo $reply['is_internal'] ? 'bg-warning-subtle border-warning' : 'bg-light'; ?> rounded p-3">
                            <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Reply Form -->
                    <?php if (!in_array($viewing_ticket['status'], ['closed'])): ?>
                    <div class="reply-form mt-4 pt-4 border-top">
                        <h6 class="mb-3"><i class="fas fa-reply me-2"></i>Send Reply</h6>
                        <form method="POST" action="?page=help&view=<?php echo $viewing_ticket['id']; ?>">
                            <input type="hidden" name="action" value="reply">
                            <input type="hidden" name="ticket_id" value="<?php echo $viewing_ticket['id']; ?>">
                            
                            <div class="mb-3">
                                <textarea name="message" class="form-control" rows="4" 
                                          placeholder="Type your reply here..." required></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_internal" id="isInternal" value="1">
                                    <label class="form-check-label text-muted" for="isInternal">
                                        <i class="fas fa-lock me-1"></i>Internal note (not visible to user)
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ticket Actions Sidebar -->
        <div class="col-lg-4">
            <!-- Status Update -->
            <div class="card card-modern mb-4">
                <div class="card-body-modern">
                    <h6 class="mb-3"><i class="fas fa-cog me-2"></i>Ticket Actions</h6>
                    
                    <form method="POST" action="?page=help&view=<?php echo $viewing_ticket['id']; ?>">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="ticket_id" value="<?php echo $viewing_ticket['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small text-muted">Update Status</label>
                            <select name="status" class="form-select">
                                <option value="open" <?php echo $viewing_ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="in_progress" <?php echo $viewing_ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="pending_user" <?php echo $viewing_ticket['status'] === 'pending_user' ? 'selected' : ''; ?>>Pending User Response</option>
                                <option value="resolved" <?php echo $viewing_ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="closed" <?php echo $viewing_ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-save me-2"></i>Update Status
                        </button>
                    </form>
                </div>
            </div>

            <!-- Ticket Info -->
            <div class="card card-modern">
                <div class="card-body-modern">
                    <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Ticket Information</h6>
                    
                    <div class="info-item mb-3">
                        <small class="text-muted d-block">Ticket Number</small>
                        <strong><?php echo htmlspecialchars($viewing_ticket['ticket_no']); ?></strong>
                    </div>
                    
                    <div class="info-item mb-3">
                        <small class="text-muted d-block">Created</small>
                        <?php echo date('M d, Y h:i A', strtotime($viewing_ticket['created_at'])); ?>
                    </div>
                    
                    <div class="info-item mb-3">
                        <small class="text-muted d-block">Last Updated</small>
                        <?php echo date('M d, Y h:i A', strtotime($viewing_ticket['updated_at'])); ?>
                    </div>
                    
                    <?php if ($viewing_ticket['resolved_at']): ?>
                    <div class="info-item mb-3">
                        <small class="text-muted d-block">Resolved</small>
                        <?php echo date('M d, Y h:i A', strtotime($viewing_ticket['resolved_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <small class="text-muted d-block">Assigned To</small>
                        <?php echo htmlspecialchars($viewing_ticket['assigned_to_name'] ?? 'Unassigned'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Tickets List View -->
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-modern stat-card">
                <div class="card-body-modern text-center">
                    <div class="stat-icon bg-primary-subtle text-primary mb-2">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['open_tickets']; ?></h3>
                    <small class="text-muted">Open Tickets</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-modern stat-card">
                <div class="card-body-modern text-center">
                    <div class="stat-icon bg-danger-subtle text-danger mb-2">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['urgent_high']; ?></h3>
                    <small class="text-muted">Urgent/High Priority</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-modern stat-card">
                <div class="card-body-modern text-center">
                    <div class="stat-icon bg-success-subtle text-success mb-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="mb-0"><?php echo ($stats['by_status']['resolved'] ?? 0) + ($stats['by_status']['closed'] ?? 0); ?></h3>
                    <small class="text-muted">Resolved/Closed</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-modern stat-card">
                <div class="card-body-modern text-center">
                    <div class="stat-icon bg-info-subtle text-info mb-2">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['today']; ?></h3>
                    <small class="text-muted">Created Today</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card card-modern mb-4">
        <div class="card-body-modern">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="help">
                
                <div class="col-md-3">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Ticket #, subject, name..."
                           value="<?php echo htmlspecialchars($filters['search']); ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="open" <?php echo $filters['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="pending_user" <?php echo $filters['status'] === 'pending_user' ? 'selected' : ''; ?>>Pending User</option>
                        <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small text-muted">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="">All Priority</option>
                        <option value="urgent" <?php echo $filters['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small text-muted">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($category_labels as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $filters['category'] === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="?page=help" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card card-modern">
        <div class="card-body-modern">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Support Tickets
                    <span class="badge bg-secondary ms-2"><?php echo $total_tickets; ?></span>
                </h5>
            </div>

            <?php if (empty($tickets)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No tickets found</h5>
                <p class="text-muted">There are no support tickets matching your criteria.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket</th>
                            <th>Subject</th>
                            <th>Submitted By</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td>
                                <strong class="text-primary"><?php echo htmlspecialchars($ticket['ticket_no']); ?></strong>
                                <?php if ($ticket['reply_count'] > 0): ?>
                                <span class="badge bg-light text-dark ms-1">
                                    <i class="fas fa-comments"></i> <?php echo $ticket['reply_count']; ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?page=help&view=<?php echo $ticket['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars(substr($ticket['subject'], 0, 50)); ?>
                                    <?php if (strlen($ticket['subject']) > 50): ?>...<?php endif; ?>
                                </a>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($ticket['user_name']); ?></div>
                                <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $ticket['user_role'] ?? '')); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <?php echo htmlspecialchars($category_labels[$ticket['category']] ?? $ticket['category']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $priority_colors[$ticket['priority']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst($ticket['priority']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $status_colors[$ticket['status']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></small>
                                <br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($ticket['created_at'])); ?></small>
                            </td>
                            <td>
                                <a href="?page=help&view=<?php echo $ticket['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="View & Reply">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.ticket-management {
    padding: 1.5rem 2rem;
    max-width: 100%;
    background: var(--interface-bg, #f8fafc);
    min-height: 100vh;
}

.stat-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.ticket-message {
    position: relative;
}

.ticket-message.internal-note .message-content {
    border-left: 3px solid var(--bs-warning);
}

.message-content {
    margin-left: 44px;
    border-left: 3px solid transparent;
}

.info-item {
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #eee;
}

.info-item:last-child {
    padding-bottom: 0;
    border-bottom: none;
}

/* Table row hover */
.table tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}

/* Responsive */
@media (max-width: 768px) {
    .ticket-management {
        padding: 1rem;
    }
}

/* Dark theme support */
html[data-theme="dark"] .ticket-management {
    background: var(--interface-bg);
}

html[data-theme="dark"] .bg-light {
    background: var(--gray-800) !important;
}

html[data-theme="dark"] .message-content {
    background: var(--gray-800) !important;
}

html[data-theme="dark"] .info-item {
    border-bottom-color: var(--gray-700);
}

html[data-theme="dark"] .table-light {
    background: var(--gray-800) !important;
}

html[data-theme="dark"] .badge.bg-light {
    background: var(--gray-700) !important;
    color: var(--gray-200) !important;
}
</style>
