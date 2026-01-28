# Secure Employee File Upload System

## Overview

This implementation provides a secure file upload and management system for employee documents with the following security features:

- **Files stored outside web root** - Prevents direct access via URL
- **UUID filenames** - Original filenames not stored on disk
- **Server-side validation** - MIME type, size, and extension checks
- **Role-based access control** - Only authorized roles can upload/view
- **Audit logging** - All operations logged with user, IP, timestamp
- **Malware scanning** - Optional Windows Defender or online scanner integration

## Database Tables

### employee_files
Stores metadata for all employee documents:
- `id` - Primary key
- `employee_id` - Employee this file belongs to
- `uploaded_by` - User ID who uploaded
- `original_filename` - Original filename (for display only)
- `stored_filename` - UUID filename on disk
- `file_path` - Relative path from storage root
- `category` - Document category (Personal Records, Contracts, etc.)
- `mime_type` - Validated MIME type
- `size_bytes` - File size
- `storage_driver` - Storage backend (local/minio)
- `created_at`, `updated_at`, `deleted_at` - Timestamps

### file_audit_logs
Audit trail for all file operations:
- `id` - Primary key
- `action` - upload, download, delete, view
- `user_id` - User who performed action
- `employee_id` - Employee whose file was accessed
- `file_id` - File ID (NULL if deleted)
- `ip_address` - Requester IP
- `user_agent` - User agent string
- `success` - Whether action succeeded
- `error_message` - Error if failed
- `created_at` - Timestamp

## Installation

### 1. Run Database Migration

```sql
-- Run the migration file
SOURCE sql/migrations/001_create_employee_files_tables.sql;
```

Or manually execute the SQL in `sql/migrations/001_create_employee_files_tables.sql`.

### 2. Configure Storage Path

Set environment variable or update `config/file_upload.php`:

**Linux:**
```bash
export EMPLOYEE_FILES_STORAGE_PATH=/var/hrdash_storage/employee_docs/
```

**Windows:**
```powershell
$env:EMPLOYEE_FILES_STORAGE_PATH="C:\HRDASH_STORAGE\employee_docs\"
```

**Or in `.env` file:**
```
EMPLOYEE_FILES_STORAGE_PATH=/var/hrdash_storage/employee_docs/
```

### 3. Create Storage Directory

**Linux:**
```bash
sudo mkdir -p /var/hrdash_storage/employee_docs
sudo chown www-data:www-data /var/hrdash_storage/employee_docs
sudo chmod 755 /var/hrdash_storage/employee_docs
```

**Windows:**
```powershell
New-Item -ItemType Directory -Path "C:\HRDASH_STORAGE\employee_docs" -Force
```

### 4. Configure File Upload Limits

Update `config/file_upload.php` or set environment variables:

```env
MAX_UPLOAD_SIZE_MB=20
ENABLE_MALWARE_SCAN=true
MALWARE_SCAN_METHOD=windows_defender  # or 'online' or 'none'
```

## API Endpoints

### Upload File
**POST** `/api/employee_files.php?action=upload`

**Form Data:**
- `employee_id` (int) - Employee ID
- `category` (string) - Category: Personal Records, Contracts, Government IDs, Certifications, Other
- `file` (file) - File to upload

**Response:**
```json
{
  "success": true,
  "file_id": 123,
  "message": "File uploaded successfully"
}
```

**Error Codes:**
- `400` - Bad Request (invalid employee ID, no file)
- `403` - Forbidden (insufficient permissions)
- `413` - Payload Too Large (file too big)
- `415` - Unsupported Media Type (invalid file type)
- `500` - Internal Server Error

### Download File
**GET** `/api/employee_files.php?action=download&file_id={id}`

**Response:** File stream with appropriate headers

**Error Codes:**
- `401` - Unauthorized
- `403` - Forbidden
- `404` - File not found
- `500` - Internal Server Error

### Delete File
**POST** `/api/employee_files.php?action=delete&file_id={id}`

**Response:**
```json
{
  "success": true,
  "message": "File deleted successfully"
}
```

### List Files
**GET** `/api/employee_files.php?action=list&employee_id={id}&page={page}&limit={limit}`

**Response:**
```json
{
  "success": true,
  "files": [...],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 50,
    "pages": 3
  }
}
```

## Security Features

### 1. File Storage Outside Web Root
Files are stored in a directory outside the web root (e.g., `/var/hrdash_storage/`), preventing direct URL access.

### 2. UUID Filenames
Original filenames are replaced with UUIDs on disk. Original names are stored only in the database for display.

### 3. Server-Side Validation
- **MIME type validation** using `finfo_file()` (not trusting client)
- **Extension allowlist**: pdf, jpg, jpeg, png, docx
- **Size limits**: Configurable (default 20MB)
- **Blocked types**: php, exe, js, bat, sh, html

### 4. Access Control
- **Upload roles**: super_admin, hr_admin, hr, admin
- **View roles**: super_admin, hr_admin, hr, admin, accounting, operation
- **Employee access**: Users can only access files for employees they're authorized to view

### 5. Audit Logging
All file operations (upload, download, delete) are logged with:
- User ID
- Employee ID
- File ID
- IP address
- User agent
- Success/failure status
- Error messages

### 6. Malware Scanning
Optional malware scanning:
- **Windows Defender**: Automatic scan on Windows systems
- **Online scanner**: VirusTotal API (requires API key)
- **Disabled**: Set `ENABLE_MALWARE_SCAN=false`

## Configuration

### Allowed File Types
Edit `config/file_upload.php`:

```php
'allowed_mime_types' => [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
],
```

### Maximum File Size
```php
'max_file_size' => 20 * 1024 * 1024, // 20MB
```

Or via environment:
```env
MAX_UPLOAD_SIZE_MB=20
```

### Storage Path
```php
'storage_path' => '/var/hrdash_storage/employee_docs/',
```

## Users Table

**No changes needed** to the `users` table. It already has all required fields:
- `id` - Primary key
- `role` - User role (used for permissions)
- All other existing fields remain unchanged

## Testing

### Test Upload
1. Login as HR Admin
2. Go to Documents page
3. Click "Upload" button
4. Select employee, category, and file
5. Verify file appears in employee folder

### Test Download
1. Click dropdown menu on file
2. Click "Download"
3. Verify file downloads with original filename

### Test Permissions
1. Login as non-HR role
2. Try to access upload endpoint
3. Should receive 403 Forbidden

### Test Validation
1. Try uploading .php file → Should be rejected
2. Try uploading file > 20MB → Should be rejected (413)
3. Try uploading .exe file → Should be rejected

## Error Handling

The system returns appropriate HTTP status codes:
- `401` - Unauthorized (not logged in)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found (file doesn't exist)
- `413` - Payload Too Large (file too big)
- `415` - Unsupported Media Type (invalid file type)
- `500` - Internal Server Error

## File Structure

```
employee_docs/
├── 1/                    # Employee ID 1
│   ├── .htaccess         # Deny web access
│   ├── uuid1.pdf         # UUID filename
│   └── uuid2.jpg
├── 2/                    # Employee ID 2
│   └── uuid3.docx
└── ...
```

## Migration from Old System

If you have an existing `employee_documents` table:

1. **Backup existing data**
2. **Run migration** to create new tables
3. **Migrate data** (optional script):
   ```sql
   INSERT INTO employee_files 
   (employee_id, uploaded_by, original_filename, stored_filename, file_path, category, mime_type, size_bytes, created_at)
   SELECT 
     employee_id,
     uploaded_by,
     filename,
     CONCAT(UUID(), '.', SUBSTRING_INDEX(filename, '.', -1)),
     file_path,
     document_type,
     'application/octet-stream',
     file_size,
     upload_date
   FROM employee_documents;
   ```
4. **Move files** to new storage location
5. **Update UI** to use new API endpoints (already done in `documents.php`)

## Troubleshooting

### Files not uploading
- Check storage directory permissions
- Check PHP `upload_max_filesize` and `post_max_size`
- Check error logs: `error_log()` output

### Permission denied
- Verify user role is in `upload_allowed_roles`
- Check employee access permissions
- Verify session is active

### Files not downloading
- Verify file exists on disk
- Check file path in database
- Verify storage directory is accessible

### Malware scan failing
- Windows: Verify Windows Defender is enabled
- Online: Set `ONLINE_SCANNER_API_KEY` in environment
- Disable: Set `ENABLE_MALWARE_SCAN=false`

## Security Best Practices

1. **Regular backups** of storage directory
2. **Monitor audit logs** for suspicious activity
3. **Review file access** regularly
4. **Keep PHP updated** for security patches
5. **Use HTTPS** for all file operations
6. **Limit file size** to prevent DoS
7. **Regular malware scans** on storage directory

## Support

For issues or questions:
1. Check error logs: `error_log()` output
2. Review audit logs: `file_audit_logs` table
3. Verify configuration: `config/file_upload.php`
4. Test API endpoints directly
