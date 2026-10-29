<?php
/**
 * FlatConnect - Home Page (After Login)
 */

define('PAGE_TITLE', 'FlatConnect - Find Your Next Home Without the Middleman');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Protect page: require user to be logged in
require_login();
$user = current_user();

// Fetch Urgent Vacancies
$urgent_stmt = $pdo->prepare("
    SELECT p.*, COALESCE(pi.image_path, 'assets/images/properties/prop1.svg') AS primary_image 
    FROM properties p 
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE p.status = 'Available' AND p.is_urgent = 1 
    ORDER BY p.id DESC 
    LIMIT 3
");
$urgent_stmt->execute();
$urgent_properties = $urgent_stmt->fetchAll();

// Fetch Featured Properties
$feat_stmt = $pdo->prepare("
    SELECT p.*, COALESCE(pi.image_path, 'assets/images/properties/prop1.svg') AS primary_image 
    FROM properties p 
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE p.status = 'Available' 
    ORDER BY p.id DESC 
    LIMIT 6
");
$feat_stmt->execute();
$featured_properties = $feat_stmt->fetchAll();

// Get saved properties for this user
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
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <span style="display:inline-block; background:rgba(21,152,149,0.25); color:#5EEAD4; padding:6px 14px; border-radius:30px; font-size:0.85rem; font-weight:700; margin-bottom:14px; border:1px solid rgba(94,234,212,0.3);">
                    <i class="fa-solid fa-sparkles"></i> Direct Connect • Zero Brokerage
                </span>
                <h1 class="hero-title">Find Your Next Home Without the Middleman</h1>
                <p class="hero-subtitle">Find flats, PGs, rooms and roommates easily. Connect directly with owners and tenants.</p>

                <!-- Hero Search Bar -->
                <form action="properties.php" method="GET" class="hero-search-bar">
                    <!-- Location -->
                    <div class="search-field">
                        <select name="location">
                            <option value="">All Locations in Pune</option>
                            <option value="Hinjewadi">Hinjewadi (IT Hub)</option>
                            <option value="Wakad">Wakad</option>
                            <option value="Tathawade">Tathawade</option>
                            <option value="Baner">Baner</option>
                            <option value="Punawale">Punawale</option>
                            <option value="Pimpri">Pimpri</option>
                        </select>
                    </div>

                    <!-- Property Type -->
                    <div class="search-field">
                        <select name="type">
                            <option value="">Any Property Type</option>
                            <option value="Flat">Flat / Apartment</option>
                            <option value="PG">PG / Paying Guest</option>
                            <option value="Room">Private Room / 1RK</option>
                        </select>
                    </div>

                    <!-- Max Budget -->
                    <div class="search-field">
                        <select name="max_budget">
                            <option value="">Any Budget</option>
                            <option value="8000">Up to ₹8,000</option>
                            <option value="12000">Up to ₹12,000</option>
                            <option value="18000">Up to ₹18,000</option>
                            <option value="25000">Up to ₹25,000</option>
                            <option value="35000">Up to ₹35,000</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-secondary btn-lg" style="height: 100%;">
                        <i class="fa-solid fa-magnifying-glass"></i> Search
                    </button>
                </form>
            </div>
        </section>

        <!-- Four Category Cards -->
        <section>
            <div class="category-grid">
                <!-- 1. Find Flats -->
                <a href="properties.php?type=Flat" class="category-card">
                    <div class="category-icon">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <h3 class="category-title">Find Flats</h3>
                    <p class="category-desc">1BHK, 2BHK and 3BHK flats directly from owners with no broker fees.</p>
                </a>

                <!-- 2. Find PGs -->
                <a href="properties.php?type=PG" class="category-card">
                    <div class="category-icon" style="background:#FEF3C7; color:#D97706;">
                        <i class="fa-solid fa-bed"></i>
                    </div>
                    <h3 class="category-title">Find PGs</h3>
                    <p class="category-desc">Well-managed PGs for students and working professionals with food & WiFi.</p>
                </a>

                <!-- 3. Find Roommates -->
                <a href="roommates.php" class="category-card">
                    <div class="category-icon" style="background:#E0E7FF; color:#4338CA;">
                        <i class="fa-solid fa-user-group"></i>
                    </div>
                    <h3 class="category-title">Find Roommates</h3>
                    <p class="category-desc">Match with compatible roommates based on location, budget and habits.</p>
                </a>

                <!-- 4. Tenant Replacement -->
                <a href="replacement.php" class="category-card">
                    <div class="category-icon" style="background:#FCE7F3; color:#DB2777;">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </div>
                    <h3 class="category-title">Tenant Replacement</h3>
                    <p class="category-desc">Take over existing tenant leases directly with instant occupancy.</p>
                </a>
            </div>
        </section>

        <!-- Feature Highlight: Find My Ideal Flat Wizard -->
        <section style="margin-bottom: 40px;">
            <div style="background: linear-gradient(135deg, #142D4E 0%, #1F3E6D 100%); border-radius: var(--radius-lg); padding: 28px 32px; color: #FFFFFF; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; box-shadow: var(--shadow-md); position: relative; overflow: hidden;">
                <div style="position: absolute; right: -20px; bottom: -20px; opacity: 0.08; font-size: 11rem; pointer-events: none;">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div style="max-width: 680px; position: relative; z-index: 1;">
                    <span style="display:inline-block; background:rgba(21,152,149,0.3); color:#5EEAD4; padding:4px 12px; border-radius:20px; font-size:0.8rem; font-weight:700; margin-bottom:10px; border:1px solid rgba(94,234,212,0.35);">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> NEW FEATURE FOR STUDENTS
                    </span>
                    <h2 style="color: #FFFFFF; font-size: 1.6rem; margin-bottom: 8px;">Find My Ideal Flat - Interactive Recommendation Wizard</h2>
                    <p style="color: #CBD5E1; font-size: 0.95rem; line-height: 1.5; margin: 0;">
                        Moving to Pune for college or your first job? Answer 6 quick questions about your rent budget, preferred locality, single/shared room, and amenities to get personalized matches instantly.
                    </p>
                </div>
                <div style="position: relative; z-index: 1;">
                    <a href="ideal-flat.php" class="btn btn-secondary btn-lg" style="box-shadow: 0 4px 14px rgba(21, 152, 149, 0.4);">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Launch Recommendation Wizard
                    </a>
                </div>
            </div>
        </section>

        <!-- Urgent Vacancies Section -->
        <?php if (!empty($urgent_properties)): ?>
            <section style="margin-bottom: 50px;">
                <div class="section-header">
                    <div>
                        <span style="color:var(--urgent); font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px;">
                            <i class="fa-solid fa-bolt"></i> Immediate Move-in
                        </span>
                        <h2 class="section-title">Urgent Vacancies</h2>
                        <p class="section-subtitle">Properties available right now with verified immediate occupancy</p>
                    </div>
                    <a href="properties.php?urgent=1" class="btn btn-outline btn-sm">
                        View All Urgent <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div class="property-grid">
                    <?php foreach ($urgent_properties as $prop): ?>
                        <div class="property-card">
                            <div class="property-img-wrapper">
                                <img src="<?php echo e($prop['primary_image']); ?>" alt="<?php echo e($prop['title']); ?>" loading="lazy">
                                <span class="urgent-badge"><i class="fa-solid fa-bolt"></i> Urgent Vacancy</span>
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
                                    <span class="amenity-tag"><i class="fa-solid fa-indian-rupee-sign"></i> Dep: <?php echo format_inr($prop['deposit']); ?></span>
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
            </section>
        <?php endif; ?>

        <!-- Featured Properties Section -->
        <section style="margin-bottom: 50px;">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Featured Properties</h2>
                    <p class="section-subtitle">Handpicked flats, PGs and rooms from verified property owners</p>
                </div>
                <a href="properties.php" class="btn btn-outline btn-sm">
                    View All Properties <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="property-grid">
                <?php foreach ($featured_properties as $prop): ?>
                    <div class="property-card">
                        <div class="property-img-wrapper">
                            <img src="<?php echo e($prop['primary_image']); ?>" alt="<?php echo e($prop['title']); ?>" loading="lazy">
                            <?php if ($prop['is_urgent']): ?>
                                <span class="urgent-badge"><i class="fa-solid fa-bolt"></i> Urgent</span>
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
                                <span class="amenity-tag"><i class="fa-regular fa-calendar-check"></i> <?php echo date('d M', strtotime($prop['available_from'])); ?></span>
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
        </section>

        <!-- How FlatConnect Works -->
        <section style="background:var(--bg-white); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:40px; margin-bottom:50px;">
            <div class="text-center" style="max-width:650px; margin:0 auto 36px;">
                <h2 style="font-size:1.8rem; margin-bottom:8px;">How FlatConnect Works</h2>
                <p class="text-muted">A direct 3-step solution to eliminate brokers and simplify your rental journey.</p>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:30px;">
                <div style="text-align:center; padding:20px;">
                    <div style="width:64px; height:64px; background:var(--secondary-light); color:var(--secondary); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:1.6rem; font-weight:800;">
                        1
                    </div>
                    <h3 style="font-size:1.2rem; margin-bottom:8px;">Search & Match</h3>
                    <p class="text-muted" style="font-size:0.925rem;">Browse verified flats, PGs, roommate profiles or tenant replacement posts by budget and location.</p>
                </div>

                <div style="text-align:center; padding:20px;">
                    <div style="width:64px; height:64px; background:var(--secondary-light); color:var(--secondary); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:1.6rem; font-weight:800;">
                        2
                    </div>
                    <h3 style="font-size:1.2rem; margin-bottom:8px;">Connect Directly</h3>
                    <p class="text-muted" style="font-size:0.925rem;">Send direct enquiries or contact owners and outgoing tenants directly without any middlemen.</p>
                </div>

                <div style="text-align:center; padding:20px;">
                    <div style="width:64px; height:64px; background:var(--secondary-light); color:var(--secondary); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:1.6rem; font-weight:800;">
                        3
                    </div>
                    <h3 style="font-size:1.2rem; margin-bottom:8px;">Move In Zero Brokerage</h3>
                    <p class="text-muted" style="font-size:0.925rem;">Finalize terms, save thousands of rupees in brokerage fees, and move into your new home seamlessly.</p>
                </div>
            </div>
        </section>

        <!-- Why Choose FlatConnect -->
        <section style="margin-bottom: 30px;">
            <div class="card" style="background: linear-gradient(135deg, #142D4E 0%, #1A365D 100%); color: #FFFFFF; border: none;">
                <div class="card-body" style="padding: 44px 36px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 30px; align-items: center;">
                        <div>
                            <span style="color: var(--secondary); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Why Choose FlatConnect</span>
                            <h2 style="color: #FFFFFF; font-size: 1.85rem; margin-top: 6px; margin-bottom: 12px;">Direct Rental Marketplace</h2>
                            <p style="color: #CBD5E1; font-size: 0.95rem; line-height: 1.6;">
                                Built to address real problems faced by students, working professionals, and newcomers when settling in a new city.
                            </p>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            <div style="display:flex; gap:12px; align-items:flex-start;">
                                <i class="fa-solid fa-circle-check" style="color:var(--secondary); margin-top:4px; font-size:1.1rem;"></i>
                                <div>
                                    <strong style="display:block; font-size:1rem;">Zero Brokerage Fees</strong>
                                    <span style="font-size:0.875rem; color:#CBD5E1;">Never pay 1 to 2 months rent as broker commission.</span>
                                </div>
                            </div>
                            <div style="display:flex; gap:12px; align-items:flex-start;">
                                <i class="fa-solid fa-circle-check" style="color:var(--secondary); margin-top:4px; font-size:1.1rem;"></i>
                                <div>
                                    <strong style="display:block; font-size:1rem;">Clear Bachelor Permissions</strong>
                                    <span style="font-size:0.875rem; color:#CBD5E1;">No awkward rejections: know beforehand if students/bachelors are allowed.</span>
                                </div>
                            </div>
                            <div style="display:flex; gap:12px; align-items:flex-start;">
                                <i class="fa-solid fa-circle-check" style="color:var(--secondary); margin-top:4px; font-size:1.1rem;"></i>
                                <div>
                                    <strong style="display:block; font-size:1rem;">Unique Tenant Replacement</strong>
                                    <span style="font-size:0.875rem; color:#CBD5E1;">Helps existing tenants exit lease smoothly while seekers get ready flats.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
