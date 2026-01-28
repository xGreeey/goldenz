#!/bin/bash
# Setup script for backup container

chmod +x /usr/local/bin/mc
/usr/local/bin/mc alias set localminio http://minio:9000 goldenz SUOMYNONA
/usr/local/bin/mc mb -p localminio/db-backups || true

# Create cron job with proper escaping
cat > /etc/cron.d/backup << 'CRONJOB'
*/30 * * * * root /bin/bash -c 'export PATH=/usr/local/bin:/usr/bin:/bin; ts=$(date +%F_%H-%M-%S); mysqldump -h db -u root -pSuomynona027 goldenz_hr > /backup/goldenz_hr_${ts}.sql && /usr/local/bin/mc cp /backup/goldenz_hr_${ts}.sql localminio/db-backups/ && find /backup -type f -mtime +7 -delete'
CRONJOB

chmod 0644 /etc/cron.d/backup
service cron start
echo "Backup setup complete!"
