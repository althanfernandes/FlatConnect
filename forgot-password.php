<?php
/**
 * FlatConnect - Password Reset Feature
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($email) || empty($new_pass) || empty($confirm_pass)) {
            $error = 'Please fill in all fields.';
        } elseif ($new_pass !== $confirm_pass) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new_pass) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'No account found with this email address.';
            } else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$hashed, $user['id']]);

                set_flash('success', 'Your password has been reset successfully! Please log in.');
                header('Location: index.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - FlatConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #142D4E 0%, #0F2038 100%);">
<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 460px;">
        <div class="auth-header">
            <a href="index.php" class="auth-logo">
                <span style="width: 36px; height: 36px; background: var(--secondary); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: #fff;">
                    <i class="fa-solid fa-house-chimney"></i>
                </span>
                <span>Flat<span style="color: var(--secondary);">Connect</span></span>
            </a>
            <h2 style="font-size: 1.3rem; margin-top: 10px;">Reset Password</h2>
            <p class="auth-subtitle">Enter your registered email and choose a new password.</p>
        </div>

        <div class="auth-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo e($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="forgot-password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-group">
                    <label for="email"><i class="fa-regular fa-envelope"></i> Registered Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="e.g. seeker@flatconnect.com" required value="<?php echo e($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="new_password"><i class="fa-solid fa-lock"></i> New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimum 6 characters" minlength="6" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><i class="fa-solid fa-check-double"></i> Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter new password" minlength="6" required>
                </div>

                <button type="submit" class="btn btn-secondary btn-block btn-lg" style="margin-top: 10px;">
                    <i class="fa-solid fa-key"></i> Reset Password
                </button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <a href="index.php" style="color: var(--text-muted); font-size: 0.9rem;">
                    <i class="fa-solid fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
