<?php
/**
 * Test MinIO Client Upload
 */

header('Content-Type: text/plain');

$endpoint = 'http://minio:9000';
$bucket = 'goldenz-uploads';
$access_key = 'goldenz';
$secret_key = 'SUOMYNONA';
$destination_path = 'test/test_mc_' . time() . '.txt';

// Create test file
$test_file = '/tmp/test_mc_' . time() . '.txt';
file_put_contents($test_file, "Test content from PHP at " . date('Y-m-d H:i:s'));

echo "Testing MinIO Client Upload...\n\n";
echo "Source file: $test_file\n";
echo "Destination: $bucket/$destination_path\n\n";

// Check if mc is available
exec('which mc 2>&1', $which_output, $which_return);
if ($which_return !== 0) {
    die("ERROR: mc command not found!\n");
}
echo "✓ mc is installed: " . implode("\n", $which_output) . "\n\n";

// Set alias
$alias = 'testminio_' . time();
$alias_cmd = sprintf(
    'mc alias set %s %s %s %s 2>&1',
    escapeshellarg($alias),
    escapeshellarg($endpoint),
    escapeshellarg($access_key),
    escapeshellarg($secret_key)
);

echo "Setting alias...\n";
echo "Command: $alias_cmd\n";
exec($alias_cmd, $alias_output, $alias_return);
echo "Return code: $alias_return\n";
echo "Output: " . implode("\n", $alias_output) . "\n\n";

if ($alias_return !== 0) {
    die("ERROR: Failed to set alias!\n");
}
echo "✓ Alias set successfully\n\n";

// Upload file
$minio_path = "$alias/$bucket/$destination_path";
$upload_cmd = sprintf(
    'mc cp %s %s 2>&1',
    escapeshellarg($test_file),
    escapeshellarg($minio_path)
);

echo "Uploading file...\n";
echo "Command: $upload_cmd\n";
exec($upload_cmd, $upload_output, $upload_return);
echo "Return code: $upload_return\n";
echo "Output: " . implode("\n", $upload_output) . "\n\n";

if ($upload_return === 0) {
    echo "✓ Upload successful!\n\n";
    
    // Verify file exists
    echo "Verifying file in MinIO...\n";
    $list_cmd = sprintf('mc ls %s/%s/test/ 2>&1', escapeshellarg($alias), escapeshellarg($bucket));
    exec($list_cmd, $list_output, $list_return);
    echo implode("\n", $list_output) . "\n";
} else {
    echo "✗ Upload failed!\n";
}

// Clean up
exec(sprintf('mc alias remove %s 2>&1', escapeshellarg($alias)));
@unlink($test_file);
