<?php
/**
 * FlatConnect - Accommodation Seeker Dashboard
 * 
 * Features:
 * - Innovative Feature A: Smart Recommendation System based on Seeker preferences
 * - Saved (favorited) properties management
 * - My Sent Enquiries and replies tracker
 * - Quick shortcuts to search flats, PGs, and roommates
 */

define('PAGE_TITLE', 'Seeker Dashboard - FlatConnect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_role('seeker');
$user = current_user();

// 1. Fetch Seeker Profile Preferences for Smart Matching
$user_stmt = $pdo->prepare("SELECT preferred_location, max_budget, preferred_type, preferred_furnishing, bio FROM users WHERE id = ?");
$user_stmt->execute([$user['id']]);
$profile = $user_stmt->fetch() ?: [];

$pref_loc     = $profile['preferred_location'] ?? 'Hinjewadi';
$pref_budget  = (int) ($profile['max_budget'] ?? 18000);
$pref_type    = $profile['preferred_type'] ?? 'Flat';
$pref_furnish = $profile['preferred_furnishing'] ?? 'Semi-Furnished';

// 2. Innovative Feature A: Rule-Based Smart Matching Recommendation Engine
// Scores candidate properties based on location, budget, type and furnishing alignment
$rec_stmt = $pdo->prepare("
    SELECT p.*, COALESCE(pi.image_path, 'assets/images/properties/prop1.svg') AS primary_image,
        (
            (CASE WHEN p.location LIKE :loc THEN 40 ELSE 0 END) +
            (CASE WHEN p.rent <= :budget THEN 30 ELSE 0 END) +
            (CASE WHEN p.property_type = :ptype THEN 20 ELSE 0 END) +
            (CASE WHEN p.furnishing = :furnish THEN 10 ELSE 0 END)
        ) AS match_score
    FROM properties p 
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE p.status = 'Available'
    ORDER BY match_score DESC, p.id DESC 
    LIMIT 4
");
$rec_stmt->execute([
    ':loc'     => '%' . $pref_loc . '%',
    ':budget'  => $pref_budget,
    ':ptype'   => $pref_type,
    ':furnish' => $pref_furnish
]);
$recommended_properties = $rec_stmt->fetchAll();

// 3. Fetch Saved Properties
$saved_stmt = $pdo->prepare("
    SELECT p.*, COALESCE(pi.image_path, 'assets/images/properties/prop1.svg') AS primary_image 
    FROM saved_properties sp 
    JOIN properties p ON sp.property_id = p.id 
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE sp.user_id = ? 
    ORDER BY sp.id DESC
");
$saved_stmt->execute([$user['id']]);
$saved_properties = $saved_stmt->fetchAll();
$saved_ids = array_column($saved_properties, 'id');

// 4. Fetch Enquiries sent by this seeker
$enq_stmt = $pdo->prepare("
    SELECT e.*, u.name AS receiver_name, u.mobile AS receiver_mobile,
           p.title AS property_title,
           rep.property_name AS replacement_title,
           rm.preferred_location AS roommate_location
    FROM enquiries e 
    JOIN users u ON e.receiver_id = u.id 
    LEFT JOIN properties p ON e.property_id = p.id 
    LEFT JOIN replacement_posts rep ON e.replacement_id = rep.id 
    LEFT JOIN roommate_posts rm ON e.roommate_post_id = rm.id 
    WHERE e.sender_id = ? 
    ORDER BY e.id DESC
");
$enq_stmt->execute([$user['id']]);
$sent_enquiries = $enq_stmt->fetchAll();

$flash = get_flash();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Welcome Bar -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:28px;">
            <div>
                <span class="role-badge seeker"><i class="fa-solid fa-magnifying-glass"></i> Accommodation Seeker</span>
                <h1 style="font-size: 2rem; margin-top: 4px;">Welcome, <?php echo e($user['name']); ?></h1>
                <p class="text-muted">Explore AI-free smart matches, manage your saved flats, and track enquiry replies.</p>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="ideal-flat.php" class="btn btn-secondary" style="background:linear-gradient(135deg, var(--secondary) 0%, #0E7F7C 100%);">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Find My Ideal Flat
                </a>
                <a href="properties.php" class="btn btn-outline">
                    <i class="fa-solid fa-building"></i> Browse Flats
                </a>
                <a href="roommates.php" class="btn btn-outline">
                    <i class="fa-solid fa-user-group"></i> Roommates
                </a>
                <a href="replacement.php" class="btn btn-outline">
                    <i class="fa-solid fa-arrows-rotate"></i> Replacements
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo e($flash['message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Seeker Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#E0F2FE; color:#0284C7;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <div class="stat-val"><?php echo count($recommended_properties); ?></div>
                    <div class="stat-label">Smart Matches Found</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:var(--urgent-bg); color:var(--urgent);">
                    <i class="fa-solid fa-heart"></i>
                </div>
                <div>
                    <div class="stat-val"><?php echo count($saved_properties); ?></div>
                    <div class="stat-label">Saved Properties</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-regular fa-paper-plane"></i>
                </div>
                <div>
                    <div class="stat-val"><?php echo count($sent_enquiries); ?></div>
                    <div class="stat-label">Enquiries Sent</div>
                </div>
            </div>
        </div>

        <!-- Section 1: Innovative Feature A - Smart Recommended Properties -->
        <section style="margin-bottom: 40px;">
            <div class="card" style="border: 2px solid var(--secondary-border); background: #FFFFFF;">
                <div class="card-header" style="background: #F0FDF4; border-bottom: 1px solid #BBF7D0;">
                    <div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="background:var(--secondary); color:#fff; width:28px; height:28px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; font-size:0.9rem;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </span>
                            <h2 style="font-size: 1.25rem; color:var(--primary); margin:0;">Smart Matching Recommendations</h2>
                        </div>
                        <span class="text-muted" style="font-size: 0.85rem; display:block; margin-top:4px;">
                            Ranked rule-based properties matching your preferences: <strong><?php echo e($pref_loc); ?></strong> • Max <strong><?php echo format_inr($pref_budget); ?></strong> • <strong><?php echo e($pref_type); ?></strong>
                        </span>
                    </div>
                    <a href="profile.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-sliders"></i> Edit Preferences
                    </a>
                </div>

                <div class="card-body">
                    <?php if (empty($recommended_properties)): ?>
                        <p class="text-muted text-center" style="padding:20px;">No exact smart matches right now. Update your profile budget or preferred location to see tailored recommendations.</p>
                    <?php else: ?>
                        <div class="property-grid" style="margin-bottom:0;">
                            <?php foreach ($recommended_properties as $prop): ?>
                                <div class="property-card" style="border-color: #E2E8F0;">
                                    <div class="property-img-wrapper">
                                        <img src="<?php echo e($prop['primary_image']); ?>" alt="<?php echo e($prop['title']); ?>" loading="lazy">
                                        <span style="position:absolute; top:12px; left:12px; background:#059669; color:#fff; padding:4px 10px; border-radius:var(--radius-full); font-size:0.75rem; font-weight:700;">
                                            <i class="fa-solid fa-circle-check"></i> <?php echo $prop['match_score']; ?>% Match
                                        </span>
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
                                            <span class="amenity-tag"><?php echo e($prop['furnishing']); ?></span>
                                            <span class="amenity-tag">Bachelors: <?php echo e($prop['bachelor_allowed']); ?></span>
                                        </div>
                                        <div class="property-actions">
                                            <span class="text-muted" style="font-size:0.8rem;">Deposit: <?php echo format_inr($prop['deposit']); ?></span>
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
        </section>

        <!-- Section 2: Saved (Favorite) Properties -->
        <section style="margin-bottom: 40px;">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 style="font-size: 1.25rem;"><i class="fa-solid fa-heart" style="color:var(--urgent);"></i> Saved Properties</h2>
                        <span class="text-muted" style="font-size: 0.85rem;">Properties bookmarked for easy inspection and direct owner contact</span>
                    </div>
                </div>

                <div class="card-body">
                    <?php if (empty($saved_properties)): ?>
                        <div style="padding: 35px 20px; text-align: center;">
                            <i class="fa-regular fa-heart" style="font-size: 2.2rem; color: var(--text-light); margin-bottom: 10px;"></i>
                            <h4>No saved properties yet</h4>
                            <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 14px;">Click the heart icon on any property card to save it here.</p>
                            <a href="properties.php" class="btn btn-secondary btn-sm">Browse Properties</a>
                        </div>
                    <?php else: ?>
                        <div class="property-grid" style="margin-bottom:0;">
                            <?php foreach ($saved_properties as $prop): ?>
                                <div class="property-card">
                                    <div class="property-img-wrapper">
                                        <img src="<?php echo e($prop['primary_image']); ?>" alt="<?php echo e($prop['title']); ?>" loading="lazy">
                                        <span class="type-pill"><?php echo e($prop['property_type']); ?></span>
                                        <button type="button" class="save-btn active" data-id="<?php echo $prop['id']; ?>" title="Remove from saved">
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
                                        <div class="property-actions">
                                            <span class="text-muted" style="font-size:0.8rem;">Deposit: <?php echo format_inr($prop['deposit']); ?></span>
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
        </section>

        <!-- Section 3: My Sent Enquiries -->
        <section>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 style="font-size: 1.25rem;"><i class="fa-regular fa-paper-plane"></i> My Sent Enquiries</h2>
                        <span class="text-muted" style="font-size: 0.85rem;">Status of messages sent to property owners and outgoing tenants</span>
                    </div>
                </div>

                <div class="card-body" style="padding: 0;">
                    <?php if (empty($sent_enquiries)): ?>
                        <div style="padding: 35px 20px; text-align: center;">
                            <i class="fa-regular fa-envelope-open" style="font-size: 2.2rem; color: var(--text-light); margin-bottom: 10px;"></i>
                            <h4>No enquiries sent yet</h4>
                            <p class="text-muted" style="font-size: 0.9rem;">Send direct enquiries from any listing or roommate profile.</p>
                            <a href="properties.php" class="btn btn-secondary btn-sm">Find Properties</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Target Listing</th>
                                        <th>Owner / Tenant Contact</th>
                                        <th>Your Message</th>
                                        <th>Status</th>
                                        <th>Owner Response</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sent_enquiries as $enq): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight:700; color:var(--primary);">
                                                    <?php echo e($enq['property_title'] ?: ($enq['replacement_title'] ?: ('Roommate (' . $enq['roommate_location'] . ')'))); ?>
                                                </div>
                                                <small class="text-muted"><?php echo date('d M Y, h:i A', strtotime($enq['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div style="font-weight:600;"><?php echo e($enq['receiver_name']); ?></div>
                                                <div style="font-size:0.8rem; color:var(--text-muted);"><i class="fa-solid fa-phone"></i> <?php echo e($enq['receiver_mobile']); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size:0.9rem; color:var(--text-dark); max-width:280px;">
                                                    "<?php echo e($enq['message']); ?>"
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($enq['status'] === 'Replied'): ?>
                                                    <span style="background:#DCFCE7; color:#15803D; padding:3px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">
                                                        <i class="fa-solid fa-check"></i> Replied
                                                    </span>
                                                <?php else: ?>
                                                    <span style="background:#FEF3C7; color:#B45309; padding:3px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">
                                                        <i class="fa-regular fa-clock"></i> Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($enq['response'])): ?>
                                                    <div style="padding:8px 12px; background:#F0FDF4; border-radius:6px; border-left:3px solid var(--success); font-size:0.85rem; color:#166534;">
                                                        <strong>Reply:</strong> <?php echo e($enq['response']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size:0.85rem; font-style:italic;">Awaiting response...</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
