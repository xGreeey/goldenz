<?php
/**
 * Storage Helper Functions
 * Handles file uploads to local storage or MinIO/S3
 */

require_once __DIR__ . '/../config/storage.php';

/**
 * Get storage configuration
 */
function get_storage_config() {
    static $config = null;
    if ($config === null) {
        $config = include __DIR__ . '/../config/storage.php';
    }
    return $config;
}

/**
 * Upload file to MinIO using AWS SDK (if available)
 */
function upload_to_minio_aws_sdk($source_path, $destination_path, $options = []) {
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

/**
 * Test MinIO connectivity
 */
function test_minio_connectivity() {
    $config = get_storage_config();
    $disk_config = $config['disks']['minio'];
    
    $endpoint = $disk_config['endpoint'];
    $parsed_url = parse_url($endpoint);
    $host = $parsed_url['host'];
    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $host_header = $host . $port;
    
    // Simple connectivity test - try to list buckets
    $url = $endpoint;
    $amz_date = gmdate('Ymd\THis\Z');
    $date_stamp = gmdate('Ymd');
    $region = $disk_config['region'];
    $access_key = $disk_config['key'];
    $secret_key = $disk_config['secret'];
    
    // Generate signature for GET request
    $canonical_uri = '/';
    $canonical_querystring = '';
    $canonical_headers = "host:" . $host_header . "\n" .
                         "x-amz-date:" . $amz_date . "\n";
    $signed_headers = 'host;x-amz-date';
    $payload_hash = hash('sha256', '');
    
    $canonical_request = "GET\n" .
                        $canonical_uri . "\n" .
                        $canonical_querystring . "\n" .
                        $canonical_headers . "\n" .
                        $signed_headers . "\n" .
                        $payload_hash;
    
    $algorithm = 'AWS4-HMAC-SHA256';
    $credential_scope = $date_stamp . '/' . $region . '/s3/aws4_request';
    $string_to_sign = $algorithm . "\n" .
                     $amz_date . "\n" .
                     $credential_scope . "\n" .
                     hash('sha256', $canonical_request);
    
    $kSecret = 'AWS4' . $secret_key;
    $kDate = hash_hmac('sha256', $date_stamp, $kSecret, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $string_to_sign, $kSigning);
    
    $authorization = $algorithm . ' ' .
                    'Credential=' . $access_key . '/' . $credential_scope . ', ' .
                    'SignedHeaders=' . $signed_headers . ', ' .
                    'Signature=' . $signature;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Host: ' . $host_header,
            'x-amz-date: ' . $amz_date,
            'Authorization: ' . $authorization,
        ],
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => ($http_code >= 200 && $http_code < 300),
        'http_code' => $http_code,
        'error' => $error,
        'response' => $response,
        'endpoint' => $endpoint,
    ];
}

/**
 * Get the active storage driver
 */
function get_storage_driver() {
    $config = get_storage_config();
    return $config['default'];
}

/**
 * Upload file to storage (MinIO or local)
 * 
 * @param string $source_path Local file path to upload
 * @param string $destination_path Destination path in storage (e.g., 'uploads/users/avatar.jpg')
 * @param array $options Additional options
 * @return string|false Returns the storage path/URL on success, false on failure
 */
function upload_to_storage($source_path, $destination_path, $options = []) {
    $driver = get_storage_driver();
    
    if ($driver === 'minio') {
        return upload_to_minio($source_path, $destination_path, $options);
    } else {
        return upload_to_local($source_path, $destination_path, $options);
    }
}

/**
 * Upload file to local storage
 */
function upload_to_local($source_path, $destination_path, $options = []) {
    $config = get_storage_config();
    $disk_config = $config['disks']['local'];
    
    $full_destination = $disk_config['root'] . '/' . $destination_path;
    $destination_dir = dirname($full_destination);
    
    // Create directory if it doesn't exist
    if (!file_exists($destination_dir)) {
        if (!mkdir($destination_dir, 0755, true)) {
            error_log("Failed to create directory: $destination_dir");
            return false;
        }
    }
    
    // Copy file
    if (copy($source_path, $full_destination)) {
        return $destination_path;
    }
    
    error_log("Failed to copy file from $source_path to $full_destination");
    return false;
}

/**
 * Upload file to MinIO using S3-compatible API
 * Tries multiple methods in order:
 * 1. AWS SDK (if available)
 * 2. MinIO client via exec (if available)
 * 3. Manual signature implementation (fallback)
 */
function upload_to_minio($source_path, $destination_path, $options = []) {
    // Try AWS SDK first if available
    $vendor_autoload = __DIR__ . '/../../vendor/autoload.php';
    if (file_exists($vendor_autoload)) {
        require_once $vendor_autoload;
        if (class_exists('Aws\S3\S3Client')) {
            try {
                return upload_to_minio_aws_sdk($source_path, $destination_path, $options);
            } catch (Exception $e) {
                error_log("AWS SDK upload failed, trying next method: " . $e->getMessage());
            }
        }
    }
    
    // Try using MinIO client (mc) via Docker exec if available
    $config = get_storage_config();
    $disk_config = $config['disks']['minio'];
    $result = upload_to_minio_via_mc($source_path, $destination_path, $disk_config);
    if ($result !== false) {
        return $result;
    }
    
    // Fall back to manual implementation
    return upload_to_minio_manual($source_path, $destination_path, $options);
}

/**
 * Upload to MinIO using MinIO client (mc) - installed directly in web container
 */
function upload_to_minio_via_mc($source_path, $destination_path, $disk_config) {
    // Check if we can use exec and if mc is available
    if (!function_exists('exec')) {
        return false;
    }
    
    // Check if mc is installed
    exec('which mc 2>&1', $which_output, $which_return);
    if ($which_return !== 0) {
        return false; // mc not installed
    }
    
    try {
        $endpoint = $disk_config['endpoint'];
        $bucket = $disk_config['bucket'];
        $access_key = $disk_config['key'];
        $secret_key = $disk_config['secret'];
        
        // Use a unique alias for this upload
        $alias = 'hrminio_' . time() . '_' . rand(1000, 9999);
        $minio_path = "$alias/$bucket/$destination_path";
        
        // Verify source file exists
        if (!file_exists($source_path)) {
            error_log("MinIO client: Source file does not exist: $source_path");
            return false;
        }
        
        // Set up alias first
        $alias_cmd = sprintf(
            'mc alias set %s %s %s %s 2>&1',
            escapeshellarg($alias),
            escapeshellarg($endpoint),
            escapeshellarg($access_key),
            escapeshellarg($secret_key)
        );
        
        exec($alias_cmd, $alias_output, $alias_return);
        if ($alias_return !== 0) {
            error_log("MinIO client: Failed to set alias. Output: " . implode("\n", $alias_output));
            return false;
        }
        
        // Upload file
        $upload_cmd = sprintf(
            'mc cp %s %s 2>&1',
            escapeshellarg($source_path),
            escapeshellarg($minio_path)
        );
        
        error_log("MinIO client: Executing upload command: $upload_cmd");
        exec($upload_cmd, $output, $return_var);
        $output_str = implode("\n", $output);
        
        // Clean up alias (don't fail if this fails)
        exec(sprintf('mc alias remove %s 2>&1', escapeshellarg($alias)), $remove_output, $remove_return);
        
        if ($return_var === 0) {
            error_log("MinIO client: Successfully uploaded $destination_path. Output: $output_str");
            return $destination_path;
        } else {
            error_log("MinIO client upload failed. Return code: $return_var. Command: $upload_cmd. Output: $output_str");
            // Also try to get stderr
            $full_cmd = $upload_cmd . ' ; echo "EXIT_CODE:$?"';
            exec($full_cmd, $full_output, $full_return);
            error_log("MinIO client: Full output: " . implode("\n", $full_output));
            return false;
        }
    } catch (Exception $e) {
        error_log("MinIO client upload error: " . $e->getMessage());
        return false;
    }
}

/**
 * Upload file to MinIO using manual S3-compatible API (fallback)
 */
function upload_to_minio_manual($source_path, $destination_path, $options = []) {
    $config = get_storage_config();
    $disk_config = $config['disks']['minio'];
    
    $endpoint = $disk_config['endpoint'];
    $bucket = $disk_config['bucket'];
    $access_key = $disk_config['key'];
    $secret_key = $disk_config['secret'];
    $region = $disk_config['region'];
    
    // Ensure bucket exists
    if (!minio_bucket_exists($endpoint, $bucket, $access_key, $secret_key, $region)) {
        if (!minio_create_bucket($endpoint, $bucket, $access_key, $secret_key, $region)) {
            error_log("Failed to create MinIO bucket: $bucket");
            return false;
        }
    }
    
    // Read file content
    $file_content = file_get_contents($source_path);
    if ($file_content === false) {
        error_log("Failed to read file: $source_path");
        return false;
    }
    
    $content_type = $options['content_type'] ?? mime_content_type($source_path) ?? 'application/octet-stream';
    $file_size = filesize($source_path);
    
    // Parse endpoint URL
    $parsed_url = parse_url($endpoint);
    $host = strtolower($parsed_url['host']); // Host must be lowercase
    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $host_header = $host . $port;
    
    // Prepare S3 PUT request
    $amz_date = gmdate('Ymd\THis\Z');
    $date_stamp = gmdate('Ymd');
    
    // Generate AWS Signature Version 4
    // For canonical URI, we need to URL encode each path segment but preserve slashes
    $path_parts = explode('/', $destination_path);
    $encoded_parts = array_map('rawurlencode', array_filter($path_parts)); // Filter empty parts
    $encoded_path = implode('/', $encoded_parts);
    
    // Canonical URI should have the bucket and encoded path
    // Bucket name doesn't need encoding (unless it has special chars)
    // Path segments should be URL encoded
    $canonical_uri = '/' . $bucket;
    if (!empty($encoded_path)) {
        $canonical_uri .= '/' . $encoded_path;
    }
    // Normalize the path (remove double slashes, etc.)
    $canonical_uri = preg_replace('#/+#', '/', $canonical_uri);
    
    // For the actual URL, use the same encoding
    $url = $endpoint . '/' . $bucket;
    if (!empty($encoded_path)) {
        $url .= '/' . $encoded_path;
    }
    
    $canonical_querystring = '';
    // Canonical headers: each header on one line, sorted alphabetically, with trailing \n
    // Headers must be sorted and lowercase
    // For MinIO/S3, we typically don't include Content-Type in signature for PUT requests
    // Only include headers that are required: host and x-amz-date
    $canonical_headers = "host:" . $host_header . "\n" .
                         "x-amz-date:" . $amz_date . "\n";
    $signed_headers = 'host;x-amz-date';
    $payload_hash = hash('sha256', $file_content);
    
    // Canonical request format (AWS Signature Version 4):
    // HTTPMethod\n
    // CanonicalURI\n
    // CanonicalQueryString\n
    // CanonicalHeaders (with trailing \n)
    // \n (empty line)
    // SignedHeaders\n
    // HashedPayload
    $canonical_request = "PUT\n" .
                        $canonical_uri . "\n" .
                        $canonical_querystring . "\n" .
                        $canonical_headers .  // Already has trailing \n
                        "\n" .  // Empty line after headers
                        $signed_headers . "\n" .
                        $payload_hash;
    
    $algorithm = 'AWS4-HMAC-SHA256';
    $credential_scope = $date_stamp . '/' . $region . '/s3/aws4_request';
    $string_to_sign = $algorithm . "\n" .
                     $amz_date . "\n" .
                     $credential_scope . "\n" .
                     hash('sha256', $canonical_request);
    
    $kSecret = 'AWS4' . $secret_key;
    $kDate = hash_hmac('sha256', $date_stamp, $kSecret, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $string_to_sign, $kSigning);
    // Ensure signature is lowercase hex
    $signature = strtolower($signature);
    
    $authorization = $algorithm . ' ' .
                    'Credential=' . $access_key . '/' . $credential_scope . ', ' .
                    'SignedHeaders=' . $signed_headers . ', ' .
                    'Signature=' . $signature;
    
    // Debug logging - enable for troubleshooting
    error_log("MinIO Upload Debug - URL: $url");
    error_log("MinIO Upload Debug - Canonical URI: $canonical_uri");
    error_log("MinIO Upload Debug - Host Header: $host_header");
    error_log("MinIO Upload Debug - Canonical Request (hex): " . bin2hex($canonical_request));
    error_log("MinIO Upload Debug - Canonical Request (text):\n" . str_replace("\n", "\\n", $canonical_request));
    error_log("MinIO Upload Debug - String to Sign:\n" . str_replace("\n", "\\n", $string_to_sign));
    error_log("MinIO Upload Debug - Signature: $signature");
    error_log("MinIO Upload Debug - Access Key: $access_key");
    
    // Make PUT request
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $file_content,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_VERBOSE => false,
        CURLOPT_HTTPHEADER => [
            'Host: ' . $host_header,
            'x-amz-date: ' . $amz_date,
            'Authorization: ' . $authorization,
            'Content-Type: ' . $content_type,
            'Content-Length: ' . $file_size,
        ],
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($http_code >= 200 && $http_code < 300) {
        // Return the path that can be used to access the file
        return $destination_path;
    } else {
        $error_details = [
            'HTTP Code' => $http_code,
            'cURL Error' => $error,
            'Response' => $response,
            'URL' => $url,
            'Endpoint' => $endpoint,
            'Bucket' => $bucket,
            'Path' => $destination_path,
            'File Size' => $file_size,
        ];
        error_log("MinIO upload failed. Details: " . print_r($error_details, true));
        error_log("MinIO upload - Canonical Request: " . $canonical_request);
        error_log("MinIO upload - String to Sign: " . $string_to_sign);
        return false;
    }
}

/**
 * Check if MinIO bucket exists
 */
function minio_bucket_exists($endpoint, $bucket, $access_key, $secret_key, $region) {
    $url = $endpoint . '/' . $bucket;
    
    // Parse endpoint URL
    $parsed_url = parse_url($endpoint);
    $host = $parsed_url['host'];
    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $host_header = $host . $port;
    $amz_date = gmdate('Ymd\THis\Z');
    $date_stamp = gmdate('Ymd');
    
    // Generate HEAD request signature
    $canonical_uri = '/' . $bucket;
    $canonical_querystring = '';
    $canonical_headers = "host:" . $host_header . "\n" .
                         "x-amz-date:" . $amz_date . "\n";
    $signed_headers = 'host;x-amz-date';
    $payload_hash = hash('sha256', '');
    
    $canonical_request = "HEAD\n" .
                        $canonical_uri . "\n" .
                        $canonical_querystring . "\n" .
                        $canonical_headers . "\n" .
                        $signed_headers . "\n" .
                        $payload_hash;
    
    $algorithm = 'AWS4-HMAC-SHA256';
    $credential_scope = $date_stamp . '/' . $region . '/s3/aws4_request';
    $string_to_sign = $algorithm . "\n" .
                     $amz_date . "\n" .
                     $credential_scope . "\n" .
                     hash('sha256', $canonical_request);
    
    $kSecret = 'AWS4' . $secret_key;
    $kDate = hash_hmac('sha256', $date_stamp, $kSecret, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $string_to_sign, $kSigning);
    
    $authorization = $algorithm . ' ' .
                    'Credential=' . $access_key . '/' . $credential_scope . ', ' .
                    'SignedHeaders=' . $signed_headers . ', ' .
                    'Signature=' . $signature;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'HEAD',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_HTTPHEADER => [
            'Host: ' . $host_header,
            'x-amz-date: ' . $amz_date,
            'Authorization: ' . $authorization,
        ],
    ]);
    
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code >= 200 && $http_code < 300;
}

/**
 * Create MinIO bucket
 */
function minio_create_bucket($endpoint, $bucket, $access_key, $secret_key, $region) {
    $url = $endpoint . '/' . $bucket;
    
    // Parse endpoint URL
    $parsed_url = parse_url($endpoint);
    $host = $parsed_url['host'];
    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $host_header = $host . $port;
    $amz_date = gmdate('Ymd\THis\Z');
    $date_stamp = gmdate('Ymd');
    
    // Generate PUT request signature for bucket creation
    $canonical_uri = '/' . $bucket;
    $canonical_querystring = '';
    $canonical_headers = "host:" . $host_header . "\n" .
                         "x-amz-date:" . $amz_date . "\n";
    $signed_headers = 'host;x-amz-date';
    $payload_hash = hash('sha256', '');
    
    $canonical_request = "PUT\n" .
                        $canonical_uri . "\n" .
                        $canonical_querystring . "\n" .
                        $canonical_headers . "\n" .
                        $signed_headers . "\n" .
                        $payload_hash;
    
    $algorithm = 'AWS4-HMAC-SHA256';
    $credential_scope = $date_stamp . '/' . $region . '/s3/aws4_request';
    $string_to_sign = $algorithm . "\n" .
                     $amz_date . "\n" .
                     $credential_scope . "\n" .
                     hash('sha256', $canonical_request);
    
    $kSecret = 'AWS4' . $secret_key;
    $kDate = hash_hmac('sha256', $date_stamp, $kSecret, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $string_to_sign, $kSigning);
    
    $authorization = $algorithm . ' ' .
                    'Credential=' . $access_key . '/' . $credential_scope . ', ' .
                    'SignedHeaders=' . $signed_headers . ', ' .
                    'Signature=' . $signature;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Host: ' . $host_header,
            'x-amz-date: ' . $amz_date,
            'Authorization: ' . $authorization,
        ],
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code >= 200 && $http_code < 300;
}

/**
 * Get file URL from storage
 */
function get_storage_url($path) {
    $driver = get_storage_driver();
    $config = get_storage_config();
    
    if ($driver === 'minio') {
        $disk_config = $config['disks']['minio'];
        $public_url = $disk_config['url'] ?? $disk_config['endpoint'];
        return rtrim($public_url, '/') . '/' . $disk_config['bucket'] . '/' . $path;
    } else {
        return '/' . $path;
    }
}

/**
 * Delete file from storage
 */
function delete_from_storage($path) {
    $driver = get_storage_driver();
    
    if ($driver === 'minio') {
        return delete_from_minio($path);
    } else {
        $config = get_storage_config();
        $disk_config = $config['disks']['local'];
        $full_path = $disk_config['root'] . '/' . $path;
        if (file_exists($full_path)) {
            return unlink($full_path);
        }
        return true;
    }
}

/**
 * Delete file from MinIO
 */
function delete_from_minio($path) {
    $config = get_storage_config();
    $disk_config = $config['disks']['minio'];
    
    $endpoint = $disk_config['endpoint'];
    $bucket = $disk_config['bucket'];
    $access_key = $disk_config['key'];
    $secret_key = $disk_config['secret'];
    $region = $disk_config['region'];
    
    $url = $endpoint . '/' . $bucket . '/' . $path;
    
    // Parse endpoint URL
    $parsed_url = parse_url($endpoint);
    $host = $parsed_url['host'];
    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $host_header = $host . $port;
    $amz_date = gmdate('Ymd\THis\Z');
    $date_stamp = gmdate('Ymd');
    
    // Generate DELETE request signature
    $canonical_uri = '/' . $bucket . '/' . $path;
    $canonical_querystring = '';
    $canonical_headers = "host:" . $host_header . "\n" .
                         "x-amz-date:" . $amz_date . "\n";
    $signed_headers = 'host;x-amz-date';
    $payload_hash = hash('sha256', '');
    
    $canonical_request = "DELETE\n" .
                        $canonical_uri . "\n" .
                        $canonical_querystring . "\n" .
                        $canonical_headers . "\n" .
                        $signed_headers . "\n" .
                        $payload_hash;
    
    $algorithm = 'AWS4-HMAC-SHA256';
    $credential_scope = $date_stamp . '/' . $region . '/s3/aws4_request';
    $string_to_sign = $algorithm . "\n" .
                     $amz_date . "\n" .
                     $credential_scope . "\n" .
                     hash('sha256', $canonical_request);
    
    $kSecret = 'AWS4' . $secret_key;
    $kDate = hash_hmac('sha256', $date_stamp, $kSecret, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $string_to_sign, $kSigning);
    
    $authorization = $algorithm . ' ' .
                    'Credential=' . $access_key . '/' . $credential_scope . ', ' .
                    'SignedHeaders=' . $signed_headers . ', ' .
                    'Signature=' . $signature;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Host: ' . $host_header,
            'x-amz-date: ' . $amz_date,
            'Authorization: ' . $authorization,
        ],
    ]);
    
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code >= 200 && $http_code < 300;
}
