<?php
/**
 * HR Admin Help & Support - Submit Support Tickets
 * Allows HR Admin users to submit and track support tickets
 */
$page_title = 'Help & Support - HR Admin - Golden Z-5 HR System';
$page = 'help';

// Get current user info
$current_user = [
    'id' => $_SESSION['user_id'] ?? null,
    'name' => $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'HR Admin',
    'role' => $_SESSION['user_role'] ?? 'hr_admin',
    'email' => $_SESSION['email'] ?? null
];

// Handle form submissions
$message = '';
$message_type = '';

// Handle new ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'submit_ticket') {
        $ticket_data = [
            'user_id' => $current_user['id'],
            'user_name' => $current_user['name'],
            'user_email' => $current_user['email'],
            'user_role' => $current_user['role'],
            'category' => $_POST['category'] ?? 'general_inquiry',
            'priority' => $_POST['priority'] ?? 'medium',
            'subject' => trim($_POST['subject'] ?? ''),
            'description' => trim($_POST['description'] ?? '')
        ];
        
        if (empty($ticket_data['subject']) || empty($ticket_data['description'])) {
            $message = 'Please fill in all required fields';
            $message_type = 'danger';
        } else {
            $result = create_support_ticket($ticket_data);
            if ($result['success']) {
                $message = "Ticket {$result['ticket_no']} submitted successfully! Our support team will respond soon.";
                $message_type = 'success';
            } else {
                $message = $result['message'];
                $message_type = 'danger';
            }
        }
    }
    
    if ($_POST['action'] === 'reply' && !empty($_POST['ticket_id']) && !empty($_POST['message'])) {
        $result = add_ticket_reply($_POST['ticket_id'], [
            'user_id' => $current_user['id'],
            'user_name' => $current_user['name'],
            'user_role' => $current_user['role'],
            'message' => trim($_POST['message']),
            'is_internal' => 0
        ]);
        
        if ($result['success']) {
            $message = 'Reply sent successfully';
            $message_type = 'success';
        } else {
            $message = $result['message'];
            $message_type = 'danger';
        }
    }
}

// Check if viewing a specific ticket
$view_ticket_id = isset($_GET['view']) ? (int)$_GET['view'] : null;
$viewing_ticket = null;

if ($view_ticket_id) {
    $viewing_ticket = get_ticket_by_id($view_ticket_id);
    // Verify this ticket belongs to the current user
    if ($viewing_ticket && $viewing_ticket['user_id'] != $current_user['id']) {
        $viewing_ticket = null; // Not authorized to view
    }
}

// Get user's tickets
$user_tickets = [];
try {
    $pdo = get_db_connection();
    $sql = "SELECT t.*, 
                   (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id AND is_internal = 0) as reply_count
            FROM support_tickets t
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
            LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_user['id']]);
    $user_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching user tickets: " . $e->getMessage());
}

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

<div class="container-fluid hr-help-support">
    <!-- Page Header -->
    <div class="page-header-modern mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="page-title-modern">
                <h1 class="page-title-main">
                    <i class="fas fa-headset me-2"></i>Help & Support
                </h1>
                <p class="page-subtitle">Submit and track your support requests</p>
            </div>
            <?php if ($viewing_ticket): ?>
            <a href="?page=help" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Tickets
            </a>
            <?php else: ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                <i class="fas fa-plus me-2"></i>New Ticket
            </button>
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
                            </div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($viewing_ticket['subject']); ?></h4>
                            <small class="text-muted">
                                <strong><?php echo htmlspecialchars($viewing_ticket['ticket_no']); ?></strong>
                                &middot; Submitted <?php echo date('M d, Y \a\t h:i A', strtotime($viewing_ticket['created_at'])); ?>
                            </small>
                        </div>
                    </div>

                    <!-- Original Description -->
                    <div class="ticket-message mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-circle bg-primary text-white">
                                <?php echo strtoupper(substr($viewing_ticket['user_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <strong>You</strong>
                                <small class="text-muted ms-2"><?php echo date('M d, Y h:i A', strtotime($viewing_ticket['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="message-content bg-light rounded p-3">
                            <?php echo nl2br(htmlspecialchars($viewing_ticket['description'])); ?>
                        </div>
                    </div>

                    <!-- Replies (excluding internal notes) -->
                    <?php 
                    $public_replies = array_filter($viewing_ticket['replies'] ?? [], function($r) {
                        return !$r['is_internal'];
                    });
                    if (!empty($public_replies)): 
                    ?>
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-comments me-2"></i>Conversation (<?php echo count($public_replies); ?>)
                    </h6>
                    <?php foreach ($public_replies as $reply): ?>
                    <div class="ticket-message mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-circle <?php echo ($reply['user_role'] === 'super_admin') ? 'bg-success' : 'bg-primary'; ?> text-white">
                                <?php echo strtoupper(substr($reply['user_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <strong><?php echo ($reply['user_id'] == $current_user['id']) ? 'You' : htmlspecialchars($reply['user_name']); ?></strong>
                                <?php if ($reply['user_role'] === 'super_admin'): ?>
                                <span class="badge bg-success ms-1">Support Team</span>
                                <?php endif; ?>
                                <small class="text-muted ms-2"><?php echo date('M d, Y h:i A', strtotime($reply['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="message-content <?php echo ($reply['user_role'] === 'super_admin') ? 'bg-success-subtle' : 'bg-light'; ?> rounded p-3">
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
                                          placeholder="Type your message here..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send Reply
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-secondary mt-4">
                        <i class="fas fa-lock me-2"></i>This ticket is closed. If you need further assistance, please open a new ticket.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ticket Info Sidebar -->
        <div class="col-lg-4">
            <div class="card card-modern">
                <div class="card-body-modern">
                    <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Ticket Information</h6>
                    
                    <div class="info-item mb-3">
                        <small class="text-muted d-block">Ticket Number</small>
                        <strong><?php echo htmlspecialchars($viewing_ticket['ticket_no']); ?></strong>
                    </div>
                    
                    <div class="info-item mb-3">
                        <small class="text-muted d-block">Category</small>
                        <?php echo htmlspecialchars($category_labels[$viewing_ticket['category']] ?? $viewing_ticket['category']); ?>
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
                    <div class="info-item">
                        <small class="text-muted d-block">Resolved</small>
                        <?php echo date('M d, Y h:i A', strtotime($viewing_ticket['resolved_at'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Tickets List View -->
    <div class="row g-4">
        <!-- Quick Help Section -->
        <div class="col-lg-4">
            <div class="card card-modern mb-4">
                <div class="card-body-modern">
                    <h5 class="mb-3"><i class="fas fa-lightbulb me-2 text-warning"></i>Quick Help</h5>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0">
                            <strong>Employee Management</strong>
                            <p class="text-muted small mb-0">Add, edit, and manage employee records</p>
                        </div>
                        <div class="list-group-item px-0">
                            <strong>Time & Attendance</strong>
                            <p class="text-muted small mb-0">DTR entries and time-off requests</p>
                        </div>
                        <div class="list-group-item px-0">
                            <strong>Posts & Assignments</strong>
                            <p class="text-muted small mb-0">Manage locations and employee assignments</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card card-modern">
                <div class="card-body-modern">
                    <h5 class="mb-3"><i class="fas fa-phone-alt me-2 text-primary"></i>Contact Support</h5>
                    <p class="text-muted small">For urgent issues, contact:</p>
                    <p class="mb-1"><strong>Email:</strong> support@goldenz5.com</p>
                    <p class="mb-0"><strong>Phone:</strong> +63 (02) 1234-5678</p>
                </div>
            </div>
        </div>

        <!-- My Tickets -->
        <div class="col-lg-8">
            <div class="card card-modern">
                <div class="card-body-modern">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">
                            <i class="fas fa-ticket-alt me-2"></i>My Tickets
                            <span class="badge bg-secondary ms-2"><?php echo count($user_tickets); ?></span>
                        </h5>
                    </div>

                    <?php if (empty($user_tickets)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No tickets yet</h5>
                        <p class="text-muted">Click "New Ticket" to submit a support request.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Ticket</th>
                                    <th>Subject</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_tickets as $ticket): ?>
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
                                            <?php echo htmlspecialchars(substr($ticket['subject'], 0, 40)); ?>
                                            <?php if (strlen($ticket['subject']) > 40): ?>...<?php endif; ?>
                                        </a>
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
                                    </td>
                                    <td>
                                        <a href="?page=help&view=<?php echo $ticket['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
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
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- New Ticket Modal -->
<div class="modal fade" id="newTicketModal" tabindex="-1" aria-labelledby="newTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newTicketModalLabel">
                    <i class="fas fa-ticket-alt me-2"></i>Submit New Ticket
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="?page=help">
                <div class="modal-body">
                    <input type="hidden" name="action" value="submit_ticket">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="general_inquiry">General Inquiry</option>
                                <option value="system_issue">System Issue</option>
                                <option value="access_request">Access Request</option>
                                <option value="data_issue">Data Issue</option>
                                <option value="feature_request">Feature Request</option>
                                <option value="bug_report">Bug Report</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" 
                                   placeholder="Brief description of your issue" required maxlength="255">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="5" 
                                      placeholder="Please provide detailed information about your issue or request..." 
                                      required></textarea>
                            <small class="text-muted">Include any relevant details, error messages, or steps to reproduce the issue.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Submit Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.hr-help-support {
    padding: 1.5rem 2rem;
    max-width: 100%;
    background: var(--interface-bg, #f8fafc);
    min-height: 100vh;
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

.ticket-message .message-content {
    margin-left: 44px;
}

.info-item {
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #eee;
}

.info-item:last-child {
    padding-bottom: 0;
    border-bottom: none;
}

.bg-success-subtle {
    background-color: rgba(25, 135, 84, 0.1) !important;
}

/* Dark theme support */
html[data-theme="dark"] .hr-help-support {
    background: var(--interface-bg);
}

html[data-theme="dark"] .bg-light {
    background: var(--gray-800) !important;
}

html[data-theme="dark"] .info-item {
    border-bottom-color: var(--gray-700);
}

html[data-theme="dark"] .list-group-item {
    background: transparent;
    border-color: var(--gray-700);
}
</style>
