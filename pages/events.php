<?php
$page_title = 'Events - Golden Z-5 HR System';
$page = 'events';

// Data prep
$pdo = get_db_connection();

// Function to get schedule events for a specific date
function get_schedule_events($date) {
    $pdo = get_db_connection();
    $events = [];
    
    try {
        $dateStr = date('Y-m-d', strtotime($date));
        
        // Get events from employee_alerts table
        $alertsStmt = $pdo->prepare("SELECT 
                ea.id,
                ea.title,
                ea.description,
                ea.priority,
                ea.status,
                ea.created_at,
                ea.alert_date as event_date,
                TIME(ea.created_at) as event_time,
                e.first_name,
                e.surname,
                e.post,
                'alert' as event_source
            FROM employee_alerts ea
            LEFT JOIN employees e ON ea.employee_id = e.id
            WHERE (ea.alert_date = ? OR DATE(ea.created_at) = ?)
                AND (ea.status = 'active' OR ea.status IS NULL)
            ORDER BY 
                FIELD(ea.priority, 'Urgent', 'High', 'Medium', 'Low'),
                TIME(ea.created_at) ASC,
                ea.created_at ASC");
        $alertsStmt->execute([$dateStr, $dateStr]);
        $alerts = $alertsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get events from events table
        $eventsStmt = $pdo->prepare("SELECT 
                e.id,
                e.title,
                e.description,
                CASE 
                    WHEN e.event_type = 'Holiday' THEN 'Urgent'
                    WHEN e.event_type = 'Examination' THEN 'High'
                    WHEN e.event_type = 'Academic' THEN 'Medium'
                    ELSE 'Low'
                END as priority,
                'active' as status,
                CONCAT(e.start_date, ' ', COALESCE(e.start_time, '00:00:00')) as created_at,
                e.start_date as event_date,
                COALESCE(e.start_time, '00:00:00') as event_time,
                NULL as first_name,
                NULL as surname,
                NULL as post,
                'event' as event_source
            FROM events e
            WHERE e.start_date = ?
            ORDER BY 
                COALESCE(e.start_time, '00:00:00') ASC,
                e.start_date ASC");
        $eventsStmt->execute([$dateStr]);
        $calendarEvents = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge and format events
        $allEvents = array_merge($alerts, $calendarEvents);
        
        // Sort by time
        usort($allEvents, function($a, $b) {
            $timeA = $a['event_time'] ?? '00:00:00';
            $timeB = $b['event_time'] ?? '00:00:00';
            return strcmp($timeA, $timeB);
        });
        
        // Return all events (no limit)
        return $allEvents;
        
    } catch (Exception $e) {
        error_log("Error fetching schedule events: " . $e->getMessage());
        return [];
    }
}

// Get today's schedule
$todayDate = date('Y-m-d');
$today_schedule = get_schedule_events($todayDate);

// Get upcoming events (next 30 days)
$upcoming_events = [];
try {
    $upcomingStmt = $pdo->prepare("SELECT 
            e.id,
            e.title,
            e.description,
            e.start_date,
            e.start_time,
            e.event_type,
            CASE 
                WHEN e.event_type = 'Holiday' THEN 'Urgent'
                WHEN e.event_type = 'Examination' THEN 'High'
                WHEN e.event_type = 'Academic' THEN 'Medium'
                ELSE 'Low'
            END as priority,
            'event' as event_source
        FROM events e
        WHERE e.start_date >= CURDATE()
        ORDER BY e.start_date ASC, COALESCE(e.start_time, '00:00:00') ASC
        LIMIT 10");
    $upcomingStmt->execute();
    $upcoming_events = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching upcoming events: " . $e->getMessage());
}

// Get announcements (from employee_alerts with high priority or recent)
$announcements = [];
try {
    $announcementsStmt = $pdo->prepare("SELECT 
            ea.id,
            ea.title,
            ea.description,
            ea.priority,
            ea.alert_type,
            ea.created_at,
            e.first_name,
            e.surname
        FROM employee_alerts ea
        LEFT JOIN employees e ON ea.employee_id = e.id
        WHERE ea.status = 'active'
            AND (ea.priority IN ('Urgent', 'High') OR DATE(ea.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))
        ORDER BY 
            FIELD(ea.priority, 'Urgent', 'High', 'Medium', 'Low'),
            ea.created_at DESC
        LIMIT 5");
    $announcementsStmt->execute();
    $announcements = $announcementsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching announcements: " . $e->getMessage());
}
?>

<?php if (($_SESSION['user_role'] ?? '') === 'hr_admin'): ?>
<div class="container-fluid events-page">
    <div class="row g-4">
        <!-- Left Column: Calendar -->
        <div class="col-xl-7">
            <div class="card events-card">
                <div class="events-card__header">
                    <h5 class="events-card__title">Calendar</h5>
                    <button type="button" class="events-card__expand" title="Expand Calendar">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <div class="events-card__body">
                    <!-- Calendar Grid -->
                    <div class="events-calendar">
                        <!-- Calendar Header with Navigation -->
                        <div class="events-calendar__header">
                            <button type="button" class="events-calendar__nav events-calendar__nav--prev" title="Previous Month">
                                <i class="fas fa-angle-left"></i>
                            </button>
                            <div class="events-calendar__month-year">
                                <span class="events-calendar__month"><?php echo date('F'); ?></span>
                                <span class="events-calendar__year"><?php echo date('Y'); ?></span>
                            </div>
                            <button type="button" class="events-calendar__nav events-calendar__nav--next" title="Next Month">
                                <i class="fas fa-angle-right"></i>
                            </button>
                        </div>
                        
                        <!-- Days of Week Header -->
                        <div class="events-calendar__weekdays">
                            <div class="events-calendar__weekday">Sun</div>
                            <div class="events-calendar__weekday">Mon</div>
                            <div class="events-calendar__weekday">Tue</div>
                            <div class="events-calendar__weekday">Wed</div>
                            <div class="events-calendar__weekday">Thu</div>
                            <div class="events-calendar__weekday">Fri</div>
                            <div class="events-calendar__weekday">Sat</div>
                        </div>
                        
                        <!-- Calendar Grid -->
                        <div class="events-calendar__grid" data-current-month="<?php echo date('Y-m'); ?>">
                            <?php
                            $today = new DateTime();
                            $currentMonth = (int)$today->format('m');
                            $currentYear = (int)$today->format('Y');
                            $currentDay = (int)$today->format('j');
                            
                            // Get first day of month and number of days
                            $firstDay = new DateTime("$currentYear-$currentMonth-01");
                            $firstDayOfWeek = (int)$firstDay->format('w'); // 0 (Sunday) to 6 (Saturday)
                            $daysInMonth = (int)$firstDay->format('t');
                            
                            // Show previous month's trailing days
                            $prevMonth = clone $firstDay;
                            $prevMonth->modify('-1 month');
                            $daysInPrevMonth = (int)$prevMonth->format('t');
                            
                            // Start from Sunday (0), show days before the first day of month
                            if ($firstDayOfWeek > 0) {
                                for ($i = $firstDayOfWeek - 1; $i >= 0; $i--) {
                                    $day = $daysInPrevMonth - $i;
                                    $date = clone $prevMonth;
                                    $date->setDate($currentYear, $currentMonth - 1, $day);
                                    $dateStr = $date->format('Y-m-d');
                                    echo '<div class="events-calendar__day events-calendar__day--other" data-date="' . $dateStr . '">';
                                    echo '<span class="events-calendar__day-num">' . $day . '</span>';
                                    echo '<span class="events-calendar__day-indicator" data-date="' . $dateStr . '"></span>';
                                    echo '</div>';
                                }
                            }
                            
                            // Show current month days
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                                $isToday = ($day == $currentDay);
                                $classes = 'events-calendar__day';
                                if ($isToday) {
                                    $classes .= ' events-calendar__day--today active';
                                }
                                echo '<div class="' . $classes . '" data-date="' . $dateStr . '">';
                                echo '<span class="events-calendar__day-num">' . $day . '</span>';
                                echo '<span class="events-calendar__day-indicator" data-date="' . $dateStr . '"></span>';
                                echo '</div>';
                            }
                            
                            // Show next month's leading days to fill the grid
                            // Calculate total cells used (previous month days + current month days)
                            $prevDaysCount = $firstDayOfWeek; // Number of previous month days shown (0-6)
                            $totalCells = $prevDaysCount + $daysInMonth;
                            $remainingCells = 42 - $totalCells; // 6 weeks * 7 days = 42 cells
                            if ($remainingCells < 0) $remainingCells = 0;
                            if ($remainingCells > 0) {
                                $nextMonth = clone $firstDay;
                                $nextMonth->modify('+1 month');
                                for ($day = 1; $day <= $remainingCells; $day++) {
                                    $date = clone $nextMonth;
                                    $date->setDate($currentYear, $currentMonth + 1, $day);
                                    $dateStr = $date->format('Y-m-d');
                                    echo '<div class="events-calendar__day events-calendar__day--other" data-date="' . $dateStr . '">';
                                    echo '<span class="events-calendar__day-num">' . $day . '</span>';
                                    echo '<span class="events-calendar__day-indicator" data-date="' . $dateStr . '"></span>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Announcements -->
        <div class="col-xl-5">
            <div class="card events-card">
                <div class="events-card__header">
                    <h5 class="events-card__title">Announcements</h5>
                </div>
                <div class="events-card__body">
                    <div class="events-announcements">
                        <?php if (empty($announcements)): ?>
                            <div class="events-empty">
                                <i class="fas fa-bullhorn"></i>
                                <div>No announcements</div>
                                <small class="text-muted">Check back later for updates.</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): 
                                $priority = strtolower($announcement['priority'] ?? 'medium');
                                $alertType = $announcement['alert_type'] ?? 'other';
                                
                                // Determine color based on priority and type
                                $colorClass = 'announcement--default';
                                if ($priority === 'urgent') {
                                    $colorClass = 'announcement--urgent';
                                } elseif ($priority === 'high') {
                                    $colorClass = 'announcement--high';
                                } elseif ($alertType === 'license_expiry' || $alertType === 'document_expiry') {
                                    $colorClass = 'announcement--warning';
                                } elseif ($alertType === 'training_due') {
                                    $colorClass = 'announcement--info';
                                }
                                
                                $employeeName = trim(($announcement['first_name'] ?? '') . ' ' . ($announcement['surname'] ?? ''));
                                $createdDate = date('M d, Y', strtotime($announcement['created_at']));
                            ?>
                                <div class="events-announcement <?php echo $colorClass; ?>">
                                    <div class="announcement__bar"></div>
                                    <div class="announcement__content">
                                        <div class="announcement__header">
                                            <span class="announcement__tag"><?php echo strtoupper($alertType); ?></span>
                                            <span class="announcement__date"><?php echo $createdDate; ?></span>
                                        </div>
                                        <h6 class="announcement__title"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                        <?php if (!empty($announcement['description'])): ?>
                                            <p class="announcement__description"><?php echo htmlspecialchars($announcement['description']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($employeeName)): ?>
                                            <div class="announcement__meta">
                                                <i class="fas fa-user"></i>
                                                <span><?php echo htmlspecialchars($employeeName); ?></span>
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

    <!-- Upcoming Events Section -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card events-card">
                <div class="events-card__header">
                    <h5 class="events-card__title">Upcoming Events</h5>
                </div>
                <div class="events-card__body">
                    <div class="events-upcoming">
                        <?php if (empty($upcoming_events)): ?>
                            <div class="events-empty">
                                <i class="fas fa-calendar-check"></i>
                                <div>No upcoming events</div>
                                <small class="text-muted">All events are up to date.</small>
                            </div>
                        <?php else: ?>
                            <div class="events-upcoming__list">
                                <?php foreach ($upcoming_events as $event): 
                                    $eventDate = new DateTime($event['start_date']);
                                    $isToday = $event['start_date'] === date('Y-m-d');
                                    $isPast = $eventDate < new DateTime();
                                    
                                    // Format time
                                    $timeDisplay = '';
                                    if (!empty($event['start_time']) && $event['start_time'] !== '00:00:00') {
                                        $timeParts = explode(':', $event['start_time']);
                                        $hour = (int)$timeParts[0];
                                        $minute = (int)$timeParts[1];
                                        $ampm = $hour >= 12 ? 'P.M' : 'A.M';
                                        $hour = $hour % 12;
                                        $hour = $hour ? $hour : 12;
                                        $timeDisplay = sprintf('%d:%02d %s', $hour, $minute, $ampm);
                                    }
                                    
                                    $priority = strtolower($event['priority'] ?? 'low');
                                    $priorityClass = 'event--low';
                                    $priorityDot = 'event-dot--default';
                                    if ($priority === 'urgent') {
                                        $priorityClass = 'event--urgent';
                                        $priorityDot = 'event-dot--red';
                                    } elseif ($priority === 'high') {
                                        $priorityClass = 'event--high';
                                        $priorityDot = 'event-dot--gold';
                                    }
                                    
                                    $dateDisplay = $isToday ? 'Today' : $eventDate->format('F j, Y');
                                    $eventType = $event['event_type'] ?? 'Other';
                                ?>
                                    <div class="events-upcoming__item <?php echo $priorityClass; ?>">
                                        <div class="upcoming-event__time">
                                            <?php if (!empty($timeDisplay)): ?>
                                                <span class="event-dot <?php echo $priorityDot; ?>"></span>
                                                <?php echo $timeDisplay; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="upcoming-event__content">
                                            <div class="upcoming-event__header">
                                                <h6 class="upcoming-event__title"><?php echo htmlspecialchars($event['title']); ?></h6>
                                                <span class="upcoming-event__tag"><?php echo htmlspecialchars($eventType); ?></span>
                                            </div>
                                            <?php if (!empty($event['description'])): ?>
                                                <p class="upcoming-event__description"><?php echo htmlspecialchars($event['description']); ?></p>
                                            <?php endif; ?>
                                            <div class="upcoming-event__meta">
                                                <span class="upcoming-event__date"><?php echo $dateDisplay; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Events Page Styles */
.events-page {
    padding: 2rem 0;
    background: #fafbfc;
    min-height: 100vh;
}

.events-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #e8ecf1;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.events-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e8ecf1;
    background: #f8fafc;
}

.events-card__title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #334155;
    margin: 0;
}

.events-card__expand {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.375rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.events-card__expand:hover {
    background: #e8ecf1;
    color: #334155;
}

.events-card__body {
    flex: 1;
    padding: 1.25rem;
    overflow-y: auto;
}

/* Calendar Styles */
.events-calendar {
    width: 100%;
}

.events-calendar__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 0;
    margin-bottom: 0.75rem;
    border-bottom: 1px solid #e8ecf1;
}

.events-calendar__month-year {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    font-weight: 600;
    color: #334155;
}

.events-calendar__month {
    font-size: 1rem;
}

.events-calendar__year {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

.events-calendar__nav {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: 1px solid #e8ecf1;
    border-radius: 6px;
    background: #ffffff;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.events-calendar__nav:hover {
    background: #f1f4f8;
    border-color: #cbd5e1;
    color: #334155;
}

.events-calendar__weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #ffffff;
    border-bottom: 1px solid #e8ecf1;
    margin-bottom: 0.5rem;
}

.events-calendar__weekday {
    padding: 0.625rem 0.5rem;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.events-calendar__grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #ffffff;
    gap: 1px;
    background: #e8ecf1;
}

.events-calendar__day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    min-height: 45px;
}

.events-calendar__day:hover {
    background: #f8fafc;
}

.events-calendar__day--other {
    color: #cbd5e1;
    background: #fafbfc;
}

.events-calendar__day--today {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: #ffffff;
    font-weight: 700;
}

.events-calendar__day.active {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.15);
}

.events-calendar__day-num {
    font-size: 0.875rem;
    font-weight: 500;
    line-height: 1.2;
}

.events-calendar__day--today .events-calendar__day-num,
.events-calendar__day.active .events-calendar__day-num {
    font-weight: 700;
    color: #ffffff;
}

.events-calendar__day--other .events-calendar__day-num {
    color: #cbd5e1;
    font-weight: 400;
}

.events-calendar__day-indicator {
    position: absolute;
    bottom: 0.25rem;
    left: 50%;
    transform: translateX(-50%);
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: none !important;
    transition: all 0.2s ease;
    z-index: 1;
}

.events-calendar__day-indicator.has-events {
    display: block !important;
}

.events-calendar__day-indicator.indicator-red {
    background: #dc2626 !important;
    box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.2);
    width: 8px !important;
    height: 8px !important;
}

.events-calendar__day-indicator.indicator-gold {
    background: #f59e0b !important;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
    width: 7px !important;
    height: 7px !important;
}

.events-calendar__day-indicator.indicator-default {
    background: #64748b !important;
    box-shadow: 0 0 0 2px rgba(100, 116, 139, 0.2);
    width: 6px !important;
    height: 6px !important;
}

.events-calendar__day--today .events-calendar__day-indicator.indicator-red,
.events-calendar__day.active .events-calendar__day-indicator.indicator-red {
    background: #fca5a5 !important;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
}

.events-calendar__day--today .events-calendar__day-indicator.indicator-gold,
.events-calendar__day.active .events-calendar__day-indicator.indicator-gold {
    background: #fcd34d !important;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
}

.events-calendar__day--today .events-calendar__day-indicator.indicator-default,
.events-calendar__day.active .events-calendar__day-indicator.indicator-default {
    background: #cbd5e1 !important;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
}

/* Announcements Styles */
.events-announcements {
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}

.events-announcement {
    display: flex;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 10px;
    background: #ffffff;
    border: 1px solid #e8ecf1;
    transition: all 0.2s ease;
    position: relative;
}

.events-announcement:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transform: translateY(-1px);
}

.announcement__bar {
    width: 4px;
    border-radius: 2px;
    flex-shrink: 0;
}

.announcement--urgent .announcement__bar {
    background: #dc2626;
}

.announcement--high .announcement__bar {
    background: #f59e0b;
}

.announcement--warning .announcement__bar {
    background: #eab308;
}

.announcement--info .announcement__bar {
    background: #3b82f6;
}

.announcement--default .announcement__bar {
    background: #64748b;
}

.announcement__content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.announcement__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.announcement__tag {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    background: #f1f4f8;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.announcement__date {
    font-size: 0.75rem;
    color: #64748b;
}

.announcement__title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #334155;
    margin: 0;
    line-height: 1.4;
}

.announcement__description {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}

.announcement__meta {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: #64748b;
}

.announcement__meta i {
    font-size: 0.75rem;
}

/* Upcoming Events Styles */
.events-upcoming__list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.events-upcoming__item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-radius: 10px;
    background: #ffffff;
    border: 1px solid #e8ecf1;
    transition: all 0.2s ease;
}

.events-upcoming__item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transform: translateY(-1px);
}

.upcoming-event__time {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
    min-width: 100px;
    flex-shrink: 0;
}

.event-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.event-dot--red {
    background: #dc2626;
}

.event-dot--gold {
    background: #f59e0b;
}

.event-dot--default {
    background: #64748b;
}

.upcoming-event__content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.upcoming-event__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.upcoming-event__title {
    font-size: 1rem;
    font-weight: 600;
    color: #334155;
    margin: 0;
    line-height: 1.4;
    flex: 1;
}

.upcoming-event__tag {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.625rem;
    border-radius: 6px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: #ffffff;
    white-space: nowrap;
    flex-shrink: 0;
}

.upcoming-event__description {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}

.upcoming-event__meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.upcoming-event__date {
    font-size: 0.8125rem;
    color: #64748b;
    font-weight: 500;
}

.events-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    text-align: center;
    color: #64748b;
}

.events-empty i {
    font-size: 2.5rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.events-empty div {
    font-size: 1rem;
    font-weight: 500;
    color: #475569;
    margin-bottom: 0.5rem;
}

.events-empty small {
    font-size: 0.875rem;
    color: #94a3b8;
}

/* Responsive */
@media (max-width: 1200px) {
    .events-page .row > [class*="col-"] {
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 768px) {
    .events-calendar__day {
        min-height: 40px;
        padding: 0.375rem;
    }
    
    .events-calendar__day-num {
        font-size: 0.75rem;
    }
    
    .events-calendar__weekday {
        padding: 0.5rem 0.25rem;
        font-size: 0.6875rem;
    }
}
</style>

<script>
// Events page functionality
(function() {
    function initEventsPage() {
        const calendarGrid = document.querySelector('.events-calendar__grid');
        const prevMonthBtn = document.querySelector('.events-calendar__nav--prev');
        const nextMonthBtn = document.querySelector('.events-calendar__nav--next');
        const monthEl = document.querySelector('.events-calendar__month');
        const yearEl = document.querySelector('.events-calendar__year');
        
        if (!calendarGrid) return;
        
        let currentDate = new Date();
        let currentMonth = currentDate.getMonth();
        let currentYear = currentDate.getFullYear();
        let selectedDate = new Date(currentDate);
        
        // Function to render calendar
        function renderCalendar(year, month, selectedDay = null) {
            const firstDay = new Date(year, month, 1);
            const firstDayOfWeek = firstDay.getDay(); // 0 (Sunday) to 6 (Saturday)
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            const prevMonth = month === 0 ? 11 : month - 1;
            const prevYear = month === 0 ? year - 1 : year;
            const daysInPrevMonth = new Date(prevYear, prevMonth + 1, 0).getDate();
            
            const nextMonth = month === 11 ? 0 : month + 1;
            const nextYear = month === 11 ? year + 1 : year;
            
            let html = '';
            const datesToCheck = [];
            
            // Previous month trailing days (starting from Sunday)
            if (firstDayOfWeek > 0) {
                for (let i = firstDayOfWeek - 1; i >= 0; i--) {
                    const day = daysInPrevMonth - i;
                    const date = new Date(prevYear, prevMonth, day);
                    const dateStr = date.toISOString().split('T')[0];
                    datesToCheck.push(dateStr);
                    html += `<div class="events-calendar__day events-calendar__day--other" data-date="${dateStr}">
                        <span class="events-calendar__day-num">${day}</span>
                        <span class="events-calendar__day-indicator" data-date="${dateStr}"></span>
                    </div>`;
                }
            }
            
            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const dateStr = date.toISOString().split('T')[0];
                datesToCheck.push(dateStr);
                const isToday = date.toDateString() === new Date().toDateString();
                const isSelected = selectedDay && date.toDateString() === selectedDay.toDateString();
                const classes = `events-calendar__day ${isToday ? 'events-calendar__day--today' : ''} ${isSelected ? 'active' : ''}`;
                
                html += `<div class="${classes}" data-date="${dateStr}">
                    <span class="events-calendar__day-num">${day}</span>
                    <span class="events-calendar__day-indicator" data-date="${dateStr}"></span>
                </div>`;
            }
            
            // Next month leading days
            const totalCells = firstDayOfWeek + daysInMonth;
            const remainingCells = 42 - totalCells;
            for (let day = 1; day <= remainingCells; day++) {
                const date = new Date(nextYear, nextMonth, day);
                const dateStr = date.toISOString().split('T')[0];
                datesToCheck.push(dateStr);
                html += `<div class="events-calendar__day events-calendar__day--other" data-date="${dateStr}">
                    <span class="events-calendar__day-num">${day}</span>
                    <span class="events-calendar__day-indicator" data-date="${dateStr}"></span>
                </div>`;
            }
            
            calendarGrid.innerHTML = html;
            calendarGrid.setAttribute('data-current-month', `${year}-${String(month + 1).padStart(2, '0')}`);
            
            if (monthEl) monthEl.textContent = firstDay.toLocaleDateString('en-US', { month: 'long' });
            if (yearEl) yearEl.textContent = year;
            
            // Add click handlers
            const dayElements = calendarGrid.querySelectorAll('.events-calendar__day');
            dayElements.forEach(dayEl => {
                dayEl.addEventListener('click', function() {
                    const dateStr = this.getAttribute('data-date');
                    if (dateStr) {
                        const date = new Date(dateStr + 'T00:00:00');
                        selectedDate = date;
                        renderCalendar(year, month, date);
                    }
                });
            });
            
            // Fetch event indicators
            fetchEventCounts(datesToCheck).then(counts => {
                dayElements.forEach(dayEl => {
                    const dateStr = dayEl.getAttribute('data-date');
                    const eventData = counts[dateStr] || { count: 0, color: 'default' };
                    const indicator = dayEl.querySelector('.events-calendar__day-indicator');
                    
                    if (indicator && eventData.count > 0) {
                        indicator.classList.add('has-events', `indicator-${eventData.color}`);
                        indicator.setAttribute('data-count', eventData.count);
                        
                        const dayNum = dayEl.querySelector('.events-calendar__day-num').textContent;
                        dayEl.setAttribute('title', `${dayNum} (${eventData.count} event${eventData.count !== 1 ? 's' : ''})`);
                    }
                });
            });
        }
        
        // Function to fetch event counts
        async function fetchEventCounts(dates) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_event_counts');
                dates.forEach(date => {
                    if (date) {
                        formData.append('dates[]', date);
                    }
                });
                
                const currentUrl = window.location.pathname + window.location.search;
                const response = await fetch(currentUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if (result.success && result.counts) {
                    return result.counts;
                }
            } catch (error) {
                console.error('Error fetching event counts:', error);
            }
            
            const emptyCounts = {};
            dates.forEach(date => {
                emptyCounts[date] = { count: 0, color: 'default' };
            });
            return emptyCounts;
        }
        
        // Month navigation
        if (prevMonthBtn) {
            prevMonthBtn.addEventListener('click', function(e) {
                e.preventDefault();
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar(currentYear, currentMonth, selectedDate);
            });
        }
        
        if (nextMonthBtn) {
            nextMonthBtn.addEventListener('click', function(e) {
                e.preventDefault();
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar(currentYear, currentMonth, selectedDate);
            });
        }
        
        // Initialize
        renderCalendar(currentYear, currentMonth, currentDate);
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEventsPage);
    } else {
        initEventsPage();
    }
})();
</script>

<?php endif; ?>
