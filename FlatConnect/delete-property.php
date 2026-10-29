<?php
/**
 * FlatConnect - Delete Property Listing
 * Accessible only to the property's owner with CSRF protection
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_role('owner');
$user = current_user();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$token = $_GET['token'] ?? '';

if ($id <= 0 || !verify_csrf_token($token)) {
    set_flash('error', 'Invalid request or security token expired.');
    header('Location: owner-dashboard.php');
    exit;
}

// Check ownership before deleting
$stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
$stmt->execute([$id, $user['id']]);
$property = $stmt->fetch();

if (!$property) {
    set_flash('error', 'Property not found or you are not authorized to delete it.');
    header('Location: owner-dashboard.php');
    exit;
}

// Delete property record (Cascades automatically)
$delete_stmt = $pdo->prepare("DELETE FROM properties WHERE id = ? AND owner_id = ?");
$deleted = $delete_stmt->execute([$id, $user['id']]);

if ($deleted) {
    set_flash('success', 'Property listing has been permanently deleted.');
} else {
    set_flash('error', 'Could not delete property listing.');
}

header('Location: owner-dashboard.php');
exit;
