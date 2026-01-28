FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    default-mysql-client \
    unzip \
    git \
    wget \
    curl \
    && docker-php-ext-install pdo pdo_mysql mysqli curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install MinIO client (mc)
RUN wget -qO /usr/local/bin/mc https://dl.min.io/client/mc/release/linux-amd64/mc && \
    chmod +x /usr/local/bin/mc

# Install rclone for Google Drive backups
RUN curl https://rclone.org/install.sh | bash

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable SSL module
RUN a2enmod ssl
RUN a2enmod rewrite
RUN a2enmod headers

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-interaction; fi

# Set up cron for database backups (every 30 minutes)
RUN apt-get update && apt-get install -y cron && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Create cron job for database backups (every 30 minutes)
RUN echo "*/30 * * * * www-data /usr/local/bin/php /var/www/html/cron/backup-to-minio.php >> /var/www/html/storage/logs/backup-cron.log 2>&1" > /etc/cron.d/db-backup && \
    chmod 0644 /etc/cron.d/db-backup

# Create entrypoint script to start cron
RUN cat <<'EOF' > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh
#!/bin/bash
set -e

# Enable required Apache modules before starting Apache
a2enmod ssl rewrite headers || true

# Ensure rclone config directories exist (volumes are mounted, but ensure parent dirs exist)
mkdir -p /root/.config/rclone
mkdir -p /var/www/.config/rclone

# Set proper permissions on rclone config (volume is mounted to both locations)
# The volume persists across restarts, so we just need to ensure permissions are correct
if [ -f /var/www/.config/rclone/rclone.conf ]; then
    chown -R www-data:www-data /var/www/.config
    chmod 600 /var/www/.config/rclone/rclone.conf
    # Also ensure root can access it
    chmod 600 /root/.config/rclone/rclone.conf 2>/dev/null || true
fi

# Fallback: If config doesn't exist in volume, try to copy from source (first-time setup)
if [ ! -f /root/.config/rclone/rclone.conf ] && [ -f /var/www/html/rclone/rclone.conf ]; then
    cp /var/www/html/rclone/rclone.conf /root/.config/rclone/rclone.conf
    chmod 600 /root/.config/rclone/rclone.conf
    chown -R www-data:www-data /var/www/.config 2>/dev/null || true
fi

# Write cron file with container environment (cron doesn't inherit docker env)
CRON_FILE="/etc/cron.d/db-backup"
CRON_SCHEDULE="${BACKUP_SCHEDULE:-*/30 * * * *}"
cat > "$CRON_FILE" <<CRON_EOF
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-goldenz_hr}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}
MINIO_ENDPOINT=${MINIO_ENDPOINT:-http://minio:9000}
MINIO_ACCESS_KEY=${MINIO_ACCESS_KEY:-goldenz}
MINIO_SECRET_KEY=${MINIO_SECRET_KEY:-SUOMYNONA}
MINIO_BUCKET=${MINIO_BUCKET:-goldenz-uploads}
MINIO_USE_SSL=${MINIO_USE_SSL:-false}
MINIO_REGION=${MINIO_REGION:-us-east-1}
RCLONE_REMOTE=${RCLONE_REMOTE:-rclone}
RCLONE_CONFIG=${RCLONE_CONFIG:-/var/www/.config/rclone/rclone.conf}
${CRON_SCHEDULE} www-data /usr/local/bin/php /var/www/html/cron/backup-to-minio.php >> /var/www/html/storage/logs/backup-cron.log 2>&1
CRON_EOF
chmod 0644 "$CRON_FILE"

# Start cron in background
service cron start

# Start Apache in foreground
exec apache2-foreground
EOF

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]