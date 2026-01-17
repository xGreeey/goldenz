<?php
/**
 * Developer System Logs
 * Standalone system logs viewer for developers
 */

$page_title = 'System Logs - Developer Dashboard';
$page = 'system_logs';

// Ensure only developer role can access
if (($_SESSION['user_role'] ?? '') !== 'developer') {
    header('Location: ../landing/index.php');
    exit;
}

// Get filters
$level_filter = $_GET['level'] ?? '';
$context_filter = $_GET['context'] ?? '';
$search = trim($_GET['search'] ?? '');
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Pagination
$page_num = max(1, (int)($_GET['p'] ?? 1));
$per_page = 50;
$offset = ($page_num - 1) * $per_page;

// Build filters
$filters = [];
if ($level_filter) $filters['level'] = $level_filter;
if ($context_filter) $filters['context'] = $context_filter;
if ($search) $filters['search'] = $search;
if ($date_from) $filters['date_from'] = $date_from;
if ($date_to) $filters['date_to'] = $date_to;

// Get logs
$system_logs = [];
$total_count = 0;

try {
    if (function_exists('get_system_logs')) {
        $system_logs = get_system_logs($filters, $per_page, $offset);
        
        // Get total count
        if (function_exists('get_system_logs_count')) {
            $total_count = get_system_logs_count($filters);
        } else {
            // Fallback: count all matching logs
            $all_logs = get_system_logs($filters, 10000, 0);
            $total_count = count($all_logs);
        }
    }
} catch (Exception $e) {
    error_log("Error fetching system logs: " . $e->getMessage());
}

$total_pages = max(1, (int)ceil($total_count / $per_page));
$page_num = min($page_num, $total_pages);

// Get unique contexts for filter
$contexts = [];
try {
    $pdo = get_db_connection();
    $checkTable = $pdo->query("SHOW TABLES LIKE 'system_logs'");
    if ($checkTable->rowCount() > 0) {
        $stmt = $pdo->query("SELECT DISTINCT context FROM system_logs WHERE context IS NOT NULL AND context != '' ORDER BY context");
        $contexts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Exception $e) {
    // Silent fail
}

// Log system logs page access
if (function_exists('log_system_event')) {
    log_system_event('info', 'System logs page accessed', 'logs', [
        'filters' => $filters,
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
}
?>

<link rel="stylesheet" href="<?php echo asset_url('css/developer-dashboard.css'); ?>">

<div class="developer-dashboard">
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">System Logs</h1>
            <p class="dashboard-subtitle">View and monitor all system activities</p>
        </div>

        <!-- Filters -->
        <div class="dashboard-card mb-4">
            <div class="card-header">
                <h3 class="card-title">Filters</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="?page=system_logs" class="logs-filters">
                    <input type="hidden" name="page" value="system_logs">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label class="filter-label">Level</label>
                            <select name="level" class="filter-select">
                                <option value="">All Levels</option>
                                <option value="info" <?php echo $level_filter === 'info' ? 'selected' : ''; ?>>Info</option>
                                <option value="warning" <?php echo $level_filter === 'warning' ? 'selected' : ''; ?>>Warning</option>
                                <option value="error" <?php echo $level_filter === 'error' ? 'selected' : ''; ?>>Error</option>
                                <option value="debug" <?php echo $level_filter === 'debug' ? 'selected' : ''; ?>>Debug</option>
                                <option value="security" <?php echo $level_filter === 'security' ? 'selected' : ''; ?>>Security</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Context</label>
                            <select name="context" class="filter-select">
                                <option value="">All Contexts</option>
                                <?php foreach ($contexts as $ctx): ?>
                                    <option value="<?php echo htmlspecialchars($ctx); ?>" <?php echo $context_filter === $ctx ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ctx); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Date From</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="filter-input">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Date To</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="filter-input">
                        </div>
                        <div class="filter-group filter-group-search">
                            <label class="filter-label">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search logs..." class="filter-input">
                        </div>
                        <div class="filter-group filter-group-action">
                            <label class="filter-label">&nbsp;</label>
                            <button type="submit" class="filter-btn">Apply Filters</button>
                            <a href="?page=system_logs" class="filter-btn filter-btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">System Logs</h3>
                <div class="logs-meta">
                    <span>Showing <?php echo number_format(count($system_logs)); ?> of <?php echo number_format($total_count); ?> entries</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($system_logs)): ?>
                    <div class="empty-state">No logs found for the selected filters.</div>
                <?php else: ?>
                    <div class="logs-table-container">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Level</th>
                                    <th>Context</th>
                                    <th>Message</th>
                                    <th>User</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($system_logs as $log): ?>
                                    <tr class="log-row log-<?php echo htmlspecialchars(strtolower($log['level'] ?? 'info')); ?>">
                                        <td class="log-time"><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($log['created_at'] ?? 'now'))); ?></td>
                                        <td class="log-level">
                                            <span class="level-badge level-<?php echo htmlspecialchars(strtolower($log['level'] ?? 'info')); ?>">
                                                <?php echo htmlspecialchars(strtoupper($log['level'] ?? 'INFO')); ?>
                                            </span>
                                        </td>
                                        <td class="log-context"><?php echo htmlspecialchars($log['context'] ?? '-'); ?></td>
                                        <td class="log-message"><?php echo htmlspecialchars($log['message'] ?? ''); ?></td>
                                        <td class="log-user"><?php echo htmlspecialchars($log['user_name'] ?? $log['username'] ?? '-'); ?></td>
                                        <td class="log-ip"><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="logs-pagination">
                            <ul class="pagination-list">
                                <?php
                                $query_base = [
                                    'page' => 'system_logs',
                                    'level' => $level_filter,
                                    'context' => $context_filter,
                                    'search' => $search,
                                    'date_from' => $date_from,
                                    'date_to' => $date_to,
                                ];
                                ?>
                                <li class="pagination-item <?php echo $page_num <= 1 ? 'disabled' : ''; ?>">
                                    <a href="?<?php echo http_build_query(array_merge($query_base, ['p' => max(1, $page_num - 1)])); ?>" class="pagination-link">« Previous</a>
                                </li>
                                <?php
                                $start = max(1, $page_num - 2);
                                $end = min($total_pages, $page_num + 2);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <li class="pagination-item <?php echo $i === $page_num ? 'active' : ''; ?>">
                                        <a href="?<?php echo http_build_query(array_merge($query_base, ['p' => $i])); ?>" class="pagination-link"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="pagination-item <?php echo $page_num >= $total_pages ? 'disabled' : ''; ?>">
                                    <a href="?<?php echo http_build_query(array_merge($query_base, ['p' => min($total_pages, $page_num + 1)])); ?>" class="pagination-link">Next »</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* System Logs Page - Slimmer Header */
.developer-dashboard .dashboard-container .dashboard-header {
    margin-bottom: var(--spacing-md);
    padding: var(--spacing-sm) 0;
}

.developer-dashboard .dashboard-container .dashboard-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: var(--spacing-xs);
}

.developer-dashboard .dashboard-container .dashboard-subtitle {
    font-size: 0.875rem;
    margin-top: 0;
}

.logs-filters {
    margin-bottom: 0;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--spacing-md);
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.filter-group-search {
    grid-column: span 2;
}

.filter-group-action {
    grid-column: span 2;
    flex-direction: row;
    gap: var(--spacing-sm);
}

.filter-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--dev-text-light);
}

.filter-select,
.filter-input {
    padding: var(--spacing-sm) var(--spacing-md);
    background: #1a1a1a;
    border: 1px solid var(--dev-border);
    border-radius: var(--radius-md);
    color: var(--dev-text);
    font-size: 0.875rem;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: var(--log-info);
}

.filter-btn {
    padding: var(--spacing-sm) var(--spacing-lg);
    background: var(--log-info);
    border: none;
    border-radius: var(--radius-md);
    color: white;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-btn:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.filter-btn-secondary {
    background: var(--dev-card-bg);
    border: 1px solid var(--dev-border);
    color: var(--dev-text);
}

.filter-btn-secondary:hover {
    background: #222222;
}

.logs-meta {
    font-size: 0.875rem;
    color: var(--dev-text-light);
}

.logs-table-container {
    overflow-x: auto;
}

.logs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.logs-table thead {
    background: #1a1a1a;
    border-bottom: 2px solid var(--dev-border);
}

.logs-table th {
    padding: var(--spacing-md);
    text-align: left;
    font-weight: 600;
    color: var(--dev-text);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

.logs-table td {
    padding: var(--spacing-sm) var(--spacing-md);
    border-bottom: 1px solid var(--dev-border);
    color: var(--dev-text);
}

.log-row:hover {
    background: #1a1a1a;
}

.log-time {
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    color: var(--dev-text-light);
    white-space: nowrap;
}

.level-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.level-badge.level-info {
    background: rgba(59, 130, 246, 0.2);
    color: var(--log-info);
}

.level-badge.level-warning {
    background: rgba(245, 158, 11, 0.2);
    color: var(--log-warning);
}

.level-badge.level-error {
    background: rgba(239, 68, 68, 0.2);
    color: var(--log-error);
}

.level-badge.level-debug {
    background: rgba(156, 163, 175, 0.2);
    color: #9ca3af;
}

.level-badge.level-security {
    background: rgba(139, 92, 246, 0.2);
    color: var(--log-security);
}

.log-context {
    color: var(--dev-text-light);
    font-size: 0.75rem;
}

.log-message {
    max-width: 400px;
    word-break: break-word;
}

.log-user {
    color: var(--dev-text-light);
    font-size: 0.75rem;
}

.log-ip {
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    color: var(--dev-text-light);
}

.logs-pagination {
    margin-top: var(--spacing-lg);
    padding-top: var(--spacing-lg);
    border-top: 1px solid var(--dev-border);
}

.pagination-list {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: var(--spacing-xs);
    list-style: none;
    padding: 0;
    margin: 0;
}

.pagination-item {
    display: inline-block;
}

.pagination-link {
    padding: var(--spacing-xs) var(--spacing-md);
    background: #1a1a1a;
    border: 1px solid var(--dev-border);
    border-radius: var(--radius-md);
    color: var(--dev-text);
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.pagination-link:hover:not(.disabled) {
    background: #222222;
    border-color: var(--log-info);
    color: var(--log-info);
}

.pagination-item.active .pagination-link {
    background: var(--log-info);
    border-color: var(--log-info);
    color: white;
}

.pagination-item.disabled .pagination-link {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-group-search,
    .filter-group-action {
        grid-column: span 1;
    }
    
    .logs-table {
        font-size: 0.75rem;
    }
    
    .logs-table th,
    .logs-table td {
        padding: var(--spacing-xs);
    }
}
</style>
