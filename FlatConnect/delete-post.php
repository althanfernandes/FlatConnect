<?php
/**
 * FlatConnect - Delete Post Handler (Roommate or Replacement)
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

$type  = $_GET['type'] ?? '';
$id    = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$token = $_GET['token'] ?? '';

if ($id <= 0 || !verify_csrf_token($token)) {
    set_flash('error', 'Invalid request or session expired.');
    header('Location: home.php');
    exit;
}

if ($type === 'roommate') {
    $stmt = $pdo->prepare("DELETE FROM roommate_posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user['id']]);
    set_flash('success', 'Roommate post removed successfully.');
    header('Location: ' . get_dashboard_url($user['role']));
    exit;
} elseif ($type === 'replacement') {
    $stmt = $pdo->prepare("DELETE FROM replacement_posts WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $user['id']]);
    set_flash('success', 'Tenant replacement listing removed successfully.');
    header('Location: ' . get_dashboard_url($user['role']));
    exit;
}

header('Location: home.php');
exit;
