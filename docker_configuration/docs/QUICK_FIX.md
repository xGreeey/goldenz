# Quick Fix: Rebuild Container to Install MinIO Client

## The Problem

The connection to MinIO is working fine (verified with curl). The issue is that the MinIO client (mc) is not yet installed in the web container.

## Solution: Rebuild the Container

Run these commands:

```powershell
# Stop containers
docker-compose down

# Rebuild web container (this installs mc)
docker-compose build web

# Start containers
docker-compose up -d
```

## After Rebuild

1. The MinIO client (mc) will be installed in the web container
2. Uploads will use mc instead of manual signature
3. Test: http://localhost/test-minio-backup.php

## Verify Installation

After rebuild, check if mc is installed:

```powershell
docker exec hr_web which mc
```

Should output: `/usr/local/bin/mc`

## Why This Works

- **Connection**: ✅ Working (containers can reach each other)
- **MinIO**: ✅ Running and accessible
- **Credentials**: ✅ Correct (goldenz/SUOMYNONA)
- **MinIO Client**: ❌ Not installed yet (needs rebuild)

Once mc is installed, it will handle authentication automatically!
