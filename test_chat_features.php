<?php
/**
 * Chat System Feature Test Page
 * 
 * Visual test page to verify all chat features and icons are working correctly.
 * Access this page to test the chat system after installation.
 */

session_start();

// Mock session for testing (remove in production)
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['name'] = 'Test User';
    $_SESSION['user_role'] = 'super_admin';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat System - Feature Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
            padding: 40px 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .test-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 40px;
            text-align: center;
        }
        .test-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .test-section h2 {
            color: #0f172a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        .icon-test {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            background: #f1f5f9;
            border-radius: 12px;
            margin: 8px;
            font-size: 20px;
            color: #475569;
            transition: all 0.2s;
        }
        .icon-test:hover {
            background: #667eea;
            color: white;
            transform: scale(1.1);
        }
        .feature-item {
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .feature-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .status-badge {
            margin-left: auto;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-success {
            background: #dcfce7;
            color: #166534;
        }
        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .btn-test {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .checklist-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            border-left: 3px solid #e2e8f0;
            margin-bottom: 10px;
        }
        .checklist-item input[type="checkbox"] {
            margin-top: 4px;
            width: 20px;
            height: 20px;
        }
        .emoji-preview {
            font-size: 24px;
            margin: 4px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .emoji-preview:hover {
            transform: scale(1.3);
        }
    </style>
</head>
<body>

<div class="test-container">
    <!-- Header -->
    <div class="test-header">
        <h1><i class="fas fa-vial"></i> Chat System Feature Test</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">
            Verify all features, icons, and functionality are working correctly
        </p>
    </div>

    <!-- Icon Visibility Test -->
    <div class="test-section">
        <h2><i class="fas fa-icons"></i> Icon Visibility Test</h2>
        <p class="text-muted mb-4">All icons below should be visible and properly rendered:</p>
        
        <div class="mb-3">
            <strong>Chat Icons:</strong><br>
            <div class="icon-test" title="Comments"><i class="fas fa-comments"></i></div>
            <div class="icon-test" title="Paper Plane"><i class="fas fa-paper-plane"></i></div>
            <div class="icon-test" title="Smile"><i class="fas fa-smile"></i></div>
            <div class="icon-test" title="Paperclip"><i class="fas fa-paperclip"></i></div>
            <div class="icon-test" title="Trash"><i class="fas fa-trash-alt"></i></div>
            <div class="icon-test" title="Times"><i class="fas fa-times"></i></div>
            <div class="icon-test" title="Minus"><i class="fas fa-minus"></i></div>
            <div class="icon-test" title="Arrow Left"><i class="fas fa-arrow-left"></i></div>
            <div class="icon-test" title="Search"><i class="fas fa-search"></i></div>
            <div class="icon-test" title="Check"><i class="fas fa-check"></i></div>
        </div>

        <div class="mb-3">
            <strong>Additional Icons:</strong><br>
            <div class="icon-test" title="User"><i class="fas fa-user"></i></div>
            <div class="icon-test" title="Users"><i class="fas fa-users"></i></div>
            <div class="icon-test" title="Image"><i class="fas fa-image"></i></div>
            <div class="icon-test" title="Spinner"><i class="fas fa-spinner fa-spin"></i></div>
            <div class="icon-test" title="Exclamation"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="icon-test" title="Info"><i class="fas fa-info-circle"></i></div>
            <div class="icon-test" title="Circle"><i class="fas fa-circle"></i></div>
        </div>

        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle"></i>
            <strong>Note:</strong> If you see squares (□) instead of icons, Font Awesome is not loading properly.
        </div>
    </div>

    <!-- Feature Checklist -->
    <div class="test-section">
        <h2><i class="fas fa-tasks"></i> Feature Testing Checklist</h2>
        <p class="text-muted mb-4">Test each feature and check off when working:</p>

        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-comments"></i></div>
            <div style="flex: 1;">
                <strong>Chat Button Visible</strong>
                <div class="text-muted small">Purple gradient button at bottom-left with message icon</div>
            </div>
            <span class="status-badge status-success">Required</span>
        </div>

        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-users"></i></div>
            <div style="flex: 1;">
                <strong>Contact List</strong>
                <div class="text-muted small">Click button to see list of users sorted by activity</div>
            </div>
            <span class="status-badge status-success">Required</span>
        </div>

        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-paper-plane"></i></div>
            <div style="flex: 1;">
                <strong>Send Messages</strong>
                <div class="text-muted small">Type and send text messages with Enter key</div>
            </div>
            <span class="status-badge status-success">Required</span>
        </div>

        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-smile"></i></div>
            <div style="flex: 1;">
                <strong>Emoji Picker</strong>
                <div class="text-muted small">Click smile icon to open emoji selector</div>
            </div>
            <span class="status-badge status-success">Required</span>
        </div>

        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-paperclip"></i></div>
            <div style="flex: 1;">
                <strong>Photo Attachments</strong>
                <div class="text-muted small">Click paperclip to upload and send images</div>
            </div>
            <span class="status-badge status-success">Required</span>
        </div>

        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-image"></i></div>
            <div style="flex: 1;">
                <strong>Photo Preview</strong>
                <div class="text-muted small">Click images in chat to view full size</div>
            </div>
            <span class="status-badge status-success">Required</span>
        </div>

        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-trash-alt"></i></div>
            <div style="flex: 1;">
                <strong>Clear History</strong>
                <div class="text-muted small">Click trash icon in header with confirmation</div>
            </div>
            <span class="status-badge status-success">Required</span>
        </div>

        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-check"></i></div>
            <div style="flex: 1;">
                <strong>Read Receipts</strong>
                <div class="text-muted small">Double check marks when message is read</div>
            </div>
            <span class="status-badge status-warning">Optional</span>
        </div>

        <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-sync-alt"></i></div>
            <div style="flex: 1;">
                <strong>Real-time Updates</strong>
                <div class="text-muted small">New messages appear automatically</div>
            </div>
            <span class="status-badge status-success">Required</span>
        </div>
    </div>

    <!-- Emoji Preview -->
    <div class="test-section">
        <h2><i class="fas fa-smile"></i> Emoji Preview</h2>
        <p class="text-muted mb-3">Sample emojis that will be available in the picker:</p>
        <div>
            <?php
            $sampleEmojis = ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '👍', '👎', '👌', '✌️', '🤞', '🤟', '🤘', '🤙', '👏', '🙌', '👐', '🤲', '🙏', '✍️', '💪', '🦾', '❤️', '🧡', '💛', '💚', '💙', '💜'];
            foreach ($sampleEmojis as $emoji) {
                echo "<span class='emoji-preview' title='Click to copy'>{$emoji}</span>";
            }
            ?>
        </div>
    </div>

    <!-- Manual Test Steps -->
    <div class="test-section">
        <h2><i class="fas fa-clipboard-list"></i> Manual Testing Steps</h2>
        
        <h5 class="mt-4">1. Basic Messaging</h5>
        <div class="checklist-item">
            <input type="checkbox" id="test1">
            <label for="test1">Open chat by clicking the button at bottom-left</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test2">
            <label for="test2">See list of users (if no users, create test accounts)</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test3">
            <label for="test3">Click a user to open conversation</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test4">
            <label for="test4">Type a message and press Enter to send</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test5">
            <label for="test5">Message appears in chat with timestamp</label>
        </div>

        <h5 class="mt-4">2. Emoji Feature</h5>
        <div class="checklist-item">
            <input type="checkbox" id="test6">
            <label for="test6">Click emoji button (smile icon) above input</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test7">
            <label for="test7">Emoji picker appears with grid of emojis</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test8">
            <label for="test8">Click an emoji to insert it in input</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test9">
            <label for="test9">Send message with emoji</label>
        </div>

        <h5 class="mt-4">3. Photo Feature</h5>
        <div class="checklist-item">
            <input type="checkbox" id="test10">
            <label for="test10">Click attachment button (paperclip icon)</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test11">
            <label for="test11">Select an image file (JPG, PNG, GIF, WEBP)</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test12">
            <label for="test12">Preview appears showing selected image</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test13">
            <label for="test13">Add optional caption text</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test14">
            <label for="test14">Click Send - image uploads and appears in chat</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test15">
            <label for="test15">Click image in chat to view full size</label>
        </div>

        <h5 class="mt-4">4. Clear History</h5>
        <div class="checklist-item">
            <input type="checkbox" id="test16">
            <label for="test16">Click trash icon in conversation header</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test17">
            <label for="test17">Confirmation modal appears</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test18">
            <label for="test18">Click Confirm - all messages are cleared</label>
        </div>
        <div class="checklist-item">
            <input type="checkbox" id="test19">
            <label for="test19">Chat shows empty state message</label>
        </div>
    </div>

    <!-- Database Check -->
    <div class="test-section">
        <h2><i class="fas fa-database"></i> Database Status</h2>
        <?php
        try {
            require_once __DIR__ . '/bootstrap/app.php';
            require_once __DIR__ . '/includes/database.php';
            $pdo = get_db_connection();
            
            // Check tables
            $tables = ['chat_messages', 'chat_typing_status', 'chat_conversations'];
            echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Database connection successful!</div>";
            
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($stmt->rowCount() > 0) {
                    echo "<div class='feature-item'>";
                    echo "<div class='feature-icon'><i class='fas fa-check'></i></div>";
                    echo "<div style='flex: 1;'><strong>{$table}</strong><div class='text-muted small'>Table exists</div></div>";
                    echo "<span class='status-badge status-success'>OK</span>";
                    echo "</div>";
                    
                    // Count records
                    $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
                    echo "<div class='text-muted small ms-5 mb-2'>Records: {$count}</div>";
                } else {
                    echo "<div class='feature-item'>";
                    echo "<div class='feature-icon'><i class='fas fa-times'></i></div>";
                    echo "<div style='flex: 1;'><strong>{$table}</strong><div class='text-muted small'>Table missing</div></div>";
                    echo "<span class='status-badge' style='background: #fee2e2; color: #991b1b;'>MISSING</span>";
                    echo "</div>";
                }
            }
            
            // Check attachment columns
            $stmt = $pdo->query("SHOW COLUMNS FROM chat_messages LIKE 'attachment_%'");
            $attachmentCols = $stmt->rowCount();
            
            if ($attachmentCols >= 4) {
                echo "<div class='alert alert-success mt-3'><i class='fas fa-check-circle'></i> Attachment columns present ({$attachmentCols} columns)</div>";
            } else {
                echo "<div class='alert alert-warning mt-3'><i class='fas fa-exclamation-triangle'></i> Attachment columns missing. Run: php migrations/run_chat_attachments_migration.php</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>

    <!-- File Check -->
    <div class="test-section">
        <h2><i class="fas fa-folder"></i> File System Status</h2>
        <?php
        $files = [
            'API Backend' => __DIR__ . '/api/chat.php',
            'Chat Widget' => __DIR__ . '/includes/chat-widget.php',
            'JavaScript' => __DIR__ . '/assets/js/chat-widget.js',
            'Upload Directory' => __DIR__ . '/uploads/chat_attachments'
        ];
        
        foreach ($files as $label => $path) {
            $exists = file_exists($path);
            $icon = $exists ? 'check' : 'times';
            $status = $exists ? 'success' : 'danger';
            $statusText = $exists ? 'OK' : 'MISSING';
            
            echo "<div class='feature-item'>";
            echo "<div class='feature-icon'><i class='fas fa-{$icon}'></i></div>";
            echo "<div style='flex: 1;'>";
            echo "<strong>{$label}</strong>";
            echo "<div class='text-muted small'>" . basename($path) . "</div>";
            echo "</div>";
            echo "<span class='status-badge status-{$status}'>{$statusText}</span>";
            echo "</div>";
            
            if ($exists && is_dir($path)) {
                $writable = is_writable($path);
                $permIcon = $writable ? 'check' : 'exclamation-triangle';
                $permColor = $writable ? 'success' : 'warning';
                echo "<div class='text-muted small ms-5 mb-2'>";
                echo "<i class='fas fa-{$permIcon} text-{$permColor}'></i> ";
                echo $writable ? 'Writable' : 'Not writable (chmod 755 recommended)';
                echo "</div>";
            }
        }
        ?>
    </div>

    <!-- Action Buttons -->
    <div class="test-section text-center">
        <h2><i class="fas fa-rocket"></i> Ready to Test?</h2>
        <p class="text-muted mb-4">Open the chat widget and test all features!</p>
        <button class="btn btn-test" onclick="alert('Look for the chat button at the bottom-left corner of the screen!')">
            <i class="fas fa-comments"></i> Open Chat Widget
        </button>
        <a href="/" class="btn btn-test ms-2">
            <i class="fas fa-home"></i> Go to Dashboard
        </a>
    </div>

    <!-- Footer -->
    <div class="text-center text-muted mt-4">
        <p>For detailed documentation, see <strong>CHAT_SYSTEM_README.md</strong></p>
        <p>For quick start guide, see <strong>CHAT_QUICK_START.md</strong></p>
    </div>
</div>

<?php include __DIR__ . '/includes/chat-widget.php'; ?>
<script src="<?php echo '/assets/js/chat-widget.js'; ?>"></script>

<script>
// Auto-check items on click
document.querySelectorAll('.checklist-item input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        // Save to localStorage
        localStorage.setItem(this.id, this.checked);
        
        // Visual feedback
        this.closest('.checklist-item').style.opacity = this.checked ? '0.6' : '1';
    });
    
    // Restore from localStorage
    const saved = localStorage.getItem(checkbox.id);
    if (saved === 'true') {
        checkbox.checked = true;
        checkbox.closest('.checklist-item').style.opacity = '0.6';
    }
});

// Copy emoji on click
document.querySelectorAll('.emoji-preview').forEach(emoji => {
    emoji.addEventListener('click', function() {
        const text = this.textContent;
        navigator.clipboard.writeText(text).then(() => {
            this.style.transform = 'scale(1.5)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    });
});
</script>

</body>
</html>
