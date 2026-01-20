<?php
/**
 * Storage Helper using AWS SDK for PHP
 * This is an alternative implementation using the AWS SDK
 */

require_once __DIR__ . '/../config/storage.php';

// Check if AWS SDK is available
$aws_sdk_available = false;
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $aws_sdk_available = class_exists('Aws\S3\S3Client');
}

/**
 * Upload file to MinIO using AWS SDK
 */
function upload_to_minio_aws_sdk($source_path, $destination_path, $options = []) {
    global $aws_sdk_available;
    
    if (!$aws_sdk_available) {
        error_log("AWS SDK not available. Please run: composer install");
        return false;
    }
    
    $config = get_storage_config();
    $disk_config = $config['disks']['minio'];
    
    try {
        $s3Client = new Aws\S3\S3Client([
            'version' => 'latest',
            'region' => $disk_config['region'],
            'endpoint' => $disk_config['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $disk_config['key'],
                'secret' => $disk_config['secret'],
            ],
        ]);
        
        // Ensure bucket exists
        if (!$s3Client->doesBucketExist($disk_config['bucket'])) {
            $s3Client->createBucket([
                'Bucket' => $disk_config['bucket'],
            ]);
        }
        
        // Upload file
        $result = $s3Client->putObject([
            'Bucket' => $disk_config['bucket'],
            'Key' => $destination_path,
            'SourceFile' => $source_path,
            'ContentType' => $options['content_type'] ?? mime_content_type($source_path) ?? 'application/octet-stream',
        ]);
        
        return $destination_path;
        
    } catch (Exception $e) {
        error_log("AWS SDK MinIO upload failed: " . $e->getMessage());
        return false;
    }
}
