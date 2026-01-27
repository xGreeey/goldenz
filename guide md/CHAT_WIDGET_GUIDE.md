# Chat Widget - Floating Chat Interface

## Overview

The chat system has been refactored from a full-page sidebar view into a **floating chat widget** that:

- ✅ Appears as a **fixed button** in the bottom-left corner of every page
- ✅ Opens a **compact modal/popup** (not full screen)
- ✅ Includes **recipient selector** within the popup
- ✅ Maintains **conversation context** per selected recipient
- ✅ Works across **all dashboard pages** without layout disruption
- ✅ **Production-ready** with clean code separation (PHP, JS, CSS)

## Visual Design

```
┌─────────────────────────────────────────────────┐
│                Dashboard Page                    │
│                                                  │
│                                                  │
│  ┌──────────────────┐                           │
│  │  Chat Widget     │                           │
│  │  ┌─────────────┐ │                           │
│  │  │ Messages    │ │                           │
│  │  ├─────────────┤ │                           │
│  │  │ 🔍 Search   │ │                           │
│  │  ├─────────────┤ │                           │
│  │  │ [👤] Alice  │ │  ← Recipient list          │
│  │  │ [👤] Bob    │ │                           │
│  │  │ [👤] Charlie│ │                           │
│  │  └─────────────┘ │                           │
│  └──────────────────┘                           │
│                                                  │
│  [💬] ← Fixed chat button                       │
│    [3] ← Unread badge                           │
└─────────────────────────────────────────────────┘
```

## File Structure

```
src/
├── includes/
│   ├── chat-widget.php         # Widget HTML & inline CSS
│   ├── header.php              # HR Admin header (updated)
│   ├── footer.php              # Footer with widget include (updated)
│   ├── sidebar.php             # Sidebar without Messages link (updated)
│   └── headers/
│       └── super-admin-header.php  # Super Admin header (updated)
├── assets/
│   └── js/
│       └── chat-widget.js      # All widget JavaScript logic
├── api/
│   └── chat.php                # Backend API (unchanged)
└── pages/
    └── chat.php                # Old page view (no longer used)
```

## Key Components

### 1. Floating Chat Button (`chat-toggle-btn`)

**Location:** Bottom-left corner, fixed position  
**Features:**
- Gradient purple background
- Badge showing total unread messages
- Smooth hover animations
- Always visible, z-index: 9999

**Trigger:** Click to open/close the chat popup

### 2. Chat Popup Panel (`chat-popup-panel`)

**Dimensions:**
- Width: 380px (max 100vw - 40px on mobile)
- Height: 600px (max 100vh - 120px)
- Position: Above the button (bottom: 90px, left: 20px)

**Features:**
- Rounded corners (16px)
- Box shadow for depth
- Slide-in animation
- Two main views: Recipients and Conversation

### 3. Recipient Selector View

**Components:**
- Search bar with real-time filtering
- Scrollable contacts list
- Shows for each contact:
  - Avatar (or initials)
  - Name
  - Last message preview
  - Unread badge (if any)

**Behavior:**
- Contacts sorted by most recent conversation
- Debounced search (300ms)
- Click to open conversation

### 4. Conversation View

**Components:**
- Header with back button and recipient info
- Scrollable messages container
- Typing indicator
- Message input with auto-resize
- Send button

**Behavior:**
- Loads last 50 messages
- Polls every 3 seconds for new messages
- Marks messages as read automatically
- Shows read receipts (✓✓)

## Integration Points

### Included in Footer (`includes/footer.php`)

```php
<!-- Chat Widget - Floating chat accessible from all pages -->
<?php 
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    include __DIR__ . '/chat-widget.php';
}
?>

<!-- Chat Widget JavaScript -->
<?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
<script src="<?php echo asset_url('js/chat-widget.js'); ?>"></script>
<?php endif; ?>
```

**Result:** Widget automatically appears on every page for logged-in users.

### Removed from Sidebar

**Before:**
```php
['title' => 'Messages', 'page' => 'chat', 'section' => null, 'icon' => 'fa-comments']
```

**After:** This entry is removed from both HR Admin and Super Admin sidebars.

### Removed from Page Routing

**Before:**
```php
case 'chat':
    include $pagesPath . 'chat.php';
    break;
```

**After:** This case is removed from both header files.

## JavaScript Architecture

### Initialization

```javascript
window.CHAT_WIDGET_CONFIG = {
    currentUserId: <?php echo json_encode($current_user_id); ?>,
    currentUserName: <?php echo json_encode($current_user_name); ?>,
    currentUserRole: <?php echo json_encode($current_user_role); ?>,
    apiEndpoint: '/api/chat.php',
    pollInterval: 3000,
    typingTimeout: 5000
};
```

### State Management

```javascript
const state = {
    isOpen: false,
    currentView: 'recipients',  // or 'conversation'
    selectedUserId: null,
    selectedUserName: null,
    messages: [],
    lastMessageId: 0,
    isPolling: false,
    recipients: [],
    totalUnreadCount: 0
};
```

### Key Functions

| Function | Purpose |
|----------|---------|
| `openPopup()` | Shows the chat panel |
| `closePopup()` | Hides the chat panel |
| `showRecipientsView()` | Returns to contact list |
| `showConversationView()` | Opens a specific conversation |
| `loadRecipients()` | Fetches and renders contact list |
| `loadMessages()` | Fetches conversation messages |
| `sendMessage()` | Sends a new message |
| `startPolling()` | Begins real-time updates |
| `stopPolling()` | Stops polling |

### Event Listeners

```javascript
// Toggle button
toggleBtn.addEventListener('click', togglePopup);

// Close button
closeBtn.addEventListener('click', closePopup);

// Back button
backBtn.addEventListener('click', showRecipientsView);

// Search input
recipientSearch.addEventListener('input', handleRecipientSearch);

// Message input
messageInput.addEventListener('input', handleMessageInput);
messageInput.addEventListener('keydown', handleMessageKeyDown);

// Send button
sendBtn.addEventListener('click', sendMessage);

// Outside click
document.addEventListener('click', handleOutsideClick);
```

## CSS Architecture

### Layout Strategy

- **Fixed positioning** for button and popup
- **Absolute positioning** for view switching (recipients/conversation)
- **Flexbox** for internal layouts
- **Z-index layering:**
  - Chat widget: 9999
  - Button: Highest in widget
  - Popup: Below button

### Color Scheme

```css
/* Primary Gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Background Colors */
--bg-primary: #ffffff;
--bg-secondary: #f8fafc;
--bg-hover: #f1f5f9;

/* Text Colors */
--text-primary: #0f172a;
--text-secondary: #64748b;
--text-muted: #94a3b8;

/* Accent Colors */
--accent-red: #ef4444;      /* Unread badges */
--accent-blue: #3b82f6;     /* Read status */
```

### Animations

```css
/* Slide-in animation */
@keyframes chatSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Typing indicator */
@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-6px); }
}

/* Message bubble slide-in */
@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

## Mobile Responsiveness

### Breakpoint: 768px

**Below 768px (Mobile):**

```css
.chat-toggle-btn {
    width: 56px;
    height: 56px;
}

.chat-popup-panel {
    width: calc(100vw - 20px);
    height: calc(100vh - 96px);
}

.chat-message {
    max-width: 85%;  /* More readable on small screens */
}
```

**Body scroll lock:**
```css
body.chat-widget-open {
    overflow: hidden;  /* Only on mobile */
}
```

## API Endpoints Used

All endpoints in `api/chat.php`:

| Action | Method | Parameters | Purpose |
|--------|--------|------------|---------|
| `get_users` | GET | `search` (optional) | Fetch recipients list |
| `get_messages` | GET | `user_id`, `limit` | Fetch conversation |
| `send_message` | POST | `receiver_id`, `message` | Send message |
| `mark_as_read` | POST | `sender_id` | Mark messages read |
| `get_unread_count` | GET | - | Global unread count |
| `update_typing_status` | POST | `recipient_id`, `is_typing` | Typing indicator |
| `get_typing_status` | GET | `user_id` | Check if typing |

## Features Preserved

All original chat features remain functional:

✅ **One-to-one messaging**  
✅ **Real-time updates** (AJAX polling)  
✅ **Read receipts** (✓ sent, ✓✓ read)  
✅ **Typing indicators**  
✅ **Unread badges**  
✅ **Message timestamps**  
✅ **Search functionality**  
✅ **Conversation ordering** (most recent first)  
✅ **Last message preview**  
✅ **Auto-scroll to latest**  
✅ **Security** (sanitization, XSS protection, SQL injection prevention)  

## New Features Added

🆕 **Floating widget** - Always accessible from any page  
🆕 **Compact modal** - Doesn't cover entire screen  
🆕 **Smooth animations** - Professional transitions  
🆕 **Click outside to close** - Intuitive UX  
🆕 **Mobile-optimized** - Full-screen on mobile  
🆕 **Global unread badge** - See total unread from button  
🆕 **Minimize button** - Same as close (for UX consistency)  
🆕 **Better z-index handling** - Never overlaps critical UI  

## Usage Instructions

### For End Users

1. **Opening Chat:**
   - Click the purple chat button (💬) in bottom-left corner
   - Badge shows total unread messages

2. **Selecting a Recipient:**
   - Use search bar to find contacts
   - Click on any contact to open conversation
   - Contacts with recent messages appear first

3. **Sending Messages:**
   - Type in the input field at bottom
   - Press Enter to send (Shift+Enter for new line)
   - Or click the send button (paper plane icon)

4. **Navigating:**
   - Click back arrow (←) to return to contacts
   - Click X or minimize to close widget
   - Click outside widget to close

5. **Real-time Updates:**
   - New messages appear automatically
   - Typing indicator shows when other person is typing
   - Unread badges update instantly

### For Developers

#### Customization Options

**Change Widget Position:**
```css
.chat-widget {
    bottom: 20px;     /* Distance from bottom */
    left: 20px;       /* Distance from left */
}
```

**Change Popup Size:**
```css
.chat-popup-panel {
    width: 380px;     /* Popup width */
    height: 600px;    /* Popup height */
}
```

**Change Colors:**
```css
/* Update gradient in multiple places: */
.chat-toggle-btn { background: linear-gradient(...); }
.chat-popup-header { background: linear-gradient(...); }
.chat-send-btn { background: linear-gradient(...); }
```

**Change Polling Interval:**
```php
// In includes/chat-widget.php
pollInterval: 3000,  // Change to desired milliseconds
```

**Change Message Limit:**
```javascript
// In assets/js/chat-widget.js, loadMessages()
url.searchParams.set('limit', '50');  // Change 50 to desired limit
```

#### Adding Features

**Example: Add Sound Notifications**

```javascript
// In pollNewMessages(), after detecting new messages:
if (newMessages.length > 0) {
    const audio = new Audio('/assets/sounds/notification.mp3');
    audio.play().catch(e => console.log('Audio play failed'));
}
```

**Example: Add Browser Notifications**

```javascript
// Request permission on init
if (Notification.permission === 'default') {
    Notification.requestPermission();
}

// In pollNewMessages(), after detecting new messages:
if (Notification.permission === 'granted' && !state.isOpen) {
    new Notification('New message from ' + senderName, {
        body: lastMessage,
        icon: '/public/logo.svg'
    });
}
```

## Testing Checklist

### Functional Tests

- [ ] Widget button appears on all pages
- [ ] Click button opens popup
- [ ] Click outside closes popup
- [ ] Search filters contacts correctly
- [ ] Click contact opens conversation
- [ ] Send message works
- [ ] Receive message works (test with 2 users)
- [ ] Unread badges update correctly
- [ ] Back button returns to contacts
- [ ] Typing indicator shows/hides
- [ ] Read receipts update
- [ ] Conversation ordering (most recent first)
- [ ] Last message preview shows

### UI/UX Tests

- [ ] Animations are smooth
- [ ] No layout shift when widget appears
- [ ] Widget doesn't overlap critical UI
- [ ] Button is easily clickable
- [ ] Popup is readable and well-spaced
- [ ] Mobile view is usable
- [ ] Colors are consistent with dashboard
- [ ] Loading states are visible
- [ ] Error states are handled gracefully

### Browser Compatibility

- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Chrome
- [ ] Mobile Safari

### Performance Tests

- [ ] Widget loads quickly (<500ms)
- [ ] No lag when scrolling messages
- [ ] Polling doesn't slow down page
- [ ] No memory leaks after extended use
- [ ] Smooth on low-end devices

## Troubleshooting

### Widget Not Appearing

**Check:**
1. User is logged in (`$_SESSION['logged_in']` is true)
2. `includes/footer.php` includes the widget
3. `assets/js/chat-widget.js` is loaded
4. No JavaScript console errors

**Fix:**
- Clear browser cache
- Check file paths in `asset_url()`
- Verify `CHAT_WIDGET_CONFIG` is defined

### Messages Not Sending

**Check:**
1. API endpoint is correct (`/api/chat.php`)
2. User has permission to send messages
3. Network tab shows successful POST request
4. No PHP errors in server logs

**Fix:**
- Check `error.log` in `storage/logs/`
- Verify database connection
- Test API endpoint directly

### Popup Position Issues

**Check:**
1. Z-index conflicts with other elements
2. CSS for `.chat-widget` is loaded
3. No custom CSS overriding position

**Fix:**
```css
/* Increase z-index if needed */
.chat-widget {
    z-index: 99999 !important;
}
```

### Mobile Issues

**Check:**
1. Viewport meta tag is present
2. Touch events work on button
3. Popup fills screen on mobile

**Fix:**
```html
<!-- Ensure this is in <head> -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

### Polling Not Working

**Check:**
1. `state.isPolling` is true when conversation open
2. No JavaScript errors in console
3. API returns valid JSON

**Fix:**
```javascript
// Debug polling
console.log('Polling state:', state.isPolling);
console.log('Selected user:', state.selectedUserId);
```

## Performance Optimization

### For High Volume

1. **Increase polling interval:**
   ```javascript
   pollInterval: 5000  // 5 seconds instead of 3
   ```

2. **Reduce message history:**
   ```javascript
   url.searchParams.set('limit', '30');  // Load fewer messages
   ```

3. **Add caching:**
   ```javascript
   // Cache recipient list for 30 seconds
   const recipientsCacheTime = 30000;
   ```

4. **Lazy load messages:**
   ```javascript
   // Load older messages only when scrolling to top
   messagesContainer.addEventListener('scroll', handleScrollTop);
   ```

### For Low Bandwidth

1. **Compress responses:**
   ```php
   // In api/chat.php
   if (extension_loaded('zlib')) {
       ob_start('ob_gzhandler');
   }
   ```

2. **Minimize data transfer:**
   ```sql
   -- Don't fetch unnecessary fields
   SELECT id, sender_id, message, created_at, is_read
   FROM chat_messages
   -- Don't SELECT *
   ```

## Security Considerations

### Already Implemented

✅ **Session-based authentication** - Only logged-in users  
✅ **Input sanitization** - `sanitize_input()` function  
✅ **SQL injection prevention** - Prepared statements  
✅ **XSS protection** - `check_xss()` and `escapeHtml()`  
✅ **Message length validation** - 5000 char max  
✅ **User verification** - Can only see own conversations  
✅ **Security logging** - All actions logged  

### Additional Recommendations

1. **Rate limiting** (prevent spam):
   ```php
   // In api/chat.php, before sending message
   $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages 
                           WHERE sender_id = ? 
                           AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
   $stmt->execute([$user_id]);
   if ($stmt->fetchColumn() >= 10) {
       throw new Exception('Rate limit exceeded');
   }
   ```

2. **Content Security Policy** (prevent XSS):
   ```php
   // In includes/header.php
   header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net");
   ```

3. **Sanitize file uploads** (if adding attachments):
   ```php
   $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
   if (!in_array($_FILES['file']['type'], $allowed_types)) {
       throw new Exception('Invalid file type');
   }
   ```

## Deployment Checklist

### Pre-deployment

- [ ] All tests pass
- [ ] No console errors
- [ ] No PHP warnings/errors
- [ ] Database migration completed
- [ ] API endpoints tested
- [ ] Mobile view tested
- [ ] Cross-browser tested
- [ ] Security review completed
- [ ] Performance acceptable

### Deployment Steps

1. **Backup database:**
   ```bash
   mysqldump -u root -p goldenz_hr > backup_$(date +%Y%m%d).sql
   ```

2. **Deploy files:**
   ```bash
   # Upload via FTP/SFTP or git pull
   git pull origin main
   ```

3. **Clear caches:**
   ```bash
   # Clear PHP opcache if enabled
   php -r "opcache_reset();"
   
   # Clear browser caches (notify users)
   ```

4. **Verify deployment:**
   - Visit website
   - Open chat widget
   - Send test message
   - Check logs for errors

### Post-deployment

- [ ] Monitor error logs
- [ ] Check user feedback
- [ ] Monitor server performance
- [ ] Verify unread counts are accurate
- [ ] Test with real users

## Future Enhancements

### Planned Features

🔮 **WebSocket support** - True real-time (no polling)  
🔮 **Group chats** - Multi-user conversations  
🔮 **File attachments** - Share images/documents  
🔮 **Voice messages** - Record and send audio  
🔮 **Video calls** - Integrated video chat  
🔮 **Message search** - Search within conversations  
🔮 **Message reactions** - Like/emoji reactions  
🔮 **Message forwarding** - Forward to other users  
🔮 **Message deletion** - Delete sent messages  
🔮 **Offline mode** - Queue messages when offline  

### Easy Additions

1. **Online status indicator:**
   ```sql
   ALTER TABLE users ADD COLUMN last_seen TIMESTAMP;
   ```

2. **Message delivery status:**
   ```sql
   ALTER TABLE chat_messages ADD COLUMN delivered_at TIMESTAMP;
   ```

3. **Conversation muting:**
   ```sql
   CREATE TABLE chat_muted_conversations (
       user_id INT,
       other_user_id INT,
       muted_until TIMESTAMP
   );
   ```

## Support & Maintenance

### Logging

All chat actions are logged in `storage/logs/security.log`:

```
[2026-01-23 10:30:45] INFO Chat Message Sent - User ID: 25 sent message to User ID: 30
[2026-01-23 10:30:46] INFO Chat Messages Read - User ID: 30 marked messages from User ID: 25 as read
```

### Monitoring Queries

**Check chat usage:**
```sql
SELECT DATE(created_at) as date, COUNT(*) as messages
FROM chat_messages
GROUP BY DATE(created_at)
ORDER BY date DESC
LIMIT 30;
```

**Most active users:**
```sql
SELECT u.name, COUNT(*) as messages_sent
FROM chat_messages cm
JOIN users u ON cm.sender_id = u.id
GROUP BY cm.sender_id
ORDER BY messages_sent DESC
LIMIT 10;
```

**Unread message count:**
```sql
SELECT receiver_id, COUNT(*) as unread
FROM chat_messages
WHERE is_read = 0
GROUP BY receiver_id;
```

### Maintenance Tasks

**Clean old typing statuses** (automated):
```sql
-- Already in API, runs automatically
DELETE FROM chat_typing_status WHERE updated_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)
```

**Archive old messages** (manual, if needed):
```sql
-- Move messages older than 1 year to archive table
INSERT INTO chat_messages_archive SELECT * FROM chat_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
DELETE FROM chat_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

---

**Version:** 2.0 - Floating Widget  
**Last Updated:** January 23, 2026  
**Status:** ✅ Production Ready  
**Maintained by:** Development Team
