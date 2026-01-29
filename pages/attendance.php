<?php
$page_title = 'Attendance - Golden Z-5 HR System';
$page = 'attendance';

// Get database connection
$pdo = get_db_connection();

function hms_to_ampm(?string $time): string {
    if (!$time) return '-';
    return date('g:i A', strtotime($time));
}

function compute_attendance_status(?string $time_in, ?string $time_out): string {
    if (!$time_in && !$time_out) return 'Absent';
    if ($time_in && !$time_out) return 'Present';

    // Business rule: Late if time-in after 08:00 AM
    try {
        $in = strtotime($time_in);
        if ($in !== false && $in > strtotime('08:00:00')) return 'Late';
    } catch (Exception $e) {
        // ignore parse errors
    }
    return 'Present';
}

function compute_hours_worked(?string $time_in, ?string $time_out): float {
    if (!$time_in || !$time_out) return 0.0;
    $start = strtotime($time_in);
    $end = strtotime($time_out);
    if ($start === false || $end === false) return 0.0;
    $diff = $end - $start;
    if ($diff <= 0) return 0.0;
    return round($diff / 3600, 2);
}

// Handle manual adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'adjust_attendance') {
        $dtr_id = isset($_POST['dtr_id']) ? (int)$_POST['dtr_id'] : 0;
        $employee_id_post = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
        $entry_date_post = $_POST['entry_date'] ?? '';
        $time_in = $_POST['time_in'] ?? null;
        $time_out = $_POST['time_out'] ?? null;
        $reason = trim((string)($_POST['adjustment_reason'] ?? ''));

        if ($employee_id_post <= 0 || !$entry_date_post) {
            redirect_with_message('?page=attendance', 'Invalid employee or date for adjustment.', 'danger');
        }
        if ($reason === '') {
            redirect_with_message('?page=attendance', 'Adjustment reason is required.', 'danger');
        }

        // Normalize empty strings to NULL (time inputs can be blank)
        $time_in = $time_in !== null ? trim((string)$time_in) : '';
        $time_out = $time_out !== null ? trim((string)$time_out) : '';
        $time_in = $time_in !== '' ? ($time_in . ':00') : null;   // input type="time" gives HH:MM
        $time_out = $time_out !== '' ? ($time_out . ':00') : null;

        // Validate date
        $dt = DateTime::createFromFormat('Y-m-d', $entry_date_post);
        if (!$dt || $dt->format('Y-m-d') !== $entry_date_post) {
            redirect_with_message('?page=attendance', 'Invalid date format.', 'danger');
        }

        // Fetch existing record (by provided id or by unique employee/date)
        $existing = null;
        if ($dtr_id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM dtr_entries WHERE id = ? LIMIT 1");
            $stmt->execute([$dtr_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if (!$existing) {
            $stmt = $pdo->prepare("SELECT * FROM dtr_entries WHERE employee_id = ? AND entry_date = ? LIMIT 1");
            $stmt->execute([$employee_id_post, $entry_date_post]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $old_time_in = $existing['time_in'] ?? null;
        $old_time_out = $existing['time_out'] ?? null;
        $dtr_entry_id = $existing['id'] ?? null;

        // Insert or update the DTR entry (schema uses one row per employee/date)
        if ($existing) {
            $stmt = $pdo->prepare("
                UPDATE dtr_entries
                SET time_in = ?, time_out = ?, entry_type = 'time-in', updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$time_in, $time_out, (int)$existing['id']]);
            $dtr_entry_id = (int)$existing['id'];
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO dtr_entries (employee_id, entry_date, time_in, time_out, entry_type, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'time-in', NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$employee_id_post, $entry_date_post, $time_in, $time_out]);
            $dtr_entry_id = (int)$pdo->lastInsertId();
        }

        // Audit log
        $adjusted_by = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO dtr_adjustments
                    (employee_id, entry_date, dtr_entry_id, old_time_in, new_time_in, old_time_out, new_time_out, reason, adjusted_by, ip_address, user_agent)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $employee_id_post,
                $entry_date_post,
                $dtr_entry_id,
                $old_time_in,
                $time_in,
                $old_time_out,
                $time_out,
                $reason,
                $adjusted_by,
                $ip,
                $ua
            ]);
        } catch (Exception $e) {
            // If audit table isn't migrated yet, don't block the adjustment
            error_log('DTR adjustment audit insert failed: ' . $e->getMessage());
        }

        // Preserve current filters when returning
        $q = $_POST['return_query'] ?? '';
        $redirect = '?page=attendance';
        if (is_string($q) && $q !== '') $redirect .= '&' . ltrim($q, '&?');
        redirect_with_message($redirect, 'Attendance adjusted successfully!', 'success');
    }
}

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$employee_id = $_GET['employee_id'] ?? '';
$status = $_GET['status'] ?? '';

// Get all employees for filter dropdown
$employees = get_employees();

// Attendance data (real DB)
$attendance_records = [];
$statuses = ['Present', 'Late', 'Absent'];

// Basic validation for dates (fallback to today)
$df = DateTime::createFromFormat('Y-m-d', $date_from) ?: new DateTime();
$dt = DateTime::createFromFormat('Y-m-d', $date_to) ?: new DateTime();
$date_from = $df->format('Y-m-d');
$date_to = $dt->format('Y-m-d');

try {
    if ($date_from === $date_to) {
        // Single day view: show all employees, even without DTR entry (Absent)
        $sql = "
            SELECT
                e.id AS employee_id,
                CONCAT(e.surname, ', ', e.first_name) AS employee_name,
                e.post AS employee_post,
                d.id AS dtr_id,
                ? AS entry_date,
                d.time_in AS time_in,
                d.time_out AS time_out,
                d.updated_at AS updated_at
            FROM employees e
            LEFT JOIN dtr_entries d
                ON d.employee_id = e.id AND d.entry_date = ?
            WHERE (? = '' OR e.id = ?)
            ORDER BY e.surname ASC, e.first_name ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$date_from, $date_from, $employee_id, $employee_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Range view: show only employees with entries in range (keeps result size manageable)
        $sql = "
            SELECT
                e.id AS employee_id,
                CONCAT(e.surname, ', ', e.first_name) AS employee_name,
                e.post AS employee_post,
                d.id AS dtr_id,
                d.entry_date AS entry_date,
                d.time_in AS time_in,
                d.time_out AS time_out,
                d.updated_at AS updated_at
            FROM dtr_entries d
            INNER JOIN employees e ON e.id = d.employee_id
            WHERE d.entry_date BETWEEN ? AND ?
              AND (? = '' OR e.id = ?)
            ORDER BY d.entry_date DESC, e.surname ASC, e.first_name ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$date_from, $date_to, $employee_id, $employee_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($rows as $r) {
        $status_val = compute_attendance_status($r['time_in'] ?? null, $r['time_out'] ?? null);
        $hours = compute_hours_worked($r['time_in'] ?? null, $r['time_out'] ?? null);

        if ($status !== '' && $status_val !== $status) continue;

        // Determine if adjusted (exists in audit log)
        $is_adjusted = false;
        try {
            $chk = $pdo->prepare("SELECT 1 FROM dtr_adjustments WHERE employee_id = ? AND entry_date = ? LIMIT 1");
            $chk->execute([(int)$r['employee_id'], (string)$r['entry_date']]);
            $is_adjusted = (bool)$chk->fetchColumn();
        } catch (Exception $e) {
            $is_adjusted = false;
        }

        $attendance_records[] = [
            'id' => (int)($r['dtr_id'] ?? 0),
            'dtr_id' => (int)($r['dtr_id'] ?? 0),
            'employee_id' => (int)$r['employee_id'],
            'employee_name' => (string)$r['employee_name'],
            'employee_post' => (string)($r['employee_post'] ?? ''),
            'date' => (string)$r['entry_date'],
            'time_in' => $r['time_in'] ?? null,
            'time_out' => $r['time_out'] ?? null,
            'status' => $status_val,
            'hours_worked' => $hours,
            'is_adjusted' => $is_adjusted,
        ];
    }
} catch (Exception $e) {
    error_log('Attendance load failed: ' . $e->getMessage());
    $attendance_records = [];
}

// Get statistics
$total_present = count(array_filter($attendance_records, fn($r) => $r['status'] === 'Present'));
$total_late = count(array_filter($attendance_records, fn($r) => $r['status'] === 'Late'));
$total_absent = count(array_filter($attendance_records, fn($r) => $r['status'] === 'Absent'));
$avg_hours = array_sum(array_column($attendance_records, 'hours_worked')) / max(count($attendance_records), 1);
?>

<div class="container-fluid hrdash">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Present</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($total_present); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Employees present today</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Late</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-warning"><?php echo number_format($total_late); ?></div>
                </div>
                <div class="hrdash-stat__meta">Late arrivals</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Absent</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value text-danger"><?php echo number_format($total_absent); ?></div>
                </div>
                <div class="hrdash-stat__meta">Absent today</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Avg Hours</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($avg_hours, 1); ?>h</div>
                </div>
                <div class="hrdash-stat__meta">Average hours worked</div>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card card-modern">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title-modern">Daily Attendance</h5>
                <div class="card-subtitle">Track employee attendance and work hours</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-modern" id="exportAttendanceBtn">
                    <i class="fas fa-file-export me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-body-modern">
            <form method="GET" action="" class="mb-4" id="attendanceFilterForm">
                <input type="hidden" name="page" value="attendance">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <div class="position-relative">
                            <i class="fas fa-search position-absolute top-50 translate-middle-y text-muted" style="left: 12px;"></i>
                            <input
                                type="search"
                                id="attendanceSearch"
                                class="form-control"
                                placeholder="Search employee, post, status, date…"
                                autocomplete="off"
                                spellcheck="false"
                                style="padding-left: 36px;"
                            >
                        </div>
                        <small class="text-muted">Type to filter instantly.</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select">
                            <option value="">All Employees</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" <?php echo $employee_id == $emp['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(($emp['surname'] ?? '') . ', ' . ($emp['first_name'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $status === $s ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-modern" onclick="window.location.href='?page=attendance'" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Attendance Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Post</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendance_records)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No attendance records found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr class="attendance-row"
                                    data-employee="<?php echo htmlspecialchars(strtolower((string)($record['employee_name'] ?? ''))); ?>"
                                    data-post="<?php echo htmlspecialchars(strtolower((string)($record['employee_post'] ?? ''))); ?>"
                                    data-status="<?php echo htmlspecialchars(strtolower((string)($record['status'] ?? ''))); ?>"
                                    data-date="<?php echo htmlspecialchars((string)($record['date'] ?? '')); ?>"
                                >
                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($record['employee_name']); ?></div>
                                        <?php if ($record['is_adjusted']): ?>
                                            <small class="text-warning"><i class="fas fa-edit"></i> Adjusted</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted"><?php echo htmlspecialchars($record['employee_post']); ?></td>
                                    <td><?php echo hms_to_ampm($record['time_in']); ?></td>
                                    <td><?php echo hms_to_ampm($record['time_out']); ?></td>
                                    <td><?php echo $record['hours_worked'] > 0 ? number_format($record['hours_worked'], 1) . 'h' : '-'; ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'bg-secondary';
                                        if ($record['status'] === 'Present') $badge_class = 'bg-success';
                                        elseif ($record['status'] === 'Late') $badge_class = 'bg-warning';
                                        elseif ($record['status'] === 'Absent') $badge_class = 'bg-danger';
                                        elseif ($record['status'] === 'On Leave') $badge_class = 'bg-info';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($record['status']); ?></span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary adjust-btn" 
                                                data-record='<?php echo htmlspecialchars(json_encode($record), ENT_QUOTES, 'UTF-8'); ?>'
                                                data-bs-toggle="modal" data-bs-target="#adjustAttendanceModal"
                                                title="Adjust Time">
                                            <i class="fas fa-edit"></i>
                                        </button>
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

<style>
/* Smooth "butter" filtering transitions (respects reduced motion) */
.attendance-row {
    transition: opacity 180ms ease, transform 180ms ease;
    will-change: opacity, transform;
}
.attendance-row.is-filtered-out {
    opacity: 0;
    transform: translateY(-6px);
}
@media (prefers-reduced-motion: reduce) {
    .attendance-row {
        transition: none !important;
    }
}
</style>

<!-- Adjust Attendance Modal -->
<div class="modal fade" id="adjustAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="adjust_attendance">
                    <input type="hidden" name="dtr_id" id="adjust_dtr_id">
                    <input type="hidden" name="employee_id" id="adjust_employee_id">
                    <input type="hidden" name="entry_date" id="adjust_entry_date">
                    <input type="hidden" name="return_query" value="<?php echo htmlspecialchars($_SERVER['QUERY_STRING'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" id="adjust_employee_name" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" id="adjust_date" class="form-control" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Time In</label>
                            <input type="time" name="time_in" id="adjust_time_in" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Time Out</label>
                            <input type="time" name="time_out" id="adjust_time_out" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Adjustment <span class="text-danger">*</span></label>
                        <textarea name="adjustment_reason" class="form-control" rows="3" placeholder="Provide a detailed reason for this adjustment..." required></textarea>
                        <small class="text-muted">This will be logged for audit purposes.</small>
                    </div>
                    
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Manual adjustments are logged and audited. Ensure the reason is valid and documented.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle adjust button
document.addEventListener('DOMContentLoaded', function() {
    const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const filterForm = document.getElementById('attendanceFilterForm');
    const searchInput = document.getElementById('attendanceSearch');
    const tableBody = document.querySelector('.table-responsive tbody');

    function debounce(fn, wait) {
        let t;
        return function(...args) {
            window.clearTimeout(t);
            t = window.setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function getRows() {
        return tableBody ? Array.from(tableBody.querySelectorAll('tr.attendance-row')) : [];
    }

    function showRow(row) {
        row.style.display = '';
        row.classList.remove('is-filtered-out');
        if (!prefersReducedMotion) {
            // re-trigger transition
            row.style.opacity = '0';
            row.style.transform = 'translateY(-6px)';
            requestAnimationFrame(() => {
                row.style.opacity = '';
                row.style.transform = '';
            });
        }
    }

    function hideRow(row) {
        if (prefersReducedMotion) {
            row.style.display = 'none';
            return;
        }
        row.classList.add('is-filtered-out');
        window.setTimeout(() => {
            // Ensure it’s still filtered out before hiding
            if (row.classList.contains('is-filtered-out')) {
                row.style.display = 'none';
            }
        }, 180);
    }

    function filterRowsBySearch() {
        if (!searchInput || !tableBody) return;
        const q = (searchInput.value || '').trim().toLowerCase();
        const rows = getRows();

        if (!q) {
            rows.forEach(showRow);
            return;
        }

        rows.forEach(row => {
            const haystack = [
                row.dataset.employee || '',
                row.dataset.post || '',
                row.dataset.status || '',
                row.dataset.date || ''
            ].join(' ');

            if (haystack.includes(q)) {
                showRow(row);
            } else {
                hideRow(row);
            }
        });
    }

    // Instant search (no Enter / no button)
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterRowsBySearch, 120));
    }

    // Keep existing server filters, but make them auto-apply when changed
    if (filterForm) {
        filterForm.querySelectorAll('input[type="date"], select').forEach(el => {
            el.addEventListener('change', () => filterForm.submit());
        });
    }

    document.querySelectorAll('.adjust-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const record = JSON.parse(this.dataset.record);
            document.getElementById('adjust_dtr_id').value = record.dtr_id || record.id || 0;
            document.getElementById('adjust_employee_id').value = record.employee_id || '';
            document.getElementById('adjust_entry_date').value = record.date || '';
            document.getElementById('adjust_employee_name').value = record.employee_name;
            document.getElementById('adjust_date').value = new Date(record.date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'});
            // type="time" expects HH:MM
            document.getElementById('adjust_time_in').value = record.time_in ? String(record.time_in).slice(0,5) : '';
            document.getElementById('adjust_time_out').value = record.time_out ? String(record.time_out).slice(0,5) : '';
        });
    });
});
</script>
