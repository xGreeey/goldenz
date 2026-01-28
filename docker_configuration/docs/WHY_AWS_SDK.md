# Why Do We Need AWS SDK for MinIO?

## The Problem

MinIO uses the **S3-compatible API**, which requires **AWS Signature Version 4 (SigV4)** for authentication. This is a complex cryptographic signature process.

## What is AWS Signature Version 4?

When you upload a file to MinIO (or AWS S3), you need to prove you have the right credentials. Instead of sending your password directly, you create a cryptographic signature that:

1. **Canonical Request**: Formats your HTTP request in a specific way
2. **String to Sign**: Creates a special string from the request
3. **Signing Key**: Derives a key from your secret key using multiple HMAC operations
4. **Signature**: Signs the string using the derived key

If ANY part of this process is wrong, MinIO rejects it with `SignatureDoesNotMatch`.

## Why Manual Implementation Fails

We've been trying to implement SigV4 manually, but it's extremely error-prone because:

### 1. Exact Formatting Required
```
PUT
/goldenz-uploads/test/file.txt

host:minio:9000
x-amz-date:20260120T123456Z

host;x-amz-date
<payload-hash>
```
Every newline, space, and character must be EXACTLY right.

### 2. Complex Key Derivation
```php
kSecret = HMAC('AWS4' + secret_key, date)
kDate = HMAC(kSecret, '20260120')
kRegion = HMAC(kDate, 'us-east-1')
kService = HMAC(kRegion, 's3')
kSigning = HMAC(kService, 'aws4_request')
signature = HMAC(kSigning, string_to_sign)
```
One mistake in any step breaks everything.

### 3. URL Encoding Rules
- Some characters must be encoded
- Some must NOT be encoded
- Slashes are special
- Bucket names have different rules than object keys

### 4. Header Ordering
Headers must be:
- Sorted alphabetically
- Lowercase
- Properly formatted
- Included in the exact order

## What AWS SDK Does

The AWS SDK for PHP:
- ✅ Implements SigV4 correctly (tested by millions of users)
- ✅ Handles all edge cases
- ✅ Gets updated when AWS changes requirements
- ✅ Works with MinIO (S3-compatible)
- ✅ Simple to use:

```php
$s3 = new S3Client([
    'endpoint' => 'http://minio:9000',
    'credentials' => ['key' => 'goldenz', 'secret' => 'SUOMYNONA'],
]);

$s3->putObject([
    'Bucket' => 'goldenz-uploads',
    'Key' => 'test/file.txt',
    'SourceFile' => '/path/to/file.txt',
]);
```

That's it! No manual signature calculation needed.

## Alternatives (If You Don't Want AWS SDK)

### Option 1: Use Local Storage
- Store files on the server's filesystem
- No external dependencies
- Simpler, but files are tied to the server

### Option 2: Use MinIO Client (mc) via exec
- MinIO provides a command-line client
- Can use it via PHP's `exec()` function
- Less elegant, but works

### Option 3: Fix Manual Implementation
- Continue debugging the signature
- Will take significant time
- May break again if AWS changes requirements

## Recommendation

**Use AWS SDK** because:
1. It's the industry standard
2. It's well-tested and maintained
3. It's only ~5MB when installed
4. It saves you hours of debugging
5. It's free and open-source

## Installation

The AWS SDK is installed automatically when you rebuild the Docker container:

```powershell
docker-compose build web
```

Or manually:
```bash
composer require aws/aws-sdk-php
```

## Current Status

- ✅ MinIO is running correctly
- ✅ Port 9000 (API) is accessible
- ✅ Credentials are correct
- ❌ Manual signature implementation is failing
- ✅ System works with local storage as fallback

Once AWS SDK is installed, everything will work automatically!
