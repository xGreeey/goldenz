# Database Backup Restore Guide

## ✅ Fixed Issues

The backup system has been updated to fix restore issues:

1. **Foreign Key Constraints**: Backups now disable foreign key checks during restore
2. **Table Locking**: Removed LOCK TABLES commands that caused "table doesn't exist" errors
3. **Transaction Handling**: Proper transaction management for consistent restores

## 📥 How to Restore a Backup

### Method 1: Using phpMyAdmin (Easiest)

1. **Download the backup from MinIO:**
   - Go to: `http://localhost:9001`
   - Login: `goldenz` / `SUOMYNONA`
   - Navigate to: `goldenz-uploads` → `db-backups/`
   - Download the `.sql.gz` file

2. **Extract the .gz file:**
   - Use 7-Zip, WinRAR, or any archive tool to extract the `.sql` file

3. **Import via phpMyAdmin:**
   - Go to: `http://localhost:8080`
   - Login with your MySQL credentials
   - Select database: `goldenz_hr`
   - Click "Import" tab
   - Choose the extracted `.sql` file
   - Click "Go"

   **Note:** The backup file already includes `SET FOREIGN_KEY_CHECKS=0` at the start, so it will restore without foreign key errors.

### Method 2: Using Command Line

1. **Download and extract backup:**
   ```powershell
   # Download from MinIO (if needed)
   docker exec hr_web mc cp backup/goldenz-uploads/db-backups/backup_goldenz_hr_2026-01-20_162831.sql.gz /tmp/
   
   # Extract
   docker exec hr_web gunzip /tmp/backup_goldenz_hr_2026-01-20_162831.sql.gz
   ```

2. **Restore the database:**
   ```powershell
   # Copy SQL file to container
   docker cp backup_file.sql hr_web:/tmp/restore.sql
   
   # Restore using mysql command
   docker exec -i hr_db mysql -uroot -pSuomynona027 goldenz_hr < /tmp/restore.sql
   ```

   Or from inside the container:
   ```powershell
   docker exec -it hr_web bash
   mysql -h db -uroot -pSuomynona027 goldenz_hr < /tmp/restore.sql
   ```

### Method 3: Direct from Container

```powershell
# Extract and restore in one command
docker exec hr_web sh -c "gunzip -c /var/www/html/storage/backups/backup_goldenz_hr_2026-01-20_162831.sql.gz | mysql -h db -uroot -pSuomynona027 goldenz_hr"
```

## 🔍 Verify Restore

After restoring, verify the database:

```powershell
# Check tables
docker exec hr_db mysql -uroot -pSuomynona027 -e "USE goldenz_hr; SHOW TABLES;"

# Check record counts
docker exec hr_db mysql -uroot -pSuomynona027 -e "USE goldenz_hr; SELECT COUNT(*) FROM employees; SELECT COUNT(*) FROM users;"
```

## ⚠️ Troubleshooting

### Error: "Table doesn't exist" during restore

**Fixed!** The backup now includes `SET FOREIGN_KEY_CHECKS=0` at the start, which allows tables to be created in any order.

If you still see this error:
1. Make sure you're using a **new backup** (created after the fix)
2. The backup file should start with `SET FOREIGN_KEY_CHECKS=0;`
3. Check that the backup file is not corrupted

### Error: "Foreign key constraint is incorrectly formed"

**Fixed!** The backup disables foreign key checks during restore.

If you still see this:
1. Ensure you're restoring to an **empty database** or drop all tables first
2. Use a backup created after the fix
3. The backup should have `SET FOREIGN_KEY_CHECKS=0;` at the beginning

### Error: "LOCK TABLES ... WRITE" failed

**Fixed!** LOCK TABLES commands have been removed from backups.

If you see this:
- You're using an **old backup** created before the fix
- Create a new backup and use that instead

### Backup file is corrupted

If the backup file appears corrupted:

1. **Check file integrity:**
   ```powershell
   # Check if file can be extracted
   docker exec hr_web gunzip -t /var/www/html/storage/backups/backup_goldenz_hr_YYYY-MM-DD_HHMMSS.sql.gz
   ```

2. **Create a fresh backup:**
   ```powershell
   docker exec hr_web php /var/www/html/cron/backup-to-minio.php
   ```

3. **Verify backup structure:**
   ```powershell
   # Check first 20 lines
   docker exec hr_web sh -c "gunzip -c /var/www/html/storage/backups/backup_goldenz_hr_*.sql.gz | head -20"
   
   # Should show:
   # SET FOREIGN_KEY_CHECKS=0;
   # SET UNIQUE_CHECKS=0;
   # SET AUTOCOMMIT=0;
   ```

## 📋 Backup File Structure

A properly formatted backup file should have:

**At the start:**
```sql
-- Golden Z-5 HR System Database Backup
-- Generated: YYYY-MM-DD HH:MM:SS
-- Database: goldenz_hr

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS=0;
SET UNIQUE_CHECKS=0;
SET AUTOCOMMIT=0;
```

**At the end:**
```sql
SET FOREIGN_KEY_CHECKS=1;
SET UNIQUE_CHECKS=1;
COMMIT;
SET AUTOCOMMIT=1;
```

**Should NOT contain:**
- `LOCK TABLES ... WRITE;`
- `UNLOCK TABLES;`

## 🎯 Quick Restore Checklist

- [ ] Download backup from MinIO
- [ ] Extract .gz file to .sql
- [ ] Verify backup starts with `SET FOREIGN_KEY_CHECKS=0;`
- [ ] Verify backup has no `LOCK TABLES` commands
- [ ] Restore to empty database (or drop existing tables)
- [ ] Verify restore completed successfully
- [ ] Check table counts match expectations

## 💡 Best Practices

1. **Always test restores** on a development database first
2. **Keep multiple backups** - don't rely on a single backup
3. **Verify backups regularly** by testing restores
4. **Document restore procedures** for your team
5. **Monitor backup logs** to ensure backups are running successfully

## 📞 Need Help?

If you encounter issues:

1. Check the backup log: `docker exec hr_web tail -50 /var/www/html/storage/logs/backup-cron.log`
2. Verify backup file structure (see above)
3. Create a fresh backup and try again
4. Check MySQL error logs: `docker exec hr_db tail -50 /var/log/mysql/error.log`
