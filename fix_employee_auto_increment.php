<?php
/**
 * Fix Employee Auto-Increment Counter
 * 
 * This script resets the auto-increment counter for the employees table
 * to be one more than the maximum existing ID.
 * 
 * Usage: Run this file directly in your browser or via command line
 */

// Include necessary files
require_once __DIR__ . '/includes/database.php';

// Check if function exists
if (!function_exists('fix_employee_auto_increment')) {
    die("Error: fix_employee_auto_increment function not found. Please check includes/database.php");
}

// Run the fix
$result = fix_employee_auto_increment();

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Employee Auto-Increment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .details {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .details p {
            margin: 8px 0;
        }
        .details strong {
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Employee Auto-Increment Counter</h1>
        
        <?php if ($result['success']): ?>
            <div class="success">
                <strong>✓ Success!</strong> <?php echo htmlspecialchars($result['message']); ?>
            </div>
            
            <div class="details">
                <h3>Details:</h3>
                <p><strong>Maximum Existing ID:</strong> <?php echo $result['max_id']; ?></p>
                <p><strong>New Auto-Increment Value:</strong> <?php echo $result['new_auto_increment']; ?></p>
                <p><strong>Actual Auto-Increment Value:</strong> <?php echo $result['actual_auto_increment']; ?></p>
            </div>
            
            <div class="info">
                <strong>Note:</strong> The next employee you create will have ID = <?php echo $result['new_auto_increment']; ?>
            </div>
        <?php else: ?>
            <div class="error">
                <strong>✗ Error:</strong> <?php echo htmlspecialchars($result['message']); ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
            <p><a href="?page=employees" style="color: #007bff; text-decoration: none;">← Back to Employees</a></p>
        </div>
    </div>
</body>
</html>
