# Database Backup Guide

## 🧪 Manual Testing

### Run Backup Manually

To test the backup script manually and see the output in real-time:

```powershell
docker exec hr_web php /var/www/html/cron/backup-to-minio.php
```

**Expected Output:**
```
[2026-01-20 16:16:23] === Starting scheduled database backup ===
[2026-01-20 16:16:23] Creating database backup...
[2026-01-20 16:16:23] Backup created successfully: backup_goldenz_hr_2026-01-20_161623.sql
[2026-01-20 16:16:23] Backup size: 66.48 KB
[2026-01-20 16:16:23] ✓ Backup compressed: backup_goldenz_hr_2026-01-20_161623.sql.gz (9.87 KB, 85.2% reduction)
[2026-01-20 16:16:23] ✓ Backup uploaded to MinIO: db-backups/backup_goldenz_hr_2026-01-20_161623.sql.gz
[2026-01-20 16:16:24] === Backup completed successfully ===
```

### Check Backup Logs

View the backup log file:

```powershell
# View last 20 lines
docker exec hr_web tail -20 /var/www/html/storage/logs/backup-cron.log

# Follow logs in real-time (press Ctrl+C to exit)
docker exec hr_web tail -f /var/www/html/storage/logs/backup-cron.log
```

### Verify Backup Files

Check local backup files:

```powershell
# List backup files
docker exec hr_web ls -lh /var/www/html/storage/backups/

# List compressed files
docker exec hr_web ls -lh /var/www/html/storage/backups/*.gz
```

### Verify MinIO Upload

Check if backups are in MinIO:

```powershell
# List backups in MinIO
docker exec hr_web mc alias set backup http://minio:9000 goldenz SUOMYNONA
docker exec hr_web mc ls backup/goldenz-uploads/db-backups/
```

Or use the MinIO Console:
1. Go to: `http://localhost:9001`
2. Login: `goldenz` / `SUOMYNONA`
3. Open bucket: `goldenz-uploads`
4. Navigate to: `db-backups/` folder

---

## ⏰ Setting Up Cron (Automatic Backups)

### Option 1: Verify Existing Cron Setup

The Dockerfile already has cron configured. Check if it's working:

```powershell
# Check if cron service is running
docker exec hr_web service cron status

# Check if cron job exists
docker exec hr_web cat /etc/cron.d/db-backup

# Check cron logs
docker exec hr_web tail -f /var/log/cron.log
```

### Option 2: Manual Cron Setup (If Not Working)

If cron is not running, set it up manually:

#### Step 1: Enter the container

```powershell
docker exec -it hr_web bash
```

#### Step 2: Start cron service

```bash
service cron start
```

#### Step 3: Create/Edit crontab

```bash
# Edit crontab for www-data user
crontab -u www-data -e
```

Add this line (runs every 30 minutes):
```
*/30 * * * * /usr/local/bin/php /var/www/html/cron/backup-to-minio.php >> /var/www/html/storage/logs/backup-cron.log 2>&1
```

Save and exit (in nano: `Ctrl+X`, then `Y`, then `Enter`)

#### Step 4: Verify cron job

```bash
# List cron jobs for www-data
crontab -u www-data -l

# Exit container
exit
```

### Option 3: Fix Dockerfile Entrypoint (Recommended)

If cron isn't starting automatically, fix the entrypoint script:

```powershell
# Check current entrypoint
docker exec hr_web cat /usr/local/bin/docker-entrypoint.sh
```

If it's not working, you may need to rebuild the container:

```powershell
docker-compose down
docker-compose build web
docker-compose up -d
```

Then verify cron is running:

```powershell
docker exec hr_web service cron status
```

---

## 📅 Cron Schedule Examples

Edit the cron job to change the backup frequency:

### Every 30 minutes (current)
```
*/30 * * * * /usr/local/bin/php /var/www/html/cron/backup-to-minio.php >> /var/www/html/storage/logs/backup-cron.log 2>&1
```

### Every 15 minutes
```
*/15 * * * * /usr/local/bin/php /var/www/html/cron/backup-to-minio.php >> /var/www/html/storage/logs/backup-cron.log 2>&1
```

### Every hour
```
0 * * * * /usr/local/bin/php /var/www/html/cron/backup-to-minio.php >> /var/www/html/storage/logs/backup-cron.log 2>&1
```

### Every 6 hours
```
0 */6 * * * /usr/local/bin/php /var/www/html/cron/backup-to-minio.php >> /var/www/html/storage/logs/backup-cron.log 2>&1
```

### Daily at 2 AM
```
0 2 * * * /usr/local/bin/php /var/www/html/cron/backup-to-minio.php >> /var/www/html/storage/logs/backup-cron.log 2>&1
```

### Daily at midnight
```
0 0 * * * /usr/local/bin/php /var/www/html/cron/backup-to-minio.php >> /var/www/html/storage/logs/backup-cron.log 2>&1
```

---

## 🔍 Troubleshooting

### Cron Not Running

1. **Check cron status:**
   ```powershell
   docker exec hr_web service cron status
   ```

2. **Start cron manually:**
   ```powershell
   docker exec hr_web service cron start
   ```

3. **Check if cron job exists:**
   ```powershell
   docker exec hr_web cat /etc/cron.d/db-backup
   ```

4. **Check cron logs:**
   ```powershell
   docker exec hr_web tail -50 /var/log/cron.log
   ```

### Backups Not Creating

1. **Test backup manually:**
   ```powershell
   docker exec hr_web php /var/www/html/cron/backup-to-minio.php
   ```

2. **Check PHP errors:**
   ```powershell
   docker exec hr_web tail -50 /var/log/apache2/error.log
   ```

3. **Check backup log:**
   ```powershell
   docker exec hr_web cat /var/www/html/storage/logs/backup-cron.log
   ```

### Backups Not Uploading to MinIO

1. **Test MinIO connection:**
   ```powershell
   docker exec hr_web mc alias set test http://minio:9000 goldenz SUOMYNONA
   docker exec hr_web mc ls test/
   ```

2. **Check storage configuration:**
   ```powershell
   docker exec hr_web php -r "require '/var/www/html/includes/storage.php'; echo 'Storage driver: ' . get_storage_driver() . PHP_EOL;"
   ```
   Should output: `Storage driver: minio`

3. **Check MinIO container:**
   ```powershell
   docker ps | Select-String minio
   ```

---

## 📊 Monitoring Backups

### Quick Status Check

Create a simple script to check backup status:

```powershell
# Check last backup time
docker exec hr_web ls -lt /var/www/html/storage/backups/*.sql | Select-Object -First 1

# Check last backup in MinIO
docker exec hr_web mc ls backup/goldenz-uploads/db-backups/ | Select-Object -First 1

# Check backup log
docker exec hr_web tail -5 /var/www/html/storage/logs/backup-cron.log
```

### View All Backups in MinIO

```powershell
docker exec hr_web mc ls backup/goldenz-uploads/db-backups/ --recursive
```

---

## ✅ Quick Test Checklist

- [ ] Manual backup runs successfully
- [ ] Backup file is created locally
- [ ] Backup is compressed (.sql.gz)
- [ ] Compressed file is uploaded to MinIO
- [ ] Cron service is running
- [ ] Cron job is configured
- [ ] Backup log shows successful runs
- [ ] Backups appear in MinIO console

---

## 🎯 Summary

**Manual Testing:**
```powershell
docker exec hr_web php /var/www/html/cron/backup-to-minio.php
```

**Check Logs:**
```powershell
docker exec hr_web tail -f /var/www/html/storage/logs/backup-cron.log
```

**Verify Cron:**
```powershell
docker exec hr_web service cron status
docker exec hr_web cat /etc/cron.d/db-backup
```

**View Backups in MinIO:**
- Web: `http://localhost:9001` → `goldenz-uploads` → `db-backups/`
- CLI: `docker exec hr_web mc ls backup/goldenz-uploads/db-backups/`
