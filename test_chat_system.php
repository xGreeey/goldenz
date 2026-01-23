<?php
/**
 * Chat System Test & Verification Script
 * Tests all components of the chat system
 * 
 * Access: /test_chat_system.php (requires super_admin login)
 */

// Load bootstrap
require_once __DIR__ . '/bootstrap/app.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/security.php';

// Security check
session_start();
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['super_admin', 'developer'])) {
    die('Access denied. This script requires super_admin or developer access.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat System Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding: 2rem; background: #f8f9fa; }
        .test-section { background: white; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-result { margin: 0.5rem 0; padding: 0.5rem 1rem; border-radius: 4px; }
        .test-pass { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .test-fail { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .test-warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4"><i class="fas fa-vial me-2"></i>Chat System Test & Verification</h1>

        <?php
        $tests = [];
        $errors = [];

        // Test 1: Database Connection
        echo '<div class="test-section">';
        echo '<h3><i class="fas fa-database me-2"></i>1. Database Connection</h3>';
        try {
            $pdo = get_db_connection();
            echo '<div class="test-result test-pass"><i class="fas fa-check-circle me-2"></i>Database connection successful</div>';
            $tests['db_connection'] = true;
        } catch (Exception $e) {
            echo '<div class="test-result test-fail"><i class="fas fa-times-circle me-2"></i>Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $tests['db_connection'] = false;
            $errors[] = 'Database connection failed';
        }
        echo '</div>';

        // Test 2: Tables Exist
        echo '<div class="test-section">';
        echo '<h3><i class="fas fa-table me-2"></i>2. Database Tables</h3>';
        $requiredTables = ['chat_messages', 'chat_typing_status', 'chat_conversations', 'users'];
        foreach ($requiredTables as $table) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($stmt->rowCount() > 0) {
                    echo '<div class="test-result test-pass"><i class="fas fa-check-circle me-2"></i>Table <strong>' . $table . '</strong> exists</div>';
                    $tests["table_{$table}"] = true;
                    
                    // Show row count
                    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
                    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                    echo '<div class="ms-4 text-muted small">Rows: ' . $count . '</div>';
                } else {
                    echo '<div class="test-result test-fail"><i class="fas fa-times-circle me-2"></i>Table <strong>' . $table . '</strong> does NOT exist</div>';
                    $tests["table_{$table}"] = false;
                    $errors[] = "Table {$table} missing";
                }
            } catch (Exception $e) {
                echo '<div class="test-result test-fail"><i class="fas fa-times-circle me-2"></i>Error checking table ' . $table . ': ' . htmlspecialchars($e->getMessage()) . '</div>';
                $tests["table_{$table}"] = false;
            }
        }
        echo '</div>';

        // Test 3: Table Structure
        if (isset($tests['table_chat_messages']) && $tests['table_chat_messages']) {
            echo '<div class="test-section">';
            echo '<h3><i class="fas fa-columns me-2"></i>3. Table Structure</h3>';
            try {
                $stmt = $pdo->query("DESCRIBE chat_messages");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $requiredColumns = ['id', 'sender_id', 'receiver_id', 'message', 'is_read', 'read_at', 'created_at'];
                
                foreach ($requiredColumns as $col) {
                    $found = false;
                    foreach ($columns as $column) {
                        if ($column['Field'] === $col) {
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        echo '<div class="test-result test-pass"><i class="fas fa-check-circle me-2"></i>Column <strong>' . $col . '</strong> exists</div>';
                    } else {
                        echo '<div class="test-result test-fail"><i class="fas fa-times-circle me-2"></i>Column <strong>' . $col . '</strong> missing</div>';
                        $errors[] = "Column {$col} missing from chat_messages";
                    }
                }
                $tests['table_structure'] = true;
            } catch (Exception $e) {
                echo '<div class="test-result test-fail"><i class="fas fa-times-circle me-2"></i>Error checking table structure: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $tests['table_structure'] = false;
            }
            echo '</div>';
        }

        // Test 4: Files Exist
        echo '<div class="test-section">';
        echo '<h3><i class="fas fa-file-code me-2"></i>4. Required Files</h3>';
        $requiredFiles = [
            'api/chat.php' => 'API Endpoint',
            'pages/chat.php' => 'Chat Page',
            'assets/js/chat.js' => 'JavaScript Client',
            'migrations/add_chat_system.sql' => 'Migration File'
        ];
        
        foreach ($requiredFiles as $file => $description) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                $size = filesize($path);
                echo '<div class="test-result test-pass"><i class="fas fa-check-circle me-2"></i><strong>' . $description . '</strong> (' . $file . ') - ' . number_format($size) . ' bytes</div>';
                $tests["file_" . str_replace(['/', '.'], '_', $file)] = true;
            } else {
                echo '<div class="test-result test-fail"><i class="fas fa-times-circle me-2"></i><strong>' . $description . '</strong> (' . $file . ') NOT FOUND</div>';
                $tests["file_" . str_replace(['/', '.'], '_', $file)] = false;
                $errors[] = "File {$file} missing";
            }
        }
        echo '</div>';

        // Test 5: API Endpoint
        echo '<div class="test-section">';
        echo '<h3><i class="fas fa-plug me-2"></i>5. API Endpoint Test</h3>';
        $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/chat.php?action=get_users';
        echo '<div class="mb-2"><strong>Testing:</strong> <code>' . htmlspecialchars($apiUrl) . '</code></div>';
        
        try {
            // Simulate API call using file_get_contents with session cookie
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => 'Cookie: ' . session_name() . '=' . session_id()
                ]
            ]);
            
            $response = @file_get_contents($apiUrl, false, $context);
            if ($response !== false) {
                $data = json_decode($response, true);
                if (isset($data['success']) && $data['success']) {
                    echo '<div class="test-result test-pass"><i class="fas fa-check-circle me-2"></i>API endpoint is responding correctly</div>';
                    echo '<div class="ms-4 text-muted small">Users found: ' . count($data['users']) . '</div>';
                    $tests['api_endpoint'] = true;
                } else {
                    echo '<div class="test-result test-warning"><i class="fas fa-exclamation-triangle me-2"></i>API responded but returned error: ' . htmlspecialchars($data['error'] ?? 'Unknown error') . '</div>';
                    $tests['api_endpoint'] = false;
                }
            } else {
                echo '<div class="test-result test-fail"><i class="fas fa-times-circle me-2"></i>API endpoint did not respond</div>';
                $tests['api_endpoint'] = false;
                $errors[] = 'API endpoint not responding';
            }
        } catch (Exception $e) {
            echo '<div class="test-result test-fail"><i class="fas fa-times-circle me-2"></i>API test failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $tests['api_endpoint'] = false;
        }
        echo '</div>';

        // Test 6: Session & Authentication
        echo '<div class="test-section">';
        echo '<h3><i class="fas fa-user-lock me-2"></i>6. Session & Authentication</h3>';
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            echo '<div class="test-result test-pass"><i class="fas fa-check-circle me-2"></i>User is authenticated</div>';
            echo '<div class="ms-4 text-muted small">User ID: ' . ($_SESSION['user_id'] ?? 'N/A') . '</div>';
            echo '<div class="ms-4 text-muted small">Username: ' . htmlspecialchars($_SESSION['username'] ?? 'N/A') . '</div>';
            echo '<div class="ms-4 text-muted small">Role: ' . htmlspecialchars($_SESSION['user_role'] ?? 'N/A') . '</div>';
            $tests['authentication'] = true;
        } else {
            echo '<div class="test-result test-fail"><i class="fas fa-times-circle me-2"></i>User is NOT authenticated</div>';
            $tests['authentication'] = false;
            $errors[] = 'User not authenticated';
        }
        echo '</div>';

        // Test 7: Active Users
        if ($tests['db_connection'] && $tests['table_users']) {
            echo '<div class="test-section">';
            echo '<h3><i class="fas fa-users me-2"></i>7. Active Users</h3>';
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'active' AND id != " . ($_SESSION['user_id'] ?? 0));
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($count > 0) {
                    echo '<div class="test-result test-pass"><i class="fas fa-check-circle me-2"></i>Found ' . $count . ' active user(s) to chat with</div>';
                    
                    // Show sample users
                    $usersStmt = $pdo->query("SELECT id, name, username, role FROM users WHERE status = 'active' AND id != " . ($_SESSION['user_id'] ?? 0) . " LIMIT 5");
                    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
                    echo '<div class="ms-4 mt-2"><strong>Sample users:</strong></div>';
                    echo '<ul class="ms-4">';
                    foreach ($users as $user) {
                        echo '<li>' . htmlspecialchars($user['name']) . ' (@' . htmlspecialchars($user['username']) . ') - ' . htmlspecialchars($user['role']) . '</li>';
                    }
                    echo '</ul>';
                    $tests['active_users'] = true;
                } else {
                    echo '<div class="test-result test-warning"><i class="fas fa-exclamation-triangle me-2"></i>No other active users found. Create more users to test chat.</div>';
                    $tests['active_users'] = false;
                }
            } catch (Exception $e) {
                echo '<div class="test-result test-fail"><i class="fas fa-times-circle me-2"></i>Error checking users: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $tests['active_users'] = false;
            }
            echo '</div>';
        }

        // Summary
        $totalTests = count($tests);
        $passedTests = count(array_filter($tests));
        $failedTests = $totalTests - $passedTests;
        
        echo '<div class="test-section">';
        echo '<h3><i class="fas fa-chart-pie me-2"></i>Test Summary</h3>';
        echo '<div class="row">';
        echo '<div class="col-md-4"><div class="alert alert-info">Total Tests: <strong>' . $totalTests . '</strong></div></div>';
        echo '<div class="col-md-4"><div class="alert alert-success">Passed: <strong>' . $passedTests . '</strong></div></div>';
        echo '<div class="col-md-4"><div class="alert alert-danger">Failed: <strong>' . $failedTests . '</strong></div></div>';
        echo '</div>';
        
        if ($failedTests === 0) {
            echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><strong>All tests passed!</strong> The chat system is ready to use.</div>';
            echo '<div class="text-center mt-3">';
            echo '<a href="/hr-admin/?page=chat" class="btn btn-primary btn-lg"><i class="fas fa-comments me-2"></i>Open Chat System</a>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i><strong>Some tests failed.</strong> Please fix the issues above before using the chat system.</div>';
            if (!empty($errors)) {
                echo '<div class="mt-3"><strong>Issues to fix:</strong><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . htmlspecialchars($error) . '</li>';
                }
                echo '</ul></div>';
            }
        }
        echo '</div>';
        ?>

        <div class="test-section">
            <h3><i class="fas fa-book me-2"></i>Next Steps</h3>
            <ol>
                <li>If migration not run, execute: <code>php migrations/run_chat_migration.php</code></li>
                <li>Access chat at: <code>/hr-admin/?page=chat</code></li>
                <li>Test sending messages between users</li>
                <li>Check unread indicators update correctly</li>
                <li>Review deployment guide: <code>CHAT_DEPLOYMENT_GUIDE.md</code></li>
            </ol>
        </div>

        <div class="text-center mt-4">
            <a href="/hr-admin/" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
            <button onclick="location.reload()" class="btn btn-primary"><i class="fas fa-sync-alt me-2"></i>Rerun Tests</button>
        </div>
    </div>
</body>
</html>
