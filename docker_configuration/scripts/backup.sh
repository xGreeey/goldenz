#!/bin/bash
# Database Backup Script
# Backs up MySQL database to MinIO storage

set -e

# Configuration from environment variables
MYSQL_HOST="${MYSQL_HOST:-db}"
MYSQL_USER="${MYSQL_USER:-root}"
MYSQL_PASSWORD="${MYSQL_PASSWORD}"
MYSQL_DATABASE="${MYSQL_DATABASE:-goldenz_hr}"
MINIO_ALIAS="${MINIO_ALIAS:-localminio}"
MINIO_BUCKET="${MINIO_BUCKET:-db-backups}"
BACKUP_DIR="/backup"

# Generate timestamp
TIMESTAMP=$(date +%F_%H-%M-%S)
BACKUP_FILE="${MYSQL_DATABASE}_${TIMESTAMP}.sql"
BACKUP_PATH="${BACKUP_DIR}/${BACKUP_FILE}"

echo "=========================================="
echo "Starting database backup at $(date)"
echo "=========================================="

# Create backup directory if it doesn't exist
mkdir -p "${BACKUP_DIR}"

# Create MySQL dump
echo "Creating MySQL dump: ${BACKUP_FILE}"
mysqldump -h "${MYSQL_HOST}" -u "${MYSQL_USER}" -p"${MYSQL_PASSWORD}" "${MYSQL_DATABASE}" > "${BACKUP_PATH}"

if [ $? -eq 0 ]; then
    echo "MySQL dump created successfully"
    FILESIZE=$(stat -c%s "${BACKUP_PATH}" 2>/dev/null || stat -f%z "${BACKUP_PATH}" 2>/dev/null)
    echo "Backup size: ${FILESIZE} bytes"
else
    echo "ERROR: MySQL dump failed!"
    exit 1
fi

# Upload to MinIO
echo "Uploading to MinIO: ${MINIO_ALIAS}/${MINIO_BUCKET}/${BACKUP_FILE}"
/usr/local/bin/mc cp "${BACKUP_PATH}" "${MINIO_ALIAS}/${MINIO_BUCKET}/"

if [ $? -eq 0 ]; then
    echo "Upload to MinIO successful"
else
    echo "ERROR: Upload to MinIO failed!"
    exit 1
fi

# Clean up old local backups (older than 7 days)
echo "Cleaning up old local backups..."
find "${BACKUP_DIR}" -type f -name "*.sql" -mtime +7 -delete

echo "=========================================="
echo "Backup completed successfully at $(date)"
echo "=========================================="
