<?php
/**
 * FlatConnect - Choose Your Role Page
 * 
 * Step 4 of the new login workflow:
 * After credentials verification, users choose which role to continue as.
 * All three roles (Owner, Tenant, Seeker) are accessible from ONE single account.
 */

define('PAGE_TITLE', 'Choose Your Role - FlatConnect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Protect page: user must be logged in
require_login();
$user = current_user();

// Handle Role Selection (GET or POST)
if (isset($_REQUEST['role'])) {
    $selected_role = trim($_REQUEST['role']);

    if (in_array($selected_role, ['owner', 'tenant', 'seeker'], true)) {
        // 1. Update session role
        $_SESSION['user_role'] = $selected_role;

        // 2. Update active role in database safely
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$selected_role, $user['id']]);
        } catch (Exception $e) {
            // Non-fatal error; session still holds the active role
        }

        // 3. Set a friendly flash message
        $role_names = [
            'owner'  => 'Property Owner',
            'tenant' => 'Existing Tenant',
            'seeker' => 'Accommodation Seeker'
        ];
        set_flash('success', 'Active role set to ' . $role_names[$selected_role] . '. You can switch roles anytime from the top navigation bar.');

        // 4. Redirect to the chosen dashboard
        header("Location: " . get_dashboard_url($selected_role));
        exit;
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Role - FlatConnect</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- FlatConnect Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .role-choice-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #142D4E 0%, #0F2038 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .role-choice-container {
            width: 100%;
            max-width: 1100px;
            background: #FFFFFF;
            border-radius: var(--radius-lg);
            padding: 44px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
        }
        .role-cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 32px;
            margin-bottom: 28px;
        }
        .role-action-card {
            background: #FFFFFF;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .role-action-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(20, 45, 78, 0.12);
        }
        .role-action-card.owner-card:hover {
            border-color: #0284C7;
        }
        .role-action-card.tenant-card:hover {
            border-color: #F59E0B;
        }
        .role-action-card.seeker-card:hover {
            border-color: var(--secondary);
        }
        .role-icon-bubble {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 1.85rem;
        }
        .role-feature-list {
            list-style: none;
            text-align: left;
            margin: 20px 0 26px;
            padding: 0;
            font-size: 0.9rem;
            color: var(--text-dark);
            flex: 1;
        }
        .role-feature-list li {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .role-feature-list i {
            margin-top: 3px;
        }
        @media (max-width: 900px) {
            .role-cards-grid {
                grid-template-columns: 1fr;
            }
            .role-choice-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<div class="role-choice-wrapper">
    <div class="role-choice-container">
        <!-- Header -->
        <div class="text-center" style="max-width: 680px; margin: 0 auto;">
            <div style="display: inline-flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <span style="width: 38px; height: 38px; background: var(--secondary); border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem;">
                    <i class="fa-solid fa-house-chimney"></i>
                </span>
                <span style="font-size: 1.6rem; font-weight: 800; color: var(--primary);">Flat<span style="color: var(--secondary);">Connect</span></span>
            </div>
            
            <h1 style="font-size: 2.1rem; color: var(--primary); margin-bottom: 8px;">Choose Your Role</h1>
            <p style="font-size: 1rem; color: var(--text-muted); line-height: 1.6;">
                Welcome, <strong><?php echo e($user['name']); ?></strong>! How would you like to continue today?  
                Your single account gives you access to all three roles — you can switch anytime without logging out.
            </p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>" style="margin-top: 20px;">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo e($flash['message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Three Attractive Cards -->
        <div class="role-cards-grid">
            <!-- 1. Property Owner Card -->
            <div class="role-action-card owner-card">
                <div class="role-icon-bubble" style="background: #E0F2FE; color: #0284C7;">
                    <i class="fa-solid fa-key"></i>
                </div>
                <span style="display: inline-block; background: #E0F2FE; color: #0369A1; font-weight: 700; font-size: 0.75rem; padding: 4px 10px; border-radius: var(--radius-full); text-transform: uppercase; margin-bottom: 10px;">
                    Role 1 • Rent Properties
                </span>
                <h2 style="font-size: 1.35rem; color: var(--primary); margin-bottom: 8px;">Property Owner</h2>
                <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5;">
                    List your flats, PGs, or rooms with photos, manage pricing, and connect directly with tenants.
                </p>

                <ul class="role-feature-list">
                    <li><i class="fa-solid fa-check" style="color: #0284C7;"></i> <span>Post new flats, PGs & rooms</span></li>
                    <li><i class="fa-solid fa-check" style="color: #0284C7;"></i> <span>Upload photos & set bachelor rules</span></li>
                    <li><i class="fa-solid fa-check" style="color: #0284C7;"></i> <span>Manage & edit your listings</span></li>
                    <li><i class="fa-solid fa-check" style="color: #0284C7;"></i> <span>View & reply to seeker enquiries</span></li>
                </ul>

                <a href="choose-role.php?role=owner" class="btn btn-secondary btn-block btn-lg" style="background: #0284C7; border-color: #0284C7;">
                    Continue as Property Owner <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- 2. Existing Tenant Card -->
            <div class="role-action-card tenant-card">
                <div class="role-icon-bubble" style="background: #FEF3C7; color: #D97706;">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </div>
                <span style="display: inline-block; background: #FEF3C7; color: #B45309; font-weight: 700; font-size: 0.75rem; padding: 4px 10px; border-radius: var(--radius-full); text-transform: uppercase; margin-bottom: 10px;">
                    Role 2 • Replacements & Roommates
                </span>
                <h2 style="font-size: 1.35rem; color: var(--primary); margin-bottom: 8px;">Existing Tenant</h2>
                <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5;">
                    Leaving your rented flat early? Post a tenant replacement vacancy or search for a roommate to share rent.
                </p>

                <ul class="role-feature-list">
                    <li><i class="fa-solid fa-check" style="color: #D97706;"></i> <span>Post tenant replacement vacancies</span></li>
                    <li><i class="fa-solid fa-check" style="color: #D97706;"></i> <span>Transfer lease & recover deposit</span></li>
                    <li><i class="fa-solid fa-check" style="color: #D97706;"></i> <span>Post roommate requirements</span></li>
                    <li><i class="fa-solid fa-check" style="color: #D97706;"></i> <span>Receive direct replacement enquiries</span></li>
                </ul>

                <a href="choose-role.php?role=tenant" class="btn btn-secondary btn-block btn-lg" style="background: #D97706; border-color: #D97706;">
                    Continue as Existing Tenant <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- 3. Accommodation Seeker Card -->
            <div class="role-action-card seeker-card">
                <div class="role-icon-bubble" style="background: var(--secondary-light); color: var(--secondary);">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <span style="display: inline-block; background: var(--secondary-light); color: var(--secondary-hover); font-weight: 700; font-size: 0.75rem; padding: 4px 10px; border-radius: var(--radius-full); text-transform: uppercase; margin-bottom: 10px;">
                    Role 3 • Search Accommodations
                </span>
                <h2 style="font-size: 1.35rem; color: var(--primary); margin-bottom: 8px;">Accommodation Seeker</h2>
                <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5;">
                    Browse verified flats, PGs, urgent vacancies, and roommate profiles with zero brokerage.
                </p>

                <ul class="role-feature-list">
                    <li><i class="fa-solid fa-check" style="color: var(--secondary);"></i> <span>Rule-based Smart Matching</span></li>
                    <li><i class="fa-solid fa-check" style="color: var(--secondary);"></i> <span>Filter by Pune location & budget</span></li>
                    <li><i class="fa-solid fa-check" style="color: var(--secondary);"></i> <span>Save favorite properties</span></li>
                    <li><i class="fa-solid fa-check" style="color: var(--secondary);"></i> <span>Send direct enquiries to owners</span></li>
                </ul>

                <a href="choose-role.php?role=seeker" class="btn btn-secondary btn-block btn-lg">
                    Continue as Seeker <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Explanatory note & logout option -->
        <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; color: var(--text-dark);">
                <i class="fa-solid fa-circle-info" style="color: var(--secondary); font-size: 1.1rem;"></i>
                <span><strong>Multi-Role Platform:</strong> You can switch roles at any time using the <strong>Switch Role</strong> button in the navigation bar.</span>
            </div>
            <a href="logout.php" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: #FECACA;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
