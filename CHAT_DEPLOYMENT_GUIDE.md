# Private Chat System - Deployment Guide

## Overview

This is a production-ready private chat system for the Golden Z-5 HR Management System. It provides secure, one-to-one messaging between authenticated users with real-time updates via AJAX polling.

## Features

- ✅ **One-to-One Messaging**: Private conversations between users
- ✅ **Real-time Updates**: AJAX polling every 3 seconds for new messages
- ✅ **User Search**: Filter contacts by name, username, or email
- ✅ **Unread Message Indicators**: Visual badges showing unread counts
- ✅ **Read Receipts**: Message status indicators (sent/read)
- ✅ **Typing Indicators**: Optional typing status display
- ✅ **Responsive Design**: Mobile-friendly interface
- ✅ **Security Features**: 
  - Session-based authentication
  - SQL injection prevention via prepared statements
  - XSS protection with input sanitization
  - Message length validation
  - User status verification
- ✅ **Modern UI**: Clean, professional design with smooth animations
- ✅ **Extensible Architecture**: Ready for WebSocket upgrades

## Files Created

### 1. Database Migration
- **File**: `migrations/add_chat_system.sql`
- **Description**: Creates three tables:
  - `chat_messages`: Stores all messages
  - `chat_typing_status`: Stores typing indicators
  - `chat_conversations`: Conversation metadata (optional optimization)

### 2. Backend API
- **File**: `api/chat.php`
- **Description**: RESTful API endpoint handling:
  - Get users list
  - Get messages between users
  - Send new messages
  - Get unread counts
  - Mark messages as read
  - Typing status management

### 3. Frontend Page
- **File**: `pages/chat.php`
- **Description**: Main chat interface with:
  - User list panel
  - Chat conversation area
  - Message input
  - Embedded CSS styles

### 4. JavaScript Client
- **File**: `assets/js/chat.js`
- **Description**: Handles:
  - AJAX polling for messages
  - Real-time UI updates
  - Message sending
  - User selection
  - Typing indicators
  - Scroll management

### 5. Navigation Update
- **File**: `includes/sidebar.php` (modified)
- **Description**: Added "Messages" menu item with chat icon

## Deployment Steps

### Step 1: Run Database Migration

```bash
# Connect to your MySQL database
mysql -u root -p goldenz_hr

# Run the migration
source /path/to/src/migrations/add_chat_system.sql
```

Or via phpMyAdmin:
1. Log in to phpMyAdmin
2. Select the `goldenz_hr` database
3. Go to SQL tab
4. Copy and paste contents of `add_chat_system.sql`
5. Click "Go" to execute

### Step 2: Verify File Permissions

Ensure web server can read all files:

```bash
chmod 644 src/api/chat.php
chmod 644 src/pages/chat.php
chmod 644 src/assets/js/chat.js
chmod 644 src/migrations/add_chat_system.sql
```

### Step 3: Clear Browser Cache

After deployment, clear browser cache to ensure new JavaScript and CSS are loaded:
- Chrome: Ctrl+Shift+Delete (Windows) / Cmd+Shift+Delete (Mac)
- Firefox: Ctrl+Shift+Delete (Windows) / Cmd+Shift+Delete (Mac)

### Step 4: Test the System

1. Log in to the HR admin portal
2. Click on "Messages" in the sidebar
3. Select a user from the contact list
4. Send a test message
5. Log in as another user to verify message delivery
6. Check unread indicators update correctly

## Usage Guide

### For End Users

1. **Starting a Conversation**
   - Click "Messages" in the sidebar
   - Search for a user or select from the list
   - Click on a user to open the chat

2. **Sending Messages**
   - Type your message in the input box
   - Press Enter to send (Shift+Enter for new line)
   - Click the send button

3. **Reading Messages**
   - Messages are marked as read automatically when you open a conversation
   - Blue double-check indicates the recipient has read your message
   - Single check means message was sent but not yet read

4. **Unread Messages**
   - Red badges show unread message count for each user
   - Badges update every 3 seconds automatically

### For Administrators

**Monitoring Chat Activity:**
- Chat interactions are logged in the security log
- View logs at: Dashboard > System Logs > Security Log
- Search for "Chat Message Sent" events

**Database Queries:**

```sql
-- Get total messages sent today
SELECT COUNT(*) FROM chat_messages 
WHERE DATE(created_at) = CURDATE();

-- Get most active users
SELECT u.name, COUNT(*) as message_count 
FROM chat_messages cm
JOIN users u ON cm.sender_id = u.id
GROUP BY cm.sender_id
ORDER BY message_count DESC
LIMIT 10;

-- Get unread message count by user
SELECT u.name, COUNT(*) as unread_count
FROM chat_messages cm
JOIN users u ON cm.receiver_id = u.id
WHERE cm.is_read = 0
GROUP BY cm.receiver_id
ORDER BY unread_count DESC;
```

## Configuration

### Adjust Polling Interval

In `pages/chat.php`, modify the configuration:

```javascript
window.CHAT_CONFIG = {
    currentUserId: <?php echo json_encode($current_user_id); ?>,
    currentUserName: <?php echo json_encode($current_user_name); ?>,
    apiEndpoint: '/api/chat.php',
    pollInterval: 3000, // Change this value (in milliseconds)
    typingTimeout: 5000
};
```

- **Faster updates**: Set to 2000 (2 seconds) - higher server load
- **Slower updates**: Set to 5000 (5 seconds) - lower server load
- **Recommended**: 3000-5000ms for good balance

### Message Length Limit

In `api/chat.php`, line 144:

```php
if (mb_strlen($message) > 5000) {
    throw new Exception('Message is too long (max 5000 characters)');
}
```

Change `5000` to your desired character limit.

### Enable/Disable Typing Indicators

Typing indicators are optional. To disable them:

1. In `assets/js/chat.js`, comment out these lines:
   ```javascript
   // handleTypingIndicator(); // Line ~76
   // stopTypingIndicator();    // Line ~249
   ```

2. Or set a very high timeout in config:
   ```javascript
   typingTimeout: 999999999 // Effectively disables it
   ```

## Security Considerations

### Input Validation
- All messages are sanitized using `sanitize_input()` function
- XSS checks prevent malicious scripts
- SQL injection prevented via prepared statements
- Message length limits prevent abuse

### Authentication
- All API endpoints check session authentication
- Users can only view their own conversations
- No API endpoint exposes other users' private messages

### Rate Limiting (Recommended)

Add rate limiting to prevent spam:

```php
// In api/chat.php, add before sending message
$stmt = $pdo->prepare(
    "SELECT COUNT(*) as recent_count 
     FROM chat_messages 
     WHERE sender_id = ? 
     AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)"
);
$stmt->execute([$current_user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['recent_count'] >= 10) {
    throw new Exception('Rate limit exceeded. Please wait before sending more messages.');
}
```

## Troubleshooting

### Messages not appearing

**Check:**
1. Database tables created correctly
2. JavaScript console for errors (F12 in browser)
3. Network tab shows successful API calls
4. PHP error logs for backend issues

**Solutions:**
```bash
# Check PHP error log
tail -f /var/log/apache2/error.log  # or nginx error log

# Verify database tables exist
mysql -u root -p goldenz_hr -e "SHOW TABLES LIKE 'chat_%';"

# Test API endpoint directly
curl -X GET "http://your-domain/api/chat.php?action=get_users" \
  -H "Cookie: PHPSESSID=your-session-id"
```

### Users not loading

**Check:**
1. Session is active and user is logged in
2. Database connection is working
3. `users` table has active users

**Solution:**
```sql
-- Verify active users exist
SELECT id, name, username, status FROM users WHERE status = 'active';
```

### Styling issues

**Clear cache:**
- Hard refresh: Ctrl+F5 (Windows) / Cmd+Shift+R (Mac)
- Clear browser cache completely
- Check browser console for CSS errors

### Database errors

**Foreign key constraints:**
If you get foreign key errors during migration, ensure `users` table exists first:

```sql
SHOW CREATE TABLE users;
```

If tables need to be dropped and recreated:
```sql
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS chat_typing_status;
DROP TABLE IF EXISTS chat_conversations;
DROP TABLE IF EXISTS chat_messages;
SET FOREIGN_KEY_CHECKS=1;

-- Then re-run migration
```

## Future Enhancements

### Easy Upgrades

1. **WebSocket Support**
   - Replace AJAX polling with Socket.IO or native WebSockets
   - Change `pollNewMessages()` to subscribe to WebSocket events
   - Update API to broadcast messages via WebSocket server

2. **File Attachments**
   - Add file upload input to message form
   - Store files in `uploads/chat/` directory
   - Add `attachment_path` column to `chat_messages` table

3. **Message Reactions**
   - Add emoji reactions to messages
   - Create `chat_message_reactions` table
   - Display reactions below messages

4. **Group Chat**
   - Create `chat_groups` table
   - Modify API to support group message sending
   - Update UI to show group conversations

5. **Voice Messages**
   - Integrate Web Audio API for recording
   - Store audio files like attachments
   - Add playback controls in message bubbles

6. **Search Messages**
   - Add search input in chat header
   - Query `chat_messages` with LIKE search
   - Highlight matching messages

## Performance Optimization

### For Large Message Volumes

1. **Add Indexes:**
```sql
-- Optimize conversation queries
CREATE INDEX idx_conversation_fast ON chat_messages 
(sender_id, receiver_id, created_at DESC);

-- Optimize unread counts
CREATE INDEX idx_unread_fast ON chat_messages 
(receiver_id, is_read, sender_id);
```

2. **Archive Old Messages:**
```sql
-- Create archive table
CREATE TABLE chat_messages_archive LIKE chat_messages;

-- Move messages older than 1 year
INSERT INTO chat_messages_archive 
SELECT * FROM chat_messages 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

DELETE FROM chat_messages 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

3. **Use Conversation Table:**
   - The `chat_conversations` table is already created
   - Update it on each message send/receive
   - Query this table instead of aggregating messages

## Support

For issues or questions:
1. Check logs: `storage/logs/security.log` and `storage/logs/error.log`
2. Review code comments in source files
3. Test with browser developer tools (F12)

## License

This chat system is part of the Golden Z-5 HR Management System.
© 2026 Golden Z-5 Security Agency. All rights reserved.
