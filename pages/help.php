<?php
// Super Admin Help & Support – UI scaffold (no persistence yet)
$page_title = 'Help & Support - Super Admin - Golden Z-5 HR System';
$page = 'help';

// Enforce Super Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../landing/index.php');
    exit;
}
?>

<div class="container-fluid super-admin-help">
    <div class="page-header-modern mb-4">
        <div class="page-title-modern">
            <h1 class="page-title-main">Help & Support</h1>
            <p class="page-subtitle">Docs, FAQs, tickets, health, and support contacts.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Documentation -->
        <div class="col-lg-4">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-3">
                        <h5 class="card-title-modern">System Documentation</h5>
                        <small class="card-subtitle">Super Admin resources</small>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php
                        $docs = [
                            ['title' => 'Admin Guide', 'desc' => 'User management, roles, security'],
                            ['title' => 'System Architecture', 'desc' => 'Services, DB, integrations'],
                            ['title' => 'API Reference', 'desc' => 'REST & webhook specs'],
                            ['title' => 'FAQs', 'desc' => 'Common issues & resolutions'],
                        ];
                        foreach ($docs as $doc):
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($doc['title']); ?></strong>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($doc['desc']); ?></p>
                            </div>
                            <button class="btn btn-outline-modern btn-sm" disabled>Open</button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Support Tools -->
        <div class="col-lg-8">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-3">
                        <h5 class="card-title-modern">Issue Reporting & Tickets</h5>
                        <small class="card-subtitle">Submit or track requests (UI only)</small>
                    </div>
                    <form class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Issue Category</label>
                            <select class="form-select" disabled>
                                <option>System outage</option>
                                <option>Data issue</option>
                                <option>Access request</option>
                                <option>Feature question</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority</label>
                            <select class="form-select" disabled>
                                <option>Low</option>
                                <option>Medium</option>
                                <option selected>High</option>
                                <option>Urgent</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" placeholder="Describe the issue or request…" disabled></textarea>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="button" class="btn btn-primary-modern" disabled>
                                <i class="fas fa-paper-plane me-2"></i>Submit Ticket
                            </button>
                            <button type="button" class="btn btn-outline-modern" disabled>
                                <i class="fas fa-list me-2"></i>View Open Tickets
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <!-- System Health -->
        <div class="col-xl-6">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-3">
                        <h5 class="card-title-modern">System Health</h5>
                        <small class="card-subtitle">Placeholder metrics; wire to monitoring later.</small>
                    </div>
                    <div class="row g-3">
                        <?php
                        $health = [
                            ['label' => 'Web Application', 'status' => 'Operational', 'badge' => 'success'],
                            ['label' => 'Database', 'status' => 'Operational', 'badge' => 'success'],
                            ['label' => 'Integrations', 'status' => 'Minor warnings', 'badge' => 'warning'],
                            ['label' => 'Queue / Jobs', 'status' => 'Operational', 'badge' => 'success'],
                        ];
                        foreach ($health as $metric):
                        ?>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?php echo htmlspecialchars($metric['label']); ?></strong>
                                    <span class="badge bg-<?php echo $metric['badge']; ?>">
                                        <?php echo htmlspecialchars($metric['status']); ?>
                                    </span>
                                </div>
                                <small class="text-muted">Last checked: <?php echo date('M d, Y H:i'); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Reports -->
        <div class="col-xl-6">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-3">
                        <h5 class="card-title-modern">Recent Error Reports</h5>
                        <small class="card-subtitle">Read-only placeholder</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Component</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Jan 13, 2026 13:44</td>
                                    <td>API Gateway</td>
                                    <td>Invalid token from external client</td>
                                    <td><span class="badge bg-warning text-dark">Investigating</span></td>
                                </tr>
                                <tr>
                                    <td>Jan 13, 2026 09:12</td>
                                    <td>SMS Provider</td>
                                    <td>Delivery delay detected</td>
                                    <td><span class="badge bg-success">Mitigated</span></td>
                                </tr>
                                <tr>
                                    <td>Jan 12, 2026 20:05</td>
                                    <td>Auth Service</td>
                                    <td>Multiple failed logins</td>
                                    <td><span class="badge bg-danger">Alerted</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-outline-modern btn-sm" disabled>
                            <i class="fas fa-file-alt me-2"></i>Download error log
                        </button>
                        <button class="btn btn-outline-modern btn-sm" disabled>
                            <i class="fas fa-sync-alt me-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <!-- Contact -->
        <div class="col-md-12">
            <div class="card card-modern">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-3">
                        <h5 class="card-title-modern">Contact Technical Support</h5>
                        <small class="card-subtitle">Secure channels for escalation.</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <strong>Email Support</strong>
                                <p class="text-muted small mb-2">support@goldenz5.com</p>
                                <button class="btn btn-outline-modern btn-sm" disabled>Compose</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <strong>Emergency Hotline</strong>
                                <p class="text-muted small mb-2">+63 (02) 1234-5678</p>
                                <button class="btn btn-outline-modern btn-sm" disabled>View details</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <strong>Secure Chat</strong>
                                <p class="text-muted small mb-2">Internal SOC channel</p>
                                <button class="btn btn-outline-modern btn-sm" disabled>Open</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.super-admin-help {
    padding: 2rem 2.5rem;
    max-width: 100%;
    background: #f8fafc;
    min-height: 100vh;
}
.super-admin-help .list-group-item {
    border: none;
    border-radius: 0;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
}
</style>
