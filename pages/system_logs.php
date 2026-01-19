<?php
// Super Admin - System Logs
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    echo '<div class="container-fluid"><div class="alert alert-danger">Access denied.</div></div>';
    return;
}

$basePath = dirname(__DIR__);
$logsDir = $basePath . '/storage/logs/';

// Available logs (add more here if you create new log files)
$availableLogs = [
    'security' => [
        'label' => 'Security Log',
        'path' => $logsDir . 'security.log',
    ],
    'error' => [
        'label' => 'Error Log',
        'path' => $logsDir . 'error.log',
    ],
];

$logKey = $_GET['log'] ?? 'security';
if (!isset($availableLogs[$logKey])) {
    $logKey = 'security';
}

$search = trim($_GET['search'] ?? '');
$level = strtoupper(trim($_GET['level'] ?? ''));
if ($level === 'ALL') $level = '';

$pageNum = max(1, (int)($_GET['p'] ?? 1));
$perPage = 100;

$logPath = $availableLogs[$logKey]['path'];
$lines = [];
$fileExists = is_file($logPath);

if ($fileExists) {
    // Read file lines; show newest first
    $raw = @file($logPath, FILE_IGNORE_NEW_LINES);
    if (is_array($raw)) {
        $lines = array_reverse($raw);
    }
}

// Filter lines
$filtered = [];
foreach ($lines as $ln) {
    if ($search !== '' && stripos($ln, $search) === false) {
        continue;
    }
    if ($level !== '' && stripos($ln, $level) === false) {
        continue;
    }
    $filtered[] = $ln;
}

$total = count($filtered);
$totalPages = max(1, (int)ceil($total / $perPage));
$pageNum = min($pageNum, $totalPages);
$offset = ($pageNum - 1) * $perPage;
$pageLines = array_slice($filtered, $offset, $perPage);

// Helpers
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<div class="container-fluid dashboard-modern super-admin-dashboard">
    <div class="page-header-modern mb-4">
        <div class="page-title-modern">
            <h1 class="page-title-main">System Logs</h1>
            <p class="page-subtitle">View and manage application logs (Security & Errors)</p>
        </div>
        <div class="page-actions-modern d-flex gap-2 align-items-center">
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="liveLogsToggle" checked>
                <label class="form-check-label" for="liveLogsToggle">Live</label>
            </div>
            <a class="btn btn-outline-modern" href="?page=system_logs&log=<?php echo urlencode($logKey); ?>&download=1" title="Download current log">
                <i class="fas fa-download me-2"></i>Download
            </a>
            <button class="btn btn-outline-modern" type="button" id="clearLogBtn" title="Clear current log">
                <i class="fas fa-trash me-2"></i>Clear
            </button>
        </div>
    </div>

    <div class="card card-modern mb-3">
        <div class="card-body-modern">
            <form class="row g-3" method="GET" action="">
                <input type="hidden" name="page" value="system_logs">
                <div class="col-md-3">
                    <label class="form-label">Log File</label>
                    <select class="form-select" name="log">
                        <?php foreach ($availableLogs as $k => $meta): ?>
                            <option value="<?php echo h($k); ?>" <?php echo $k === $logKey ? 'selected' : ''; ?>>
                                <?php echo h($meta['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Level</label>
                    <select class="form-select" name="level">
                        <?php
                        $levels = ['ALL', 'ERROR', 'WARNING', 'INFO', 'DEBUG'];
                        $currentLevel = $level === '' ? 'ALL' : $level;
                        ?>
                        <?php foreach ($levels as $lvl): ?>
                            <option value="<?php echo h($lvl); ?>" <?php echo $lvl === $currentLevel ? 'selected' : ''; ?>>
                                <?php echo h($lvl); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input class="form-control" name="search" value="<?php echo h($search); ?>" placeholder="Search logs...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary-modern w-100" type="submit">
                        <i class="fas fa-filter me-2"></i>Apply
                    </button>
                </div>
            </form>

            <div class="mt-3 d-flex flex-wrap gap-3 text-muted small" id="logMetaRow">
                <div><strong>File:</strong> <?php echo h($availableLogs[$logKey]['label']); ?></div>
                <div><strong>Status:</strong> <span id="logStatusText"><?php echo $fileExists ? 'Found' : 'Not found (will appear when first written)'; ?></span></div>
                <div><strong>Showing:</strong> <span id="logShownCount"><?php echo number_format(count($pageLines)); ?></span> of <span id="logTotalCount"><?php echo number_format($total); ?></span> lines</div>
            </div>
        </div>
    </div>

    <div class="card card-modern">
        <div class="card-body-modern">
            <?php if ($total === 0): ?>
                <div class="alert alert-info mb-0">
                    No log entries found for the current filters.
                </div>
            <?php else: ?>
                <div class="logs-view" role="region" aria-label="Log output" id="logsView">
                    <?php foreach ($pageLines as $ln): ?>
                        <?php
                        $cls = 'log-line';
                        $u = strtoupper($ln);
                        if (strpos($u, 'ERROR') !== false) $cls .= ' is-error';
                        elseif (strpos($u, 'WARN') !== false) $cls .= ' is-warn';
                        elseif (strpos($u, 'INFO') !== false) $cls .= ' is-info';
                        ?>
                        <div class="<?php echo $cls; ?>"><?php echo h($ln); ?></div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-3" aria-label="System logs pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <?php
                            $queryBase = [
                                'page' => 'system_logs',
                                'log' => $logKey,
                                'search' => $search,
                                'level' => ($level === '' ? 'ALL' : $level),
                            ];
                            ?>
                            <li class="page-item <?php echo $pageNum <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($queryBase, ['p' => max(1, $pageNum - 1)])); ?>">&laquo;</a>
                            </li>
                            <?php
                            $start = max(1, $pageNum - 2);
                            $end = min($totalPages, $pageNum + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $pageNum ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($queryBase, ['p' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $pageNum >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($queryBase, ['p' => min($totalPages, $pageNum + 1)])); ?>">&raquo;</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Page Header - Rectangle container with rounded corners */
.super-admin-dashboard .page-header-modern {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 14px !important;
    padding: 1.5rem 2rem !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04) !important;
}

.super-admin-dashboard .page-header-modern .page-title-modern {
    padding-left: 1rem;
}

.logs-view{
    background:#0b1220;
    color:#e2e8f0;
    border-radius:12px;
    padding:12px;
    max-height: 70vh;
    overflow:auto;
    border: 1px solid rgba(148,163,184,.25);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.875rem;
    line-height: 1.5;
}
.log-line{
    padding:4px 6px;
    border-bottom: 1px solid rgba(148,163,184,.08);
    white-space: pre-wrap;
    word-break: break-word;
}
.log-line:last-child{border-bottom:0;}
.log-line.is-error{color:#fecaca;background:rgba(239,68,68,.08);}
.log-line.is-warn{color:#fde68a;background:rgba(245,158,11,.08);}
.log-line.is-info{color:#bae6fd;background:rgba(14,165,233,.08);}
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

<script>
(function() {
    const clearBtn = document.getElementById('clearLogBtn');
    if (!clearBtn) return;

    const liveToggle = document.getElementById('liveLogsToggle');
    const logsView = document.getElementById('logsView');
    const statusText = document.getElementById('logStatusText');
    const shownCount = document.getElementById('logShownCount');
    const totalCount = document.getElementById('logTotalCount');

    const state = {
        log: <?php echo json_encode($logKey); ?>,
        search: <?php echo json_encode($search); ?>,
        level: <?php echo json_encode($level === '' ? 'ALL' : $level); ?>,
        p: <?php echo json_encode($pageNum); ?>,
        per_page: <?php echo json_encode($perPage); ?>,
        lastMtime: null,
        inflight: false,
    };

    clearBtn.addEventListener('click', function() {
        if (!confirm('Clear this log file? This cannot be undone.')) return;

        const formData = new FormData();
        formData.append('action', 'clear_log');
        formData.append('log', state.log);

        fetch(window.location.pathname + '?page=system_logs', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                window.location.reload();
            } else {
                alert((data && data.message) ? data.message : 'Failed to clear log');
            }
        })
        .catch(() => alert('Failed to clear log'));
    });

    function pollOnce() {
        if (!liveToggle || !liveToggle.checked) return;
        if (state.inflight) return;

        state.inflight = true;
        const fd = new FormData();
        fd.append('action', 'fetch_log');
        fd.append('log', state.log);
        fd.append('search', state.search);
        fd.append('level', state.level);
        fd.append('p', String(state.p));
        fd.append('per_page', String(state.per_page));

        fetch(window.location.pathname + '?page=system_logs', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) return;
            if (state.lastMtime === null || data.file_mtime !== state.lastMtime) {
                state.lastMtime = data.file_mtime;
                if (logsView) logsView.innerHTML = data.html || '';
                if (shownCount) shownCount.textContent = String(data.shown ?? 0);
                if (totalCount) totalCount.textContent = String(data.total ?? 0);
                if (statusText) statusText.textContent = data.file_exists ? 'Found' : 'Not found (will appear when first written)';
            }
        })
        .catch(() => {})
        .finally(() => { state.inflight = false; });
    }

    setInterval(pollOnce, 3000);
    setTimeout(pollOnce, 400);
})();
</script>

