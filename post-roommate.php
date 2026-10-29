<?php
/**
 * FlatConnect - Handle Posting Roommate Requirement
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Session expired. Please try again.');
        header('Location: roommates.php');
        exit;
    }

    $occupation            = trim($_POST['occupation'] ?? '');
    $preferred_location    = trim($_POST['preferred_location'] ?? '');
    $budget                = (int) ($_POST['budget'] ?? 0);
    $sharing_preference    = trim($_POST['sharing_preference'] ?? '');
    $lifestyle_preferences = trim($_POST['lifestyle_preferences'] ?? '');
    $move_in_date          = trim($_POST['move_in_date'] ?? '');
    $contact_number        = trim($_POST['contact_number'] ?? $user['mobile']);
    $notes                 = trim($_POST['notes'] ?? '');

    if (empty($occupation) || empty($preferred_location) || $budget <= 0 || empty($move_in_date)) {
        set_flash('error', 'Please fill all required fields for roommate requirement.');
        header('Location: roommates.php');
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO roommate_posts (user_id, occupation, preferred_location, budget, sharing_preference, lifestyle_preferences, move_in_date, contact_number, notes, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
    ");
    $saved = $stmt->execute([
        $user['id'], $occupation, $preferred_location, $budget, $sharing_preference, $lifestyle_preferences, $move_in_date, $contact_number, $notes
    ]);

    if ($saved) {
        set_flash('success', 'Your roommate requirement has been posted successfully!');
    } else {
        set_flash('error', 'Could not post roommate requirement.');
    }
}

header('Location: roommates.php');
exit;
