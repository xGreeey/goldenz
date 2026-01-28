# Installing AWS SDK for MinIO Support

Since the automatic installation failed due to network issues, here are manual installation options:

## Option 1: Download AWS SDK Manually

1. Download the AWS SDK from: https://github.com/aws/aws-sdk-php/releases
2. Extract it to: `src/vendor/aws/aws-sdk-php/`
3. The storage.php will automatically detect and use it

## Option 2: Use MinIO Client (mc) via exec

The db_backup container already has the MinIO client installed. We can use it via exec.

## Option 3: Rebuild Container When Network is Available

```powershell
docker-compose down
docker-compose build web
docker-compose up -d
```

This will automatically install Composer and the AWS SDK.
