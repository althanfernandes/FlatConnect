<?php
/**
 * FlatConnect - Handle Posting Tenant Replacement Listing
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Session expired. Please try again.');
        header('Location: replacement.php');
        exit;
    }

    $property_name      = trim($_POST['property_name'] ?? '');
    $location           = trim($_POST['location'] ?? '');
    $rent               = (int) ($_POST['rent'] ?? 0);
    $deposit            = (int) ($_POST['deposit'] ?? 0);
    $available_from     = trim($_POST['available_from'] ?? '');
    $sharing_details    = trim($_POST['sharing_details'] ?? '');
    $facilities         = trim($_POST['facilities'] ?? '');
    $description        = trim($_POST['description'] ?? '');
    $contact_preference = trim($_POST['contact_preference'] ?? '');

    if (empty($property_name) || empty($location) || $rent <= 0 || empty($available_from) || empty($description)) {
        set_flash('error', 'Please fill in all mandatory fields.');
        header('Location: replacement.php');
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO replacement_posts (tenant_id, property_name, location, rent, deposit, available_from, sharing_details, facilities, description, contact_preference, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
    ");
    $saved = $stmt->execute([
        $user['id'], $property_name, $location, $rent, $deposit, $available_from, $sharing_details, $facilities, $description, $contact_preference
    ]);

    if ($saved) {
        set_flash('success', 'Your tenant replacement listing has been posted successfully!');
    } else {
        set_flash('error', 'Could not post replacement listing.');
    }
}

header('Location: replacement.php');
exit;
