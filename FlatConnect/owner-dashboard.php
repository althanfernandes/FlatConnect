<?php
/**
 * FlatConnect - Property Owner Dashboard
 * 
 * Shows:
 * - Property statistics (Total, Active, Enquiries, Urgent)
 * - Managed listings with Edit and Delete capabilities
 * - Incoming seeker enquiries with direct reply feature
 */

define('PAGE_TITLE', 'Owner Dashboard - FlatConnect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_role('owner');
$user = current_user();

// 1. Fetch Owner's Properties
$prop_stmt = $pdo->prepare("
    SELECT p.*, COALESCE(pi.image_path, 'assets/images/properties/prop1.svg') AS primary_image 
    FROM properties p 
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE p.owner_id = ? 
    ORDER BY p.id DESC
");
$prop_stmt->execute([$user['id']]);
$properties = $prop_stmt->fetchAll();

// 2. Compute stats
$total_properties = count($properties);
$active_properties = count(array_filter($properties, fn($p) => $p['status'] === 'Available'));
$urgent_properties = count(array_filter($properties, fn($p) => $p['is_urgent'] == 1));

// 3. Fetch Enquiries received for this owner's properties
$enq_stmt = $pdo->prepare("
    SELECT e.*, p.title AS property_title, u.name AS sender_name, u.email AS sender_email, u.mobile AS sender_mobile 
    FROM enquiries e 
    JOIN properties p ON e.property_id = p.id 
    JOIN users u ON e.sender_id = u.id 
    WHERE e.receiver_id = ? 
    ORDER BY e.id DESC
");
$enq_stmt->execute([$user['id']]);
$enquiries = $enq_stmt->fetchAll();

$total_enquiries = count($enquiries);

$flash = get_flash();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Dashboard Welcome Bar -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:28px;">
            <div>
                <span class="role-badge owner"><i class="fa-solid fa-shield-halved"></i> Property Owner</span>
                <h1 style="font-size: 2rem; margin-top: 4px;">Welcome back, <?php echo e($user['name']); ?></h1>
                <p class="text-muted">Manage your rental properties, track tenant enquiries, and update vacancy status.</p>
            </div>
            <div>
                <a href="post-property.php" class="btn btn-secondary btn-lg">
                    <i class="fa-solid fa-circle-plus"></i> Post New Property
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo e($flash['message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div>
                    <div class="stat-val"><?php echo $total_properties; ?></div>
                    <div class="stat-label">Total Properties Listed</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:#D1FAE5; color:#059669;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <div class="stat-val"><?php echo $active_properties; ?></div>
                    <div class="stat-label">Active Listings</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:#E0E7FF; color:#4338CA;">
                    <i class="fa-regular fa-envelope-open"></i>
                </div>
                <div>
                    <div class="stat-val"><?php echo $total_enquiries; ?></div>
                    <div class="stat-label">Enquiries Received</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:var(--urgent-bg); color:var(--urgent);">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div>
                    <div class="stat-val"><?php echo $urgent_properties; ?></div>
                    <div class="stat-label">Urgent Vacancies</div>
                </div>
            </div>
        </div>

        <!-- Section: My Properties -->
        <div class="card" style="margin-bottom: 40px;">
            <div class="card-header">
                <div>
                    <h2 style="font-size: 1.25rem;"><i class="fa-solid fa-list"></i> My Listed Properties</h2>
                    <span class="text-muted" style="font-size: 0.85rem;">All flats, PGs and rooms posted under your account</span>
                </div>
                <a href="post-property.php" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-plus"></i> Add Another
                </a>
            </div>

            <div class="card-body" style="padding: 0;">
                <?php if (empty($properties)): ?>
                    <div style="padding: 40px 20px; text-align: center;">
                        <i class="fa-solid fa-house-chimney" style="font-size: 2.5rem; color: var(--text-light); margin-bottom: 12px;"></i>
                        <h3>You haven't listed any properties yet</h3>
                        <p class="text-muted" style="margin-bottom: 16px;">Publish your first listing to start receiving direct tenant enquiries.</p>
                        <a href="post-property.php" class="btn btn-secondary btn-sm">Post Property Now</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Type</th>
                                    <th>Rent / Month</th>
                                    <th>Availability</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($properties as $p): ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:12px;">
                                                <img src="<?php echo e($p['primary_image']); ?>" alt="thumbnail" style="width:54px; height:42px; object-fit:cover; border-radius:6px;">
                                                <div>
                                                    <div style="font-weight:700; color:var(--primary);"><?php echo e($p['title']); ?></div>
                                                    <div style="font-size:0.8rem; color:var(--text-muted);"><i class="fa-solid fa-location-dot"></i> <?php echo e($p['location']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="amenity-tag"><?php echo e($p['property_type']); ?></span></td>
                                        <td><strong><?php echo format_inr($p['rent']); ?></strong></td>
                                        <td><?php echo date('d M Y', strtotime($p['available_from'])); ?></td>
                                        <td>
                                            <span style="display:inline-block; padding:3px 8px; border-radius:4px; font-size:0.75rem; font-weight:700; background: <?php echo ($p['status'] === 'Available') ? '#DCFCE7' : '#F1F5F9'; ?>; color: <?php echo ($p['status'] === 'Available') ? '#15803D' : '#475569'; ?>;">
                                                <?php echo e($p['status']); ?>
                                            </span>
                                            <?php if ($p['is_urgent']): ?>
                                                <span style="font-size:0.7rem; color:var(--urgent); font-weight:700; margin-left:4px;"><i class="fa-solid fa-bolt"></i> Urgent</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:6px;">
                                                <a href="property-details.php?id=<?php echo $p['id']; ?>" class="btn btn-outline btn-sm" title="View Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <a href="edit-property.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm" title="Edit Property">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="delete-property.php?id=<?php echo $p['id']; ?>&token=<?php echo csrf_token(); ?>" class="btn btn-danger btn-sm" title="Delete Property" onclick="return confirm('Are you sure you want to permanently delete this listing?');">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section: Received Enquiries -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 style="font-size: 1.25rem;"><i class="fa-regular fa-paper-plane"></i> Direct Enquiries Received</h2>
                    <span class="text-muted" style="font-size: 0.85rem;">Messages and contact requests from interested seekers</span>
                </div>
            </div>

            <div class="card-body" style="padding: 0;">
                <?php if (empty($enquiries)): ?>
                    <div style="padding: 40px 20px; text-align: center;">
                        <i class="fa-regular fa-comments" style="font-size: 2.5rem; color: var(--text-light); margin-bottom: 12px;"></i>
                        <h3>No enquiries received yet</h3>
                        <p class="text-muted">When seekers browse your properties and send messages, they will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Seeker Details</th>
                                    <th>Target Property</th>
                                    <th>Enquiry Message</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enquiries as $enq): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:700; color:var(--primary);"><?php echo e($enq['sender_name']); ?></div>
                                            <div style="font-size:0.8rem; color:var(--text-muted);"><i class="fa-solid fa-phone"></i> <?php echo e($enq['sender_mobile']); ?></div>
                                            <div style="font-size:0.8rem; color:var(--text-muted);"><i class="fa-regular fa-envelope"></i> <?php echo e($enq['sender_email']); ?></div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600; font-size:0.9rem;"><?php echo e($enq['property_title']); ?></div>
                                            <small class="text-muted"><?php echo date('d M Y, h:i A', strtotime($enq['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div style="font-size:0.9rem; line-height:1.4; color:var(--text-dark); max-width:320px;">
                                                "<?php echo e($enq['message']); ?>"
                                            </div>
                                            <?php if (!empty($enq['response'])): ?>
                                                <div style="margin-top:6px; padding:6px 10px; background:#F0FDF4; border-left:3px solid var(--success); font-size:0.8rem; color:#166534;">
                                                    <strong>Your Reply:</strong> <?php echo e($enq['response']); ?>
                                                </div>
                                            <?php endif; ?>
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
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="openReplyModal(<?php echo $enq['id']; ?>, '<?php echo addslashes($enq['sender_name']); ?>', '<?php echo addslashes($enq['message']); ?>');">
                                                <i class="fa-solid fa-reply"></i> <?php echo ($enq['status'] === 'Replied') ? 'Update Reply' : 'Reply'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Reply Modal -->
<div id="replyModal" class="modal-backdrop">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size:1.15rem; color:var(--primary);"><i class="fa-solid fa-reply"></i> Reply to Seeker Enquiry</h3>
            <button type="button" class="modal-close" onclick="closeModal('replyModal');">&times;</button>
        </div>
        <form action="enquiry.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="enquiry_id" id="modal_enquiry_id" value="">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="redirect_url" value="owner-dashboard.php">

                <div style="background:#F8FAFC; padding:12px; border-radius:var(--radius-sm); margin-bottom:14px; font-size:0.875rem;">
                    <div>Enquiry from: <strong id="modal_seeker_name"></strong></div>
                    <div style="font-style:italic; color:var(--text-muted); margin-top:4px;" id="modal_enquiry_text"></div>
                </div>

                <div class="form-group">
                    <label for="response">Your Response Message</label>
                    <textarea id="response" name="response" class="form-control" rows="4" placeholder="Hello! Yes, the flat is available. You can visit tomorrow at 5 PM..." required></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('replyModal');">Cancel</button>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-paper-plane"></i> Send Reply
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openReplyModal(enquiryId, seekerName, msg) {
    document.getElementById('modal_enquiry_id').value = enquiryId;
    document.getElementById('modal_seeker_name').textContent = seekerName;
    document.getElementById('modal_enquiry_text').textContent = '"' + msg + '"';
    openModal('replyModal');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
