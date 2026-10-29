<?php
/**
 * FlatConnect - Entry & Authentication Page (Login & Register)
 * 
 * First page loaded when visiting http://localhost/FlatConnect-Final/
 * All three user roles (Owner, Tenant, Seeker) use the same secure login form.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// If user is already logged in, redirect them according to whether they've chosen a role
if (is_logged_in()) {
    if (!empty($_SESSION['user_role'])) {
        header("Location: " . get_dashboard_url($_SESSION['user_role']));
    } else {
        header("Location: choose-role.php");
    }
    exit;
}

$login_error = '';
$register_error = '';
$active_tab = 'login';

// -------------------------------------------------------------------------
// Handle POST Requests (Login or Register)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Verify CSRF Token for security
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $login_error = 'Security session expired. Please refresh and try again.';
    } else {
        // A. HANDLE LOGIN
        if ($action === 'login') {
            $active_tab = 'login';
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $login_error = 'Please enter both your email address and password.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $login_error = 'Please enter a valid email address.';
            } else {
                // Fetch user from database
                $stmt = $pdo->prepare("SELECT id, name, email, mobile, password, role FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Password matches -> Initialize secure session
                    session_regenerate_id(true);
                    $_SESSION['user_id']     = $user['id'];
                    $_SESSION['user_name']   = $user['name'];
                    $_SESSION['user_email']  = $user['email'];
                    $_SESSION['user_mobile'] = $user['mobile'];

                    // Clear previous role so user selects their active role on Choose Your Role page
                    unset($_SESSION['user_role']);

                    // Redirect to Step 4: Choose Your Role
                    header("Location: choose-role.php");
                    exit;
                } else {
                    $login_error = 'Invalid email address or password. Please try again.';
                }
            }
        }

        // B. HANDLE REGISTRATION
        elseif ($action === 'register') {
            $active_tab = 'register';
            $name             = trim($_POST['name'] ?? '');
            $email            = trim($_POST['email'] ?? '');
            $mobile           = trim($_POST['mobile'] ?? '');
            $password         = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Validation checks
            if (empty($name) || empty($email) || empty($mobile) || empty($password) || empty($confirm_password)) {
                $register_error = 'All fields are required. Please fill in the complete registration form.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $register_error = 'Please enter a valid email address.';
            } elseif (strlen($password) < 6) {
                $register_error = 'Password must be at least 6 characters long.';
            } elseif ($password !== $confirm_password) {
                $register_error = 'Password and Confirm Password do not match.';
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $register_error = 'An account with this email address already exists. Please log in.';
                } else {
                    // Hash password securely with PHP standard bcrypt
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $insert_stmt = $pdo->prepare("INSERT INTO users (name, email, mobile, password, role) VALUES (?, ?, ?, ?, 'seeker')");
                    $success = $insert_stmt->execute([$name, $email, $mobile, $hashed_password]);

                    if ($success) {
                        set_flash('success', 'Registration successful! Please log in with your email and password, then choose your role.');
                        $active_tab = 'login';
                    } else {
                        $register_error = 'Registration failed due to a server error. Please try again.';
                    }
                }
            }
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlatConnect - Direct Flat, PG & Roommate Finder</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #142D4E 0%, #0F2038 100%);">

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Logo and Header -->
        <div class="auth-header">
            <div class="auth-logo">
                <span style="width: 40px; height: 40px; background: var(--secondary); border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; color: #fff;">
                    <i class="fa-solid fa-house-chimney"></i>
                </span>
                <span>Flat<span style="color: var(--secondary);">Connect</span></span>
            </div>
            <p class="auth-subtitle">"Find a place. Find a roommate. Connect directly."</p>
        </div>

        <!-- Navigation Tabs between Login and Register -->
        <div class="auth-tabs">
            <button type="button" class="auth-tab-btn <?php echo ($active_tab === 'login') ? 'active' : ''; ?>" data-target="login">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
            </button>
            <button type="button" class="auth-tab-btn <?php echo ($active_tab === 'register') ? 'active' : ''; ?>" data-target="register">
                <i class="fa-solid fa-user-plus"></i> Create New Account
            </button>
        </div>

        <div class="auth-body">
            <!-- Flash Notification -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>">
                    <i class="fa-solid <?php echo ($flash['type'] === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                    <span><?php echo e($flash['message']); ?></span>
                </div>
            <?php endif; ?>

            <!-- ========================================================= -->
            <!-- 1. LOGIN FORM SECTION                                     -->
            <!-- ========================================================= -->
            <div id="loginFormSection" style="display: <?php echo ($active_tab === 'login') ? 'block' : 'none'; ?>;">
                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo e($login_error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <div class="form-group">
                        <label for="login_email"><i class="fa-regular fa-envelope"></i> Email Address</label>
                        <input type="email" id="login_email" name="email" class="form-control" placeholder="e.g. owner@flatconnect.com" required value="<?php echo e($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label for="login_password" style="margin-bottom: 0;"><i class="fa-solid fa-lock"></i> Password</label>
                            <a href="forgot-password.php" style="font-size: 0.85rem; color: var(--secondary); font-weight: 600;">Forgot Password?</a>
                        </div>
                        <input type="password" id="login_password" name="password" class="form-control" placeholder="Enter your account password" required>
                    </div>

                    <button type="submit" class="btn btn-secondary btn-block btn-lg" style="margin-top: 10px;">
                        <i class="fa-solid fa-right-to-bracket"></i> Login to FlatConnect
                    </button>
                </form>

                <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center;">
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 8px;">New to FlatConnect?</p>
                    <button type="button" class="btn btn-outline btn-block btn-sm" onclick="document.querySelector('[data-target=\'register\']').click();">
                        <i class="fa-solid fa-user-plus"></i> Create New Account
                    </button>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 2. REGISTRATION FORM SECTION                              -->
            <!-- ========================================================= -->
            <div id="registerFormSection" style="display: <?php echo ($active_tab === 'register') ? 'block' : 'none'; ?>;">
                <?php if (!empty($register_error)): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo e($register_error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST" id="userRegisterForm">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <div class="form-group">
                        <label for="reg_name"><i class="fa-regular fa-user"></i> Full Name</label>
                        <input type="text" id="reg_name" name="name" class="form-control" placeholder="e.g. Rajesh Sharma" required value="<?php echo e($_POST['name'] ?? ''); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="reg_email"><i class="fa-regular fa-envelope"></i> Email Address</label>
                            <input type="email" id="reg_email" name="email" class="form-control" placeholder="name@example.com" required value="<?php echo e($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="reg_mobile"><i class="fa-solid fa-phone"></i> Mobile Number</label>
                            <input type="tel" id="reg_mobile" name="mobile" class="form-control" placeholder="10-digit mobile" required pattern="[0-9]{10}" value="<?php echo e($_POST['mobile'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="reg_password"><i class="fa-solid fa-lock"></i> Password</label>
                            <input type="password" id="reg_password" name="password" class="form-control" placeholder="Min 6 characters" minlength="6" required>
                        </div>
                        <div class="form-group">
                            <label for="reg_confirm_password"><i class="fa-solid fa-check-double"></i> Confirm Password</label>
                            <input type="password" id="reg_confirm_password" name="confirm_password" class="form-control" placeholder="Repeat password" minlength="6" required>
                        </div>
                    </div>

                    <p class="form-help" style="margin-bottom: 14px; font-size: 0.825rem; color: var(--text-muted); background: var(--bg-light); padding: 8px 12px; border-radius: var(--radius-sm); border-left: 3px solid var(--secondary);">
                        <i class="fa-solid fa-circle-info" style="color: var(--secondary);"></i> One account gives you full access to be an <strong>Owner</strong>, <strong>Tenant</strong>, or <strong>Seeker</strong>. You will choose your active role right after login.
                    </p>

                    <button type="submit" class="btn btn-secondary btn-block btn-lg" style="margin-top: 10px;">
                        <i class="fa-solid fa-user-plus"></i> Register Account
                    </button>
                </form>

                <div style="margin-top: 20px; text-align: center;">
                    <span style="font-size: 0.9rem; color: var(--text-muted);">Already have an account? </span>
                    <button type="button" style="background:none; border:none; color:var(--secondary); font-weight:700; cursor:pointer;" onclick="document.querySelector('[data-target=\'login\']').click();">
                        Sign In here
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
