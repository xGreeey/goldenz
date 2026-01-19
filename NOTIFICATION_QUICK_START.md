# Notification System - Quick Start Guide

**Golden Z-5 HR Management System**  
**Replace ALL Browser Alerts with Professional Notifications**

---

## ✅ Installation Complete

The notification system is now globally available across all pages!

### Files Added:
- ✅ `assets/css/notifications.css`
- ✅ `assets/js/notifications.js`
- ✅ Included in `includes/header.php` (all system pages)
- ✅ Included in `landing/index.php` (login page)

---

## 🚀 Usage (Copy & Paste Ready)

### Top-Center System Alerts
**Use for:** Validation errors, system notices, important messages

```javascript
// Simple alerts
GoldenNotify.success('Operation successful!');
GoldenNotify.error('An error occurred');
GoldenNotify.warning('Please review your input');
GoldenNotify.info('System maintenance scheduled');

// With custom message
GoldenNotify.alert('Success', 'Employee added successfully', 'success');
GoldenNotify.alert('Error', 'Failed to save changes', 'error');
```

### Bottom-Right Toast Notifications
**Use for:** Success messages, reminders, announcements

```javascript
// Simple toasts
GoldenNotify.toastSuccess('Changes saved');
GoldenNotify.toastInfo('New message received');
GoldenNotify.toastWarning('Session expires in 5 minutes');
GoldenNotify.toastError('Upload failed');

// With title
GoldenNotify.toast('Your profile has been updated', 'success', 'Update Complete');
```

### Confirmation Dialogs
**Use for:** Delete actions, important confirmations

```javascript
// Simple confirm
GoldenNotify.confirm('Are you sure you want to proceed?').then(confirmed => {
    if (confirmed) {
        // User clicked confirm
        deleteRecord();
    }
});

// Delete confirmation (pre-built)
GoldenNotify.confirmDelete('this employee').then(confirmed => {
    if (confirmed) {
        // Proceed with deletion
    }
});

// Custom confirmation
GoldenNotify.confirm(
    'This action cannot be undone. Continue?',
    'danger',           // Type: warning, danger, info
    'Confirm Action',   // Title
    'Yes, Delete',      // Confirm button
    'Cancel'            // Cancel button
).then(confirmed => {
    if (confirmed) {
        // Delete
    }
});
```

---

## 📝 Migration Examples

### Replace alert()

**Before:**
```javascript
alert('Password must be at least 8 characters long.');
```

**After:**
```javascript
GoldenNotify.error('Password must be at least 8 characters long.');
```

---

### Replace confirm()

**Before:**
```javascript
if (confirm('Delete this post?')) {
    deletePost(id);
}
```

**After:**
```javascript
GoldenNotify.confirmDelete('this post').then(confirmed => {
    if (confirmed) {
        deletePost(id);
    }
});
```

---

### PHP Success Message

**Before:**
```php
echo '<script>alert("Post deleted successfully");</script>';
```

**After:**
```php
echo '<script>GoldenNotify.toastSuccess("Post deleted successfully");</script>';
```

---

### Form Validation

**Before:**
```javascript
if (!username || !password) {
    alert('Please enter both username and password');
    return false;
}
```

**After:**
```javascript
if (!username || !password) {
    GoldenNotify.error('Please enter both username and password', 'Required Fields');
    return false;
}
```

---

### AJAX Success/Error

**Before:**
```javascript
fetch('/api/save')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Saved successfully');
        } else {
            alert('Save failed');
        }
    });
```

**After:**
```javascript
fetch('/api/save')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            GoldenNotify.toastSuccess('Saved successfully');
        } else {
            GoldenNotify.toastError('Save failed');
        }
    });
```

---

## 📊 Files to Update (Priority Order)

### Phase 1: Core Features (Do First)
1. ✅ `landing/index.php` - **Already done**
2. `pages/employees.php` - 2 alert()
3. `pages/posts.php` - 2 alert()
4. `pages/post_assignments.php` - 4 alert()
5. `pages/users.php` - 3 confirm()
6. `landing/reset-password.php` - 2 alert()
7. `landing/forgot-password.php` - 1 alert()

### Phase 2: Admin Features
8. `pages/alerts.php` - 6 alert() + 4 confirm()
9. `pages/system_logs.php` - 2 alert() + 1 confirm()
10. `pages/permissions.php` - 6 alert() + 1 confirm()
11. `pages/profile.php` - 3 alert()
12. `pages/dashboard.php` - 1 alert()

### Phase 3: Remaining
13. All files in `pages/archive/` folder

---

## 🎨 Notification Types

### When to Use Each Type:

| Type | Use For | Position |
|------|---------|----------|
| **System Alert - Success** | Validation passed, operation completed | Top Center |
| **System Alert - Error** | Validation failed, operation failed | Top Center |
| **System Alert - Warning** | Important notices, cautions | Top Center |
| **System Alert - Info** | System information, updates | Top Center |
| **Toast - Success** | Save confirmed, upload complete | Bottom Right |
| **Toast - Info** | New messages, reminders | Bottom Right |
| **Toast - Warning** | Temporary issues, warnings | Bottom Right |
| **Toast - Error** | Background process failed | Bottom Right |
| **Confirmation** | Delete actions, important decisions | Center Modal |

---

## ⚡ Quick Tips

### DO:
✅ Use system alerts for **validation and errors**  
✅ Use toasts for **success messages**  
✅ Use confirmations for **destructive actions**  
✅ Keep messages **short and clear**  
✅ Test the notification before committing  

### DON'T:
❌ Don't show too many notifications at once  
❌ Don't use alerts for trivial messages  
❌ Don't auto-dismiss critical errors  
❌ Don't forget to test on mobile  

---

## 🧪 Test Your Changes

### In Browser Console:
```javascript
// Test system alert
GoldenNotify.success('Test successful!');

// Test toast
GoldenNotify.toastInfo('Test notification');

// Test confirmation
GoldenNotify.confirm('Test confirm?').then(r => console.log(r));
```

---

## 💡 Common Patterns

### Pattern 1: Save with Feedback
```javascript
function saveForm() {
    const data = getFormData();
    
    fetch('/api/save', {
        method: 'POST',
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            GoldenNotify.toastSuccess('Changes saved successfully');
        } else {
            GoldenNotify.error(result.message || 'Failed to save');
        }
    })
    .catch(error => {
        GoldenNotify.error('An error occurred while saving');
    });
}
```

### Pattern 2: Delete with Confirmation
```javascript
function deleteItem(id, name) {
    GoldenNotify.confirmDelete(name).then(confirmed => {
        if (!confirmed) return;
        
        fetch(`/api/delete/${id}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    GoldenNotify.toastSuccess('Item deleted successfully');
                    location.reload();
                } else {
                    GoldenNotify.toastError('Failed to delete item');
                }
            });
    });
}
```

### Pattern 3: Form Validation
```javascript
function validateForm() {
    const errors = [];
    
    if (!username.value) errors.push('Username is required');
    if (!email.value) errors.push('Email is required');
    
    if (errors.length > 0) {
        GoldenNotify.error(errors.join('<br>'), 'Validation Failed');
        return false;
    }
    
    return true;
}
```

### Pattern 4: PHP Session Message
```php
<?php
// In your processing script
$_SESSION['notification'] = [
    'type' => 'success',
    'message' => 'Employee added successfully'
];
header('Location: employees.php');
?>

<!-- In employees.php -->
<?php if (isset($_SESSION['notification'])): ?>
<script>
const notif = <?= json_encode($_SESSION['notification']) ?>;
GoldenNotify[`toast${notif.type.charAt(0).toUpperCase() + notif.type.slice(1)}`](notif.message);
</script>
<?php unset($_SESSION['notification']); endif; ?>
```

---

## 🆘 Need Help?

1. Check `NOTIFICATION_SYSTEM_GUIDE.md` for full documentation
2. Test in browser console: `console.log(GoldenNotify)`
3. Check browser console for errors
4. Verify CSS and JS files are loaded

---

## 📈 Progress Tracker

Track your migration progress:

- [ ] Phase 1: Core features (7 files)
- [ ] Phase 2: Admin features (5 files)
- [ ] Phase 3: Archive pages (7 files)
- [ ] Test all notifications
- [ ] Mobile testing
- [ ] Documentation update

---

**Status:** ✅ **READY TO USE**  
**Total Alert() Calls to Replace:** 71  
**Total Confirm() Calls to Replace:** 27  
**Start with:** `landing/index.php` (already done) → `pages/employees.php`

---

🎯 **Goal:** Replace all browser alerts system-wide with beautiful, professional notifications!
