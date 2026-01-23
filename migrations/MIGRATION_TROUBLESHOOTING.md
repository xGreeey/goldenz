# Chat System Migration Troubleshooting

## Quick Fix Guide

### Error: "Failed to add foreign key constraint"

This error occurs when the `users` table doesn't have a proper primary key on the `id` column.

#### Solution 1: Use Migration Without Foreign Keys (RECOMMENDED)

The easiest solution is to use the version without foreign key constraints:

```sql
-- In phpMyAdmin or MySQL command line:
SOURCE /path/to/migrations/add_chat_system_no_fk.sql
```

**Note:** The chat system works perfectly fine without foreign keys. They're only for database-level referential integrity.

#### Solution 2: Fix Users Table First

If you want foreign keys, first check and fix the `users` table:

**Step 1: Check current structure**
```sql
SHOW CREATE TABLE users;
```

**Step 2: Check if id has PRIMARY KEY**
```sql
SHOW INDEXES FROM users WHERE Key_name = 'PRIMARY';
```

**Step 3: Add PRIMARY KEY if missing**
```sql
-- If id is not a PRIMARY KEY, fix it:
ALTER TABLE users ADD PRIMARY KEY (id);

-- If id is not AUTO_INCREMENT, fix it:
ALTER TABLE users MODIFY id INT NOT NULL AUTO_INCREMENT PRIMARY KEY;
```

**Step 4: Now run the main migration**
```sql
SOURCE /path/to/migrations/add_chat_system.sql
```

### Error: "Incorrect table definition; there can be only one auto column"

This means the `users` table structure has issues.

#### Solution: Skip Users Table Modification

The updated `add_chat_system.sql` no longer modifies the `users` table. Just run it directly:

```sql
SOURCE /path/to/migrations/add_chat_system.sql
```

If you still get foreign key errors, use the no-FK version:

```sql
SOURCE /path/to/migrations/add_chat_system_no_fk.sql
```

## Step-by-Step Migration (Foolproof Method)

### Method 1: Using phpMyAdmin (Easiest)

1. **Log in to phpMyAdmin**
2. **Select `goldenz_hr` database**
3. **Click SQL tab**
4. **Copy the contents** of `migrations/add_chat_system_no_fk.sql`
5. **Paste into SQL box**
6. **Click "Go"**
7. **Verify success** - you should see "3 tables created"

### Method 2: Using MySQL Command Line

```bash
# Log in to MySQL
mysql -u root -p goldenz_hr

# Run the no-FK migration
source C:/docker-projects/goldenz_hr_system/src/migrations/add_chat_system_no_fk.sql

# Exit MySQL
exit
```

### Method 3: Using PHP Migration Script

```bash
# Navigate to project directory
cd C:/docker-projects/goldenz_hr_system/src

# Run migration script
php migrations/run_chat_migration.php
```

Or via browser (after logging in as super_admin):
```
http://localhost/migrations/run_chat_migration.php
```

## Verify Migration Success

After running migration, verify tables were created:

```sql
-- Check if tables exist
SHOW TABLES LIKE 'chat_%';

-- Should show:
-- chat_conversations
-- chat_messages
-- chat_typing_status

-- Check table structures
DESCRIBE chat_messages;
DESCRIBE chat_typing_status;
DESCRIBE chat_conversations;
```

Or use the test page (after logging in as super_admin):
```
http://localhost/test_chat_system.php
```

## Common Issues and Solutions

### Issue: "Table already exists"

**Solution:** Tables already created, migration successful! Just verify:
```sql
SELECT COUNT(*) FROM chat_messages;
```

### Issue: "Access denied"

**Solution:** Check database credentials:
```php
// In config/database.php or .env file
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=your_password
DB_DATABASE=goldenz_hr
```

### Issue: Foreign key constraint fails after migration

**Solution:** Drop and recreate without foreign keys:

```sql
-- Drop existing tables
DROP TABLE IF EXISTS chat_conversations;
DROP TABLE IF EXISTS chat_typing_status;
DROP TABLE IF EXISTS chat_messages;

-- Re-run migration without FK
SOURCE /path/to/migrations/add_chat_system_no_fk.sql
```

### Issue: "SQLSTATE[HY000]: General error: 1005"

**Solution:** This is a foreign key error. Use the no-FK version:
```sql
SOURCE /path/to/migrations/add_chat_system_no_fk.sql
```

## Manual Table Creation (Last Resort)

If all else fails, create tables manually in phpMyAdmin:

### 1. Create chat_messages table

```sql
CREATE TABLE `chat_messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_sender_id` (`sender_id`),
  INDEX `idx_receiver_id` (`receiver_id`),
  INDEX `idx_conversation` (`sender_id`, `receiver_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Create chat_typing_status table

```sql
CREATE TABLE `chat_typing_status` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `recipient_id` INT NOT NULL,
  `is_typing` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_recipient` (`user_id`, `recipient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. Create chat_conversations table

```sql
CREATE TABLE `chat_conversations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user1_id` INT NOT NULL,
  `user2_id` INT NOT NULL,
  `last_message_id` INT DEFAULT NULL,
  `last_message_at` TIMESTAMP NULL DEFAULT NULL,
  `user1_unread_count` INT NOT NULL DEFAULT 0,
  `user2_unread_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_conversation` (`user1_id`, `user2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## After Successful Migration

1. **Test the system:**
   - Visit: `http://localhost/test_chat_system.php`
   - All tests should pass (or only FK tests may fail if using no-FK version)

2. **Access chat:**
   - Log in to HR admin portal
   - Click "Messages" in sidebar
   - Select a user and send a test message

3. **Verify in database:**
   ```sql
   SELECT * FROM chat_messages;
   SELECT COUNT(*) as total_messages FROM chat_messages;
   ```

## Need More Help?

- Check PHP error log: `storage/logs/error.log`
- Check security log: `storage/logs/security.log`
- Check browser console (F12) for JavaScript errors
- Review deployment guide: `CHAT_DEPLOYMENT_GUIDE.md`

## Summary: Which Migration File to Use?

| Scenario | Use This File |
|----------|---------------|
| **First time setup** (Recommended) | `add_chat_system_no_fk.sql` |
| Want foreign keys & no issues | `add_chat_system.sql` |
| Getting FK errors | `add_chat_system_no_fk.sql` |
| Want to use PHP script | `php migrations/run_chat_migration.php` |

**Best Practice:** Always use `add_chat_system_no_fk.sql` - it's simpler and the chat system works identically with or without foreign keys.
