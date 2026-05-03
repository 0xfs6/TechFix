<?php
/**
 * ========================================
 * Save User Preference (AJAX)
 * ========================================
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['key']) || !isset($input['value'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$key = sanitize($input['key']);
$value = sanitize($input['value']);
$userId = $_SESSION['user_id'];

$allowedKeys = ['theme', 'language'];

if (!in_array($key, $allowedKeys)) {
    echo json_encode(['success' => false, 'message' => 'Invalid preference key']);
    exit;
}

try {
    db()->update('users', [$key => $value], 'id = :id', ['id' => $userId]);
    $_SESSION[$key] = $value;
    if ($key === 'language') {
        loadTranslations($value);
    }
    
    echo json_encode(['success' => true, 'message' => 'Preference saved']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to save preference']);
}
