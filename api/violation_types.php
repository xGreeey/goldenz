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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = get_db_connection();

    if ($action !== 'add_violation') {
        throw new Exception('Invalid action');
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
            $stmt = $pdo->prepare(
                "SELECT MAX(CAST(SUBSTRING_INDEX(reference_no, '.', -1) AS UNSIGNED)) AS max_num
                 FROM violation_types
                 WHERE subcategory = ?"
            );
            $stmt->execute([$subcategory]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $maxNum = (int)($row['max_num'] ?? 0);
            $reference_no = $subcategory . '.' . ($maxNum + 1);
        } else {
            $prefix = $category === 'Major' ? 'MAJ' : 'MIN';
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
    $is_active = isset($_POST['is_active']) ? 1 : 0;

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

