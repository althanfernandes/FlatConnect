<?php
/**
 * FlatConnect - Edit Property Listing
 * Accessible only to the property's owner
 */

define('PAGE_TITLE', 'Edit Property - FlatConnect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_role('owner');
$user = current_user();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: owner-dashboard.php');
    exit;
}

// Fetch property and verify ownership
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
$stmt->execute([$id, $user['id']]);
$property = $stmt->fetch();

if (!$property) {
    set_flash('error', 'Property not found or you do not have permission to edit it.');
    header('Location: owner-dashboard.php');
    exit;
}

$error = '';
$existing_facilities = array_map('trim', explode(',', $property['facilities']));

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
        $status           = trim($_POST['status'] ?? 'Available');
        $description      = trim($_POST['description'] ?? '');
        $contact_phone    = trim($_POST['contact_phone'] ?? $property['contact_phone']);

        $facilities = is_array($facilities_input) ? implode(', ', $facilities_input) : trim($facilities_input);

        if (empty($title) || empty($property_type) || empty($location) || empty($address) || $rent <= 0 || empty($available_from) || empty($description)) {
            $error = 'Please fill in all mandatory fields with valid values.';
        } else {
            $update = $pdo->prepare("
                UPDATE properties SET 
                    title = ?, property_type = ?, location = ?, address = ?, 
                    rent = ?, deposit = ?, furnishing = ?, facilities = ?, 
                    bachelor_allowed = ?, available_from = ?, is_urgent = ?, 
                    status = ?, description = ?, contact_phone = ? 
                WHERE id = ? AND owner_id = ?
            ");
            $success = $update->execute([
                $title, $property_type, $location, $address,
                $rent, $deposit, $furnishing, $facilities,
                $bachelor_allowed, $available_from, $is_urgent,
                $status, $description, $contact_phone,
                $id, $user['id']
            ]);

            if ($success) {
                set_flash('success', 'Property listing updated successfully!');
                header("Location: owner-dashboard.php");
                exit;
            } else {
                $error = 'Failed to update property details.';
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
                <a href="owner-dashboard.php">Owner Dashboard</a> / <span>Edit Property</span>
            </div>
            <h1 style="font-size: 1.85rem;"><i class="fa-solid fa-pen-to-square" style="color: var(--secondary);"></i> Edit Property Listing</h1>
            <p class="text-muted">Update details, price or availability status for your listing.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form action="edit-property.php?id=<?php echo $property['id']; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <div class="form-group">
                        <label for="title">Property Title *</label>
                        <input type="text" id="title" name="title" class="form-control" required value="<?php echo e($property['title']); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="property_type">Property Type *</label>
                            <select name="property_type" id="property_type" class="form-control" required>
                                <option value="Flat" <?php echo ($property['property_type'] === 'Flat') ? 'selected' : ''; ?>>Flat / Apartment</option>
                                <option value="PG" <?php echo ($property['property_type'] === 'PG') ? 'selected' : ''; ?>>PG (Paying Guest)</option>
                                <option value="Room" <?php echo ($property['property_type'] === 'Room') ? 'selected' : ''; ?>>Private Room / 1RK</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location">Pune Locality *</label>
                            <select name="location" id="location" class="form-control" required>
                                <option value="Hinjewadi" <?php echo ($property['location'] === 'Hinjewadi') ? 'selected' : ''; ?>>Hinjewadi</option>
                                <option value="Wakad" <?php echo ($property['location'] === 'Wakad') ? 'selected' : ''; ?>>Wakad</option>
                                <option value="Tathawade" <?php echo ($property['location'] === 'Tathawade') ? 'selected' : ''; ?>>Tathawade</option>
                                <option value="Baner" <?php echo ($property['location'] === 'Baner') ? 'selected' : ''; ?>>Baner</option>
                                <option value="Punawale" <?php echo ($property['location'] === 'Punawale') ? 'selected' : ''; ?>>Punawale</option>
                                <option value="Pimpri" <?php echo ($property['location'] === 'Pimpri') ? 'selected' : ''; ?>>Pimpri</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Full Address / Landmark *</label>
                        <input type="text" id="address" name="address" class="form-control" required value="<?php echo e($property['address']); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="rent">Monthly Rent (₹) *</label>
                            <input type="number" id="rent" name="rent" class="form-control" required value="<?php echo e($property['rent']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="deposit">Security Deposit (₹) *</label>
                            <input type="number" id="deposit" name="deposit" class="form-control" required value="<?php echo e($property['deposit']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="furnishing">Furnishing Status *</label>
                            <select name="furnishing" id="furnishing" class="form-control" required>
                                <option value="Semi-Furnished" <?php echo ($property['furnishing'] === 'Semi-Furnished') ? 'selected' : ''; ?>>Semi-Furnished</option>
                                <option value="Fully Furnished" <?php echo ($property['furnishing'] === 'Fully Furnished') ? 'selected' : ''; ?>>Fully Furnished</option>
                                <option value="Unfurnished" <?php echo ($property['furnishing'] === 'Unfurnished') ? 'selected' : ''; ?>>Unfurnished</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bachelor_allowed">Bachelors / Students Allowed? *</label>
                            <select name="bachelor_allowed" id="bachelor_allowed" class="form-control" required>
                                <option value="Yes" <?php echo ($property['bachelor_allowed'] === 'Yes') ? 'selected' : ''; ?>>Yes (All Bachelors Welcome)</option>
                                <option value="Boys Only" <?php echo ($property['bachelor_allowed'] === 'Boys Only') ? 'selected' : ''; ?>>Boys Only</option>
                                <option value="Girls Only" <?php echo ($property['bachelor_allowed'] === 'Girls Only') ? 'selected' : ''; ?>>Girls Only</option>
                                <option value="Family Only" <?php echo ($property['bachelor_allowed'] === 'Family Only') ? 'selected' : ''; ?>>Family Only</option>
                                <option value="No" <?php echo ($property['bachelor_allowed'] === 'No') ? 'selected' : ''; ?>>No</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="available_from">Available From Date *</label>
                            <input type="date" id="available_from" name="available_from" class="form-control" required value="<?php echo e($property['available_from']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="status">Listing Status *</label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="Available" <?php echo ($property['status'] === 'Available') ? 'selected' : ''; ?>>Available</option>
                                <option value="Rented" <?php echo ($property['status'] === 'Rented') ? 'selected' : ''; ?>>Rented (Occupied)</option>
                                <option value="Under Discussion" <?php echo ($property['status'] === 'Under Discussion') ? 'selected' : ''; ?>>Under Discussion</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="background: #FFF1F2; border: 1px solid #FECDD3; padding: 12px 16px; border-radius: var(--radius-md);">
                        <label style="display:flex; align-items:center; gap:8px; margin:0; cursor:pointer; font-weight:700; color:var(--urgent);">
                            <input type="checkbox" name="is_urgent" value="1" <?php echo ($property['is_urgent']) ? 'checked' : ''; ?>>
                            <span><i class="fa-solid fa-bolt"></i> Mark as Urgent Vacancy</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Facilities & Amenities</label>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-top: 6px;">
                            <?php 
                            $common_facilities = ['WiFi', 'AC', 'Geyser', 'Lift', 'Parking', 'Power Backup', 'Washing Machine', 'RO Water', 'Security Guard', 'Gym'];
                            foreach ($common_facilities as $fac):
                            ?>
                                <label style="display:flex; align-items:center; gap:6px; font-size:0.9rem; cursor:pointer;">
                                    <input type="checkbox" name="facilities[]" value="<?php echo $fac; ?>" <?php echo in_array($fac, $existing_facilities) ? 'checked' : ''; ?>>
                                    <span><?php echo $fac; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Detailed Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="5" required><?php echo e($property['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="contact_phone">Enquiry Contact Phone *</label>
                        <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?php echo e($property['contact_phone']); ?>" required>
                    </div>

                    <div style="display:flex; gap:14px; margin-top:30px;">
                        <button type="submit" class="btn btn-secondary btn-lg" style="flex:1;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Changes
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
