<?php
// Super Admin - Audit Trail
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    echo '<div class="container-fluid"><div class="alert alert-danger">Access denied.</div></div>';
    return;
}

// Filters from query
$filters = [
    'action'     => $_GET['action']     ?? '',
    'table_name' => $_GET['table']      ?? '',
    'user_id'    => $_GET['user_id']    ?? '',
    'date_from'  => $_GET['date_from']  ?? '',
    'date_to'    => $_GET['date_to']    ?? '',
];

$pageNum = max(1, (int)($_GET['p'] ?? 1));
$perPage = 25;
$offset = ($pageNum - 1) * $perPage;

// Fetch logs + count using existing helpers
$logs       = get_audit_logs($filters, $perPage, $offset);
$totalCount = get_audit_logs_count($filters);
$totalPages = max(1, (int)ceil($totalCount / $perPage));
$pageNum    = min($pageNum, $totalPages);

// Build distinct actions + tables for dropdowns (simple queries)
try {
    $actionsStmt = execute_query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
    $allActions  = $actionsStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $tablesStmt = execute_query("SELECT DISTINCT table_name FROM audit_logs WHERE table_name IS NOT NULL AND table_name <> '' ORDER BY table_name ASC");
    $allTables  = $tablesStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Exception $e) {
    $allActions = [];
    $allTables  = [];
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<div class="container-fluid dashboard-modern super-admin-dashboard">
    <div class="page-header-modern mb-4">
        <div class="page-title-modern">
            <h1 class="page-title-main">Audit Trail</h1>
            <p class="page-subtitle">Review detailed activity history across employees, users, posts, and alerts.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card card-modern mb-4">
        <div class="card-body-modern">
            <form class="row g-3" method="GET" action="">
                <input type="hidden" name="page" value="audit_trail">

                <div class="col-md-3">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All actions</option>
                        <?php foreach ($allActions as $action): ?>
                            <option value="<?php echo h($action); ?>" <?php echo $filters['action'] === $action ? 'selected' : ''; ?>>
                                <?php echo h($action); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Table</label>
                    <select name="table" class="form-select">
                        <option value="">All tables</option>
                        <?php foreach ($allTables as $tbl): ?>
                            <option value="<?php echo h($tbl); ?>" <?php echo $filters['table_name'] === $tbl ? 'selected' : ''; ?>>
                                <?php echo h($tbl); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" value="<?php echo h($filters['date_from']); ?>" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" value="<?php echo h($filters['date_to']); ?>" class="form-control">
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary-modern w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>

            <?php if (!empty(array_filter($filters))): ?>
                <div class="mt-3">
                    <a href="?page=audit_trail" class="btn btn-outline-modern btn-sm">
                        <i class="fas fa-times me-1"></i>Clear filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="card card-modern">
        <div class="card-body-modern">
            <div class="card-header-modern mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title-modern mb-0">Activity Log</h5>
                    <small class="card-subtitle">Showing <?php echo number_format(min($perPage, $totalCount)); ?> of <?php echo number_format($totalCount); ?> events</small>
                </div>
            </div>

            <?php if (empty($logs)): ?>
                <div class="alert alert-info mb-0">
                    No audit events found for the selected filters.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date / Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Table / Record</th>
                                <th>Details</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td style="white-space: nowrap;">
                                        <?php echo h(date('Y-m-d H:i:s', strtotime($log['created_at']))); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['user_name'])): ?>
                                            <div><strong><?php echo h($log['user_name']); ?></strong></div>
                                            <small class="text-muted"><?php echo h($log['username'] . ' · ' . $log['role']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-uppercase">
                                            <?php echo h($log['action']); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted"><?php echo h($log['table_name'] ?: ''); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['related_record'])): ?>
                                            <div><strong><?php echo h($log['related_record']['display_name'] ?? ''); ?></strong></div>
                                            <small class="text-muted">
                                                <?php echo h($log['related_record']['display_id'] ?? ''); ?>
                                            </small>
                                        <?php elseif ($log['record_id']): ?>
                                            <small class="text-muted">ID: <?php echo h($log['record_id']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width: 360px;">
                                        <?php
                                        $old = $log['old_values'] ? @json_decode($log['old_values'], true) : null;
                                        $new = $log['new_values'] ? @json_decode($log['new_values'], true) : null;
                                        ?>
                                        <?php if ($old || $new): ?>
                                            <details>
                                                <summary class="small text-muted">View changes</summary>
                                                <div class="mt-2 audit-diff">
                                                    <?php if ($old): ?>
                                                        <div>
                                                            <strong>Before:</strong>
                                                            <pre><?php echo h(json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($new): ?>
                                                        <div class="mt-1">
                                                            <strong>After:</strong>
                                                            <pre><?php echo h(json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </details>
                                        <?php else: ?>
                                            <span class="text-muted small">No structured diff available.</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo h($log['ip_address'] ?? '—'); ?><br>
                                            <?php if (!empty($log['user_agent'])): ?>
                                                <span class="d-inline-block text-truncate" style="max-width: 180px;">
                                                    <?php echo h($log['user_agent']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-3" aria-label="Audit pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <?php
                            $baseQuery = array_merge(
                                ['page' => 'audit_trail'],
                                array_filter([
                                    'action'    => $filters['action'],
                                    'table'     => $filters['table_name'],
                                    'user_id'   => $filters['user_id'],
                                    'date_from' => $filters['date_from'],
                                    'date_to'   => $filters['date_to'],
                                ])
                            );
                            ?>
                            <li class="page-item <?php echo $pageNum <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($baseQuery, ['p' => max(1, $pageNum - 1)])); ?>">
                                    &laquo;
                                </a>
                            </li>
                            <?php
                            $start = max(1, $pageNum - 2);
                            $end   = min($totalPages, $pageNum + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $pageNum ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($baseQuery, ['p' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $pageNum >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($baseQuery, ['p' => min($totalPages, $pageNum + 1)])); ?>">
                                    &raquo;
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.audit-diff pre {
    background: #0b1220;
    color: #e2e8f0;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 0.75rem;
    max-height: 260px;
    overflow: auto;
}
/* Card styling to match HR admin dashboard */
.super-admin-dashboard .card-modern,
.super-admin-dashboard .card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    background: #ffffff;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.super-admin-dashboard .card-modern:hover,
.super-admin-dashboard .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
}

.super-admin-dashboard .card-body-modern,
.super-admin-dashboard .card-body {
    padding: 1.5rem;
}

.super-admin-dashboard .card-header-modern {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.super-admin-dashboard .card-title-modern {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}

</style>

