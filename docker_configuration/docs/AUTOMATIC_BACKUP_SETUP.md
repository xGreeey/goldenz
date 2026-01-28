# Automatic Database Backup to MinIO Setup

## What's Been Configured

✅ **Backup Function Updated**: The `create_database_backup()` function now automatically uploads to MinIO after creating the backup.

✅ **Cron Script Created**: `src/cron/backup-to-minio.php` - Runs backups and uploads to MinIO

✅ **Dockerfile Updated**: Cron service will be installed and configured

## Setup Steps

### 1. Rebuild the Container

```powershell
docker-compose down
docker-compose build web
docker-compose up -d
```

### 2. Verify Cron is Running

```powershell
docker exec hr_web service cron status
```

Should show: `cron is running`

### 3. Test Manual Backup

```powershell
docker exec hr_web php /var/www/html/cron/backup-to-minio.php
```

### 4. Check Logs

```powershell
docker exec hr_web tail -f /var/www/html/storage/logs/backup-cron.log
```

## How It Works

### Backup Schedule
- **Frequency**: Every 30 minutes
- **Cron Expression**: `*/30 * * * *`
- **Location in MinIO**: `goldenz-uploads/db-backups/`

### Process Flow
1. Cron triggers `backup-to-minio.php` every 30 minutes
2. Script calls `create_database_backup()`
3. Backup is created locally using `mysqldump`
4. Backup is automatically uploaded to MinIO
5. Old backups are cleaned up (retention: 90 days)
6. All actions are logged

### Backup File Format
- **Filename**: `backup_goldenz_hr_YYYY-MM-DD_HHMMSS.sql`
- **Example**: `backup_goldenz_hr_2026-01-20_153000.sql`

## Viewing Backups

### MinIO Console
1. Go to: `http://localhost:9001`
2. Login: `goldenz` / `SUOMYNONA`
3. Open bucket: `goldenz-uploads`
4. Navigate to: `db-backups/` folder
5. You'll see all backup files listed

### Via Command Line
```powershell
docker exec hr_web mc alias set backup http://minio:9000 goldenz SUOMYNONA
docker exec hr_web mc ls backup/goldenz-uploads/db-backups/
```

## Monitoring

### Check Backup Logs
```powershell
# View recent logs
docker exec hr_web tail -20 /var/www/html/storage/logs/backup-cron.log

# Follow logs in real-time
docker exec hr_web tail -f /var/www/html/storage/logs/backup-cron.log
```

### Check Cron Logs
```powershell
docker exec hr_web tail -f /var/log/cron.log
```

### Verify Last Backup
```powershell
docker exec hr_web mc ls backup/goldenz-uploads/db-backups/ | tail -1
```

## Changing Backup Schedule

To change from 30 minutes to a different interval, edit the Dockerfile:

```dockerfile
# Every 30 minutes (current)
RUN echo "*/30 * * * * www-data ..." > /etc/cron.d/db-backup

# Every hour
RUN echo "0 * * * * www-data ..." > /etc/cron.d/db-backup

# Every 15 minutes
RUN echo "*/15 * * * * www-data ..." > /etc/cron.d/db-backup

# Daily at 2 AM
RUN echo "0 2 * * * www-data ..." > /etc/cron.d/db-backup
```

Then rebuild:
```powershell
docker-compose build web
docker-compose up -d
```

## Troubleshooting

### Backups Not Running

1. **Check if cron is running:**
   ```powershell
   docker exec hr_web service cron status
   ```

2. **Start cron if not running:**
   ```powershell
   docker exec hr_web service cron start
   ```

3. **Check cron job:**
   ```powershell
   docker exec hr_web cat /etc/cron.d/db-backup
   ```

4. **Test backup manually:**
   ```powershell
   docker exec hr_web php /var/www/html/cron/backup-to-minio.php
   ```

### Backups Not Uploading to MinIO

1. **Check storage configuration:**
   - Verify `src/config/storage.php` has `'default' => 'minio'`
   
2. **Test MinIO connection:**
   ```powershell
   docker exec hr_web mc alias set test http://minio:9000 goldenz SUOMYNONA
   docker exec hr_web mc ls test/
   ```

3. **Check error logs:**
   ```powershell
   docker logs hr_web | Select-String -Pattern "MinIO|backup|upload"
   ```

## Alternative: Use Existing db_backup Container

You already have a `db_backup` container that does this! It's configured in `docker-compose.yml` and runs every 30 minutes, uploading to the `db-backups` bucket.

The PHP solution gives you more control and integrates with your PHP application, but the existing container solution also works perfectly.
