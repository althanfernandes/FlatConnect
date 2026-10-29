<?php
/**
 * FlatConnect - AJAX Save Property (Favorite / Bookmark) Handler
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (!is_logged_in()) {
    echo json_encode([
        'status'   => 'error',
        'message'  => 'Please log in to save properties.',
        'redirect' => 'index.php'
    ]);
    exit;
}

$user = current_user();
$property_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($property_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid property ID.']);
    exit;
}

try {
    // Check if property is currently saved
    $stmt = $pdo->prepare("SELECT id FROM saved_properties WHERE user_id = ? AND property_id = ? LIMIT 1");
    $stmt->execute([$user['id'], $property_id]);
    $saved = $stmt->fetch();

    if ($saved) {
        // Remove from saved
        $delete = $pdo->prepare("DELETE FROM saved_properties WHERE id = ?");
        $delete->execute([$saved['id']]);
        echo json_encode(['status' => 'success', 'saved' => false]);
    } else {
        // Add to saved
        $insert = $pdo->prepare("INSERT INTO saved_properties (user_id, property_id) VALUES (?, ?)");
        $insert->execute([$user['id'], $property_id]);
        echo json_encode(['status' => 'success', 'saved' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
