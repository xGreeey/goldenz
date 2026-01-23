<?php
/**
 * Migration Runner: Add Soft Delete Columns
 * 
 * Adds soft delete columns to existing chat_messages table
 * Run this if you already have the chat system installed
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $pdo = get_db_connection();
    
    // Check if columns already exist
    $checkStmt = $pdo->query("SHOW COLUMNS FROM chat_messages WHERE Field IN ('deleted_by_sender', 'deleted_by_receiver')");
    $existingColumns = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($existingColumns) >= 2) {
        echo "✓ Soft delete columns already exist!\n\n";
        echo "Found columns:\n";
        foreach ($existingColumns as $col) {
            echo "  - {$col}\n";
        }
        echo "\nNo migration needed. Clear history feature is ready to use.\n";
        exit(0);
    }
    
    echo "Adding soft delete columns to chat_messages table...\n\n";
    
    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/add_soft_delete_columns.sql');
    
    // Split into individual statements (skip comments)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !str_starts_with($stmt, '--');
        }
    );
    
    // Execute each statement
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            echo "✓ Statement " . ($index + 1) . " executed successfully\n";
        } catch (PDOException $e) {
            // Check if error is about column already existing
            if (strpos($e->getMessage(), 'Duplicate column name') !== false 
                || strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "⚠ Statement " . ($index + 1) . " skipped (already exists)\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n✓ Soft delete columns added successfully!\n";
    echo "\nNow clear history will only remove messages from your view,\n";
    echo "not from the other user's view (Delete for me functionality).\n";
    
} catch (PDOException $e) {
    echo "\n✗ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration and try again.\n";
    exit(1);
}
