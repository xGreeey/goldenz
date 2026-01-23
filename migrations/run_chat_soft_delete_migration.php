<?php
/**
 * Migration Runner: Add Chat Soft Delete Support
 * 
 * This script adds soft delete functionality so users can clear their own
 * chat history without affecting the other user's view.
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $pdo = get_db_connection();
    
    echo "Starting chat soft delete migration...\n\n";
    
    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/add_chat_soft_delete.sql');
    
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
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠ Statement " . ($index + 1) . " skipped (columns already exist)\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n✓ Chat soft delete migration completed successfully!\n";
    echo "\nNew functionality:\n";
    echo "  - Users can clear their own chat history\n";
    echo "  - Other user still sees all messages\n";
    echo "  - Messages are soft-deleted, not physically removed\n";
    echo "  - 'Delete for me' behavior (like WhatsApp)\n";
    
} catch (PDOException $e) {
    echo "\n✗ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration and try again.\n";
    exit(1);
}
