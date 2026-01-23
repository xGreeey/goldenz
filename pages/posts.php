<?php
/**
 * Social Media Feed Page
 * Facebook-style feed for announcements and status updates
 */

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
        'content' => "I'm so glad to share with you guys some photos from my recent trip to the New-York. This city looks amazing, the buildings, nature, people all are beautiful, i highly recommend to visit this cool place! Also i would like to know what is your favorite place here or what you would like to visit? 👋",
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
        'content' => 'Magical city, always glad to come back 👋',
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
            <!-- Top Bar: Create Post -->
            <div class="card feed-create-post mb-4">
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
                               placeholder="What's New?"
                               id="feedPostInput">
                        <div class="feed-create-actions">
                            <button class="btn btn-link feed-action-btn" type="button" title="Emoji">
                                <i class="far fa-smile"></i>
                            </button>
                            <button class="btn btn-link feed-action-btn" type="button" title="Photo">
                                <i class="far fa-image"></i>
                            </button>
                            <button class="btn btn-link feed-action-btn" type="button" title="More">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                        </div>
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
                                            <span class="feed-post-location">• <?php echo htmlspecialchars($post['location']); ?></span>
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
                                    <?php echo $post['comments']; ?> Comments • <?php echo $post['shares']; ?> Reposts
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
                    <button class="btn btn-link feed-sidebar-action" type="button" title="Add event">
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

/* Create Post Bar */
.feed-create-post {
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
    border: 1px solid #e2e8f0;
    padding: 0.75rem 1.25rem;
    font-size: 0.9375rem;
}

.feed-whats-new:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
    
    // Create post input
    const feedPostInput = document.getElementById('feedPostInput');
    if (feedPostInput) {
        feedPostInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const content = this.value.trim();
                if (content) {
                    // TODO: Send AJAX request to create post
                    this.value = '';
                }
            }
        });
    }
});
</script>
