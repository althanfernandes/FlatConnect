<?php
/**
 * FlatConnect - User Profile & Smart Matching Preferences
 */

define('PAGE_TITLE', 'My Profile - FlatConnect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

$error = '';
$success = '';

// Fetch fresh user data from MySQL
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user['id']]);
$user_data = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please try again.';
    } else {
        $form_action = $_POST['form_action'] ?? '';

        // 1. Update Profile & Preferences
        if ($form_action === 'update_profile') {
            $name                 = trim($_POST['name'] ?? '');
            $mobile               = trim($_POST['mobile'] ?? '');
            $preferred_location   = trim($_POST['preferred_location'] ?? '');
            $max_budget           = !empty($_POST['max_budget']) ? (int) $_POST['max_budget'] : null;
            $preferred_type       = trim($_POST['preferred_type'] ?? '');
            $preferred_furnishing = trim($_POST['preferred_furnishing'] ?? '');
            $bio                  = trim($_POST['bio'] ?? '');

            if (empty($name) || empty($mobile)) {
                $error = 'Name and mobile number cannot be empty.';
            } else {
                $up = $pdo->prepare("
                    UPDATE users SET 
                        name = ?, mobile = ?, preferred_location = ?, 
                        max_budget = ?, preferred_type = ?, preferred_furnishing = ?, bio = ? 
                    WHERE id = ?
                ");
                $done = $up->execute([
                    $name, $mobile, $preferred_location,
                    $max_budget, $preferred_type, $preferred_furnishing, $bio,
                    $user['id']
                ]);

                if ($done) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_mobile'] = $mobile;
                    set_flash('success', 'Profile and matching preferences updated successfully!');
                    header('Location: profile.php');
                    exit;
                } else {
                    $error = 'Failed to update profile information.';
                }
            }
        }

        // 2. Change Password
        elseif ($form_action === 'change_password') {
            $current_pass = $_POST['current_password'] ?? '';
            $new_pass     = $_POST['new_password'] ?? '';
            $confirm_pass = $_POST['confirm_password'] ?? '';

            if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
                $error = 'Please fill in all password fields.';
            } elseif (!password_verify($current_pass, $user_data['password'])) {
                $error = 'Your current password is incorrect.';
            } elseif ($new_pass !== $confirm_pass) {
                $error = 'New password and confirm password do not match.';
            } elseif (strlen($new_pass) < 6) {
                $error = 'New password must be at least 6 characters long.';
            } else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $up_pass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $up_pass->execute([$hashed, $user['id']]);

                set_flash('success', 'Your password has been changed successfully.');
                header('Location: profile.php');
                exit;
            }
        }
    }
}

$flash = get_flash();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="main-content">
    <div class="container container-sm">
        <div style="margin-bottom: 24px;">
            <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 6px;">
                <a href="<?php echo get_dashboard_url($user['role']); ?>">Dashboard</a> / <span>Profile</span>
            </div>
            <h1 style="font-size: 2rem;"><i class="fa-solid fa-user-circle" style="color: var(--secondary);"></i> My Account & Preferences</h1>
            <p class="text-muted">Manage your personal details and set your Smart Matching rental criteria.</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo e($flash['message']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Basic Details & Preferences Form -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="avatar-circle" style="width:48px; height:48px; font-size:1.2rem;">
                        <?php echo strtoupper(substr($user_data['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h2 style="font-size: 1.2rem; margin: 0;"><?php echo e($user_data['name']); ?></h2>
                        <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                            <span class="role-badge <?php echo e($user['role']); ?>">
                                Active: <?php echo ucfirst(e($user['role'])); ?>
                            </span>
                            <a href="choose-role.php" class="btn btn-outline-secondary btn-sm" style="padding: 2px 8px; font-size: 0.75rem;">
                                <i class="fa-solid fa-arrows-rotate"></i> Switch Role
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form action="profile.php" method="POST">
                    <input type="hidden" name="form_action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <h3 style="font-size: 1.05rem; margin-bottom: 16px; color: var(--primary);"><i class="fa-solid fa-id-card"></i> Contact Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" class="form-control" required value="<?php echo e($user_data['name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="mobile">Mobile Number *</label>
                            <input type="tel" id="mobile" name="mobile" class="form-control" required value="<?php echo e($user_data['mobile']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" class="form-control" value="<?php echo e($user_data['email']); ?>" disabled style="background:#F1F5F9; cursor:not-allowed;">
                        <span class="form-help">Email cannot be changed once registered.</span>
                    </div>

                    <h3 style="font-size: 1.05rem; margin: 24px 0 16px; color: var(--primary);"><i class="fa-solid fa-wand-magic-sparkles"></i> Smart Matching Preferences</h3>
                    <p class="text-muted" style="font-size: 0.85rem; margin-top: -10px; margin-bottom: 16px;">
                        These preferences power FlatConnect's rule-based recommendation engine on your dashboard.
                    </p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="preferred_location">Preferred Locality in Pune</label>
                            <select name="preferred_location" id="preferred_location" class="form-control">
                                <option value="Hinjewadi" <?php echo ($user_data['preferred_location'] === 'Hinjewadi') ? 'selected' : ''; ?>>Hinjewadi</option>
                                <option value="Wakad" <?php echo ($user_data['preferred_location'] === 'Wakad') ? 'selected' : ''; ?>>Wakad</option>
                                <option value="Tathawade" <?php echo ($user_data['preferred_location'] === 'Tathawade') ? 'selected' : ''; ?>>Tathawade</option>
                                <option value="Baner" <?php echo ($user_data['preferred_location'] === 'Baner') ? 'selected' : ''; ?>>Baner</option>
                                <option value="Punawale" <?php echo ($user_data['preferred_location'] === 'Punawale') ? 'selected' : ''; ?>>Punawale</option>
                                <option value="Pimpri" <?php echo ($user_data['preferred_location'] === 'Pimpri') ? 'selected' : ''; ?>>Pimpri</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="max_budget">Maximum Monthly Budget (₹)</label>
                            <input type="number" id="max_budget" name="max_budget" class="form-control" placeholder="e.g. 18000" step="1000" value="<?php echo e($user_data['max_budget']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="preferred_type">Preferred Property Type</label>
                            <select name="preferred_type" id="preferred_type" class="form-control">
                                <option value="Flat" <?php echo ($user_data['preferred_type'] === 'Flat') ? 'selected' : ''; ?>>Flat / Apartment</option>
                                <option value="PG" <?php echo ($user_data['preferred_type'] === 'PG') ? 'selected' : ''; ?>>PG (Paying Guest)</option>
                                <option value="Room" <?php echo ($user_data['preferred_type'] === 'Room') ? 'selected' : ''; ?>>Private Room / 1RK</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="preferred_furnishing">Furnishing Preference</label>
                            <select name="preferred_furnishing" id="preferred_furnishing" class="form-control">
                                <option value="Semi-Furnished" <?php echo ($user_data['preferred_furnishing'] === 'Semi-Furnished') ? 'selected' : ''; ?>>Semi-Furnished</option>
                                <option value="Fully Furnished" <?php echo ($user_data['preferred_furnishing'] === 'Fully Furnished') ? 'selected' : ''; ?>>Fully Furnished</option>
                                <option value="Unfurnished" <?php echo ($user_data['preferred_furnishing'] === 'Unfurnished') ? 'selected' : ''; ?>>Unfurnished</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bio">About You / Notes</label>
                        <textarea id="bio" name="bio" class="form-control" rows="3" placeholder="Tell owners or prospective flatmates a little about your routine, college, or company..."><?php echo e($user_data['bio']); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-secondary">
                        <i class="fa-solid fa-floppy-disk"></i> Save Profile Details
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="card">
            <div class="card-header">
                <h2 style="font-size: 1.15rem;"><i class="fa-solid fa-lock"></i> Change Password</h2>
            </div>
            <div class="card-body">
                <form action="profile.php" method="POST">
                    <input type="hidden" name="form_action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" minlength="6" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-key"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
