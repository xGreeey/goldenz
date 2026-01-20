<?php
/**
 * Storage Configuration
 * Configuration for file storage (local or MinIO/S3)
 */

return [
    'default' => $_ENV['STORAGE_DRIVER'] ?? 'minio', // 'local' or 'minio' - change to 'minio' to use MinIO
    
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => __DIR__ . '/../storage/app',
            'url' => '/storage/app',
            'visibility' => 'public',
        ],
        
        'minio' => [
            'driver' => 's3',
            'endpoint' => $_ENV['MINIO_ENDPOINT'] ?? 'http://minio:9000',
            'key' => $_ENV['MINIO_ACCESS_KEY'] ?? 'goldenz',
            'secret' => $_ENV['MINIO_SECRET_KEY'] ?? 'SUOMYNONA',
            'region' => $_ENV['MINIO_REGION'] ?? 'us-east-1',
            'bucket' => $_ENV['MINIO_BUCKET'] ?? 'goldenz-uploads',
            'use_path_style_endpoint' => true, // Required for MinIO
            'url' => $_ENV['MINIO_PUBLIC_URL'] ?? null, // Public URL if different from endpoint
        ],
    ],
    
    // Legacy upload paths (for backward compatibility)
    'paths' => [
        'users' => 'uploads/users',
        'employees' => 'uploads/employees',
        'documents' => 'uploads/documents',
    ],
];
