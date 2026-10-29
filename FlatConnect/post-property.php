<?php
/**
 * FlatConnect - Post New Property Listing
 * Accessible only to Property Owners
 */

define('PAGE_TITLE', 'Post New Property - FlatConnect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Only property owners can post properties
require_role('owner');
$user = current_user();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $title            = trim($_POST['title'] ?? '');
        $property_type    = trim($_POST['property_type'] ?? '');
        $location         = trim($_POST['location'] ?? '');
        $address          = trim($_POST['address'] ?? '');
        $rent             = (int) ($_POST['rent'] ?? 0);
        $deposit          = (int) ($_POST['deposit'] ?? 0);
        $furnishing       = trim($_POST['furnishing'] ?? '');
        $facilities_input = $_POST['facilities'] ?? [];
        $bachelor_allowed = trim($_POST['bachelor_allowed'] ?? 'Yes');
        $available_from   = trim($_POST['available_from'] ?? '');
        $is_urgent        = !empty($_POST['is_urgent']) ? 1 : 0;
        $description      = trim($_POST['description'] ?? '');
        $contact_phone    = trim($_POST['contact_phone'] ?? $user['mobile']);

        $facilities = is_array($facilities_input) ? implode(', ', $facilities_input) : trim($facilities_input);

        // Validation
        if (empty($title) || empty($property_type) || empty($location) || empty($address) || $rent <= 0 || empty($available_from) || empty($description)) {
            $error = 'Please fill in all mandatory fields with valid values.';
        } else {
            // Save property inside database transaction
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO properties (owner_id, title, property_type, location, address, rent, deposit, furnishing, facilities, bachelor_allowed, available_from, is_urgent, description, contact_phone, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Available')
                ");
                $stmt->execute([
                    $user['id'], $title, $property_type, $location, $address, $rent, $deposit, $furnishing, $facilities, $bachelor_allowed, $available_from, $is_urgent, $description, $contact_phone
                ]);

                $new_property_id = $pdo->lastInsertId();

                // Handle file upload safely
                $uploaded = false;
                if (!empty($_FILES['property_photos']['name'][0])) {
                    $upload_dir = __DIR__ . '/uploads/properties/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                    $total_files = count($_FILES['property_photos']['name']);

                    for ($i = 0; $i < $total_files; $i++) {
                        if ($_FILES['property_photos']['error'][$i] === UPLOAD_ERR_OK) {
                            $tmp_name = $_FILES['property_photos']['tmp_name'][$i];
                            $file_type = mime_content_type($tmp_name);

                            if (in_array($file_type, $allowed_types) && $_FILES['property_photos']['size'][$i] <= 5 * 1024 * 1024) {
                                $ext = pathinfo($_FILES['property_photos']['name'][$i], PATHINFO_EXTENSION);
                                $filename = 'prop_' . $new_property_id . '_' . uniqid() . '.' . $ext;
                                $dest = $upload_dir . $filename;

                                if (move_uploaded_file($tmp_name, $dest)) {
                                    $rel_path = 'uploads/properties/' . $filename;
                                    $is_primary = ($i === 0) ? 1 : 0;
                                    $img_stmt = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_primary) VALUES (?, ?, ?)");
                                    $img_stmt->execute([$new_property_id, $rel_path, $is_primary]);
                                    $uploaded = true;
                                }
                            }
                        }
                    }
                }

                // If no user photo uploaded, assign a clean default property illustration
                if (!$uploaded) {
                    $default_img = 'assets/images/properties/prop1.svg';
                    $img_stmt = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_primary) VALUES (?, ?, 1)");
                    $img_stmt->execute([$new_property_id, $default_img]);
                }

                $pdo->commit();
                set_flash('success', 'Your property listing has been posted successfully and is now active!');
                header("Location: owner-dashboard.php");
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Failed to save property listing: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="main-content">
    <div class="container container-sm">
        <div style="margin-bottom: 24px;">
            <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 6px;">
                <a href="owner-dashboard.php">Owner Dashboard</a> / <span>Post Property</span>
            </div>
            <h1 style="font-size: 1.85rem;"><i class="fa-solid fa-circle-plus" style="color: var(--secondary);"></i> Post New Property</h1>
            <p class="text-muted">List your flat, PG or room directly to thousands of seekers with zero brokerage.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form action="post-property.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <!-- Property Title -->
                    <div class="form-group">
                        <label for="title">Property Title *</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Spacious 2BHK Flat with Balcony in Wakad" required value="<?php echo e($_POST['title'] ?? ''); ?>">
                    </div>

                    <!-- Type and Location -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="property_type">Property Type *</label>
                            <select name="property_type" id="property_type" class="form-control" required>
                                <option value="">Select Property Type</option>
                                <option value="Flat">Flat / Apartment</option>
                                <option value="PG">PG (Paying Guest)</option>
                                <option value="Room">Private Room / 1RK</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location">Pune Locality *</label>
                            <select name="location" id="location" class="form-control" required>
                                <option value="">Select Locality</option>
                                <option value="Hinjewadi">Hinjewadi</option>
                                <option value="Wakad">Wakad</option>
                                <option value="Tathawade">Tathawade</option>
                                <option value="Baner">Baner</option>
                                <option value="Punawale">Punawale</option>
                                <option value="Pimpri">Pimpri</option>
                            </select>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="form-group">
                        <label for="address">Full Address / Landmark *</label>
                        <input type="text" id="address" name="address" class="form-control" placeholder="e.g. Near Datta Mandir, Kaspate Vasti, Wakad, Pune 411057" required value="<?php echo e($_POST['address'] ?? ''); ?>">
                    </div>

                    <!-- Rent & Deposit -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rent">Monthly Rent (₹) *</label>
                            <input type="number" id="rent" name="rent" class="form-control" placeholder="e.g. 18000" min="1000" required value="<?php echo e($_POST['rent'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="deposit">Security Deposit (₹) *</label>
                            <input type="number" id="deposit" name="deposit" class="form-control" placeholder="e.g. 35000" min="0" required value="<?php echo e($_POST['deposit'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Furnishing and Bachelor Preference -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="furnishing">Furnishing Status *</label>
                            <select name="furnishing" id="furnishing" class="form-control" required>
                                <option value="Semi-Furnished">Semi-Furnished</option>
                                <option value="Fully Furnished">Fully Furnished</option>
                                <option value="Unfurnished">Unfurnished</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bachelor_allowed">Bachelors / Students Allowed? *</label>
                            <select name="bachelor_allowed" id="bachelor_allowed" class="form-control" required>
                                <option value="Yes">Yes (All Bachelors Welcome)</option>
                                <option value="Boys Only">Boys Only</option>
                                <option value="Girls Only">Girls Only</option>
                                <option value="Family Only">Family Only</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                    </div>

                    <!-- Available Date & Urgent Vacancy -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="available_from">Available From Date *</label>
                            <input type="date" id="available_from" name="available_from" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="contact_phone">Enquiry Contact Phone *</label>
                            <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?php echo e($user['mobile']); ?>" required>
                        </div>
                    </div>

                    <!-- Urgent Checkbox -->
                    <div class="form-group" style="background: #FFF1F2; border: 1px solid #FECDD3; padding: 12px 16px; border-radius: var(--radius-md);">
                        <label style="display:flex; align-items:center; gap:8px; margin:0; cursor:pointer; font-weight:700; color:var(--urgent);">
                            <input type="checkbox" name="is_urgent" value="1">
                            <span><i class="fa-solid fa-bolt"></i> Mark as Urgent Vacancy (Available immediately for quick tenant matching)</span>
                        </label>
                    </div>

                    <!-- Facilities Checkboxes -->
                    <div class="form-group">
                        <label>Facilities & Amenities Included</label>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-top: 6px;">
                            <?php 
                            $common_facilities = ['WiFi', 'AC', 'Geyser', 'Lift', 'Parking', 'Power Backup', 'Washing Machine', 'RO Water', 'Security Guard', 'Gym'];
                            foreach ($common_facilities as $fac):
                            ?>
                                <label style="display:flex; align-items:center; gap:6px; font-size:0.9rem; cursor:pointer;">
                                    <input type="checkbox" name="facilities[]" value="<?php echo $fac; ?>">
                                    <span><?php echo $fac; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="description">Detailed Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="5" placeholder="Mention key details such as floor, society amenities, nearby colleges/IT parks, water supply, etc." required><?php echo e($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <!-- Property Photos Upload -->
                    <div class="form-group">
                        <label for="property_photos"><i class="fa-solid fa-camera"></i> Upload Property Photos</label>
                        <input type="file" id="property_photos" name="property_photos[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp">
                        <p class="form-help">You can select up to 5 photos (JPG, PNG, WebP). Max 5MB each.</p>
                        <div id="imagePreviewContainer" style="display:flex; gap:10px; margin-top:10px; flex-wrap:wrap;"></div>
                    </div>

                    <div style="display:flex; gap:14px; margin-top:30px;">
                        <button type="submit" class="btn btn-secondary btn-lg" style="flex:1;">
                            <i class="fa-solid fa-check"></i> Publish Property Listing
                        </button>
                        <a href="owner-dashboard.php" class="btn btn-outline btn-lg">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
