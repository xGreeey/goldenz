<?php
/**
 * Chat System Migration Runner
 * Run this file from browser: /migrations/run_chat_migration.php
 * Or via CLI: php run_chat_migration.php
 */

// Load bootstrap
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/database.php';

// Security: Only run if accessed by authenticated admin or via CLI
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
        die('Access denied. This script can only be run by super administrators.');
    }
}

echo "Chat System Migration\n";
echo "=====================\n\n";

try {
    $pdo = get_db_connection();
    
    // Read SQL file
    $sqlFile = __DIR__ . '/add_chat_system.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon to get individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && strpos($stmt, '--') !== 0;
        }
    );
    
    echo "Found " . count($statements) . " SQL statements to execute.\n\n";
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty($statement)) continue;
        
        try {
            echo "Executing statement " . ($index + 1) . "... ";
            $pdo->exec($statement);
            echo "✓ Success\n";
            $success++;
        } catch (PDOException $e) {
            // Check if error is "table already exists"
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ Table already exists (skipping)\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n";
    echo "=====================\n";
    echo "Migration Summary:\n";
    echo "  Successful: {$success}\n";
    echo "  Errors: {$errors}\n";
    echo "=====================\n\n";
    
    if ($errors === 0) {
        echo "✓ Migration completed successfully!\n";
        echo "\nYou can now use the chat system at: /hr-admin/?page=chat\n";
    } else {
        echo "⚠ Migration completed with errors. Please check the messages above.\n";
    }
    
    // Verify tables were created
    echo "\nVerifying tables...\n";
    $tables = ['chat_messages', 'chat_typing_status', 'chat_conversations'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ {$table} exists\n";
        } else {
            echo "  ✗ {$table} does NOT exist\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
