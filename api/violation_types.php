<?php
/**
 * Violation Types API
 * Handles add/update actions for violation_types without layout output.
 */

session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json; charset=UTF-8');

// Auth check (different areas set different session keys)
$isLoggedIn =
    (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) ||
    !empty($_SESSION['user_id']) ||
    !empty($_SESSION['id']);

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get action from POST or GET, handle both string and array cases
$action = '';
if (isset($_POST['action'])) {
    $action = is_array($_POST['action']) ? $_POST['action'][0] : $_POST['action'];
} elseif (isset($_GET['action'])) {
    $action = is_array($_GET['action']) ? $_GET['action'][0] : $_GET['action'];
}
$action = trim((string)$action);

// Debug logging (remove in production if needed)
error_log("Violation Types API - Action received: " . $action);
error_log("Violation Types API - POST data keys: " . implode(', ', array_keys($_POST)));
error_log("Violation Types API - POST action value: " . var_export($_POST['action'] ?? 'NOT SET', true));

try {
    $pdo = get_db_connection();

    if ($action !== 'add_violation') {
        error_log("Violation Types API - Invalid action: '" . $action . "' (type: " . gettype($action) . ")");
        error_log("Violation Types API - Full POST: " . json_encode($_POST));
        throw new Exception('Invalid action. Received: ' . ($action ?: 'empty') . '. Please ensure the form is submitted correctly.');
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $category = (string)($_POST['category'] ?? '');
    $subcategory = trim((string)($_POST['subcategory'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $reference_no = trim((string)($_POST['reference_no'] ?? ''));

    if ($name === '') {
        throw new Exception('Violation name is required.');
    }
    if (!in_array($category, ['Major', 'Minor'], true)) {
        throw new Exception('Category is required and must be Major or Minor.');
    }

    // Auto-generate reference number if not provided
    if ($reference_no === '') {
        if ($subcategory !== '') {
            // RA5487-style: "A.1", "B.2", ...
            // Truncate subcategory if needed to ensure reference_no fits in 20 chars
            // Format: "SUBCAT.123" - need space for number part (up to 9999 = 4 digits + 1 dot = 5 chars)
            // So subcategory can be max 15 chars
            $subcategoryPrefix = mb_substr($subcategory, 0, 15);
            
            $stmt = $pdo->prepare(
                "SELECT MAX(CAST(SUBSTRING_INDEX(reference_no, '.', -1) AS UNSIGNED)) AS max_num
                 FROM violation_types
                 WHERE subcategory = ?"
            );
            $stmt->execute([$subcategory]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $maxNum = (int)($row['max_num'] ?? 0);
            $reference_no = $subcategoryPrefix . '.' . ($maxNum + 1);
        } else {
            $prefix = $category === 'Major' ? 'MAJ' : 'MIN';
            // Format: "MAJ-12345" or "MIN-12345" - prefix is 3 chars, dash is 1 char, number can be up to 5 digits
            // So max number is 99999 (5 digits) = total 9 chars, well within 20 char limit
            $stmt = $pdo->prepare(
                "SELECT MAX(CAST(SUBSTRING_INDEX(reference_no, '-', -1) AS UNSIGNED)) AS max_num
                 FROM violation_types
                 WHERE category = ? AND subcategory IS NULL"
            );
            $stmt->execute([$category]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $maxNum = (int)($row['max_num'] ?? 0);
            $reference_no = $prefix . '-' . ($maxNum + 1);
        }
    }
    
    // Validate and truncate reference_no to max 20 characters
    if (mb_strlen($reference_no) > 20) {
        // If user provided a long reference_no, truncate it
        $reference_no = mb_substr($reference_no, 0, 20);
    }
    
    // Validate reference_no is not empty after processing
    if ($reference_no === '') {
        throw new Exception('Reference number cannot be empty.');
    }

    // Check duplicate reference_no (if provided/generated)
    if ($reference_no !== '') {
        $stmt = $pdo->prepare("SELECT id FROM violation_types WHERE reference_no = ? LIMIT 1");
        $stmt->execute([$reference_no]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Reference number already exists. Please provide a different one.');
        }
    }

    $first_offense = trim((string)($_POST['first_offense'] ?? ''));
    $second_offense = trim((string)($_POST['second_offense'] ?? ''));
    $third_offense = trim((string)($_POST['third_offense'] ?? ''));
    $fourth_offense = trim((string)($_POST['fourth_offense'] ?? ''));
    $fifth_offense = trim((string)($_POST['fifth_offense'] ?? ''));

    $ra5487_compliant = isset($_POST['ra5487_compliant']) ? 1 : 0;
    // Default to 1 (active) if checkbox is not present, since it's checked by default in the form
    $is_active = array_key_exists('is_active', $_POST) ? (isset($_POST['is_active']) ? 1 : 0) : 1;

    // Build sanctions JSON (optional column in table)
    $sanctionsPayload = [
        'first_offense' => $first_offense !== '' ? $first_offense : null,
        'second_offense' => $second_offense !== '' ? $second_offense : null,
        'third_offense' => $third_offense !== '' ? $third_offense : null,
        'fourth_offense' => $fourth_offense !== '' ? $fourth_offense : null,
        'fifth_offense' => $fifth_offense !== '' ? $fifth_offense : null,
    ];
    // If everything is empty, keep DB value NULL.
    $sanctionsJson = array_filter($sanctionsPayload, fn($v) => $v !== null);
    $sanctionsJson = empty($sanctionsJson) ? null : json_encode($sanctionsPayload, JSON_UNESCAPED_UNICODE);

    // Prepare new values for audit log
    $newValues = [
        'reference_no' => $reference_no !== '' ? $reference_no : null,
        'name' => $name,
        'category' => $category,
        'subcategory' => $subcategory !== '' ? $subcategory : null,
        'description' => $description !== '' ? $description : null,
        'sanctions' => $sanctionsJson,
        'first_offense' => $first_offense !== '' ? $first_offense : null,
        'second_offense' => $second_offense !== '' ? $second_offense : null,
        'third_offense' => $third_offense !== '' ? $third_offense : null,
        'fourth_offense' => $fourth_offense !== '' ? $fourth_offense : null,
        'fifth_offense' => $fifth_offense !== '' ? $fifth_offense : null,
        'ra5487_compliant' => $ra5487_compliant,
        'is_active' => $is_active,
    ];

    $stmt = $pdo->prepare(
        "INSERT INTO violation_types (
            reference_no, name, category, subcategory, description,
            sanctions,
            first_offense, second_offense, third_offense, fourth_offense, fifth_offense,
            ra5487_compliant, is_active
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?,
            ?, ?, ?, ?, ?,
            ?, ?
        )"
    );

    $stmt->execute([
        $reference_no !== '' ? $reference_no : null,
        $name,
        $category,
        $subcategory !== '' ? $subcategory : null,
        $description !== '' ? $description : null,
        $sanctionsJson,
        $first_offense !== '' ? $first_offense : null,
        $second_offense !== '' ? $second_offense : null,
        $third_offense !== '' ? $third_offense : null,
        $fourth_offense !== '' ? $fourth_offense : null,
        $fifth_offense !== '' ? $fifth_offense : null,
        $ra5487_compliant,
        $is_active,
    ]);

    // Get the inserted ID
    $insertedId = (int)$pdo->lastInsertId();

    // Log audit event for violation type creation
    if (function_exists('log_audit_event')) {
        $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
        log_audit_event('INSERT', 'violation_types', $insertedId, null, $newValues, $user_id);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Violation type added successfully!',
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

