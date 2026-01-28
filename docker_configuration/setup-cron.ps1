# Setup Cron for Database Backups
# This script installs and configures cron in the running container

Write-Host "=== Setting up Cron for Database Backups ===" -ForegroundColor Cyan

# Check if container is running
Write-Host "`n[1/5] Checking container status..." -ForegroundColor Yellow
$containerStatus = docker ps --filter "name=hr_web" --format "{{.Status}}"
if (-not $containerStatus) {
    Write-Host "ERROR: hr_web container is not running!" -ForegroundColor Red
    Write-Host "Please start it with: docker-compose up -d" -ForegroundColor Yellow
    exit 1
}
Write-Host "✓ Container is running" -ForegroundColor Green

# Install cron
Write-Host "`n[2/5] Installing cron..." -ForegroundColor Yellow
docker exec hr_web bash -c "apt-get update && apt-get install -y cron && apt-get clean"
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Cron installed successfully" -ForegroundColor Green
} else {
    Write-Host "✗ Failed to install cron" -ForegroundColor Red
    exit 1
}

# Create cron job file
Write-Host "`n[3/5] Creating cron job..." -ForegroundColor Yellow
$cronJob = "*/30 * * * * www-data /usr/local/bin/php /var/www/html/cron/backup-to-minio.php >> /var/www/html/storage/logs/backup-cron.log 2>&1"
docker exec hr_web bash -c "echo '$cronJob' > /etc/cron.d/db-backup && chmod 0644 /etc/cron.d/db-backup"
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Cron job created successfully" -ForegroundColor Green
} else {
    Write-Host "✗ Failed to create cron job" -ForegroundColor Red
    exit 1
}

# Start cron service
Write-Host "`n[4/5] Starting cron service..." -ForegroundColor Yellow
docker exec hr_web bash -c "service cron start"
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Cron service started" -ForegroundColor Green
} else {
    Write-Host "⚠ Cron service may already be running" -ForegroundColor Yellow
}

# Verify setup
Write-Host "`n[5/5] Verifying setup..." -ForegroundColor Yellow
$cronJobExists = docker exec hr_web bash -c "test -f /etc/cron.d/db-backup && echo 'exists'"
if ($cronJobExists -eq "exists") {
    Write-Host "✓ Cron job file exists" -ForegroundColor Green
    Write-Host "`nCron job content:" -ForegroundColor Cyan
    docker exec hr_web cat /etc/cron.d/db-backup
} else {
    Write-Host "✗ Cron job file not found" -ForegroundColor Red
}

Write-Host "`n=== Setup Complete ===" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "1. Test backup manually: docker exec hr_web php /var/www/html/cron/backup-to-minio.php" -ForegroundColor White
Write-Host "2. Check logs: docker exec hr_web tail -f /var/www/html/storage/logs/backup-cron.log" -ForegroundColor White
Write-Host "3. Verify cron is running: docker exec hr_web ps aux | grep cron" -ForegroundColor White
Write-Host "`nNote: Cron will run backups every 30 minutes automatically." -ForegroundColor Yellow
