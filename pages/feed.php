<?php
/**
 * Social Media Feed Page
 * Facebook-style feed for announcements and status updates
 */

/**
 * Handle POST request for creating events
 * Follows php-pro skill guidelines: strict validation, error handling, type safety
 */
$event_created = false;
$event_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_event') {
    // Check if this is an AJAX request
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    try {
        // Validate and sanitize input data
        $title = trim($_POST['title'] ?? '');
        $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
        $start_date = trim($_POST['start_date'] ?? '');
        $start_time = !empty($_POST['start_time']) ? trim($_POST['start_time']) : null;
        $end_date = !empty($_POST['end_date']) ? trim($_POST['end_date']) : null;
        $end_time = !empty($_POST['end_time']) ? trim($_POST['end_time']) : null;
        $event_type = $_POST['event_type'] ?? 'Other';
        $holiday_type = $_POST['holiday_type'] ?? 'N/A';
        $category = !empty($_POST['category']) ? trim($_POST['category']) : null;
        $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
        
        // Validate required fields
        if (empty($title)) {
            throw new InvalidArgumentException('Event title is required.');
        }
        
        if (empty($start_date)) {
            throw new InvalidArgumentException('Start date is required.');
        }
        
        // Validate date format
        $date_format = 'Y-m-d';
        $start_date_obj = DateTime::createFromFormat($date_format, $start_date);
        if (!$start_date_obj || $start_date_obj->format($date_format) !== $start_date) {
            throw new InvalidArgumentException('Invalid start date format.');
        }
        
        // Validate end_date if provided
        if (!empty($end_date)) {
            $end_date_obj = DateTime::createFromFormat($date_format, $end_date);
            if (!$end_date_obj || $end_date_obj->format($date_format) !== $end_date) {
                throw new InvalidArgumentException('Invalid end date format.');
            }
            
            // Ensure end_date is not before start_date
            if ($end_date_obj < $start_date_obj) {
                throw new InvalidArgumentException('End date cannot be before start date.');
            }
        }
        
        // Validate event_type enum
        $valid_event_types = ['Holiday', 'Examination', 'Academic', 'Special Event', 'Other'];
        if (!in_array($event_type, $valid_event_types, true)) {
            $event_type = 'Other';
        }
        
        // Validate holiday_type enum
        $valid_holiday_types = ['Regular Holiday', 'Special Non-Working Holiday', 'Local Special Non-Working Holiday', 'N/A'];
        if (!in_array($holiday_type, $valid_holiday_types, true)) {
            $holiday_type = 'N/A';
        }
        
        // Prepare event data array
        $event_data = [
            'title' => $title,
            'description' => $description,
            'start_date' => $start_date,
            'start_time' => $start_time,
            'end_date' => $end_date,
            'end_time' => $end_time,
            'event_type' => $event_type,
            'holiday_type' => $holiday_type,
            'category' => $category,
            'notes' => $notes
        ];
        
        // Create event using database function
        $event_id = create_event($event_data);
        
        if ($event_id !== false && $event_id > 0) {
            $event_created = true;
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Event created successfully!',
                    'event_id' => (int)$event_id
                ], JSON_THROW_ON_ERROR);
                exit;
            }
        } else {
            $event_error = 'Failed to create event. Please try again.';
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $event_error
                ], JSON_THROW_ON_ERROR);
                exit;
            }
        }
    } catch (InvalidArgumentException $e) {
        $event_error = 'Validation error: ' . $e->getMessage();
        error_log('Event validation error: ' . $e->getMessage());
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $event_error
            ], JSON_THROW_ON_ERROR);
            exit;
        }
    } catch (Exception $e) {
        $event_error = 'Error: ' . $e->getMessage();
        error_log('Error creating event: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.'
            ], JSON_THROW_ON_ERROR);
            exit;
        }
    }
}

// Get current user info
$current_user_id = $_SESSION['user_id'] ?? null;
$current_user_name = $_SESSION['name'] ?? 'User';
$current_user = get_user_by_id($current_user_id);
$current_user_avatar = !empty($current_user['avatar']) ? get_avatar_url($current_user['avatar']) : null;

// Get user initials for avatar placeholder
$initials = 'U';
if ($current_user_name) {
    $parts = preg_split('/\s+/', trim($current_user_name));
    $first = $parts[0][0] ?? 'U';
    $last = (count($parts) > 1) ? ($parts[count($parts) - 1][0] ?? 'A') : ($parts[0][1] ?? 'S');
    $initials = strtoupper($first . $last);
}

// Get feed posts (for now, we'll use mock data until table is created)
// TODO: Replace with actual database query once feed_posts table exists
$feed_posts = [
    [
        'id' => 1,
        'user_id' => 1,
        'user_name' => 'Ray Hammond',
        'user_avatar' => null,
        'content' => "I'm so glad to share with you guys some photos from my recent trip to the New-York. This city looks amazing, the buildings, nature, people all are beautiful, i highly recommend to visit this cool place! Also i would like to know what is your favorite place here or what you would like to visit? ≡ƒæï",
        'location' => 'New York, United States',
        'time_ago' => '2d',
        'images' => [
            'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=800',
            'https://images.unsplash.com/photo-1500916434205-0c77489c6cf7?w=800'
        ],
        'likes' => 925,
        'comments' => 23,
        'shares' => 4,
        'is_liked' => false
    ],
    [
        'id' => 2,
        'user_id' => 2,
        'user_name' => 'Todd Torres',
        'user_avatar' => null,
        'content' => 'Magical city, always glad to come back ≡ƒæï',
        'location' => 'San Francisco, United States',
        'time_ago' => '5d',
        'images' => [
            'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=800',
            'https://images.unsplash.com/photo-1547036967-23d11aacaee0?w=800',
            'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=800'
        ],
        'image_count' => 8, // Total images, showing "+5" overlay
        'likes' => 342,
        'comments' => 12,
        'shares' => 2,
        'is_liked' => true
    ]
];

// Get upcoming events (from events table)
$upcoming_events = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT title, start_date, start_time, event_type 
                         FROM events 
                         WHERE start_date >= CURDATE() 
                         ORDER BY start_date ASC, start_time ASC 
                         LIMIT 5");
    $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error fetching events: ' . $e->getMessage());
}

// Get birthdays (from employees table)
$birthdays = [];
try {
    $pdo = get_db_connection();
    $current_month = date('m');
    $stmt = $pdo->prepare("SELECT first_name, surname, birth_date 
                           FROM employees 
                           WHERE MONTH(birth_date) = ? 
                           AND DAY(birth_date) >= DAY(CURDATE())
                           ORDER BY DAY(birth_date) ASC 
                           LIMIT 5");
    $stmt->execute([$current_month]);
    $birthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error fetching birthdays: ' . $e->getMessage());
}

// Format event dates
function formatEventDate($date, $time) {
    $timestamp = strtotime($date . ' ' . $time);
    $day = date('D', $timestamp);
    $month = date('M', $timestamp);
    $dayNum = date('j', $timestamp);
    $timeStr = date('g:i A', $timestamp);
    return "$day, $month $dayNum at $timeStr";
}

// Format birthday date
function formatBirthdayDate($date) {
    $timestamp = strtotime($date);
    return date('F j', $timestamp);
}
?>

<div class="container-fluid hrdash feed-page">
    <div class="row g-4">
        <!-- Main Feed Column -->
        <div class="col-lg-8">
            <!-- Expandable Event Form -->
            <div class="feed-expandable-form-container mb-4" id="feedExpandableForm">
                <!-- Backdrop Overlay -->
                <div class="feed-form-backdrop" id="feedFormBackdrop" style="display: none;"></div>
                
                <!-- Collapsed State: Input Field -->
                <div class="feed-form-collapsed" id="feedFormCollapsed">
                    <div class="card feed-create-post">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="feed-avatar">
                                    <?php if ($current_user_avatar): ?>
                                        <img src="<?php echo htmlspecialchars($current_user_avatar); ?>" 
                                             alt="<?php echo htmlspecialchars($current_user_name); ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="feed-avatar-placeholder" style="display: none;">
                                            <?php echo htmlspecialchars($initials); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="feed-avatar-placeholder">
                                            <?php echo htmlspecialchars($initials); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="text" 
                                       class="form-control feed-whats-new" 
                                       placeholder="What's New? Share an event or announcement..."
                                       id="feedPostInput"
                                       readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expanded State: Full Form -->
                <div class="feed-form-expanded" id="feedFormExpanded" style="display: none;">
                    <div class="card feed-expanded-form-card">
                        <div class="feed-expanded-form-header">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="feed-expanded-form-title">
                                    <i class="fas fa-calendar-plus me-2"></i>Create New Event
                                </h5>
                                <button type="button" class="btn feed-close-expanded-form" id="closeExpandedForm" aria-label="Close">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <form id="createEventForm" method="POST">
                            <input type="hidden" name="action" value="create_event">
                            <div class="feed-expanded-form-body">
                                <div id="eventFormErrors" class="alert alert-danger d-none" role="alert"></div>
                                
                                <!-- Title -->
                                <div class="event-form-group">
                                    <label for="eventTitle" class="event-form-label">
                                        <i class="fas fa-heading me-2"></i>Event Title <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control event-form-input" 
                                           id="eventTitle" 
                                           name="title" 
                                           required 
                                           placeholder="Enter event title">
                                </div>

                                <!-- Description -->
                                <div class="event-form-group">
                                    <label for="eventDescription" class="event-form-label">
                                        <i class="fas fa-align-left me-2"></i>Description
                                    </label>
                                    <textarea class="form-control event-form-input event-form-textarea" 
                                              id="eventDescription" 
                                              name="description" 
                                              rows="2" 
                                              placeholder="Enter event description (optional)"></textarea>
                                </div>

                                <!-- Event Type and Category Row -->
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="event-form-group">
                                            <label for="eventType" class="event-form-label">
                                                <i class="fas fa-tag me-2"></i>Event Type
                                            </label>
                                            <select class="form-select event-form-input" id="eventType" name="event_type">
                                                <option value="Other">Other</option>
                                                <option value="Holiday">Holiday</option>
                                                <option value="Examination">Examination</option>
                                                <option value="Academic">Academic</option>
                                                <option value="Special Event">Special Event</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="event-form-group">
                                            <label for="eventCategory" class="event-form-label">
                                                <i class="fas fa-folder me-2"></i>Category
                                            </label>
                                            <input type="text" 
                                                   class="form-control event-form-input" 
                                                   id="eventCategory" 
                                                   name="category" 
                                                   placeholder="e.g., Semester Start, Conference">
                                        </div>
                                    </div>
                                </div>

                                <!-- Holiday Type (shown when Event Type is Holiday) -->
                                <div class="event-form-group" id="holidayTypeGroup" style="display: none;">
                                    <label for="holidayType" class="event-form-label">
                                        <i class="fas fa-calendar-check me-2"></i>Holiday Type
                                    </label>
                                    <select class="form-select event-form-input" id="holidayType" name="holiday_type">
                                        <option value="N/A">N/A</option>
                                        <option value="Regular Holiday">Regular Holiday</option>
                                        <option value="Special Non-Working Holiday">Special Non-Working Holiday</option>
                                        <option value="Local Special Non-Working Holiday">Local Special Non-Working Holiday</option>
                                    </select>
                                </div>

                                <!-- Start Date and Time Row -->
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="event-form-group">
                                            <label for="eventStartDate" class="event-form-label">
                                                <i class="fas fa-calendar-day me-2"></i>Start Date <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" 
                                                   class="form-control event-form-input" 
                                                   id="eventStartDate" 
                                                   name="start_date" 
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="event-form-group">
                                            <label for="eventStartTime" class="event-form-label">
                                                <i class="fas fa-clock me-2"></i>Start Time
                                            </label>
                                            <input type="time" 
                                                   class="form-control event-form-input" 
                                                   id="eventStartTime" 
                                                   name="start_time">
                                        </div>
                                    </div>
                                </div>

                                <!-- End Date and Time Row -->
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="event-form-group">
                                            <label for="eventEndDate" class="event-form-label">
                                                <i class="fas fa-calendar-times me-2"></i>End Date
                                            </label>
                                            <input type="date" 
                                                   class="form-control event-form-input" 
                                                   id="eventEndDate" 
                                                   name="end_date">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="event-form-group">
                                            <label for="eventEndTime" class="event-form-label">
                                                <i class="fas fa-clock me-2"></i>End Time
                                            </label>
                                            <input type="time" 
                                                   class="form-control event-form-input" 
                                                   id="eventEndTime" 
                                                   name="end_time">
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="event-form-group">
                                    <label for="eventNotes" class="event-form-label">
                                        <i class="fas fa-sticky-note me-2"></i>Additional Notes
                                    </label>
                                    <textarea class="form-control event-form-input event-form-textarea" 
                                              id="eventNotes" 
                                              name="notes" 
                                              rows="2" 
                                              placeholder="Any additional information about the event"></textarea>
                                </div>
                            </div>
                            <div class="feed-expanded-form-footer">
                                <button type="button" class="btn event-btn-secondary" id="cancelExpandedForm">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                                <button type="submit" class="btn event-btn-primary" id="submitEventBtn">
                                    <i class="fas fa-check me-2"></i>Create Event
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Feed Posts -->
            <div id="feedPostsContainer">
                <?php foreach ($feed_posts as $post): ?>
                    <div class="card feed-post mb-4" data-post-id="<?php echo $post['id']; ?>">
                        <!-- Post Header -->
                        <div class="card-header feed-post-header">
                            <div class="d-flex align-items-center">
                                <div class="feed-post-avatar">
                                    <?php 
                                    $post_user_initials = strtoupper(substr($post['user_name'], 0, 2));
                                    ?>
                                    <?php if (!empty($post['user_avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars($post['user_avatar']); ?>" 
                                             alt="<?php echo htmlspecialchars($post['user_name']); ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="feed-post-avatar-placeholder" style="display: none;">
                                            <?php echo htmlspecialchars($post_user_initials); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="feed-post-avatar-placeholder">
                                            <?php echo htmlspecialchars($post_user_initials); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="feed-post-user-info">
                                    <div class="feed-post-user-name"><?php echo htmlspecialchars($post['user_name']); ?></div>
                                    <div class="feed-post-meta">
                                        <span class="feed-post-time"><?php echo htmlspecialchars($post['time_ago']); ?></span>
                                        <?php if (!empty($post['location'])): ?>
                                            <span class="feed-post-location">ΓÇó <?php echo htmlspecialchars($post['location']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="ms-auto">
                                    <button class="btn btn-link feed-post-menu-btn" type="button" title="More options">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Post Content -->
                        <div class="card-body">
                            <div class="feed-post-content">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            </div>

                            <!-- Post Images -->
                            <?php if (!empty($post['images'])): ?>
                                <div class="feed-post-images mt-3">
                                    <?php 
                                    $image_count = count($post['images']);
                                    $display_images = array_slice($post['images'], 0, 3);
                                    ?>
                                    <?php if ($image_count <= 2): ?>
                                        <!-- 2 images side by side -->
                                        <div class="row g-2">
                                            <?php foreach ($display_images as $img): ?>
                                                <div class="col-6">
                                                    <img src="<?php echo htmlspecialchars($img); ?>" 
                                                         alt="Post image" 
                                                         class="feed-post-image">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <!-- 3+ images grid -->
                                        <div class="row g-2">
                                            <?php foreach ($display_images as $index => $img): ?>
                                                <div class="<?php echo $index === 0 && $image_count > 3 ? 'col-6' : 'col-6'; ?>">
                                                    <div class="feed-post-image-wrapper position-relative">
                                                        <img src="<?php echo htmlspecialchars($img); ?>" 
                                                             alt="Post image" 
                                                             class="feed-post-image">
                                                        <?php if ($index === 2 && $image_count > 3): ?>
                                                            <div class="feed-post-image-overlay">
                                                                +<?php echo $image_count - 3; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Post Footer -->
                        <div class="card-footer feed-post-footer">
                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="feed-post-actions-left">
                                    <button class="btn btn-link feed-action-btn <?php echo $post['is_liked'] ? 'text-danger' : ''; ?>" 
                                            type="button" 
                                            data-action="like"
                                            data-post-id="<?php echo $post['id']; ?>">
                                        <i class="<?php echo $post['is_liked'] ? 'fas' : 'far'; ?> fa-heart"></i>
                                    </button>
                                    <button class="btn btn-link feed-action-btn" type="button" data-action="comment">
                                        <i class="far fa-comment"></i>
                                    </button>
                                    <button class="btn btn-link feed-action-btn" type="button" data-action="share">
                                        <i class="far fa-paper-plane"></i>
                                    </button>
                                </div>
                                <div class="feed-post-actions-right">
                                    <button class="btn btn-link feed-action-btn" type="button" data-action="repost">
                                        <i class="fas fa-retweet"></i>
                                    </button>
                                    <button class="btn btn-link feed-action-btn" type="button" data-action="save">
                                        <i class="far fa-bookmark"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Engagement Stats -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="feed-post-likes">
                                    <?php echo number_format($post['likes']); ?> likes
                                </div>
                                <div class="feed-post-engagement">
                                    <?php echo $post['comments']; ?> Comments ΓÇó <?php echo $post['shares']; ?> Reposts
                                </div>
                            </div>

                            <!-- Comment Input -->
                            <div class="feed-post-comment-input">
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control" 
                                           placeholder="Add a comment..."
                                           data-post-id="<?php echo $post['id']; ?>">
                                    <button class="btn btn-link feed-comment-emoji" type="button">
                                        <i class="far fa-smile"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <!-- Upcoming Events -->
            <div class="card feed-sidebar-card mb-4">
                <div class="card-header feed-sidebar-header">
                    <h6 class="mb-0">Upcoming Events</h6>
                    <button class="btn btn-link feed-sidebar-action" type="button" title="Add event" id="sidebarAddEventBtn">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($upcoming_events)): ?>
                        <div class="text-muted text-center py-3">
                            <small>No upcoming events</small>
                        </div>
                    <?php else: ?>
                        <div class="feed-events-list">
                            <?php foreach ($upcoming_events as $event): ?>
                                <div class="feed-event-item">
                                    <div class="feed-event-icon">
                                        <?php 
                                        $icon = 'fa-calendar';
                                        if ($event['event_type'] === 'Examination') $icon = 'fa-film';
                                        elseif ($event['event_type'] === 'Academic') $icon = 'fa-graduation-cap';
                                        elseif ($event['event_type'] === 'Special Event') $icon = 'fa-music';
                                        ?>
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="feed-event-info">
                                        <div class="feed-event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                        <div class="feed-event-date">
                                            <?php echo formatEventDate($event['start_date'], $event['start_time'] ?? '00:00:00'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Advertising -->
            <div class="card feed-sidebar-card mb-4">
                <div class="card-header feed-sidebar-header">
                    <h6 class="mb-0">Advertising</h6>
                    <button class="btn btn-link feed-sidebar-action" type="button" title="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="feed-ad-content">
                        <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400" 
                             alt="Nike Sneaker" 
                             class="feed-ad-image mb-3">
                        <div class="feed-ad-text">
                            <strong>Special offer: 20% off today</strong>
                            <div class="mt-2">
                                <a href="http://nike.com" target="_blank" class="text-primary">http://nike.com</a>
                            </div>
                            <div class="mt-2 text-muted small">
                                Comfort is king, but that doesn't mean you have to sacrifice style.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Birthdays -->
            <div class="card feed-sidebar-card">
                <div class="card-header feed-sidebar-header">
                    <h6 class="mb-0">Birthdays</h6>
                    <button class="btn btn-link feed-sidebar-action" type="button" title="More">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($birthdays)): ?>
                        <div class="text-muted text-center py-3">
                            <small>No birthdays this month</small>
                        </div>
                    <?php else: ?>
                        <div class="feed-birthdays-list">
                            <?php 
                            $current_birthday_date = null;
                            foreach ($birthdays as $birthday): 
                                $birthday_date = formatBirthdayDate($birthday['birth_date']);
                                if ($birthday_date !== $current_birthday_date):
                                    $current_birthday_date = $birthday_date;
                            ?>
                                <div class="feed-birthday-date"><?php echo htmlspecialchars($birthday_date); ?></div>
                            <?php endif; ?>
                                <div class="feed-birthday-item">
                                    <div class="feed-birthday-avatar">
                                        <?php 
                                        $birthday_initials = strtoupper(substr($birthday['first_name'], 0, 1) . substr($birthday['surname'], 0, 1));
                                        ?>
                                        <div class="feed-birthday-avatar-placeholder">
                                            <?php echo htmlspecialchars($birthday_initials); ?>
                                        </div>
                                    </div>
                                    <div class="feed-birthday-name">
                                        <?php echo htmlspecialchars($birthday['first_name'] . ' ' . $birthday['surname']); ?>
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


<style>
/* Feed Page Styles */
.feed-page {
    max-width: 1200px;
    margin: 0 auto;
}

/* Expandable Form Container */
.feed-expandable-form-container {
    position: relative;
    z-index: 10;
}

.feed-form-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.feed-form-collapsed {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.feed-form-expanded {
    position: fixed;
    top: 5vh;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1001;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    animation: zoomInExpand 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes zoomInExpand {
    0% {
        opacity: 0;
        transform: translateX(-50%) translateY(-20px) scale(0.9);
    }
    50% {
        transform: translateX(-50%) translateY(0) scale(1.01);
    }
    100% {
        opacity: 1;
        transform: translateX(-50%) translateY(0) scale(1);
    }
}

.feed-form-collapsed.hide {
    opacity: 0;
    transform: scale(0.95);
    pointer-events: none;
}

/* Create Post Bar */
.feed-create-post {
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.feed-avatar {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
}

.feed-avatar img,
.feed-avatar-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 50%;
}

.feed-avatar-placeholder {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.125rem;
}

.feed-whats-new {
    flex: 1;
    border-radius: 24px;
    border: 2px solid #e2e8f0;
    padding: 0.875rem 1.5rem;
    font-size: 0.9375rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
}

.feed-whats-new:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    transform: translateY(-1px);
    background: #ffffff;
}

.feed-whats-new:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    outline: none;
    background: #ffffff;
}

.feed-post-event-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.875rem 1.75rem;
    border-radius: 24px;
    font-size: 0.9375rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.feed-post-event-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    color: white;
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

.feed-post-event-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.feed-post-event-btn i {
    font-size: 1rem;
}

.feed-create-actions {
    display: flex;
    gap: 0.5rem;
}

.feed-action-btn {
    color: #64748b;
    padding: 0.5rem;
    border: none;
    background: none;
    font-size: 1.125rem;
}

.feed-action-btn:hover {
    color: #3b82f6;
}

/* Feed Post Card */
.feed-post {
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: none;
}

.feed-post-header {
    background: white;
    border-bottom: 1px solid #f1f5f9;
    padding: 1rem 1.25rem;
}

.feed-post-avatar {
    width: 48px;
    height: 48px;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.feed-post-avatar img,
.feed-post-avatar-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 50%;
}

.feed-post-avatar-placeholder {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.feed-post-user-name {
    font-weight: 600;
    color: #0f172a;
    font-size: 0.9375rem;
    margin-bottom: 0.125rem;
}

.feed-post-meta {
    font-size: 0.8125rem;
    color: #64748b;
}

.feed-post-time {
    font-weight: 500;
}

.feed-post-location {
    color: #94a3b8;
}

.feed-post-menu-btn {
    color: #64748b;
    padding: 0.25rem 0.5rem;
}

.feed-post-content {
    color: #0f172a;
    font-size: 0.9375rem;
    line-height: 1.6;
    padding: 0;
}

.feed-post-images {
    margin-top: 1rem;
}

.feed-post-image {
    width: 100%;
    height: auto;
    border-radius: 8px;
    object-fit: cover;
}

.feed-post-image-wrapper {
    position: relative;
}

.feed-post-image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.25rem;
    border-radius: 8px;
}

.feed-post-footer {
    background: white;
    border-top: 1px solid #f1f5f9;
    padding: 1rem 1.25rem;
}

.feed-post-actions-left,
.feed-post-actions-right {
    display: flex;
    gap: 0.5rem;
}

.feed-post-likes {
    font-weight: 600;
    color: #0f172a;
    font-size: 0.9375rem;
}

.feed-post-engagement {
    color: #64748b;
    font-size: 0.875rem;
}

.feed-post-comment-input .form-control {
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.feed-comment-emoji {
    color: #64748b;
    padding: 0.5rem;
}

/* Sidebar Cards */
.feed-sidebar-card {
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: none;
}

.feed-sidebar-header {
    background: white;
    border-bottom: 1px solid #f1f5f9;
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.feed-sidebar-header h6 {
    font-weight: 600;
    color: #0f172a;
    margin: 0;
}

.feed-sidebar-action {
    color: #64748b;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Events List */
.feed-events-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.feed-event-item {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.feed-event-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3b82f6;
    flex-shrink: 0;
}

.feed-event-title {
    font-weight: 600;
    color: #0f172a;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.feed-event-date {
    color: #64748b;
    font-size: 0.8125rem;
}

/* Advertising */
.feed-ad-image {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.feed-ad-text {
    font-size: 0.875rem;
    color: #0f172a;
}

/* Birthdays */
.feed-birthdays-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.feed-birthday-date {
    font-weight: 600;
    color: #0f172a;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.feed-birthday-date:first-child {
    margin-top: 0;
}

.feed-birthday-item {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.feed-birthday-avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.feed-birthday-name {
    font-weight: 500;
    color: #0f172a;
    font-size: 0.875rem;
}

/* Responsive */
@media (max-width: 991px) {
    .feed-page .col-lg-4 {
        margin-top: 2rem;
    }
    
    .feed-post-event-btn span {
        display: none;
    }
    
    .feed-post-event-btn {
        padding: 0.875rem 1.25rem;
    }
}

@media (max-width: 576px) {
    .feed-create-post .card-body {
        padding: 1rem;
    }
    
    .feed-whats-new {
        font-size: 0.875rem;
        padding: 0.75rem 1rem;
    }
    
    .feed-post-event-btn {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
    
    .feed-expanded-form-header {
        padding: 0.875rem 1.25rem;
    }
    
    .feed-expanded-form-title {
        font-size: 1.125rem;
    }
    
    .feed-expanded-form-body {
        padding: 1rem 1.25rem;
    }
    
    .feed-form-expanded {
        width: 95%;
        max-width: 95%;
        top: 2vh;
        max-height: 90vh;
    }
    
    .feed-expanded-form-footer {
        padding: 0.875rem 1.25rem;
        flex-direction: column-reverse;
    }
    
    .feed-expanded-form-footer .btn {
        width: 100%;
        padding: 0.5rem 1rem;
        font-size: 0.8125rem;
    }
    
    .event-form-group {
        margin-bottom: 0.875rem;
    }
}

/* Expanded Form Styles - Beautiful, modern design with distinctive aesthetics */
.feed-expanded-form-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(102, 126, 234, 0.1);
    overflow: hidden;
    background: #ffffff;
    position: relative;
    display: flex;
    flex-direction: column;
    max-height: 80vh;
}

.feed-expanded-form-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1rem 1.5rem;
    position: relative;
    overflow: hidden;
}

.feed-expanded-form-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.feed-expanded-form-title {
    font-weight: 700;
    font-size: 1.25rem;
    margin: 0;
    display: flex;
    align-items: center;
    position: relative;
    z-index: 1;
    letter-spacing: -0.5px;
    color: white;
}

.feed-close-expanded-form {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 1;
    font-size: 0.875rem;
    padding: 0;
}

.feed-close-expanded-form:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
    color: white;
}

.feed-expanded-form-body {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(to bottom, #ffffff 0%, #f8fafc 100%);
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

.feed-expanded-form-body::-webkit-scrollbar {
    width: 8px;
}

.feed-expanded-form-body::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.feed-expanded-form-body::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px;
}

.feed-expanded-form-body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

.feed-expanded-form-footer {
    border: none;
    padding: 1rem 1.5rem;
    background: white;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.event-form-group {
    margin-bottom: 1rem;
}

.event-form-label {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.75rem;
    margin-bottom: 0.375rem;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.event-form-input,
.event-form-textarea {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.5rem 0.875rem;
    font-size: 0.875rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    color: #1e293b;
}

.event-form-input:hover,
.event-form-textarea:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.08);
}

.event-form-input:focus,
.event-form-textarea:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.12), 0 4px 12px rgba(102, 126, 234, 0.15);
    outline: none;
    transform: translateY(-1px);
}

.event-form-textarea {
    resize: vertical;
    min-height: 60px;
}


.event-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.5rem 1.5rem;
    border-radius: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
    font-size: 0.875rem;
}

.event-btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.event-btn-primary:hover::before {
    left: 100%;
}

.event-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    color: white;
}

.event-btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.event-btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.event-btn-secondary {
    background: #f1f5f9;
    border: 2px solid #e2e8f0;
    color: #64748b;
    font-weight: 600;
    padding: 0.5rem 1.5rem;
    border-radius: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.875rem;
}

.event-btn-secondary:hover {
    background: #e2e8f0;
    border-color: #cbd5e1;
    color: #475569;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.event-btn-secondary:active {
    transform: translateY(0);
}

#eventFormErrors {
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

</style>

<script>
// Feed interactions (like, comment, share, etc.)
document.addEventListener('DOMContentLoaded', function() {
    // Like button
    document.querySelectorAll('[data-action="like"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const icon = this.querySelector('i');
            const isLiked = icon.classList.contains('fas');
            
            // Toggle like state
            if (isLiked) {
                icon.classList.remove('fas');
                icon.classList.add('far');
                this.classList.remove('text-danger');
            } else {
                icon.classList.remove('far');
                icon.classList.add('fas');
                this.classList.add('text-danger');
            }
            
            // TODO: Send AJAX request to update like in database
        });
    });
    
    // Comment input
    document.querySelectorAll('.feed-post-comment-input input').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const comment = this.value.trim();
                if (comment) {
                    const postId = this.dataset.postId;
                    // TODO: Send AJAX request to add comment
                    this.value = '';
                }
            }
        });
    });
    
    // Expandable form functionality
    const feedPostInput = document.getElementById('feedPostInput');
    const feedFormCollapsed = document.getElementById('feedFormCollapsed');
    const feedFormExpanded = document.getElementById('feedFormExpanded');
    const feedFormBackdrop = document.getElementById('feedFormBackdrop');
    const closeExpandedForm = document.getElementById('closeExpandedForm');
    const cancelExpandedForm = document.getElementById('cancelExpandedForm');
    
    // Function to expand form
    function expandForm() {
        if (feedFormCollapsed && feedFormExpanded && feedFormBackdrop) {
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
            
            // Show backdrop
            feedFormBackdrop.style.display = 'block';
            
            // Hide collapsed form with animation
            feedFormCollapsed.classList.add('hide');
            
            // Show expanded form with zoom animation
            setTimeout(() => {
                feedFormExpanded.style.display = 'block';
                
                // Focus on first input
                const firstInput = feedFormExpanded.querySelector('input[type="text"]');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 300);
                }
            }, 200);
        }
    }
    
    // Function to collapse form
    function collapseForm() {
        if (feedFormCollapsed && feedFormExpanded && feedFormBackdrop) {
            // Re-enable body scroll
            document.body.style.overflow = '';
            
            feedFormExpanded.style.display = 'none';
            feedFormBackdrop.style.display = 'none';
            feedFormCollapsed.classList.remove('hide');
            
            // Reset form
            const form = document.getElementById('createEventForm');
            if (form) {
                form.reset();
                const errorsDiv = document.getElementById('eventFormErrors');
                if (errorsDiv) {
                    errorsDiv.classList.add('d-none');
                    errorsDiv.innerHTML = '';
                }
                // Reset holiday type group
                const holidayTypeGroup = document.getElementById('holidayTypeGroup');
                if (holidayTypeGroup) {
                    holidayTypeGroup.style.display = 'none';
                    document.getElementById('holidayType').value = 'N/A';
                }
            }
        }
    }
    
    // Close on backdrop click (but not when clicking the form itself)
    if (feedFormBackdrop) {
        feedFormBackdrop.addEventListener('click', function(e) {
            if (e.target === feedFormBackdrop) {
                collapseForm();
            }
        });
    }
    
    // Prevent form clicks from closing
    if (feedFormExpanded) {
        feedFormExpanded.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Open form on input click
    if (feedPostInput) {
        feedPostInput.addEventListener('click', expandForm);
    }
    
    // Close form buttons
    if (closeExpandedForm) {
        closeExpandedForm.addEventListener('click', collapseForm);
    }
    
    if (cancelExpandedForm) {
        cancelExpandedForm.addEventListener('click', collapseForm);
    }
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && feedFormExpanded && feedFormExpanded.style.display !== 'none') {
            collapseForm();
        }
    });
    
    // Sidebar add event button
    const sidebarAddEventBtn = document.getElementById('sidebarAddEventBtn');
    if (sidebarAddEventBtn) {
        sidebarAddEventBtn.addEventListener('click', function() {
            expandForm();
        });
    }

    // Event Form Handling
    const eventForm = document.getElementById('createEventForm');
    const eventTypeSelect = document.getElementById('eventType');
    const holidayTypeGroup = document.getElementById('holidayTypeGroup');
    const submitBtn = document.getElementById('submitEventBtn');
    const errorsDiv = document.getElementById('eventFormErrors');

    // Show/hide holiday type based on event type
    if (eventTypeSelect && holidayTypeGroup) {
        eventTypeSelect.addEventListener('change', function() {
            if (this.value === 'Holiday') {
                holidayTypeGroup.style.display = 'block';
            } else {
                holidayTypeGroup.style.display = 'none';
                document.getElementById('holidayType').value = 'N/A';
            }
        });
    }

    // Handle form submission
    if (eventForm) {
        eventForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Hide previous errors
            errorsDiv.classList.add('d-none');
            errorsDiv.innerHTML = '';
            
            // Disable submit button
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
            
            try {
                const formData = new FormData(this);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Collapse form
                    collapseForm();
                    
                    // Show success notification
                    showNotification('Event created successfully!', 'success');
                    
                    // Reload page to refresh events list
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error
                    errorsDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (data.message || 'Failed to create event. Please try again.');
                    errorsDiv.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Error creating event:', error);
                errorsDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>An error occurred. Please try again.';
                errorsDiv.classList.remove('d-none');
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }

    // Notification function with beautiful styling
    function showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        const isSuccess = type === 'success';
        const bgColor = isSuccess 
            ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' 
            : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
        const icon = isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        notification.className = 'alert alert-dismissible fade show position-fixed feed-notification';
        notification.style.cssText = `
            top: 20px; 
            right: 20px; 
            z-index: 9999; 
            min-width: 320px; 
            max-width: 400px;
            background: ${bgColor};
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            animation: slideInRight 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        `;
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${icon} me-2" style="font-size: 1.25rem;"></i>
                <span style="flex: 1; font-weight: 500;">${message}</span>
                <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="alert" aria-label="Close" style="opacity: 0.8;"></button>
            </div>
        `;
        
        // Add animation keyframes if not already added
        if (!document.getElementById('feedNotificationStyles')) {
            const style = document.createElement('style');
            style.id = 'feedNotificationStyles';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                .feed-notification {
                    backdrop-filter: blur(10px);
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds with fade out
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideInRight 0.3s cubic-bezier(0.4, 0, 0.2, 1) reverse';
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
});
</script>
