# MinIO Setup Instructions

## Current Status

MinIO is running and accessible:
- **Port 9000**: S3 API endpoint (for uploads) - ✅ Working
- **Port 9001**: Web Console (for management) - ✅ Working
- **Credentials**: goldenz / SUOMYNONA

## The Issue

The manual AWS Signature Version 4 implementation is failing. The AWS SDK for PHP handles this correctly.

## Solution: Install AWS SDK

### Option 1: Rebuild Container (Recommended)

When your network connection is stable:

```powershell
docker-compose down
docker-compose build web
docker-compose up -d
```

This will automatically install Composer and the AWS SDK.

### Option 2: Manual Installation

1. **Download AWS SDK manually:**
   - Go to: https://github.com/aws/aws-sdk-php/releases
   - Download the latest release (zip file)
   - Extract to: `src/vendor/aws/aws-sdk-php/`

2. **Or install via Composer in container:**
   ```powershell
   docker exec hr_web bash -c "cd /var/www/html && php composer.phar install --no-dev"
   ```

### Option 3: Use Local Storage Temporarily

The system is currently set to use local storage. To switch to MinIO after installing AWS SDK:

1. Edit `src/config/storage.php`
2. Change: `'default' => 'local'` to `'default' => 'minio'`

## Verify Installation

After installing AWS SDK, test the upload:

1. Go to: `http://localhost/test-minio-backup.php`
2. Click "Test Simple Upload"
3. Should see: ✅ Test upload successful!

## MinIO Console

Access the web console at: `http://localhost:9001`
- Username: `goldenz`
- Password: `SUOMYNONA`

You can verify uploads by checking the `goldenz-uploads` bucket in the console.
