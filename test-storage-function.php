<?php
/**
 * Test the storage function directly
 */

require_once __DIR__ . '/includes/storage.php';

header('Content-Type: text/plain');

// Create test file
$test_file = '/tmp/storage_test_' . time() . '.txt';
file_put_contents($test_file, "Test content at " . date('Y-m-d H:i:s'));

echo "Testing upload_to_storage function...\n\n";
echo "Source: $test_file\n";
echo "Destination: test/storage_test.txt\n\n";

// Test the function
$result = upload_to_storage($test_file, 'test/storage_test.txt', [
    'content_type' => 'text/plain'
]);

if ($result !== false) {
    echo "✓ Upload successful!\n";
    echo "Returned path: $result\n\n";
    
    // Verify in MinIO
    echo "Verifying in MinIO...\n";
    $alias = 'verify_' . time();
    exec("mc alias set $alias http://minio:9000 goldenz SUOMYNONA 2>&1");
    exec("mc ls $alias/goldenz-uploads/test/ 2>&1", $list_output);
    echo implode("\n", $list_output) . "\n";
    exec("mc alias remove $alias 2>&1");
} else {
    echo "✗ Upload failed!\n";
    echo "Check error logs for details.\n";
}

@unlink($test_file);
