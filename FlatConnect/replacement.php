<?php
/**
 * FlatConnect - Tenant Replacement Feature Page
 * 
 * Allows existing tenants leaving rented flats to find replacement occupants,
 * saving both landlords and incoming seekers time and brokerage fees.
 */

define('PAGE_TITLE', 'Tenant Replacement Listings in Pune - FlatConnect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

$filter_loc = trim($_GET['location'] ?? '');
$filter_budget = !empty($_GET['budget']) ? (int) $_GET['budget'] : null;

$query = "
    SELECT r.*, u.name AS tenant_name, u.email AS tenant_email, u.mobile AS tenant_mobile 
    FROM replacement_posts r 
    JOIN users u ON r.tenant_id = u.id 
    WHERE r.status = 'Active'
";
$params = [];

if (!empty($filter_loc)) {
    $query .= " AND r.location LIKE :loc";
    $params[':loc'] = '%' . $filter_loc . '%';
}

if ($filter_budget !== null) {
    $query .= " AND r.rent <= :budget";
    $params[':budget'] = $filter_budget;
}

$query .= " ORDER BY r.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$replacements = $stmt->fetchAll();

$flash = get_flash();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
            <div>
                <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 6px;">
                    <a href="home.php">Home</a> / <span>Tenant Replacement</span>
                </div>
                <h1 style="font-size: 2rem;"><i class="fa-solid fa-arrows-rotate" style="color: var(--secondary);"></i> Tenant Replacement Listings</h1>
                <p class="text-muted">Take over active rental tenancies directly from departing tenants. Instant occupancy, zero broker fees.</p>
            </div>
            
            <?php if ($user['role'] === 'tenant' || $user['role'] === 'seeker'): ?>
                <button type="button" class="btn btn-secondary" onclick="openModal('postReplacementModal');">
                    <i class="fa-solid fa-plus"></i> Post Replacement Listing
                </button>
            <?php endif; ?>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo e($flash['message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Informative Feature Explanation Card -->
        <div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, #142D4E 0%, #1A365D 100%); color: #FFFFFF; border: none;">
            <div class="card-body" style="padding: 24px 28px;">
                <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                    <div style="width: 52px; height: 52px; background: rgba(21, 152, 149, 0.25); border: 1px solid rgba(21, 152, 149, 0.5); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; color: #5EEAD4;">
                        <i class="fa-solid fa-lightbulb"></i>
                    </div>
                    <div style="flex: 1; min-width: 240px;">
                        <h3 style="color: #FFFFFF; font-size: 1.15rem; margin-bottom: 4px;">What is Tenant Replacement?</h3>
                        <p style="color: #CBD5E1; font-size: 0.9rem; margin: 0; line-height: 1.5;">
                            When an existing tenant needs to vacate before their lock-in period ends, they post their vacancy here. You get a ready-to-move room with existing setup and furniture, and the tenant recovers their security deposit!
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-body" style="padding: 16px 20px;">
                <form action="replacement.php" method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <select name="location" class="form-control">
                            <option value="">All Pune Localities</option>
                            <option value="Hinjewadi" <?php echo ($filter_loc === 'Hinjewadi') ? 'selected' : ''; ?>>Hinjewadi</option>
                            <option value="Wakad" <?php echo ($filter_loc === 'Wakad') ? 'selected' : ''; ?>>Wakad</option>
                            <option value="Tathawade" <?php echo ($filter_loc === 'Tathawade') ? 'selected' : ''; ?>>Tathawade</option>
                            <option value="Baner" <?php echo ($filter_loc === 'Baner') ? 'selected' : ''; ?>>Baner</option>
                            <option value="Punawale" <?php echo ($filter_loc === 'Punawale') ? 'selected' : ''; ?>>Punawale</option>
                        </select>
                    </div>

                    <div style="flex: 1; min-width: 200px;">
                        <select name="budget" class="form-control">
                            <option value="">Max Monthly Rent</option>
                            <option value="6000" <?php echo ($filter_budget === 6000) ? 'selected' : ''; ?>>Up to ₹6,000</option>
                            <option value="8000" <?php echo ($filter_budget === 8000) ? 'selected' : ''; ?>>Up to ₹8,000</option>
                            <option value="10000" <?php echo ($filter_budget === 10000) ? 'selected' : ''; ?>>Up to ₹10,000</option>
                            <option value="15000" <?php echo ($filter_budget === 15000) ? 'selected' : ''; ?>>Up to ₹15,000</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-secondary">
                        <i class="fa-solid fa-filter"></i> Filter Vacancies
                    </button>
                    <a href="replacement.php" class="btn btn-outline" title="Reset">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </form>
            </div>
        </div>

        <!-- Replacement Grid -->
        <?php if (empty($replacements)): ?>
            <div class="card text-center" style="padding: 50px 20px;">
                <div style="font-size: 3rem; color: var(--text-light); margin-bottom: 16px;">
                    <i class="fa-solid fa-house-user"></i>
                </div>
                <h3>No active tenant replacements found</h3>
                <p class="text-muted" style="max-width: 440px; margin: 8px auto 20px;">
                    There are no current vacancy posts matching your filters.
                </p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 24px;">
                <?php foreach ($replacements as $rep): 
                    $is_my_post = ($rep['tenant_id'] == $user['id']);
                ?>
                    <div class="card" style="display:flex; flex-direction:column;">
                        <div class="card-header" style="background:#F8FAFC; border-bottom:1px solid var(--border-color);">
                            <div>
                                <span style="font-size:0.8rem; color:var(--secondary-hover); font-weight:700;">
                                    <i class="fa-solid fa-location-dot"></i> <?php echo e($rep['location']); ?>, Pune
                                </span>
                                <h3 style="font-size:1.15rem; color:var(--primary); margin-top:2px;"><?php echo e($rep['property_name']); ?></h3>
                            </div>
                            <span style="background:var(--secondary-light); color:var(--secondary-hover); padding:4px 10px; border-radius:var(--radius-full); font-size:0.75rem; font-weight:700; border:1px solid var(--secondary-border);">
                                <?php echo e($rep['sharing_details']); ?>
                            </span>
                        </div>

                        <div class="card-body" style="flex:1; display:flex; flex-direction:column;">
                            <!-- Rent and Deposit -->
                            <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-light); padding:12px 16px; border-radius:var(--radius-md); margin-bottom:16px;">
                                <div>
                                    <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Rent Share</span>
                                    <div style="font-size:1.25rem; font-weight:800; color:var(--primary);"><?php echo format_inr($rep['rent']); ?> <span style="font-size:0.8rem; font-weight:500; color:var(--text-muted);">/mo</span></div>
                                </div>
                                <div style="text-align:right;">
                                    <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Deposit</span>
                                    <div style="font-size:1.05rem; font-weight:700; color:var(--text-dark);"><?php echo format_inr($rep['deposit']); ?></div>
                                </div>
                            </div>

                            <!-- Move-in date -->
                            <div style="font-size:0.875rem; color:var(--text-dark); margin-bottom:12px;">
                                <i class="fa-regular fa-calendar-check" style="color:var(--secondary);"></i> <strong>Available From:</strong> <?php echo date('d M Y', strtotime($rep['available_from'])); ?>
                            </div>

                            <!-- Facilities -->
                            <div style="margin-bottom:14px;">
                                <span style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; display:block; margin-bottom:6px;">Setup & Amenities</span>
                                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                    <?php 
                                    $fac_arr = array_map('trim', explode(',', $rep['facilities']));
                                    foreach ($fac_arr as $fac): 
                                        if (empty($fac)) continue;
                                    ?>
                                        <span class="amenity-tag" style="background:#F1F5F9; color:var(--text-dark);">
                                            <i class="fa-solid fa-check" style="color:var(--secondary); font-size:0.7rem;"></i> <?php echo e($fac); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Description -->
                            <p style="font-size:0.9rem; color:var(--text-muted); line-height:1.5; margin-bottom:16px; flex:1;">
                                <?php echo e($rep['description']); ?>
                            </p>

                            <!-- Contact info -->
                            <div style="background:#F8FAFC; border:1px dashed var(--border-color); border-radius:var(--radius-sm); padding:10px; font-size:0.85rem; color:var(--text-dark); margin-bottom:16px;">
                                <i class="fa-solid fa-address-book" style="color:var(--secondary);"></i> <strong>Contact Details:</strong> <?php echo e($rep['contact_preference']); ?>
                            </div>

                            <!-- Actions -->
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:auto; padding-top:14px; border-top:1px solid var(--border-color);">
                                <span style="font-size:0.85rem; color:var(--text-muted);">
                                    By tenant: <strong><?php echo e($rep['tenant_name']); ?></strong>
                                </span>

                                <?php if (!$is_my_post): ?>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="openReplacementEnquiry(<?php echo $rep['id']; ?>, <?php echo $rep['tenant_id']; ?>, '<?php echo addslashes($rep['property_name']); ?>');">
                                        <i class="fa-regular fa-paper-plane"></i> Send Enquiry
                                    </button>
                                <?php else: ?>
                                    <a href="delete-post.php?type=replacement&id=<?php echo $rep['id']; ?>&token=<?php echo csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this replacement listing?');">
                                        <i class="fa-solid fa-trash"></i> Delete Post
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal: Post Tenant Replacement -->
<div id="postReplacementModal" class="modal-backdrop">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 style="font-size:1.2rem; color:var(--primary);"><i class="fa-solid fa-arrows-rotate"></i> Post Tenant Replacement Listing</h3>
            <button type="button" class="modal-close" onclick="closeModal('postReplacementModal');">&times;</button>
        </div>
        <form action="post-replacement.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-group">
                    <label for="rep_prop_name">Apartment / Society Name *</label>
                    <input type="text" id="rep_prop_name" name="property_name" class="form-control" placeholder="e.g. Blue Ridge Tower 14, Hinjewadi" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rep_loc">Pune Locality *</label>
                        <select name="location" id="rep_loc" class="form-control" required>
                            <option value="Hinjewadi">Hinjewadi</option>
                            <option value="Wakad">Wakad</option>
                            <option value="Tathawade">Tathawade</option>
                            <option value="Baner">Baner</option>
                            <option value="Punawale">Punawale</option>
                            <option value="Pimpri">Pimpri</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="rep_sharing">Sharing Details *</label>
                        <input type="text" id="rep_sharing" name="sharing_details" class="form-control" placeholder="e.g. 1 Bed in 2BHK Shared Flat" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rep_rent">Monthly Rent (₹) *</label>
                        <input type="number" id="rep_rent" name="rent" class="form-control" placeholder="e.g. 8000" min="1000" required>
                    </div>
                    <div class="form-group">
                        <label for="rep_deposit">Security Deposit (₹) *</label>
                        <input type="number" id="rep_deposit" name="deposit" class="form-control" placeholder="e.g. 18000" min="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rep_date">Available From Date *</label>
                        <input type="date" id="rep_date" name="available_from" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="rep_contact">Contact Preference *</label>
                        <input type="text" id="rep_contact" name="contact_preference" class="form-control" placeholder="e.g. Call / WhatsApp on 9890123456" required value="Call / WhatsApp on <?php echo e($user['mobile']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="rep_fac">Facilities Included (comma-separated) *</label>
                    <input type="text" id="rep_fac" name="facilities" class="form-control" placeholder="e.g. WiFi, AC, Washing Machine, Geyser, Maid for cooking" required value="WiFi, Washing Machine, Geyser, Fridge">
                </div>

                <div class="form-group">
                    <label for="rep_desc">Description / Reason for leaving *</label>
                    <textarea id="rep_desc" name="description" class="form-control" rows="3" placeholder="Explain the flat setup, current roommates, and reasons for replacement..." required></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('postReplacementModal');">Cancel</button>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-check"></i> Publish Replacement
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Send Replacement Enquiry -->
<div id="replacementEnquiryModal" class="modal-backdrop">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size:1.15rem; color:var(--primary);"><i class="fa-regular fa-paper-plane"></i> Send Enquiry to Tenant</h3>
            <button type="button" class="modal-close" onclick="closeModal('replacementEnquiryModal');">&times;</button>
        </div>
        <form action="enquiry.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="send">
                <input type="hidden" name="replacement_id" id="modal_replacement_id" value="">
                <input type="hidden" name="receiver_id" id="modal_replacement_receiver_id" value="">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="redirect_url" value="replacement.php">

                <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:14px;">
                    Enquiring about: <strong id="modal_replacement_name"></strong>
                </p>

                <div class="form-group">
                    <label for="rep_enquiry_msg">Your Message</label>
                    <textarea id="rep_enquiry_msg" name="message" class="form-control" rows="4" placeholder="Hi! I am interested in replacing your spot in this flat. Can I visit today to check the room?" required></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('replacementEnquiryModal');">Cancel</button>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-paper-plane"></i> Send Enquiry
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openReplacementEnquiry(repId, receiverId, propertyName) {
    document.getElementById('modal_replacement_id').value = repId;
    document.getElementById('modal_replacement_receiver_id').value = receiverId;
    document.getElementById('modal_replacement_name').textContent = propertyName;
    openModal('replacementEnquiryModal');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
