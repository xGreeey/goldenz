# Debugging File Upload Issues

## Common Issues and Solutions

### Issue 1: Form Submits and Page Refreshes
**Symptoms:** Form submits normally, page refreshes, goes back to dashboard

**Causes:**
1. JavaScript event listener not attached
2. Form has `action` attribute causing navigation
3. Page transition system intercepting form

**Solutions Applied:**
- ✅ Added `onsubmit="return false;"` to form
- ✅ Wrapped event listener in `initUploadForm()` function
- ✅ Initialize on DOMContentLoaded and AJAX page loads
- ✅ Added `e.preventDefault()` and `e.stopPropagation()`

### Issue 2: Files Not Saving to Database
**Symptoms:** File uploads but doesn't appear in database

**Causes:**
1. Database table doesn't exist
2. Database insert failing silently
3. Transaction not committed

**Check:**
1. Run migration: `sql/migrations/001_create_employee_files_tables.sql`
2. Check error logs for database errors
3. Verify table exists: `SHOW TABLES LIKE 'employee_files';`

### Issue 3: API Returns Error
**Symptoms:** Upload fails with error message

**Debug Steps:**
1. Open browser console (F12)
2. Check Network tab for API request
3. Check Response tab for error message
4. Check server error logs

**Common Errors:**
- `401 Unauthorized` - Not logged in
- `403 Forbidden` - Insufficient permissions
- `503 Service Unavailable` - Table doesn't exist
- `413 Payload Too Large` - File too big
- `415 Unsupported Media Type` - Invalid file type

## Testing Checklist

1. **Check Browser Console**
   - Open F12 → Console tab
   - Look for JavaScript errors
   - Look for "Upload form submitted" message
   - Look for "Response status" and "Response data"

2. **Check Network Tab**
   - Open F12 → Network tab
   - Submit form
   - Find `employee_files.php?action=upload` request
   - Check Request payload (should have FormData)
   - Check Response (should be JSON)

3. **Check Server Logs**
   - Check PHP error log
   - Look for "File upload error" messages
   - Look for database errors

4. **Check Database**
   ```sql
   -- Check if table exists
   SHOW TABLES LIKE 'employee_files';
   
   -- Check recent uploads
   SELECT * FROM employee_files ORDER BY created_at DESC LIMIT 5;
   
   -- Check audit logs
   SELECT * FROM file_audit_logs ORDER BY created_at DESC LIMIT 5;
   ```

5. **Check Storage Directory**
   ```bash
   # Linux
   ls -la /var/hrdash_storage/employee_docs/
   
   # Windows
   dir C:\HRDASH_STORAGE\employee_docs\
   ```

## Manual API Test

Test the API directly using curl:

```bash
curl -X POST "http://your-domain/api/employee_files.php?action=upload" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -F "employee_id=1" \
  -F "category=Personal Records" \
  -F "file=@/path/to/test.pdf"
```

## Quick Fixes

### If form still submits normally:
1. Check if JavaScript is enabled
2. Check browser console for errors
3. Verify `initUploadForm()` is called
4. Add `console.log('Form found:', document.getElementById('uploadDocumentForm'))` to verify form exists

### If API returns 401:
- Check if session is active
- Verify user is logged in
- Check session cookie

### If API returns 403:
- Check user role in session
- Verify role is in `upload_allowed_roles` config
- Check `config/file_upload.php`

### If API returns 503:
- Run database migration
- Verify `employee_files` table exists

### If file uploads but not in database:
- Check database connection
- Check error logs for SQL errors
- Verify table structure matches migration
