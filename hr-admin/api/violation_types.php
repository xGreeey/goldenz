<?php
/**
 * HR Admin: Violation Types API (no-auth)
 * Per request: do not require session auth for this endpoint.
 */

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/security.php';

header('Content-Type: application/json; charset=UTF-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = get_db_connection();
    $dbInfo = $pdo->query("SELECT DATABASE() AS db")->fetch(PDO::FETCH_ASSOC);

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

    // Check duplicate reference_no
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

    // Normalize common "empty" subcategory values so the record shows under Major/Minor tabs
    // (RA5487 tab is used when subcategory is non-empty).
    if ($subcategory === '' || strcasecmp($subcategory, 'n/a') === 0 || strcasecmp($subcategory, 'na') === 0) {
        $subcategory = '';
    }

    $ra5487_compliant = isset($_POST['ra5487_compliant']) ? 1 : 0;

    // Default to active if field is missing (DB default is 1 and UI checkbox is checked by default)
    $is_active = array_key_exists('is_active', $_POST) ? 1 : 1;

    // Build sanctions JSON (optional column)
    $sanctionsPayload = [
        'first_offense' => $first_offense !== '' ? $first_offense : null,
        'second_offense' => $second_offense !== '' ? $second_offense : null,
        'third_offense' => $third_offense !== '' ? $third_offense : null,
        'fourth_offense' => $fourth_offense !== '' ? $fourth_offense : null,
        'fifth_offense' => $fifth_offense !== '' ? $fifth_offense : null,
    ];
    $hasAnySanction = false;
    foreach ($sanctionsPayload as $v) {
        if ($v !== null) { $hasAnySanction = true; break; }
    }
    $sanctionsJson = $hasAnySanction ? json_encode($sanctionsPayload, JSON_UNESCAPED_UNICODE) : null;

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

    $insertId = (int)$pdo->lastInsertId();
    $verify = $pdo->prepare("SELECT id, reference_no, name, category, subcategory, is_active FROM violation_types WHERE id = ?");
    $verify->execute([$insertId]);
    $row = $verify->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Violation type added successfully!',
        'inserted_id' => $insertId,
        'database' => $dbInfo['db'] ?? null,
        'row' => $row ?: null,
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

