<?php
/**
 * FlatConnect - Find Properties Listing & Filtering Page
 */

define('PAGE_TITLE', 'Find Flats, PGs & Rooms in Pune - FlatConnect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Require login
require_login();
$user = current_user();

// Extract filter parameters from GET request
$filter_location   = trim($_GET['location'] ?? '');
$filter_type       = trim($_GET['type'] ?? '');
$filter_min_budget = !empty($_GET['min_budget']) ? (int) $_GET['min_budget'] : null;
$filter_max_budget = !empty($_GET['max_budget']) ? (int) $_GET['max_budget'] : null;
$filter_furnishing = trim($_GET['furnishing'] ?? '');
$filter_bachelor   = trim($_GET['bachelor'] ?? '');
$filter_urgent     = !empty($_GET['urgent']) ? 1 : 0;

// Build dynamic SQL query
$query = "
    SELECT p.*, COALESCE(pi.image_path, 'assets/images/properties/prop1.svg') AS primary_image 
    FROM properties p 
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE p.status = 'Available'
";
$params = [];

if (!empty($filter_location)) {
    $query .= " AND p.location LIKE :location";
    $params[':location'] = '%' . $filter_location . '%';
}

if (!empty($filter_type)) {
    $query .= " AND p.property_type = :property_type";
    $params[':property_type'] = $filter_type;
}

if ($filter_min_budget !== null) {
    $query .= " AND p.rent >= :min_budget";
    $params[':min_budget'] = $filter_min_budget;
}

if ($filter_max_budget !== null) {
    $query .= " AND p.rent <= :max_budget";
    $params[':max_budget'] = $filter_max_budget;
}

if (!empty($filter_furnishing)) {
    $query .= " AND p.furnishing = :furnishing";
    $params[':furnishing'] = $filter_furnishing;
}

if (!empty($filter_bachelor)) {
    if ($filter_bachelor === 'Yes') {
        $query .= " AND p.bachelor_allowed IN ('Yes', 'Boys Only', 'Girls Only')";
    } else {
        $query .= " AND p.bachelor_allowed = :bachelor_allowed";
        $params[':bachelor_allowed'] = $filter_bachelor;
    }
}

if ($filter_urgent === 1) {
    $query .= " AND p.is_urgent = 1";
}

$query .= " ORDER BY p.is_urgent DESC, p.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll();

// Saved properties by current user
$saved_ids = [];
if (is_logged_in()) {
    $saved_stmt = $pdo->prepare("SELECT property_id FROM saved_properties WHERE user_id = ?");
    $saved_stmt->execute([$user['id']]);
    $saved_ids = $saved_stmt->fetchAll(PDO::FETCH_COLUMN);
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Breadcrumb & Header -->
        <div style="margin-bottom: 24px;">
            <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 6px;">
                <a href="home.php">Home</a> / <span>Properties</span>
            </div>
            <h1 style="font-size: 2rem;">Available Rental Properties in Pune</h1>
            <p class="text-muted">Direct listings with zero brokerage. Filter by your budget, location, and bachelor preference.</p>
        </div>

        <!-- Ideal Flat Wizard Helper Banner -->
        <div style="background: linear-gradient(90deg, #E0F2F1 0%, #FFFFFF 100%); border: 1px solid var(--secondary-border); border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--secondary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <strong style="color: var(--primary); font-size: 0.95rem;">Are you a student looking for budget accommodation?</strong>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">Try our interactive recommendation wizard to get tailored recommendations with scoring breakdowns.</p>
                </div>
            </div>
            <a href="ideal-flat.php" class="btn btn-secondary btn-sm" style="white-space: nowrap;">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Find My Ideal Flat
            </a>
        </div>

        <div class="filter-layout">
            <!-- Sidebar Filter Form -->
            <aside class="filter-sidebar">
                <form action="properties.php" method="GET" id="propertyFilterForm">
                    <div class="filter-title">
                        <span><i class="fa-solid fa-sliders"></i> Filter Results</span>
                        <a href="properties.php" style="font-size: 0.8rem; font-weight: 600; color: var(--danger);">Reset Filters</a>
                    </div>

                    <!-- Location Filter -->
                    <div class="filter-group">
                        <label for="filter_loc">Location in Pune</label>
                        <select name="location" id="filter_loc" class="form-control">
                            <option value="">All Locations</option>
                            <option value="Hinjewadi" <?php echo ($filter_location === 'Hinjewadi') ? 'selected' : ''; ?>>Hinjewadi</option>
                            <option value="Wakad" <?php echo ($filter_location === 'Wakad') ? 'selected' : ''; ?>>Wakad</option>
                            <option value="Tathawade" <?php echo ($filter_location === 'Tathawade') ? 'selected' : ''; ?>>Tathawade</option>
                            <option value="Baner" <?php echo ($filter_location === 'Baner') ? 'selected' : ''; ?>>Baner</option>
                            <option value="Punawale" <?php echo ($filter_location === 'Punawale') ? 'selected' : ''; ?>>Punawale</option>
                            <option value="Pimpri" <?php echo ($filter_location === 'Pimpri') ? 'selected' : ''; ?>>Pimpri</option>
                        </select>
                    </div>

                    <!-- Property Type Filter -->
                    <div class="filter-group">
                        <label for="filter_type">Property Type</label>
                        <select name="type" id="filter_type" class="form-control">
                            <option value="">All Types</option>
                            <option value="Flat" <?php echo ($filter_type === 'Flat') ? 'selected' : ''; ?>>Flat / Apartment</option>
                            <option value="PG" <?php echo ($filter_type === 'PG') ? 'selected' : ''; ?>>PG (Paying Guest)</option>
                            <option value="Room" <?php echo ($filter_type === 'Room') ? 'selected' : ''; ?>>Private Room / 1RK</option>
                        </select>
                    </div>

                    <!-- Budget Range -->
                    <div class="filter-group">
                        <label>Monthly Budget (₹)</label>
                        <div style="display:flex; gap:8px;">
                            <input type="number" name="min_budget" class="form-control" placeholder="Min" value="<?php echo e($_GET['min_budget'] ?? ''); ?>" step="1000">
                            <input type="number" name="max_budget" class="form-control" placeholder="Max" value="<?php echo e($_GET['max_budget'] ?? ''); ?>" step="1000">
                        </div>
                    </div>

                    <!-- Furnishing Status -->
                    <div class="filter-group">
                        <label for="filter_furnish">Furnishing</label>
                        <select name="furnishing" id="filter_furnish" class="form-control">
                            <option value="">Any Furnishing</option>
                            <option value="Fully Furnished" <?php echo ($filter_furnishing === 'Fully Furnished') ? 'selected' : ''; ?>>Fully Furnished</option>
                            <option value="Semi-Furnished" <?php echo ($filter_furnishing === 'Semi-Furnished') ? 'selected' : ''; ?>>Semi-Furnished</option>
                            <option value="Unfurnished" <?php echo ($filter_furnishing === 'Unfurnished') ? 'selected' : ''; ?>>Unfurnished</option>
                        </select>
                    </div>

                    <!-- Bachelors Allowed -->
                    <div class="filter-group">
                        <label for="filter_bachelor">Bachelors / Students</label>
                        <select name="bachelor" id="filter_bachelor" class="form-control">
                            <option value="">Any Preference</option>
                            <option value="Yes" <?php echo ($filter_bachelor === 'Yes') ? 'selected' : ''; ?>>Bachelors Allowed (Any)</option>
                            <option value="Boys Only" <?php echo ($filter_bachelor === 'Boys Only') ? 'selected' : ''; ?>>Boys Only</option>
                            <option value="Girls Only" <?php echo ($filter_bachelor === 'Girls Only') ? 'selected' : ''; ?>>Girls Only</option>
                            <option value="Family Only" <?php echo ($filter_bachelor === 'Family Only') ? 'selected' : ''; ?>>Family Only</option>
                        </select>
                    </div>

                    <!-- Urgent Vacancy Toggle -->
                    <div class="filter-group">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; text-transform:none; font-size:0.9rem;">
                            <input type="checkbox" name="urgent" value="1" <?php echo ($filter_urgent === 1) ? 'checked' : ''; ?>>
                            <span><i class="fa-solid fa-bolt" style="color:var(--urgent);"></i> Urgent Vacancies Only</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-secondary btn-block">
                        <i class="fa-solid fa-filter"></i> Apply Filters
                    </button>
                </form>
            </aside>

            <!-- Properties Content Area -->
            <div>
                <!-- Top Summary Bar -->
                <div style="background:var(--bg-white); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:12px 20px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-weight:700; color:var(--primary);">
                        Showing <?php echo count($properties); ?> <?php echo (count($properties) === 1) ? 'Property' : 'Properties'; ?>
                    </div>
                    <?php if ($user['role'] === 'owner'): ?>
                        <a href="post-property.php" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-plus"></i> Post New Property
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Empty State -->
                <?php if (empty($properties)): ?>
                    <div class="card text-center" style="padding: 50px 20px;">
                        <div style="font-size: 3rem; color: var(--text-light); margin-bottom: 16px;">
                            <i class="fa-solid fa-house-circle-xmark"></i>
                        </div>
                        <h3 style="font-size: 1.4rem; margin-bottom: 8px;">No matching properties found</h3>
                        <p class="text-muted" style="max-width: 440px; margin: 0 auto 20px;">
                            We could not find any properties matching your current filter criteria. Try expanding your budget range or selecting all locations.
                        </p>
                        <div>
                            <a href="properties.php" class="btn btn-secondary">
                                <i class="fa-solid fa-arrows-rotate"></i> Reset All Filters
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Property Cards Grid -->
                    <div class="property-grid">
                        <?php foreach ($properties as $prop): ?>
                            <div class="property-card">
                                <div class="property-img-wrapper">
                                    <img src="<?php echo e($prop['primary_image']); ?>" alt="<?php echo e($prop['title']); ?>" loading="lazy">
                                    <?php if ($prop['is_urgent']): ?>
                                        <span class="urgent-badge"><i class="fa-solid fa-bolt"></i> Urgent Vacancy</span>
                                    <?php endif; ?>
                                    <span class="type-pill"><?php echo e($prop['property_type']); ?></span>
                                    <button type="button" class="save-btn <?php echo in_array($prop['id'], $saved_ids) ? 'active' : ''; ?>" data-id="<?php echo $prop['id']; ?>" title="Save Property">
                                        <i class="fa-solid fa-heart"></i>
                                    </button>
                                </div>
                                <div class="property-details">
                                    <div class="property-location">
                                        <i class="fa-solid fa-location-dot"></i> <?php echo e($prop['location']); ?>, Pune
                                    </div>
                                    <h3 class="property-title"><?php echo e($prop['title']); ?></h3>
                                    <div class="property-price">
                                        <?php echo format_inr($prop['rent']); ?> <span>/ month</span>
                                    </div>
                                    <div class="property-amenities">
                                        <span class="amenity-tag"><i class="fa-solid fa-couch"></i> <?php echo e($prop['furnishing']); ?></span>
                                        <span class="amenity-tag"><i class="fa-regular fa-calendar-check"></i> From: <?php echo date('d M', strtotime($prop['available_from'])); ?></span>
                                    </div>
                                    <div class="property-actions">
                                        <span class="bachelor-tag <?php echo ($prop['bachelor_allowed'] === 'Yes') ? 'allowed' : ''; ?>">
                                            <i class="fa-solid fa-user-check"></i> Bachelors: <?php echo e($prop['bachelor_allowed']); ?>
                                        </span>
                                        <a href="property-details.php?id=<?php echo $prop['id']; ?>" class="btn btn-secondary btn-sm">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
