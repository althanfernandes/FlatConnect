<?php
/**
 * FlatConnect - Navigation Bar Component
 */
$logged_in = is_logged_in();
$user = current_user();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="navbar">
    <div class="container nav-container">
        <!-- Brand Logo -->
        <a href="<?php echo $logged_in ? 'home.php' : 'index.php'; ?>" class="nav-brand">
            <span class="brand-icon">
                <i class="fa-solid fa-house-chimney"></i>
            </span>
            <span>Flat<span class="brand-tag">Connect</span></span>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="nav-toggle" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- Navigation Links -->
        <nav class="nav-menu">
            <?php if ($logged_in): ?>
                <a href="home.php" class="nav-link <?php echo ($current_page === 'home.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-house"></i> Home
                </a>
                <a href="properties.php" class="nav-link <?php echo ($current_page === 'properties.php' || $current_page === 'property-details.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-building"></i> Find Properties
                </a>
                <a href="ideal-flat.php" class="nav-link <?php echo ($current_page === 'ideal-flat.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Ideal Flat
                </a>
                <a href="roommates.php" class="nav-link <?php echo ($current_page === 'roommates.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-group"></i> Find Roommates
                </a>
                <a href="replacement.php" class="nav-link <?php echo ($current_page === 'replacement.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-arrows-rotate"></i> Tenant Replacement
                </a>
                <a href="<?php echo get_dashboard_url($user['role']); ?>" class="nav-link <?php echo (strpos($current_page, 'dashboard') !== false) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
                <a href="choose-role.php" class="nav-link <?php echo ($current_page === 'choose-role.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-arrows-rotate"></i> Switch Role
                </a>
                <a href="profile.php" class="nav-link <?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user"></i> Profile
                </a>
                
                <div class="nav-actions">
                    <span class="role-badge <?php echo e($user['role']); ?>">
                        <i class="fa-solid fa-shield-halved"></i> <?php echo ucfirst(e($user['role'])); ?>
                    </span>
                    <a href="choose-role.php" class="btn btn-sm btn-outline-secondary" style="background: rgba(21, 152, 149, 0.2); color: #5EEAD4; border-color: rgba(94, 234, 212, 0.5); font-weight:700;" title="Switch your active role">
                        <i class="fa-solid fa-arrows-rotate"></i> Switch Role
                    </a>
                    <a href="logout.php" class="btn btn-sm btn-outline" style="color: #FFFFFF; border-color: rgba(255,255,255,0.3);" title="Log out of FlatConnect">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            <?php else: ?>
                <a href="ideal-flat.php" class="nav-link <?php echo ($current_page === 'ideal-flat.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Ideal Flat
                </a>
                <a href="index.php" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-right-to-bracket"></i> Login / Register
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>
