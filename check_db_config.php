<?php
/**
 * Database Configuration Diagnostic Script
 * Run this to check your database configuration
 */

// Load environment variables
if (file_exists(__DIR__ . '/bootstrap/env.php')) {
    require_once __DIR__ . '/bootstrap/env.php';
}

echo "=== Database Configuration Diagnostic ===\n\n";

echo "Environment Variables:\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET (defaults to localhost)') . "\n";
echo "DB_PORT: " . ($_ENV['DB_PORT'] ?? 'NOT SET (defaults to 3306)') . "\n";
echo "DB_DATABASE: " . ($_ENV['DB_DATABASE'] ?? 'NOT SET (defaults to goldenz_hr)') . "\n";
echo "DB_USERNAME: " . ($_ENV['DB_USERNAME'] ?? 'NOT SET (defaults to root)') . "\n";
echo "DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? '***SET***' : 'NOT SET') . "\n\n";

echo "Current Configuration Values:\n";
$db_config = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'database' => $_ENV['DB_DATABASE'] ?? 'goldenz_hr',
];

foreach ($db_config as $key => $value) {
    if ($key === 'password') {
        echo "$key: " . ($value ? '***SET***' : 'NOT SET') . "\n";
    } else {
        echo "$key: $value\n";
    }
}

echo "\n=== Docker Check ===\n";
if (file_exists('/.dockerenv')) {
    echo "✓ Running inside Docker container\n";
    echo "\n⚠️  IMPORTANT: In Docker, DB_HOST must be your MySQL service name!\n";
    echo "Common service names: mysql, db, database, mariadb\n";
    echo "\nTo find your MySQL service name:\n";
    echo "1. Check your docker-compose.yml file\n";
    echo "2. Look for the MySQL/MariaDB service definition\n";
    echo "3. The service name is the key under 'services:'\n";
    echo "\nExample docker-compose.yml:\n";
    echo "services:\n";
    echo "  mysql:  <-- This is your DB_HOST value\n";
    echo "    image: mysql:8.0\n";
} else {
    echo "Not running in Docker (or /.dockerenv not found)\n";
}

echo "\n=== Connection Test ===\n";
try {
    $host = $db_config['host'];
    $port = $db_config['port'];
    $dsn = "mysql:host={$host};port={$port};dbname={$db_config['database']};charset=utf8mb4";
    
    echo "Attempting connection to: {$host}:{$port}\n";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    echo "✓ Connection successful!\n";
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    if ($db_config['host'] === 'localhost' || $db_config['host'] === '127.0.0.1') {
        echo "- You're using 'localhost' or '127.0.0.1' which won't work in Docker\n";
        echo "- Set DB_HOST to your Docker MySQL service name\n";
    }
    echo "- Verify MySQL container is running: docker-compose ps\n";
    echo "- Check MySQL logs: docker-compose logs mysql\n";
    echo "- Ensure containers are on the same Docker network\n";
}

echo "\n";
