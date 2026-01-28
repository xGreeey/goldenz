# Rclone Google Drive Setup Guide

This guide explains how to set up and configure rclone for backing up to Google Drive.

## Overview

Rclone is used to sync database backups from MinIO to Google Drive. The backup flow is:

```
MySQL Database → MinIO (db-backups/) → Google Drive (GoldenZ5/backup/)
```

## Prerequisites

- Docker containers running (`hr_web`, `hr_db`, `hr_minio`)
- Google account with Google Drive access
- Access to the server/container terminal

## Initial Rclone Setup

### Step 1: Enter the Web Container

```powershell
docker exec -it hr_web bash
```

### Step 2: Configure Rclone

Run the rclone configuration wizard with the explicit config path:

```bash
rclone config --config /root/.config/rclone/rclone.conf
```

> **Important:** Use `--config /root/.config/rclone/rclone.conf` to ensure the config is saved to the persisted volume. The config will automatically be available at `/var/www/.config/rclone/rclone.conf` since both paths share the same volume.

### Step 3: Create a New Remote

Follow these prompts:

```
n) New remote
name> rclone
```

> **Note:** The remote name should match the `RCLONE_REMOTE` environment variable in `docker-compose.yml` (default: `rclone`)

### Step 4: Select Google Drive

```
Storage> drive
```

Or type the number corresponding to "Google Drive" in the list.

### Step 5: Client ID and Secret (Optional)

For basic setup, leave these blank:

```
client_id> (press Enter)
client_secret> (press Enter)
```

### Step 6: Scope

Select full access:

```
scope> 1
```

Options:
- `1` - Full access all files (recommended)
- `2` - Read-only access
- `3` - Access to files created by rclone only

### Step 7: Root Folder ID (Optional)

Leave blank for full Drive access:

```
root_folder_id> (press Enter)
```

### Step 8: Service Account (Optional)

Leave blank for personal account:

```
service_account_file> (press Enter)
```

### Step 9: Advanced Config

```
Edit advanced config? (y/n)> n
```

### Step 10: Auto Config

Since we're in a Docker container (headless), select **No**:

```
Use auto config?> n
```

### Step 11: Get Authorization Code

You'll see a message like:

```
Option config_token.
For this to work, you will need rclone available on a machine that has
a web browser available.

For more help and alternate methods see: https://rclone.org/remote_setup/

Execute the following on the machine with the web browser (same rclone
version recommended):

    rclone authorize "drive"

Then paste the result.

Enter a value.
config_token>
```

**On your local machine with a browser:**

1. Install rclone: https://rclone.org/downloads/
2. Run:
   ```
   rclone authorize "drive"
   ```
3. A browser window will open - sign in to your Google account
4. Grant rclone access to Google Drive
5. Copy the token that appears in the terminal

**Paste the token back into the container prompt.**

### Step 12: Configure as Team Drive (Optional)

```
Configure this as a Shared Drive (Team Drive)?> n
```

### Step 13: Confirm and Save

```
y) Yes this is OK
```

### Step 14: Quit Configuration

```
q) Quit config
```

## Verify Rclone Configuration

### Test Connection

```bash
# List root of Google Drive
rclone lsd rclone:

# List specific folder
rclone lsd rclone:GoldenZ5/backup/
```

### List Remote Contents

```bash
# List files in backup folder
rclone --config /root/.config/rclone/rclone.conf ls rclone:GoldenZ5/backup/

# List all configured remotes
rclone --config /root/.config/rclone/rclone.conf listremotes
```

### Test Upload

```bash
# Create a test file
echo "test" > /tmp/test.txt

# Upload to Google Drive
rclone copy /tmp/test.txt rclone:GoldenZ5/backup/

# Verify upload
rclone ls rclone:GoldenZ5/backup/test.txt

# Clean up
rclone delete rclone:GoldenZ5/backup/test.txt
rm /tmp/test.txt
```

## Changing Google Drive Account

If you need to switch to a different Google Drive account:

### Option 1: Reconfigure Existing Remote

```bash
docker exec -it hr_web bash
rclone config
```

Then:
```
e) Edit existing remote
rclone
```

Follow the prompts to re-authenticate with the new Google account.

### Option 2: Delete and Recreate Remote

```bash
docker exec -it hr_web bash
rclone config
```

Then:
```
d) Delete remote
rclone
```

Now create a new remote following the [Initial Rclone Setup](#initial-rclone-setup) steps above.

### Option 3: Edit Config File Directly

The rclone config file is located at:
- Inside container: `/var/www/.config/rclone/rclone.conf`
- Volume mount: `rclone_config` Docker volume

```bash
# View current config
docker exec hr_web cat /var/www/.config/rclone/rclone.conf

# Or edit directly
docker exec -it hr_web nano /var/www/.config/rclone/rclone.conf
```

To change accounts, delete the `[rclone]` section and run `rclone config` again.

## Configuration File Location

The rclone configuration is stored in a Docker volume (`rclone_config`) and is **persisted across container restarts**. The volume is mounted to both locations:

| Location | Path |
|----------|------|
| Container (www-data) | `/var/www/.config/rclone/rclone.conf` |
| Container (root) | `/root/.config/rclone/rclone.conf` |
| Docker Volume | `rclone_config` (persists across restarts) |

The application checks both paths and uses whichever exists. **The configuration will persist even when Docker restarts** because it's stored in a Docker volume.

## Environment Variables

In `docker-compose.yml`, these variables control rclone behavior:

```yaml
environment:
  RCLONE_REMOTE: rclone              # Name of the rclone remote
  RCLONE_CONFIG: /var/www/.config/rclone/rclone.conf  # Config file path
```

To change the remote name, update `RCLONE_REMOTE` and ensure the remote exists in rclone config.

## Backup Destination

Backups are uploaded to:
- **Google Drive Path:** `GoldenZ5/backup/`
- **File Format:** `goldenz_hr_YYYY-MM-DD_HH-MM-SS.sql.gz`

To change the destination folder, edit `src/includes/database.php`:

```php
$gdrive_path = 'GoldenZ5/backup/' . $upload_filename;
```

## Testing the Full Backup Flow

### Manual Test

```powershell
# Run the PHP backup script (MySQL → MinIO → Google Drive)
docker exec hr_web php /var/www/html/cron/backup-to-minio.php
```

### Using Web Interface

1. Open: http://localhost/test-rclone-gdrive.php
2. Click "Test Rclone Connection" to verify connectivity
3. Click "Test Full Backup Upload" to run a complete backup

### Verify in Google Drive

After running a backup, check your Google Drive:
1. Go to https://drive.google.com
2. Navigate to `GoldenZ5/backup/`
3. You should see `.sql.gz` backup files

## Troubleshooting

### "rclone not found"

Rclone should be installed in the container. Check:
```bash
docker exec hr_web which rclone
```

### "Remote not found"

Verify the remote exists:
```bash
docker exec hr_web rclone listremotes
```

The output should include `rclone:` (or whatever your remote name is).

### "Authentication failed" or "Token expired"

Re-authenticate:
```bash
docker exec -it hr_web rclone config reconnect rclone:
```

### Permission Denied

Check if the config file exists and is readable:
```bash
docker exec hr_web ls -la /var/www/.config/rclone/
docker exec hr_web ls -la /root/.config/rclone/
```

### Quota Exceeded

Google Drive has API rate limits. If you hit them:
- Wait 24 hours for limits to reset
- Consider using a service account for higher limits

### Check Logs

View backup logs:
```bash
# PHP backup logs
docker exec hr_web cat /var/www/html/storage/logs/backup-cron.log

# Container backup logs
docker logs hr_db_backup --tail 50
```

## Useful Rclone Commands

```bash
# List all configured remotes
rclone listremotes

# Check remote configuration
rclone config show rclone

# List folders in Drive root
rclone lsd rclone:

# List all files recursively
rclone ls rclone:GoldenZ5/backup/

# Copy file to Drive
rclone copy /path/to/file rclone:GoldenZ5/backup/

# Sync folder to Drive (mirror)
rclone sync /local/folder rclone:GoldenZ5/backup/

# Check available space
rclone about rclone:

# Get file info
rclone lsl rclone:GoldenZ5/backup/

# Delete old backups (older than 30 days)
rclone delete --min-age 30d rclone:GoldenZ5/backup/
```

## Security Notes

- The rclone config contains OAuth tokens - keep it secure
- The `rclone_config` Docker volume persists between container restarts
- Avoid committing rclone.conf to version control
- Consider using a dedicated Google account for backups
