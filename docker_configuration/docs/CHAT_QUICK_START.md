# Chat System - Quick Start Guide

## 🚀 Quick Installation (5 minutes)

### Step 1: Apply Database Migration
Open your terminal and run:

```bash
cd c:\docker-projects\goldenz_hr_system\src\migrations
php run_chat_migration.php
php run_chat_attachments_migration.php
```

**Expected output:**
```
✓ Chat system migration completed successfully!
✓ Chat attachments migration completed successfully!
✓ Created uploads directory
```

### Step 2: Verify Files Exist
Check that these files are in place (they should be after the previous updates):

- ✅ `src/api/chat.php` - Backend API
- ✅ `src/includes/chat-widget.php` - Chat UI
- ✅ `src/assets/js/chat-widget.js` - Chat JavaScript
- ✅ `src/uploads/chat_attachments/` - Photo storage directory

### Step 3: Test the Chat

1. **Login to your dashboard** (any user account)
2. **Look for the chat button** at the bottom-left corner (purple gradient circle with 💬 icon)
3. **Click the button** to open the chat popup
4. **Select a user** from the contact list
5. **Try these features:**

   ✅ Send a text message (type and press Enter)
   
   ✅ Add an emoji (click 😊 button above input)
   
   ✅ Send a photo (click 📎 button, select image)
   
   ✅ View full-size image (click any image in chat)
   
   ✅ Clear history (click 🗑️ in header, confirm)

---

## 🎨 Visual Guide

### Chat Button Location
```
┌─────────────────────────────────────┐
│  Dashboard Header                   │
├─────────────────────────────────────┤
│                                     │
│  Main Content Area                  │
│                                     │
│                                     │
│                                     │
│                                     │
└─────────────────────────────────────┘
  💬 ← Chat button here (bottom-left)
```

### Chat Popup Layout
```
┌─────────────────────────────┐
│ 💬 Messages          [−] [×] │ ← Header
├─────────────────────────────┤
│ 🔍 Search contacts...       │ ← Search
├─────────────────────────────┤
│ 👤 John Doe                 │
│    You: Hey there!        3 │ ← Contact list
│ 👤 Jane Smith               │
│    Jane: See you!           │
└─────────────────────────────┘
```

### Conversation View
```
┌─────────────────────────────┐
│ [←] 👤 John Doe        [🗑️] │ ← Header with clear button
├─────────────────────────────┤
│          Hey there! 👋      │ ← Received message
│                             │
│  Hello! How are you? ✓✓     │ ← Sent message (read)
│                             │
│          [Image]            │ ← Photo attachment
│         Great photo!        │
└─────────────────────────────┘
│ 😊 📎                       │ ← Emoji & Photo buttons
│ ┌─────────────────────┐ ✈️ │ ← Input & Send
│ │ Type a message...   │    │
│ └─────────────────────┘    │
└─────────────────────────────┘
```

---

## 🎯 Feature Checklist

After installation, verify these features work:

### Basic Messaging
- [ ] Click chat button to open popup
- [ ] See list of users sorted by recent activity
- [ ] Click user to open conversation
- [ ] Type and send message (Enter key or click send)
- [ ] Receive messages from other users
- [ ] See unread count badges
- [ ] Messages marked as read automatically
- [ ] Navigate back to user list

### Emojis
- [ ] Click 😊 emoji button above input
- [ ] Emoji picker opens with 120+ emojis
- [ ] Click emoji to insert at cursor position
- [ ] Multiple emojis can be added
- [ ] Emoji picker closes when clicking outside

### Photo Attachments
- [ ] Click 📎 attachment button above input
- [ ] File picker opens for image selection
- [ ] Preview appears after selecting image
- [ ] Optional caption can be added
- [ ] Photo uploads and appears in chat
- [ ] Click photo to view full size
- [ ] Full-size modal opens with close button
- [ ] Remove photo preview before sending (× button)

### Conversation Management
- [ ] Click 🗑️ trash button in header
- [ ] Confirmation modal appears
- [ ] Confirm to clear all messages
- [ ] Conversation resets to empty state
- [ ] Action is logged for security

### Real-time Features
- [ ] New messages appear automatically (3-second polling)
- [ ] Unread badge updates in real-time
- [ ] Typing indicator shows when other user types
- [ ] Read receipts show double check (✓✓) when read
- [ ] Conversations auto-sort by recent activity

### UI/UX
- [ ] Smooth animations on open/close
- [ ] Auto-scroll to latest message
- [ ] Text input auto-resizes with content
- [ ] Responsive on mobile devices
- [ ] Icons are visible and properly styled
- [ ] Colors match gradient theme

---

## 🐛 Troubleshooting

### Icons Not Showing
**Problem:** Buttons show squares or missing icons

**Solution:**
1. Check browser console for Font Awesome loading errors
2. Verify Font Awesome CDN link in header:
   ```html
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   ```
3. Clear browser cache (Ctrl+Shift+R)

### Chat Button Not Appearing
**Problem:** No chat button at bottom-left

**Solution:**
1. Verify you're logged in
2. Check `includes/footer.php` includes chat widget:
   ```php
   include __DIR__ . '/chat-widget.php';
   ```
3. Check browser console for JavaScript errors

### Photos Not Uploading
**Problem:** Photo upload fails or shows error

**Solution:**
1. Check `uploads/chat_attachments/` directory exists
2. Verify directory permissions (755)
3. Check PHP settings:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```
4. Test with smaller image (under 5MB)

### Messages Not Sending
**Problem:** Messages don't appear or show error

**Solution:**
1. Check database connection
2. Verify `chat_messages` table exists
3. Check browser console for API errors
4. Test API endpoint directly: `/api/chat.php?action=get_users`
5. Verify session is active (user is logged in)

### Emoji Picker Not Opening
**Problem:** Clicking emoji button does nothing

**Solution:**
1. Check browser console for JavaScript errors
2. Verify `chat-widget.js` is loaded
3. Check z-index of emoji picker (should be 10000)
4. Try clicking outside chat to reset, then try again

---

## 📊 Database Tables Created

After migration, these tables are created:

### chat_messages
- Stores all messages with sender/receiver mapping
- Supports text messages and image attachments
- Tracks read status and timestamps

### chat_typing_status
- Tracks real-time typing indicators
- Auto-expires after 10 seconds

### chat_conversations (optional)
- Optimization table for quick access
- Tracks last message and unread counts

---

## 🔒 Security Features Included

✅ **SQL Injection Protection** - All queries use prepared statements
✅ **XSS Prevention** - Input sanitization and output escaping
✅ **File Upload Security** - MIME type validation, size limits
✅ **Session Validation** - Every API call checks authentication
✅ **Authorization** - Users can only access their own messages
✅ **Secure Filenames** - Random names prevent direct access
✅ **Event Logging** - Important actions logged for audit

---

## 📱 Browser Compatibility

✅ Chrome 90+
✅ Firefox 88+
✅ Safari 14+
✅ Edge 90+
✅ Mobile browsers (iOS Safari, Chrome Mobile)

---

## 🎉 Success!

If all checkboxes above are checked, your chat system is working perfectly!

### What's Next?

1. **Customize Colors** - Edit gradients in chat-widget.php CSS
2. **Adjust Position** - Move chat button if needed
3. **Add More Emojis** - Expand emoji list in chat-widget.js
4. **Enable Logging** - Monitor usage and security events
5. **Performance Tune** - Adjust poll intervals for your needs

### Need Help?

- 📖 Read full documentation: `CHAT_SYSTEM_README.md`
- 🔍 Check browser console for errors
- 📝 Review server error logs
- 🧪 Test API endpoints directly

---

**Enjoy your new chat system! 🎊**
