<?php
/**
 * Test Script: Rclone Google Drive Backup
 * 
 * This script tests if backups can be uploaded to Google Drive using rclone
 * Access this file via: http://localhost/test-rclone-gdrive.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/storage.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rclone Google Drive Backup Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
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
        h2, h3 {
            color: #555;
            margin-top: 30px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 4px solid #007bff;
        }
        code {
            font-family: 'Courier New', monospace;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Rclone Google Drive Backup Test</h1>
        
        <?php
        $action = $_GET['action'] ?? 'test';
        
        if ($action === 'test_connection') {
            echo '<h2>Testing Rclone Google Drive Connection</h2>';
            
            $remote_name = $_ENV['RCLONE_REMOTE'] ?? 'gdrive';
            echo '<div class="info">Testing rclone remote: <strong>' . htmlspecialchars($remote_name) . '</strong></div>';
            
            $test_result = test_rclone_gdrive($remote_name);
            
            if ($test_result['success']) {
                echo '<div class="success">✓ Rclone Google Drive connection successful!</div>';
                echo '<h3>Remote Contents:</h3>';
                echo '<pre>' . htmlspecialchars($test_result['output']) . '</pre>';
            } else {
                echo '<div class="error">✗ Rclone Google Drive connection failed</div>';
                echo '<div class="error">';
                echo '<strong>Error:</strong> ' . htmlspecialchars($test_result['error'] ?? 'Unknown error') . '<br>';
                if (isset($test_result['output'])) {
                    echo '<strong>Output:</strong><br>';
                    echo '<pre>' . htmlspecialchars($test_result['output']) . '</pre>';
                }
                echo '</div>';
            }
            
            echo '<a href="test-rclone-gdrive.php" class="btn">← Back to Test Page</a>';
            
        } elseif ($action === 'test_upload') {
            echo '<h2>Testing Backup Upload to Google Drive</h2>';
            
            // Step 1: Create a test backup
            echo '<h3>Step 1: Creating test backup...</h3>';
            $result = create_database_backup();
            
            if ($result['success']) {
                echo '<div class="success">✓ Backup created successfully: ' . htmlspecialchars($result['filename']) . '</div>';
                echo '<div class="info">Backup size: ' . number_format($result['size'] / 1024, 2) . ' KB</div>';
                
                if (isset($result['compressed']) && $result['compressed']) {
                    echo '<div class="info">✓ Backup compressed: ' . htmlspecialchars($result['compressed_filename']) . 
                         ' (' . number_format($result['compressed_size'] / 1024, 2) . ' KB)</div>';
                }
                
                // Step 2: Check upload results
                echo '<h3>Step 2: Upload Results</h3>';
                
                if (isset($result['minio_uploaded']) && $result['minio_uploaded']) {
                    echo '<div class="success">✓ Backup uploaded to MinIO: ' . htmlspecialchars($result['minio_path']) . '</div>';
                } else {
                    echo '<div class="warning">⚠ Backup NOT uploaded to MinIO</div>';
                }
                
                if (isset($result['gdrive_uploaded']) && $result['gdrive_uploaded']) {
                    echo '<div class="success">✓ Backup uploaded to Google Drive: ' . htmlspecialchars($result['gdrive_path']) . '</div>';
                    echo '<div class="success">';
                    echo '<strong>Google Drive Path:</strong> ' . htmlspecialchars($result['gdrive_path']) . '<br>';
                    echo 'You can verify this file in your Google Drive under the "db-backups" folder.';
                    echo '</div>';
                } else {
                    echo '<div class="error">✗ Backup NOT uploaded to Google Drive</div>';
                    echo '<div class="warning">';
                    echo '<strong>Possible reasons:</strong><br>';
                    echo '<ul>';
                    echo '<li>Rclone is not installed</li>';
                    echo '<li>Rclone remote is not configured (check RCLONE_REMOTE environment variable)</li>';
                    echo '<li>Google Drive authentication failed</li>';
                    echo '<li>Check rclone configuration: <code>rclone listremotes</code></li>';
                    echo '</ul>';
                    echo '</div>';
                }
                
                echo '<h3>Step 3: Summary</h3>';
                echo '<div class="info">';
                echo '<strong>Backup Details:</strong><br>';
                echo 'Filename: ' . htmlspecialchars($result['filename']) . '<br>';
                echo 'Size: ' . number_format($result['size'] / 1024, 2) . ' KB<br>';
                echo 'MinIO: ' . (isset($result['minio_uploaded']) && $result['minio_uploaded'] ? '✓ Uploaded' : '✗ Not uploaded') . '<br>';
                echo 'Google Drive: ' . (isset($result['gdrive_uploaded']) && $result['gdrive_uploaded'] ? '✓ Uploaded' : '✗ Not uploaded') . '<br>';
                echo '</div>';
                
            } else {
                echo '<div class="error">✗ Backup creation failed: ' . htmlspecialchars($result['message']) . '</div>';
            }
            
            echo '<a href="test-rclone-gdrive.php" class="btn">← Back to Test Page</a>';
            
        } else {
            // Main test page
            ?>
            <div class="section">
                <h2>What This Test Does</h2>
                <ul>
                    <li>Tests rclone connection to Google Drive</li>
                    <li>Creates a database backup</li>
                    <li>Uploads the backup to MinIO (if configured)</li>
                    <li>Uploads the backup to Google Drive using rclone</li>
                    <li>Shows upload status and results</li>
                </ul>
            </div>
            
            <div class="section">
                <h2>Prerequisites</h2>
                <ol>
                    <li><strong>Install rclone:</strong> <code>apt-get install rclone</code> or download from <a href="https://rclone.org/" target="_blank">rclone.org</a></li>
                    <li><strong>Configure rclone remote:</strong> Run <code>rclone config</code> to set up Google Drive</li>
                    <li><strong>Set remote name:</strong> Set environment variable <code>RCLONE_REMOTE</code> (default: 'gdrive')</li>
                    <li><strong>Test connection:</strong> Run <code>rclone lsd gdrive:</code> to verify</li>
                </ol>
            </div>
            
            <div class="section">
                <h2>Configuration</h2>
                <?php
                $remote_name = $_ENV['RCLONE_REMOTE'] ?? 'gdrive';
                echo '<div class="info">';
                echo '<strong>Rclone Remote Name:</strong> ' . htmlspecialchars($remote_name) . '<br>';
                echo '(Set via RCLONE_REMOTE environment variable)';
                echo '</div>';
                
                // Check if rclone is available
                if (function_exists('exec')) {
                    exec('which rclone 2>&1', $which_output, $which_return);
                    if ($which_return === 0) {
                        echo '<div class="success">✓ rclone is installed</div>';
                    } else {
                        echo '<div class="error">✗ rclone is NOT installed</div>';
                    }
                } else {
                    echo '<div class="error">✗ exec() function is not available</div>';
                }
                ?>
            </div>
            
            <div class="section">
                <h2>Run Tests</h2>
                <a href="test-rclone-gdrive.php?action=test_connection" class="btn">Test Rclone Connection</a>
                <a href="test-rclone-gdrive.php?action=test_upload" class="btn">Test Full Backup Upload</a>
            </div>
            
            <div class="section">
                <h2>Troubleshooting</h2>
                <h3>1. Check if rclone is installed:</h3>
                <pre>docker exec hr_web which rclone</pre>
                
                <h3>2. List configured remotes:</h3>
                <pre>docker exec hr_web rclone listremotes</pre>
                
                <h3>3. Test Google Drive connection:</h3>
                <pre>docker exec hr_web rclone lsd gdrive:</pre>
                
                <h3>4. Configure rclone (if not done):</h3>
                <pre>docker exec -it hr_web rclone config</pre>
                <p>Follow the prompts to set up Google Drive. You'll need to:</p>
                <ul>
                    <li>Select "Google Drive" as the storage type</li>
                    <li>Authenticate with Google (browser will open)</li>
                    <li>Name your remote (e.g., "gdrive")</li>
                </ul>
                
                <h3>5. Manual upload test:</h3>
                <pre>docker exec hr_web rclone copy /path/to/test.txt gdrive:db-backups/</pre>
                
                <h3>Common Issues:</h3>
                <ul>
                    <li><strong>rclone not found:</strong> Install rclone in your Docker container</li>
                    <li><strong>Authentication failed:</strong> Re-run <code>rclone config</code> and re-authenticate</li>
                    <li><strong>Permission denied:</strong> Check Google Drive API permissions</li>
                    <li><strong>Remote not found:</strong> Verify remote name matches RCLONE_REMOTE environment variable</li>
                </ul>
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html>
