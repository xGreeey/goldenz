<?php
/**
 * Developer Dashboard
 * System health and technical monitoring
 */

$page_title = 'Developer Dashboard - Golden Z-5 HR System';
$page = 'developer-dashboard';

// Ensure only developer role can access
if (($_SESSION['user_role'] ?? '') !== 'developer') {
    header('Location: ../landing/index.php');
    exit;
}

// Get environment info
$app_env = $_ENV['APP_ENV'] ?? 'production';
$php_version = PHP_VERSION;
$app_version = config('app.version', '2.0.0');
$session_path = storage_path('sessions');
$session_count = 0;

// Count active sessions (session files)
if (is_dir($session_path)) {
    $files = glob($session_path . '/sess_*');
    $session_count = $files ? count($files) : 0;
}

// Get database connection status
$db_status = 'Connected';
$db_error = null;
try {
    $pdo = get_db_connection();
    $pdo->query("SELECT 1");
} catch (Exception $e) {
    $db_status = 'Error';
    $db_error = $e->getMessage();
}

// Get MySQL version (placeholder - will need actual query)
$mysql_version = 'N/A';
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $mysql_version = $result['version'] ?? 'N/A';
    }
} catch (Exception $e) {
    // Silent fail
}

// Get system status (check if system is in maintenance mode)
$system_status = 'Online';
// TODO: Check maintenance mode flag if exists

// Get recent errors count (last 24h)
$recent_errors = 0;
try {
    $pdo = get_db_connection();
    $checkTable = $pdo->query("SHOW TABLES LIKE 'system_logs'");
    if ($checkTable->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM system_logs WHERE level = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $recent_errors = (int)($result['count'] ?? 0);
    }
} catch (Exception $e) {
    // Table might not exist yet
    $recent_errors = 0;
}

// Get recent system logs (last 10)
$system_logs = [];
try {
    if (function_exists('get_system_logs')) {
        $system_logs = get_system_logs([], 10, 0);
    } else {
        // Fallback: try direct query
        $pdo = get_db_connection();
        $checkTable = $pdo->query("SHOW TABLES LIKE 'system_logs'");
        if ($checkTable->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT id, level, message, created_at FROM system_logs ORDER BY created_at DESC LIMIT 10");
            $stmt->execute();
            $system_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    // Table might not exist yet - use empty array
    $system_logs = [];
}

// Get security events (last 10)
$security_events = [];
try {
    if (function_exists('get_security_logs')) {
        $security_events = get_security_logs([], 10, 0);
    } else {
        // Fallback: try direct query
        $pdo = get_db_connection();
        $checkTable = $pdo->query("SHOW TABLES LIKE 'security_logs'");
        if ($checkTable->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT id, type, details, created_at FROM security_logs ORDER BY created_at DESC LIMIT 10");
            $stmt->execute();
            $security_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    // Table might not exist yet - use empty array
    $security_events = [];
}

// Get recent activity feed (last 20) - from audit_logs
$activity_feed = [];
try {
    if (function_exists('get_audit_logs')) {
        $activity_feed = get_audit_logs([], 20, 0);
    } else {
        // Fallback: try direct query
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT id, action, user_id, ip_address, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 20");
        $stmt->execute();
        $activity_feed = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Table might not exist yet - use empty array
    $activity_feed = [];
}

// Get current server time
$server_time = date('Y-m-d H:i:s');

// Log dashboard access
if (function_exists('log_system_event')) {
    log_system_event('info', 'Developer dashboard accessed', 'dashboard', [
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}
?>

<link rel="stylesheet" href="<?php echo asset_url('css/developer-dashboard.css'); ?>">

<div class="developer-dashboard">
    <div class="dashboard-container">
        <!-- Header Area -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Developer Dashboard</h1>
            <p class="dashboard-subtitle">System health and technical monitoring</p>
        </div>

        <!-- Top Stat Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-value <?php echo $system_status === 'Online' ? 'status-online' : 'status-offline'; ?>">
                    <?php echo htmlspecialchars($system_status); ?>
                </div>
                <div class="stat-label">System Status</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-value <?php echo $db_status === 'Connected' ? 'status-online' : 'status-error'; ?>">
                    <?php echo htmlspecialchars($db_status); ?>
                </div>
                <div class="stat-label">Database Status</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($session_count); ?></div>
                <div class="stat-label">Active Sessions</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-value <?php echo $recent_errors > 0 ? 'status-error' : ''; ?>">
                    <?php echo number_format($recent_errors); ?>
                </div>
                <div class="stat-label">Recent Errors (24h)</div>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="main-grid">
        <!-- Left Column -->
        <div class="grid-column">
            <!-- Recent System Logs -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Recent System Logs</h3>
                    <a href="?page=system_logs" class="card-link">View all</a>
                </div>
                <div class="card-body">
                    <div class="logs-list">
                        <?php if (empty($system_logs)): ?>
                            <div class="empty-state">No logs available</div>
                        <?php else: ?>
                            <?php foreach (array_slice($system_logs, 0, 10) as $log): ?>
                                <div class="log-item log-<?php echo htmlspecialchars(strtolower($log['level'] ?? 'info')); ?>">
                                    <div class="log-time"><?php echo htmlspecialchars(date('H:i:s', strtotime($log['created_at'] ?? 'now'))); ?></div>
                                    <div class="log-level"><?php echo htmlspecialchars(strtoupper($log['level'] ?? 'INFO')); ?></div>
                                    <div class="log-message"><?php echo htmlspecialchars($log['message'] ?? ''); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Security Events -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Security Events</h3>
                    <a href="#" class="card-link">View all</a>
                </div>
                <div class="card-body">
                    <div class="logs-list">
                        <?php if (empty($security_events)): ?>
                            <div class="empty-state">No security events</div>
                        <?php else: ?>
                            <?php foreach (array_slice($security_events, 0, 10) as $event): ?>
                                <div class="log-item log-security">
                                    <div class="log-time"><?php echo htmlspecialchars(date('H:i:s', strtotime($event['created_at'] ?? 'now'))); ?></div>
                                    <div class="log-type"><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($event['type'] ?? 'unknown', '_'))); ?></div>
                                    <div class="log-message"><?php echo htmlspecialchars($event['details'] ?? ''); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="grid-column">
            <!-- Environment Info -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Environment Info</h3>
                </div>
                <div class="card-body">
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">PHP Version</span>
                            <span class="info-value"><?php echo htmlspecialchars($php_version); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">MySQL Version</span>
                            <span class="info-value"><?php echo htmlspecialchars($mysql_version); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">App Version</span>
                            <span class="info-value"><?php echo htmlspecialchars($app_version); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Session Path</span>
                            <span class="info-value"><?php echo htmlspecialchars($session_path); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Developer Tools -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Developer Tools</h3>
                </div>
                <div class="card-body">
                    <div class="tools-grid">
                        <button type="button" class="tool-btn" data-action="clear-sessions" data-confirm="Are you sure you want to clear all sessions?">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            Clear Sessions
                        </button>
                        <button type="button" class="tool-btn" data-action="test-email" data-confirm="Send a test email?">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            Test Email
                        </button>
                        <button type="button" class="tool-btn" data-action="run-diagnostics" data-confirm="Run system diagnostics?">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Run Diagnostics
                        </button>
                        <button type="button" class="tool-btn" data-action="view-migrations" data-confirm="View migration status?">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            View Migrations
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Feed -->
    <div class="dashboard-card activity-feed">
        <div class="card-header">
            <h3 class="card-title">Recent Activity Feed</h3>
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php if (empty($activity_feed)): ?>
                    <div class="empty-state">No recent activity</div>
                <?php else: ?>
                    <?php foreach ($activity_feed as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="timeline-action"><?php echo htmlspecialchars($activity['action'] ?? 'Unknown action'); ?></div>
                                <div class="timeline-meta">
                                    <span class="timeline-time"><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($activity['created_at'] ?? 'now'))); ?></span>
                                    <span class="timeline-separator">•</span>
                                    <span class="timeline-ip">IP: <?php echo htmlspecialchars($activity['ip_address'] ?? 'Unknown'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- Hidden form for POST requests -->
<form id="toolActionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="toolAction">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
</form>

<script src="<?php echo asset_url('js/developer-dashboard.js'); ?>"></script>
