<?php
/**
 * AI Help API - Gemini-backed assistant for login help
 *
 * This endpoint exposes a SAFE, LIMITED Gemini proxy that:
 * - Never exposes the API key to the browser
 * - Only answers help/usage questions about the HR system
 * - Refuses hacking, bypass, credential or sensitive data requests
 */

// Bootstrap (for sessions, config, logging helpers if available)
require_once __DIR__ . '/../bootstrap/app.php';

// Enforce JSON responses
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'reply'   => 'Method not allowed',
        'blocked' => true,
    ]);
    exit;
}

// Start session for simple rate limiting
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../storage/sessions';
    if (is_dir($sessionPath) || @mkdir($sessionPath, 0755, true)) {
        session_save_path($sessionPath);
    }
    session_start();
}

// Basic same-origin protection: only accept same host by default
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($origin)) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (stripos($origin, $host) === false) {
        http_response_code(403);
        echo json_encode([
            'reply'   => 'Forbidden',
            'blocked' => true,
        ]);
        exit;
    }
}

// Simple session-based rate limiting: max 20 requests per 10 minutes
$rateKey = 'ai_help_rate';
$now     = time();
$window  = 10 * 60; // 10 minutes
$limit   = 20;

if (!isset($_SESSION[$rateKey])) {
    $_SESSION[$rateKey] = [
        'window_start' => $now,
        'count'        => 0,
        'ip'           => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ];
}

$rate = &$_SESSION[$rateKey];

// Reset window if expired
if (!isset($rate['window_start']) || ($now - (int)$rate['window_start']) > $window) {
    $rate['window_start'] = $now;
    $rate['count']        = 0;
}

if ($rate['count'] >= $limit) {
    http_response_code(429);
    echo json_encode([
        'reply'   => 'You have reached the help limit for now. Please try again in a few minutes.',
        'blocked' => true,
    ]);
    exit;
}

// Read and validate JSON body
$rawBody = file_get_contents('php://input');
$data    = json_decode($rawBody, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'reply'   => 'Invalid request format.',
        'blocked' => true,
    ]);
    exit;
}

$message = trim((string)($data['message'] ?? ''));
$history = $data['history'] ?? [];

if ($message === '') {
    http_response_code(400);
    echo json_encode([
        'reply'   => 'Please enter a question so I can help.',
        'blocked' => true,
    ]);
    exit;
}

// Hard length cap to keep requests small
if (mb_strlen($message) > 800) {
    $message = mb_substr($message, 0, 800);
}

// Soft filter: block obvious credential / hacking content before Gemini
$lower = mb_strtolower($message);
$blockedPhrases = [
    'bypass login',
    'bypass the login',
    'hack',
    'hacking',
    'sql injection',
    'get admin access',
    'admin password',
    'backdoor',
    'bruteforce',
    'brute force',
    'steal password',
    'bypass authentication',
];

foreach ($blockedPhrases as $phrase) {
    if (strpos($lower, $phrase) !== false) {
        http_response_code(200);
        echo json_encode([
            'reply'   => 'I can’t help with bypassing security or accessing accounts. You can reset your password from the login page or contact your administrator or HR for assistance.',
            'blocked' => true,
        ]);
        exit;
    }
}

// Increment rate counter only for valid, non-empty queries
$rate['count']++;

// Prepare Gemini API call
$apiKey = getenv('GEMINI_API_KEY');

// Optional fallback to local config file NOT committed to VCS
$configModel = null;
if (!$apiKey && file_exists(__DIR__ . '/../config/ai.php')) {
    /** @noinspection PhpIncludeInspection */
    require __DIR__ . '/../config/ai.php';
    if (!empty($AI_GEMINI_API_KEY)) {
        $apiKey = $AI_GEMINI_API_KEY;
    }
    if (!empty($AI_GEMINI_MODEL)) {
        $configModel = $AI_GEMINI_MODEL;
    }
}

if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode([
        'reply'   => 'AI help is not available right now. Please contact your administrator.',
        'blocked' => true,
    ]);
    exit;
}

$model = getenv('GEMINI_MODEL') ?: ($configModel ?: 'gemini-2.5-flash');

// Load centralized system knowledge (committed, non-secret)
$AI_HELP_SYSTEM_ROLE = null;
$AI_HELP_SYSTEM_KNOWLEDGE = null;
$AI_HELP_INSTRUCTIONS = null;
if (file_exists(__DIR__ . '/../config/ai_help_knowledge.php')) {
    /** @noinspection PhpIncludeInspection */
    require __DIR__ . '/../config/ai_help_knowledge.php';
}

if (empty($AI_HELP_SYSTEM_ROLE) || empty($AI_HELP_SYSTEM_KNOWLEDGE) || empty($AI_HELP_INSTRUCTIONS)) {
    http_response_code(500);
    echo json_encode([
        'reply'   => 'AI help is not available right now. Please contact your administrator.',
        'blocked' => true,
    ]);
    exit;
}

// Build the prompt in the REQUIRED structure every time
$historyText = '';
if (is_array($history)) {
    $trimmedHistory = array_slice($history, -8);
    $lines = [];
    foreach ($trimmedHistory as $item) {
        if (!is_array($item)) {
            continue;
        }
        $role = strtolower((string)($item['role'] ?? 'user'));
        $text = isset($item['text']) ? trim((string)$item['text']) : '';
        if ($text === '') {
            continue;
        }
        if (mb_strlen($text) > 800) {
            $text = mb_substr($text, 0, 800);
        }
        // Normalize roles
        $roleLabel = ($role === 'assistant') ? 'ASSISTANT' : 'USER';
        $lines[] = $roleLabel . ': ' . $text;
    }
    if (!empty($lines)) {
        $historyText = "CONVERSATION HISTORY:\n" . implode("\n", $lines) . "\n\n";
    }
}

$fullPrompt =
    "SYSTEM ROLE:\n" . $AI_HELP_SYSTEM_ROLE . "\n\n" .
    "SYSTEM KNOWLEDGE:\n" . $AI_HELP_SYSTEM_KNOWLEDGE . "\n\n" .
    $AI_HELP_INSTRUCTIONS . "\n\n" .
    $historyText .
    "USER QUESTION:\n" . $message;

// Send as a single user prompt (Gemini will treat it as the full context)
$contents = [
    [
        'role'  => 'user',
        'parts' => [
            ['text' => $fullPrompt],
        ],
    ],
];

// Optional prior conversation (bounded)
if (is_array($history)) {
    $trimmedHistory = array_slice($history, -6);
    foreach ($trimmedHistory as $item) {
        if (!is_array($item)) {
            continue;
        }
        $role = $item['role'] ?? 'user';
        $text = isset($item['text']) ? (string)$item['text'] : '';
        if ($text === '') {
            continue;
        }
        if (mb_strlen($text) > 800) {
            $text = mb_substr($text, 0, 800);
        }
        // Map roles: user / assistant → user / model
        $role = strtolower($role) === 'assistant' ? 'model' : 'user';
        $contents[] = [
            'role'  => $role,
            'parts' => [
                ['text' => $text],
            ],
        ];
    }
}

// Current user message as final content
$contents[] = [
    'role'  => 'user',
    'parts' => [
        ['text' => $message],
    ],
];

$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

$payload = [
    'contents'          => $contents,
    'generationConfig'  => [
        'temperature'      => 0.25,
        'topP'             => 0.9,
        'topK'             => 32,
        'maxOutputTokens'  => 768,
    ],
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 15,
]);

$responseBody = curl_exec($ch);
$curlErr      = curl_error($ch);
$statusCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false || $curlErr) {
    // Log only high-level error, not message content
    error_log('AI Help Error: cURL failure (' . ($curlErr ?: 'unknown') . ')');
    http_response_code(502);
    echo json_encode([
        'reply'   => 'AI help service is unavailable. Please try again later.',
        'blocked' => false,
    ]);
    exit;
}

$apiData = json_decode($responseBody, true);

if (!is_array($apiData) || $statusCode < 200 || $statusCode >= 300) {
    // Avoid leaking detailed error messages to client
    error_log('AI Help Error: Gemini API HTTP ' . $statusCode);
    http_response_code(502);
    echo json_encode([
        'reply'   => 'AI help service is unavailable. Please try again later.',
        'blocked' => false,
    ]);
    exit;
}

$replyText = '';
if (!empty($apiData['candidates'][0]['content']['parts'])) {
    foreach ($apiData['candidates'][0]['content']['parts'] as $part) {
        if (!empty($part['text'])) {
            $replyText .= (string)$part['text'];
        }
    }
}

if ($replyText === '') {
    $replyText = 'Sorry, I was unable to generate a helpful answer. Please try again or contact your administrator.';
}

echo json_encode([
    'reply'   => $replyText,
    'blocked' => false,
]);
exit;

