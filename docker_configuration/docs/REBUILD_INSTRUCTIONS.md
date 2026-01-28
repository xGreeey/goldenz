# Rebuild Instructions

## What Changed

1. **Dockerfile**: Added MinIO client (mc) installation
2. **docker-compose.yml**: Added shared volume for MinIO uploads
3. **storage.php**: Updated to use mc client directly (no docker exec needed)

## Rebuild Steps

```powershell
# Stop containers
docker-compose down

# Rebuild the web container
docker-compose build web

# Start containers
docker-compose up -d
```

## After Rebuild

1. Test the upload: `http://localhost/test-minio-backup.php`
2. Click "Test Simple Upload"
3. Check MinIO Console: `http://localhost:9001`
   - Login: `goldenz` / `SUOMYNONA`
   - Check bucket: `goldenz-uploads`
   - Look in `test/` folder

## How It Works Now

The system will try upload methods in this order:
1. **AWS SDK** (if installed) - Best option
2. **MinIO Client (mc)** - Now installed in web container ✅
3. **Manual Signature** - Fallback (may still fail)

The MinIO client (mc) should work reliably and upload files directly to MinIO!
