<?php
/**
 * Chat Soft Delete Migration - Web Accessible Version
 * 
 * Access this file via browser to run the migration:
 * http://your-domain/migrations/run_chat_soft_delete_web.php
 */

// Security: Only allow if logged in as admin or in development
session_start();

// For development - remove or restrict in production
$allow_web_access = true; // Set to false in production

if (!$allow_web_access) {
    die('Web access to migrations is disabled for security.');
}

// Check if user is logged in (optional security)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Allow in development, but you can require login
    // die('Please log in first.');
}

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Soft Delete Migration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
            margin: 20px 0;
        }
        .success {
            color: #4caf50;
        }
        .error {
            color: #f44336;
        }
        .warning {
            color: #ff9800;
        }
        .info {
            color: #2196f3;
        }
        .btn {
            background: #2196f3;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #1976d2;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Chat Soft Delete Migration</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
            echo '<div class="output">';
            echo "Starting chat soft delete migration...\n\n";
            
            try {
                $pdo = get_db_connection();
                
                // Read and execute SQL file
                $sqlFile = __DIR__ . '/add_chat_soft_delete.sql';
                
                if (!file_exists($sqlFile)) {
                    throw new Exception("SQL file not found: {$sqlFile}");
                }
                
                $sql = file_get_contents($sqlFile);
                
                // Split into individual statements (skip comments)
                $statements = array_filter(
                    array_map('trim', explode(';', $sql)),
                    function($stmt) {
                        return !empty($stmt) && !str_starts_with($stmt, '--');
                    }
                );
                
                $successCount = 0;
                $skippedCount = 0;
                
                // Execute each statement
                foreach ($statements as $index => $statement) {
                    if (empty(trim($statement))) continue;
                    
                    try {
                        $pdo->exec($statement);
                        echo "<span class='success'>✓ Statement " . ($index + 1) . " executed successfully</span>\n";
                        $successCount++;
                    } catch (PDOException $e) {
                        // Check if error is about column already existing
                        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                            echo "<span class='warning'>⚠ Statement " . ($index + 1) . " skipped (columns already exist)</span>\n";
                            $skippedCount++;
                        } else {
                            throw $e;
                        }
                    }
                }
                
                echo "\n";
                echo "<span class='success'>✓ Chat soft delete migration completed successfully!</span>\n\n";
                echo "<span class='info'>New functionality:</span>\n";
                echo "  - Users can clear their own chat history\n";
                echo "  - Other user still sees all messages\n";
                echo "  - Messages are soft-deleted, not physically removed\n";
                echo "  - 'Delete for me' behavior (like WhatsApp)\n\n";
                echo "<span class='info'>Summary:</span>\n";
                echo "  - Successfully executed: {$successCount} statements\n";
                if ($skippedCount > 0) {
                    echo "  - Skipped (already exists): {$skippedCount} statements\n";
                }
                
                // Verify columns exist
                echo "\n<span class='info'>Verifying columns...</span>\n";
                $checkStmt = $pdo->query("SHOW COLUMNS FROM chat_messages LIKE 'deleted_by%'");
                $columns = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($columns) >= 2) {
                    echo "<span class='success'>✓ Verified: Soft delete columns exist</span>\n";
                    foreach ($columns as $col) {
                        echo "  - {$col['Field']} ({$col['Type']})\n";
                    }
                } else {
                    echo "<span class='warning'>⚠ Warning: Expected 2 columns, found " . count($columns) . "</span>\n";
                }
                
            } catch (Exception $e) {
                echo "\n<span class='error'>✗ Migration failed!</span>\n";
                echo "<span class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                echo "\nPlease check your database configuration and try again.\n";
            }
            
            echo '</div>';
            echo '<a href="?" class="btn">Run Again</a>';
            
        } else {
            ?>
            <p>This will add soft delete support to the chat system, allowing users to clear their own chat history without affecting the other user's view.</p>
            
            <div class="output">
<span class="info">Ready to run migration...</span>

This migration will:
  - Add deleted_by_sender column
  - Add deleted_by_receiver column  
  - Add indexes for performance
  - Enable "Delete for me" functionality

<span class="warning">⚠ Make sure you have a database backup before proceeding.</span>
            </div>
            
            <form method="POST">
                <button type="submit" name="run_migration" class="btn">Run Migration</button>
            </form>
            <?php
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
            <p><strong>Note:</strong> This migration only needs to be run once. The changes are permanent and will persist after Docker restarts.</p>
            <p><strong>Security:</strong> Disable web access to this file in production by setting <code>$allow_web_access = false;</code></p>
        </div>
    </div>
</body>
</html>
