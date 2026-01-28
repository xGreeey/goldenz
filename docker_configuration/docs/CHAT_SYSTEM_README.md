# Private Chat System - Complete Documentation

## Overview

A production-ready, secure private messaging system with one-to-one communication between authenticated users. Features include real-time updates, emoji support, photo attachments, and conversation management.

## Features

### ✅ Core Functionality
- **One-to-One Messaging**: Private conversations between users
- **Real-time Updates**: AJAX polling for near-real-time message delivery
- **Session-based Authentication**: Secure access control
- **Message Read Status**: Track read/unread messages with visual indicators
- **Typing Indicators**: See when the other user is typing
- **Unread Count Badge**: Global notification badge on chat button

### ✅ Enhanced Features
- **Emoji Picker**: Quick access to 120+ commonly used emojis
- **Photo Attachments**: Upload and share images (JPG, PNG, WEBP, GIF)
- **Photo Preview**: In-line image preview before sending
- **Photo Modal**: Click to view full-size images
- **Clear History**: Delete conversation history with confirmation
- **Contact Search**: Quick search through user list
- **Conversation Sorting**: Most recent conversations appear first

### ✅ UI/UX Features
- **Floating Chat Button**: Fixed bottom-left trigger button with icon
- **Compact Chat Popup**: Non-intrusive modal-style panel
- **Smooth Animations**: Polished transitions and micro-interactions
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Modern Design**: Clean, professional interface with gradient accents
- **Message Bubbles**: Distinct styling for sent vs received messages
- **Auto-scroll**: Automatic scroll to latest messages
- **Auto-resize Input**: Text area grows with content

### ✅ Security Features
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Prevention**: Input sanitization and output escaping
- **File Upload Validation**: MIME type checking and size limits
- **Session Validation**: Checks on every API call
- **Secure File Storage**: Randomized filenames with safe paths
- **Authorization Checks**: Users can only access their own conversations
- **Security Event Logging**: Important actions are logged

## Installation

### Step 1: Run Database Migration

```bash
# Navigate to migrations directory
cd src/migrations

# Run the main chat system migration
php run_chat_migration.php

# Run the attachments migration
php run_chat_attachments_migration.php
```

### Step 2: Verify File Structure

Ensure these files exist:
```
src/
├── api/
│   └── chat.php                    # Backend API endpoints
├── assets/
│   └── js/
│       └── chat-widget.js          # Frontend JavaScript
├── includes/
│   └── chat-widget.php             # Chat widget HTML/CSS
├── migrations/
│   ├── add_chat_system_no_fk.sql  # Database schema
│   └── add_chat_attachments.sql   # Attachment support
└── uploads/
    └── chat_attachments/          # Photo storage (auto-created)
```

### Step 3: Verify Integration

The chat widget is automatically included via `footer.php`:
- Widget HTML/CSS is included from `includes/chat-widget.php`
- JavaScript is loaded from `assets/js/chat-widget.js`
- Font Awesome icons are loaded in all headers

## Usage

### For Users

#### Starting a Conversation
1. Click the chat button (💬 icon) at the bottom-left corner
2. Search or select a contact from the list
3. Type your message and press Enter or click Send (✈️ icon)

#### Sending an Emoji
1. Click the emoji button (😊 icon) above the input
2. Click any emoji to insert it at cursor position
3. Continue typing or send immediately

#### Sending a Photo
1. Click the attachment button (📎 icon) above the input
2. Select an image file (max 5MB)
3. Preview appears - add optional caption
4. Click Send to deliver

#### Viewing Photos
- Click any image in the chat to view full size
- Click X or outside to close

#### Clearing History
1. Click the trash icon (🗑️) in the conversation header
2. Confirm the action
3. All messages with that user are permanently deleted

### For Developers

#### API Endpoints

All endpoints are in `/api/chat.php` with `action` parameter:

**get_users**
- Method: GET
- Purpose: Fetch contact list
- Returns: Array of users with last message preview and unread count

**get_messages**
- Method: GET
- Parameters: `user_id`, `limit` (optional)
- Purpose: Fetch conversation messages
- Returns: Array of messages with attachments

**send_message**
- Method: POST
- Parameters: `receiver_id`, `message`
- Purpose: Send text message
- Returns: Newly created message object

**upload_photo**
- Method: POST
- Parameters: `receiver_id`, `photo` (file), `caption` (optional)
- Purpose: Upload and send image
- Returns: Message object with attachment

**clear_history**
- Method: POST
- Parameters: `user_id`
- Purpose: Delete all messages with specific user
- Returns: Success status and deleted count

**get_unread_count**
- Method: GET
- Purpose: Get total unread message count
- Returns: Total count and breakdown by sender

**mark_as_read**
- Method: POST
- Parameters: `sender_id`
- Purpose: Mark messages as read
- Returns: Number of messages marked

**set_typing_status**
- Method: POST
- Parameters: `recipient_id`, `is_typing`
- Purpose: Update typing indicator
- Returns: Success status

**get_typing_status**
- Method: GET
- Parameters: `user_id`
- Purpose: Check if user is typing
- Returns: Boolean typing status

#### JavaScript Configuration

Configure in chat widget PHP:
```javascript
window.CHAT_WIDGET_CONFIG = {
    currentUserId: <?php echo json_encode($current_user_id); ?>,
    currentUserName: <?php echo json_encode($current_user_name); ?>,
    currentUserRole: <?php echo json_encode($current_user_role); ?>,
    apiEndpoint: '/api/chat.php',
    pollInterval: 3000,        // Poll every 3 seconds
    typingTimeout: 5000        // Typing indicator timeout
};
```

#### Database Schema

**chat_messages**
- `id`: Primary key
- `sender_id`: User who sent the message
- `receiver_id`: User who receives the message
- `message`: Message text content
- `attachment_type`: Type of attachment (e.g., 'image')
- `attachment_path`: Path to uploaded file
- `attachment_size`: File size in bytes
- `attachment_name`: Original filename
- `is_read`: Read status (0/1)
- `read_at`: Timestamp when read
- `created_at`: Message creation time
- `updated_at`: Last update time

**chat_typing_status**
- `id`: Primary key
- `user_id`: User who is typing
- `recipient_id`: Recipient of the typing indicator
- `is_typing`: Status (0/1)
- `updated_at`: Last update time

**chat_conversations** (optimization table)
- Stores conversation metadata for quick access
- Tracks last message and unread counts

## Customization

### Changing Colors

Edit CSS in `includes/chat-widget.php`:

```css
/* Main gradient (button and sent messages) */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Change to your brand colors */
background: linear-gradient(135deg, #YOUR_COLOR_1 0%, #YOUR_COLOR_2 100%);
```

### Changing Position

Move chat button to bottom-right:
```css
.chat-widget {
    left: auto;    /* Remove left positioning */
    right: 20px;   /* Add right positioning */
}
```

### Adjusting Popup Size

```css
.chat-popup-panel {
    width: 380px;           /* Change width */
    height: 600px;          /* Change height */
}
```

### Changing Poll Interval

In `chat-widget.php`:
```javascript
pollInterval: 3000,  // Change to 5000 for 5 seconds
```

### Adding More Emojis

In `chat-widget.js`:
```javascript
const EMOJIS = [
    '😀', '😃', '😄',  // Add more emojis here
    // ... your additional emojis
];
```

## Security Best Practices

1. **File Upload Directory**: Ensure `uploads/chat_attachments/` is writable but not directly executable
2. **File Size Limits**: Current limit is 5MB - adjust in both PHP and JavaScript
3. **Allowed File Types**: Only images allowed - modify `$allowedTypes` in API if needed
4. **Rate Limiting**: Consider adding rate limiting to prevent spam
5. **HTTPS**: Always use HTTPS in production
6. **Session Security**: Ensure session cookies are HTTP-only and secure

## Troubleshooting

### Icons Not Visible
1. Check Font Awesome is loaded in header
2. Verify CDN link: `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css`
3. Check browser console for 404 errors
4. Ensure no CSS is overriding `font-family` on `<i>` elements

### Images Not Uploading
1. Check `uploads/chat_attachments/` directory exists and is writable
2. Verify PHP `upload_max_filesize` and `post_max_size` settings
3. Check file permissions (755 for directory, 644 for files)
4. Review PHP error logs

### Messages Not Appearing
1. Check browser console for JavaScript errors
2. Verify API endpoint is accessible at `/api/chat.php`
3. Check database connection
4. Ensure user is logged in with valid session

### Real-time Updates Not Working
1. Verify `pollInterval` is set correctly
2. Check browser console for fetch errors
3. Test API endpoint directly
4. Ensure session is maintained across requests

### Emoji Picker Not Opening
1. Check `renderEmojiPicker()` is called during init
2. Verify emoji picker HTML is in DOM
3. Check z-index settings
4. Review click event handlers

## Performance Optimization

### For High Traffic
1. **WebSocket Upgrade**: Replace AJAX polling with WebSocket for true real-time
2. **Message Pagination**: Load older messages on scroll
3. **Lazy Loading**: Load images on demand
4. **CDN**: Serve uploads from CDN
5. **Caching**: Cache user list and conversation metadata
6. **Database Indexing**: Ensure all indexes are created (included in migration)

### For Large Files
1. **Compression**: Compress images before upload
2. **Thumbnails**: Generate and display thumbnails, full size on click
3. **Cloud Storage**: Store files in S3, MinIO, or similar
4. **Progressive Loading**: Use progressive JPEG

## Future Enhancements

### Roadmap
- [ ] WebSocket support for true real-time messaging
- [ ] Message reactions (👍, ❤️, etc.)
- [ ] Voice messages
- [ ] File attachments (PDF, DOC, etc.)
- [ ] Message search within conversations
- [ ] Message forwarding
- [ ] Message editing and deletion
- [ ] Group chats
- [ ] Online/offline status indicators
- [ ] Last seen timestamps
- [ ] Push notifications
- [ ] Mobile app integration

## Support

For issues or questions:
1. Check this documentation
2. Review console logs and error messages
3. Verify database schema is correctly applied
4. Check file permissions
5. Test API endpoints directly

## License

This chat system is part of the Goldenz HR System and follows the same license terms.

## Credits

- **Font Awesome**: Icons (https://fontawesome.com)
- **Bootstrap**: UI Framework
- **PHP**: Backend logic
- **JavaScript**: Frontend interactivity

---

**Version**: 2.0.0  
**Last Updated**: 2024  
**Status**: Production Ready ✅
