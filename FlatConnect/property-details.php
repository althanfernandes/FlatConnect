<?php
/**
 * FlatConnect - Property Details Page
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: properties.php');
    exit;
}

// Fetch property along with owner information
$stmt = $pdo->prepare("
    SELECT p.*, u.name AS owner_name, u.email AS owner_email, u.mobile AS owner_mobile 
    FROM properties p 
    JOIN users u ON p.owner_id = u.id 
    WHERE p.id = ? 
    LIMIT 1
");
$stmt->execute([$id]);
$property = $stmt->fetch();

if (!$property) {
    die('<div class="container" style="padding: 50px 20px; text-align: center;"><h2>Property Not Found</h2><p><a href="properties.php">Return to Properties</a></p></div>');
}

define('PAGE_TITLE', e($property['title']) . ' - FlatConnect');

// Fetch all photos for this property
$img_stmt = $pdo->prepare("SELECT image_path, is_primary FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC");
$img_stmt->execute([$id]);
$images = $img_stmt->fetchAll();
if (empty($images)) {
    $images = [['image_path' => 'assets/images/properties/prop1.svg', 'is_primary' => 1]];
}

// Check if currently saved by this user
$saved_stmt = $pdo->prepare("SELECT id FROM saved_properties WHERE user_id = ? AND property_id = ?");
$saved_stmt->execute([$user['id'], $id]);
$is_saved = (bool) $saved_stmt->fetch();

// Check if user is the property owner
$is_owner = ($user['id'] == $property['owner_id']);

$flash = get_flash();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Breadcrumb -->
        <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 16px;">
            <a href="home.php">Home</a> / <a href="properties.php">Properties</a> / <span><?php echo e($property['title']); ?></span>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo e($flash['message']); ?></span>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
            <!-- Main Details Column -->
            <div>
                <!-- Property Photo Showcase -->
                <div class="card" style="margin-bottom: 24px; overflow: hidden;">
                    <div style="height: 380px; background: #E2E8F0; position: relative;">
                        <img src="<?php echo e($images[0]['image_path']); ?>" alt="<?php echo e($property['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php if ($property['is_urgent']): ?>
                            <span class="urgent-badge" style="top: 18px; left: 18px; font-size: 0.85rem; padding: 6px 14px;">
                                <i class="fa-solid fa-bolt"></i> Urgent Vacancy
                            </span>
                        <?php endif; ?>
                        <span class="type-pill" style="top: 18px; right: 18px; font-size: 0.85rem; padding: 6px 14px;">
                            <?php echo e($property['property_type']); ?>
                        </span>
                    </div>

                    <?php if (count($images) > 1): ?>
                        <div style="display: flex; gap: 10px; padding: 14px; background: #F8FAFC; overflow-x: auto;">
                            <?php foreach ($images as $img): ?>
                                <img src="<?php echo e($img['image_path']); ?>" alt="Property View" style="width: 90px; height: 60px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 1px solid var(--border-color);" onclick="document.querySelector('.card img').src = this.src;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Title & Location Header -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-body">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
                            <div>
                                <span style="color: var(--secondary); font-weight: 700; font-size: 0.9rem;">
                                    <i class="fa-solid fa-location-dot"></i> <?php echo e($property['location']); ?>, Pune
                                </span>
                                <h1 style="font-size: 1.8rem; margin: 6px 0 10px;"><?php echo e($property['title']); ?></h1>
                                <p style="color: var(--text-muted); font-size: 0.95rem;">
                                    <i class="fa-solid fa-map-pin"></i> <?php echo e($property['address']); ?>
                                </p>
                            </div>

                            <button type="button" class="save-btn <?php echo $is_saved ? 'active' : ''; ?>" data-id="<?php echo $property['id']; ?>" title="Save Property" style="position: static; width: 44px; height: 44px; font-size: 1.2rem;">
                                <i class="fa-solid fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Key Highlights Grid -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h2 style="font-size: 1.15rem;"><i class="fa-solid fa-circle-info"></i> Key Overview</h2>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px;">
                            <div style="padding: 12px; background: var(--bg-light); border-radius: var(--radius-md);">
                                <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Property Type</span>
                                <div style="font-weight: 700; font-size: 1.05rem; color: var(--primary);"><?php echo e($property['property_type']); ?></div>
                            </div>
                            <div style="padding: 12px; background: var(--bg-light); border-radius: var(--radius-md);">
                                <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Furnishing</span>
                                <div style="font-weight: 700; font-size: 1.05rem; color: var(--primary);"><?php echo e($property['furnishing']); ?></div>
                            </div>
                            <div style="padding: 12px; background: var(--bg-light); border-radius: var(--radius-md);">
                                <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Bachelors / Students</span>
                                <div style="font-weight: 700; font-size: 1.05rem; color: var(--success);"><?php echo e($property['bachelor_allowed']); ?></div>
                            </div>
                            <div style="padding: 12px; background: var(--bg-light); border-radius: var(--radius-md);">
                                <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Available From</span>
                                <div style="font-weight: 700; font-size: 1.05rem; color: var(--primary);"><?php echo date('d M Y', strtotime($property['available_from'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Facilities & Amenities -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h2 style="font-size: 1.15rem;"><i class="fa-solid fa-list-check"></i> Facilities & Amenities</h2>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <?php 
                            $facilities_arr = array_map('trim', explode(',', $property['facilities']));
                            foreach ($facilities_arr as $facility): 
                                if (empty($facility)) continue;
                            ?>
                                <span style="background: var(--secondary-light); color: var(--secondary-hover); border: 1px solid var(--secondary-border); padding: 8px 14px; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-circle-check"></i> <?php echo e($facility); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h2 style="font-size: 1.15rem;"><i class="fa-solid fa-align-left"></i> Property Description</h2>
                    </div>
                    <div class="card-body">
                        <p style="white-space: pre-line; color: var(--text-dark); line-height: 1.7; font-size: 1rem;">
                            <?php echo e($property['description']); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sticky Right Pricing & Direct Contact Column -->
            <div style="position: sticky; top: 90px;">
                <div class="card" style="border-top: 4px solid var(--secondary);">
                    <div class="card-body">
                        <!-- Rent Display -->
                        <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color);">
                            <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Monthly Rent</span>
                            <div style="font-size: 2.2rem; font-weight: 800; color: var(--primary);">
                                <?php echo format_inr($property['rent']); ?> <span style="font-size: 1rem; color: var(--text-muted); font-weight: 500;">/ month</span>
                            </div>
                            <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 4px;">
                                <strong>Security Deposit:</strong> <?php echo format_inr($property['deposit']); ?>
                            </div>
                            <div style="display: inline-block; background: #DCFCE7; color: #15803D; font-weight: 700; font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; margin-top: 8px;">
                                <i class="fa-solid fa-shield-halved"></i> 100% Zero Brokerage
                            </div>
                        </div>

                        <!-- Owner Contact Information Section -->
                        <div style="margin-bottom: 20px;">
                            <h3 style="font-size: 1rem; margin-bottom: 10px;">Listed by Property Owner</h3>
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                                <div class="avatar-circle" style="width: 44px; height: 44px; font-size: 1rem;">
                                    <?php echo strtoupper(substr($property['owner_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--primary);"><?php echo e($property['owner_name']); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">Verified Owner on FlatConnect</div>
                                </div>
                            </div>

                            <!-- Display Owner's Shared Contact -->
                            <div style="background: var(--bg-light); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px; margin-bottom: 16px;">
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 4px;">Direct Phone / WhatsApp:</div>
                                <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary);">
                                    <i class="fa-solid fa-phone" style="color: var(--secondary);"></i> 
                                    <a href="tel:<?php echo e($property['contact_phone'] ?: $property['owner_mobile']); ?>" style="color: inherit;">
                                        <?php echo e($property['contact_phone'] ?: $property['owner_mobile']); ?>
                                    </a>
                                </div>
                            </div>

                            <?php if (!$is_owner): ?>
                                <!-- Send Enquiry Trigger Button -->
                                <button type="button" class="btn btn-secondary btn-block" onclick="openModal('enquiryModal');">
                                    <i class="fa-regular fa-paper-plane"></i> Send Direct Enquiry
                                </button>
                            <?php else: ?>
                                <div style="display: flex; gap: 8px;">
                                    <a href="edit-property.php?id=<?php echo $property['id']; ?>" class="btn btn-primary btn-block btn-sm">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </a>
                                    <a href="delete-property.php?id=<?php echo $property['id']; ?>&token=<?php echo csrf_token(); ?>" class="btn btn-danger btn-block btn-sm" onclick="return confirm('Are you sure you want to delete this property listing?');">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Direct Enquiry Modal -->
<div id="enquiryModal" class="modal-backdrop">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size: 1.15rem; color: var(--primary);"><i class="fa-regular fa-paper-plane"></i> Send Direct Enquiry</h3>
            <button type="button" class="modal-close" onclick="closeModal('enquiryModal');">&times;</button>
        </div>
        <form action="enquiry.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="send">
                <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                <input type="hidden" name="receiver_id" value="<?php echo $property['owner_id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="redirect_url" value="property-details.php?id=<?php echo $property['id']; ?>">

                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 14px;">
                    Enquiring about: <strong><?php echo e($property['title']); ?></strong>
                </p>

                <div class="form-group">
                    <label for="enquiry_message">Your Message to Owner</label>
                    <textarea id="enquiry_message" name="message" class="form-control" rows="4" placeholder="Hello, I am interested in this flat. Is it available for inspection this weekend?" required></textarea>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('enquiryModal');">Cancel</button>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-paper-plane"></i> Send Message
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
