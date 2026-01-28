# Quick Backup Setup Guide

## 🚀 Quick Start

### Manual Testing (Test First!)

```powershell
# Run backup manually to test
docker exec hr_web php /var/www/html/cron/backup-to-minio.php
```

You should see output like:
```
[2026-01-20 16:16:23] === Starting scheduled database backup ===
[2026-01-20 16:16:23] Creating database backup...
[2026-01-20 16:16:23] Backup created successfully: backup_goldenz_hr_2026-01-20_161623.sql
[2026-01-20 16:16:23] Backup size: 66.48 KB
[2026-01-20 16:16:23] ✓ Backup compressed: backup_goldenz_hr_2026-01-20_161623.sql.gz (9.87 KB, 85.2% reduction)
[2026-01-20 16:16:23] ✓ Backup uploaded to MinIO: db-backups/backup_goldenz_hr_2026-01-20_161623.sql.gz
[2026-01-20 16:16:24] === Backup completed successfully ===
```

---

## ⚙️ Setup Cron (Automatic Backups)

### Option 1: Use Setup Script (Easiest)

```powershell
# Run the setup script
.\setup-cron.ps1
```

This will:
- Install cron
- Create the cron job (every 30 minutes)
- Start the cron service

### Option 2: Manual Setup

#### Step 1: Enter the container
```powershell
docker exec -it hr_web bash
```

#### Step 2: Install cron
```bash
apt-get update
apt-get install -y cron
apt-get clean
```

#### Step 3: Create cron job
```bash
echo "*/30 * * * * www-data /usr/local/bin/php /var/www/html/cron/backup-to-minio.php >> /var/www/html/storage/logs/backup-cron.log 2>&1" > /etc/cron.d/db-backup
chmod 0644 /etc/cron.d/db-backup
```

#### Step 4: Start cron
```bash
service cron start
```

#### Step 5: Verify
```bash
# Check cron job
cat /etc/cron.d/db-backup

# Check if cron is running
ps aux | grep cron

# Exit container
exit
```

---

## ✅ Verify It's Working

### Check Cron Status
```powershell
# Check if cron is running
docker exec hr_web ps aux | Select-String cron

# Check cron job file
docker exec hr_web cat /etc/cron.d/db-backup
```

### Check Backup Logs
```powershell
# View recent backups
docker exec hr_web tail -20 /var/www/html/storage/logs/backup-cron.log

# Follow logs in real-time
docker exec hr_web tail -f /var/www/html/storage/logs/backup-cron.log
```

### Check MinIO Backups
```powershell
# List backups in MinIO
docker exec hr_web mc alias set backup http://minio:9000 goldenz SUOMYNONA
docker exec hr_web mc ls backup/goldenz-uploads/db-backups/
```

Or visit: `http://localhost:9001` → Login → `goldenz-uploads` → `db-backups/`

---

## 📅 Change Backup Schedule

To change from every 30 minutes to a different schedule:

1. **Edit cron job:**
   ```powershell
   docker exec -it hr_web bash
   nano /etc/cron.d/db-backup
   ```

2. **Change the schedule:**
   - Every 15 minutes: `*/15 * * * *`
   - Every hour: `0 * * * *`
   - Daily at 2 AM: `0 2 * * *`
   - Daily at midnight: `0 0 * * *`

3. **Restart cron:**
   ```bash
   service cron restart
   exit
   ```

---

## 🔧 Troubleshooting

### Cron Not Running
```powershell
# Start cron manually
docker exec hr_web service cron start

# Check cron logs
docker exec hr_web tail -50 /var/log/cron.log
```

### Backups Not Creating
```powershell
# Test manually first
docker exec hr_web php /var/www/html/cron/backup-to-minio.php

# Check for errors
docker exec hr_web tail -50 /var/www/html/storage/logs/backup-cron.log
```

### Backups Not Uploading to MinIO
```powershell
# Test MinIO connection
docker exec hr_web mc alias set test http://minio:9000 goldenz SUOMYNONA
docker exec hr_web mc ls test/

# Check storage driver
docker exec hr_web php -r "require '/var/www/html/includes/storage.php'; echo get_storage_driver();"
```

---

## 📋 Summary

**Manual Test:**
```powershell
docker exec hr_web php /var/www/html/cron/backup-to-minio.php
```

**Setup Cron:**
```powershell
.\setup-cron.ps1
```

**Check Logs:**
```powershell
docker exec hr_web tail -f /var/www/html/storage/logs/backup-cron.log
```

**View Backups:**
- MinIO Console: `http://localhost:9001`
- CLI: `docker exec hr_web mc ls backup/goldenz-uploads/db-backups/`
