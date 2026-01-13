<?php
/**
 * Application Configuration
 */

return [
    'name' => 'Golden Z-5 HR Management System',
    'version' => '2.0.0',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => 'Asia/Manila',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    
    // Encryption
    'key' => $_ENV['APP_KEY'] ?? '',
    'cipher' => 'AES-256-CBC',
    
    // Session
    'session' => [
        'driver' => 'file',
        'lifetime' => 120, // minutes
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => __DIR__ . '/../storage/sessions',
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => 'goldenz_session',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => true,
        'same_site' => 'lax',
    ],
    
    // Logging
    'log' => [
        'default' => 'file',
        'channels' => [
            'file' => [
                'driver' => 'daily',
                'path' => __DIR__ . '/../storage/logs',
                'level' => 'debug',
                'days' => 14,
            ],
        ],
    ],
];

