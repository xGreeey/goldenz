<?php
/**
 * Test Script: Database Backup to MinIO
 * 
 * This script tests if SQL backups can be saved to MinIO
 * Access this file via: http://localhost/test-minio-backup.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/storage.php';

// Set content type
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MinIO Backup Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #17a2b8;
        }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .step h3 {
            margin-top: 0;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 MinIO Database Backup Test</h1>
        
        <?php
        $action = $_GET['action'] ?? 'test';
        
        if ($action === 'test-upload') {
            // Simple test upload
            echo '<div class="step">';
            echo '<h3>Testing Simple File Upload to MinIO...</h3>';
            
            try {
                // Create a test file
                $test_content = "This is a test file created at " . date('Y-m-d H:i:s');
                $test_file = sys_get_temp_dir() . '/minio_test_' . time() . '.txt';
                file_put_contents($test_file, $test_content);
                
                echo '<div class="info">Created test file: ' . $test_file . '</div>';
                
                // Try to upload
                $test_path = 'test/test_' . time() . '.txt';
                $result = upload_to_storage($test_file, $test_path, [
                    'content_type' => 'text/plain'
                ]);
                
                if ($result !== false) {
                    echo '<div class="success">✓ Test upload successful!</div>';
                    echo '<pre>Uploaded to: ' . $test_path . '</pre>';
                } else {
                    echo '<div class="error">✗ Test upload failed</div>';
                    echo '<div class="info">Check PHP error logs for details. Run: docker logs hr_web</div>';
                }
                
                // Clean up
                @unlink($test_file);
                
            } catch (Exception $e) {
                echo '<div class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            echo '</div>';
            echo '<a href="test-minio-backup.php" class="btn">← Back to Test Page</a>';
            
        } else if ($action === 'backup') {
            echo '<div class="step">';
            echo '<h3>Step 1: Creating Database Backup...</h3>';
            
            try {
                // Check if backup function exists
                if (!function_exists('create_database_backup')) {
                    throw new Exception('create_database_backup function not found');
                }
                
                // Create backup
                $backup_result = create_database_backup();
                
                if (!$backup_result['success']) {
                    throw new Exception($backup_result['message']);
                }
                
                echo '<div class="success">✓ Database backup created successfully!</div>';
                echo '<pre>';
                echo "Filename: " . $backup_result['filename'] . "\n";
                echo "Filepath: " . $backup_result['filepath'] . "\n";
                echo "Size: " . number_format($backup_result['size'] / 1024, 2) . " KB\n";
                echo '</pre>';
                
                // Step 2: Upload to MinIO
                echo '<h3>Step 2: Uploading to MinIO...</h3>';
                
                // Get the full local file path
                // The filepath might be relative or absolute
                if (strpos($backup_result['filepath'], '/') === 0 || strpos($backup_result['filepath'], 'C:') === 0) {
                    // Absolute path
                    $local_file_path = $backup_result['filepath'];
                } else {
                    // Relative path
                    $local_file_path = __DIR__ . '/' . $backup_result['filepath'];
                }
                
                if (!file_exists($local_file_path)) {
                    throw new Exception("Backup file not found: $local_file_path (checked from: " . __DIR__ . ")");
                }
                
                // Upload to MinIO
                $minio_path = 'backups/' . $backup_result['filename'];
                $upload_result = upload_to_storage($local_file_path, $minio_path, [
                    'content_type' => 'application/sql'
                ]);
                
                if ($upload_result === false) {
                    // Get more detailed error information
                    $error_msg = 'Failed to upload backup to MinIO. ';
                    $error_msg .= 'Check PHP error logs and Docker logs for details. ';
                    $error_msg .= 'You can check logs with: docker logs hr_web';
                    throw new Exception($error_msg);
                }
                
                echo '<div class="success">✓ Backup uploaded to MinIO successfully!</div>';
                echo '<pre>';
                echo "MinIO Path: " . $minio_path . "\n";
                echo "Storage Driver: " . get_storage_driver() . "\n";
                echo '</pre>';
                
                // Step 3: Get MinIO URL
                echo '<h3>Step 3: Getting MinIO URL...</h3>';
                $minio_url = get_storage_url($minio_path);
                echo '<div class="info">';
                echo '<strong>MinIO URL:</strong><br>';
                echo '<a href="' . htmlspecialchars($minio_url) . '" target="_blank">' . htmlspecialchars($minio_url) . '</a>';
                echo '</div>';
                
                // Step 4: Verify MinIO configuration
                echo '<h3>Step 4: MinIO Configuration</h3>';
                $config = get_storage_config();
                $minio_config = $config['disks']['minio'];
                echo '<pre>';
                echo "Endpoint: " . $minio_config['endpoint'] . "\n";
                echo "Bucket: " . $minio_config['bucket'] . "\n";
                echo "Access Key: " . $minio_config['key'] . "\n";
                echo "Region: " . $minio_config['region'] . "\n";
                echo '</pre>';
                
                echo '<div class="success">';
                echo '<h3>✅ Test Completed Successfully!</h3>';
                echo '<p>Your database backup has been successfully saved to MinIO.</p>';
                echo '<p>You can verify this by:</p>';
                echo '<ul>';
                echo '<li>Accessing MinIO Console at <a href="http://localhost:9001" target="_blank">http://localhost:9001</a></li>';
                echo '<li>Login with: goldenz / SUOMYNONA</li>';
                echo '<li>Check the bucket: ' . $minio_config['bucket'] . '</li>';
                echo '<li>Look for the file in: backups/' . $backup_result['filename'] . '</li>';
                echo '</ul>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h3>❌ Error</h3>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '<a href="test-minio-backup.php" class="btn">← Back to Test Page</a>';
            
        } else {
            // Show test page
            ?>
            <div class="info">
                <h3>What this test does:</h3>
                <ol>
                    <li>Creates a SQL backup of your database using mysqldump or PHP fallback</li>
                    <li>Uploads the backup file to MinIO storage</li>
                    <li>Displays the MinIO URL where the backup is stored</li>
                    <li>Shows MinIO configuration details</li>
                </ol>
            </div>
            
            <div class="step">
                <h3>Current Configuration</h3>
                <?php
                try {
                    $config = get_storage_config();
                    $minio_config = $config['disks']['minio'];
                    echo '<pre>';
                    echo "Storage Driver: " . $config['default'] . "\n";
                    echo "MinIO Endpoint: " . $minio_config['endpoint'] . "\n";
                    echo "MinIO Bucket: " . $minio_config['bucket'] . "\n";
                    echo "MinIO Access Key: " . $minio_config['key'] . "\n";
                    echo "MinIO Region: " . $minio_config['region'] . "\n";
                    echo '</pre>';
                    
                    // Test database connection
                    $pdo = get_db_connection();
                    echo '<div class="success">✓ Database connection successful</div>';
                    
                    // Test MinIO connectivity
                    echo '<h3>Testing MinIO Connectivity...</h3>';
                    if (function_exists('test_minio_connectivity')) {
                        $minio_test = test_minio_connectivity();
                        if ($minio_test['success']) {
                            echo '<div class="success">✓ MinIO connection successful (HTTP ' . $minio_test['http_code'] . ')</div>';
                        } else {
                            echo '<div class="error">✗ MinIO connection failed</div>';
                            echo '<pre>';
                            echo "HTTP Code: " . $minio_test['http_code'] . "\n";
                            echo "Error: " . ($minio_test['error'] ?: 'None') . "\n";
                            echo "Endpoint: " . $minio_test['endpoint'] . "\n";
                            if ($minio_test['response']) {
                                echo "Response: " . htmlspecialchars(substr($minio_test['response'], 0, 500)) . "\n";
                            }
                            echo '</pre>';
                        }
                    } else {
                        echo '<div class="error">✗ test_minio_connectivity function not found</div>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="error">✗ Configuration error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="?action=test-upload" class="btn">🧪 Test Simple Upload</a>
                <a href="?action=backup" class="btn">🚀 Start Backup Test</a>
            </div>
            
            <div class="info">
                <h3>📝 Notes:</h3>
                <ul>
                    <li>Make sure your Docker containers are running</li>
                    <li>Ensure MinIO is accessible at <code>http://minio:9000</code> from the web container</li>
                    <li>The backup will be stored in the <code>backups/</code> folder in your MinIO bucket</li>
                    <li>You can access MinIO Console at <a href="http://localhost:9001" target="_blank">http://localhost:9001</a></li>
                </ul>
            </div>
            
            <div class="step">
                <h3>🔍 Debugging</h3>
                <p>If uploads fail, check the logs:</p>
                <pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px;">
# View PHP error logs from Docker container
docker logs hr_web 2>&1 | tail -50

# Or view in real-time
docker logs -f hr_web

# Check MinIO logs
docker logs hr_minio
                </pre>
                <p><strong>Common Issues:</strong></p>
                <ul>
                    <li><strong>Connection refused:</strong> MinIO container might not be running or network issue</li>
                    <li><strong>403 Forbidden:</strong> Check MinIO credentials (Access Key / Secret Key)</li>
                    <li><strong>Signature mismatch:</strong> Usually means credentials are wrong or signature calculation issue</li>
                    <li><strong>Bucket doesn't exist:</strong> The bucket should be created automatically, but verify in MinIO Console</li>
                </ul>
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html>
