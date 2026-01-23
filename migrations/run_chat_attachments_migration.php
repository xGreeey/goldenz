<?php
/**
 * Migration Runner: Add Chat Attachments Support
 * 
 * This script adds support for image attachments in the chat system.
 * Run this after the main chat system migration.
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $pdo = get_db_connection();
    
    echo "Starting chat attachments migration...\n\n";
    
    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/add_chat_attachments.sql');
    
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
    
    echo "\n✓ Chat attachments migration completed successfully!\n";
    echo "\nNew features added:\n";
    echo "  - Image attachment support in messages\n";
    echo "  - Photo upload with preview\n";
    echo "  - Attachment metadata storage\n";
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/chat_attachments';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "\n✓ Created uploads directory: {$uploadDir}\n";
    } else {
        echo "\n✓ Uploads directory already exists: {$uploadDir}\n";
    }
    
} catch (PDOException $e) {
    echo "\n✗ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration and try again.\n";
    exit(1);
}
