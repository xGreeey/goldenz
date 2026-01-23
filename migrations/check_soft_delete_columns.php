<?php
/**
 * Check if Soft Delete Columns Exist
 * 
 * Quick check to see if you need to run the migration
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $pdo = get_db_connection();
    
    echo "Checking for soft delete columns...\n\n";
    
    $checkStmt = $pdo->query("SHOW COLUMNS FROM chat_messages WHERE Field IN ('deleted_by_sender', 'deleted_by_receiver')");
    $columns = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasBoth = count($columns) >= 2;
    
    if ($hasBoth) {
        echo "✓ Soft delete columns already exist!\n\n";
        echo "Found columns:\n";
        foreach ($columns as $col) {
            echo "  - {$col}\n";
        }
        echo "\nYou do NOT need to run the migration.\n";
        echo "Clear history feature is ready to use.\n";
    } else {
        echo "✗ Soft delete columns are missing.\n\n";
        echo "Found: " . count($columns) . " column(s)\n";
        if (count($columns) > 0) {
            echo "Columns found:\n";
            foreach ($columns as $col) {
                echo "  - {$col}\n";
            }
        }
        echo "\nYou need to run the migration ONCE:\n";
        echo "  php src/migrations/run_add_soft_delete_columns.php\n";
        echo "\nOr via browser:\n";
        echo "  http://goldenz.local/migrations/run_add_soft_delete_columns.php\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
