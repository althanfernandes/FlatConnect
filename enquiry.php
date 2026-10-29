<?php
/**
 * FlatConnect - Direct Enquiry & Reply Controller
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Session expired. Please try again.');
        header('Location: home.php');
        exit;
    }

    $action       = $_POST['action'] ?? '';
    $redirect_url = $_POST['redirect_url'] ?? get_dashboard_url($user['role']);

    // 1. Send New Enquiry
    if ($action === 'send') {
        $receiver_id      = (int) ($_POST['receiver_id'] ?? 0);
        $property_id      = !empty($_POST['property_id']) ? (int) $_POST['property_id'] : null;
        $replacement_id   = !empty($_POST['replacement_id']) ? (int) $_POST['replacement_id'] : null;
        $roommate_post_id = !empty($_POST['roommate_post_id']) ? (int) $_POST['roommate_post_id'] : null;
        $message          = trim($_POST['message'] ?? '');

        if ($receiver_id <= 0 || empty($message)) {
            set_flash('error', 'Please enter a message before sending your enquiry.');
            header("Location: $redirect_url");
            exit;
        }

        // Prevent messaging oneself
        if ($receiver_id === $user['id']) {
            set_flash('error', 'You cannot send an enquiry to yourself.');
            header("Location: $redirect_url");
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO enquiries (property_id, replacement_id, roommate_post_id, sender_id, receiver_id, message, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $saved = $stmt->execute([$property_id, $replacement_id, $roommate_post_id, $user['id'], $receiver_id, $message]);

        if ($saved) {
            set_flash('success', 'Your enquiry has been sent successfully! The owner/tenant will receive your message in their dashboard.');
        } else {
            set_flash('error', 'Could not send enquiry. Please try again.');
        }

        header("Location: $redirect_url");
        exit;
    }

    // 2. Reply to Received Enquiry
    elseif ($action === 'reply') {
        $enquiry_id = (int) ($_POST['enquiry_id'] ?? 0);
        $response   = trim($_POST['response'] ?? '');

        if ($enquiry_id <= 0 || empty($response)) {
            set_flash('error', 'Please write a reply message before submitting.');
            header("Location: $redirect_url");
            exit;
        }

        // Ensure user is the actual recipient of this enquiry
        $check = $pdo->prepare("SELECT id FROM enquiries WHERE id = ? AND receiver_id = ? LIMIT 1");
        $check->execute([$enquiry_id, $user['id']]);
        if (!$check->fetch()) {
            set_flash('error', 'You are not authorized to respond to this enquiry.');
            header("Location: $redirect_url");
            exit;
        }

        $update = $pdo->prepare("UPDATE enquiries SET response = ?, status = 'Replied' WHERE id = ?");
        $done = $update->execute([$response, $enquiry_id]);

        if ($done) {
            set_flash('success', 'Your reply has been sent successfully to the seeker.');
        } else {
            set_flash('error', 'Failed to submit response.');
        }

        header("Location: $redirect_url");
        exit;
    }
}

header('Location: home.php');
exit;
