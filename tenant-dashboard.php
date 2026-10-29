<?php
/**
 * FlatConnect - Existing Tenant Dashboard
 * 
 * Shows:
 * - Tenant stats (Roommate posts, Replacement vacancies, Received enquiries)
 * - Managed roommate postings with delete/view
 * - Managed tenant replacement posts with delete/view
 * - Received enquiries with direct reply support
 */

define('PAGE_TITLE', 'Tenant Dashboard - FlatConnect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_role('tenant');
$user = current_user();

// 1. Fetch Tenant's Roommate Posts
$room_stmt = $pdo->prepare("SELECT * FROM roommate_posts WHERE user_id = ? ORDER BY id DESC");
$room_stmt->execute([$user['id']]);
$roommate_posts = $room_stmt->fetchAll();

// 2. Fetch Tenant's Replacement Posts
$rep_stmt = $pdo->prepare("SELECT * FROM replacement_posts WHERE tenant_id = ? ORDER BY id DESC");
$rep_stmt->execute([$user['id']]);
$replacement_posts = $rep_stmt->fetchAll();

// 3. Fetch Enquiries received for this tenant (either via replacement or roommate posts)
$enq_stmt = $pdo->prepare("
    SELECT e.*, u.name AS sender_name, u.email AS sender_email, u.mobile AS sender_mobile,
           r.property_name AS replacement_title,
           rm.preferred_location AS roommate_location
    FROM enquiries e 
    JOIN users u ON e.sender_id = u.id 
    LEFT JOIN replacement_posts r ON e.replacement_id = r.id 
    LEFT JOIN roommate_posts rm ON e.roommate_post_id = rm.id 
    WHERE e.receiver_id = ? 
    ORDER BY e.id DESC
");
$enq_stmt->execute([$user['id']]);
$enquiries = $enq_stmt->fetchAll();

$flash = get_flash();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Welcome Bar -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:28px;">
            <div>
                <span class="role-badge tenant"><i class="fa-solid fa-arrows-rotate"></i> Existing Tenant</span>
                <h1 style="font-size: 2rem; margin-top: 4px;">Welcome back, <?php echo e($user['name']); ?></h1>
                <p class="text-muted">Post replacement vacancies, find flatmates, and respond to incoming requests.</p>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="replacement.php" class="btn btn-secondary">
                    <i class="fa-solid fa-plus"></i> Post Replacement
                </a>
                <a href="roommates.php" class="btn btn-primary">
                    <i class="fa-solid fa-user-plus"></i> Find Roommate
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo e($flash['message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#FCE7F3; color:#DB2777;">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </div>
                <div>
                    <div class="stat-val"><?php echo count($replacement_posts); ?></div>
                    <div class="stat-label">Replacement Posts</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:#E0E7FF; color:#4338CA;">
                    <i class="fa-solid fa-user-group"></i>
                </div>
                <div>
                    <div class="stat-val"><?php echo count($roommate_posts); ?></div>
                    <div class="stat-label">Roommate Requests</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-regular fa-envelope-open"></i>
                </div>
                <div>
                    <div class="stat-val"><?php echo count($enquiries); ?></div>
                    <div class="stat-label">Received Enquiries</div>
                </div>
            </div>
        </div>

        <!-- Section 1: My Tenant Replacement Posts -->
        <div class="card" style="margin-bottom: 36px;">
            <div class="card-header">
                <div>
                    <h2 style="font-size: 1.25rem;"><i class="fa-solid fa-arrows-rotate"></i> My Tenant Replacement Listings</h2>
                    <span class="text-muted" style="font-size: 0.85rem;">Vacancies you posted to transfer your tenancy</span>
                </div>
                <a href="replacement.php" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-plus"></i> Post New Replacement
                </a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($replacement_posts)): ?>
                    <div style="padding: 35px 20px; text-align: center;">
                        <i class="fa-solid fa-house-user" style="font-size: 2.2rem; color: var(--text-light); margin-bottom: 10px;"></i>
                        <h4 style="margin-bottom: 6px;">No replacement posts created yet</h4>
                        <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 14px;">Moving out early? Post your room vacancy to recover your deposit smoothly.</p>
                        <a href="replacement.php" class="btn btn-secondary btn-sm">Create Replacement Post</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Property & Locality</th>
                                    <th>Sharing Details</th>
                                    <th>Rent Share</th>
                                    <th>Available Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($replacement_posts as $rep): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:700; color:var(--primary);"><?php echo e($rep['property_name']); ?></div>
                                            <div style="font-size:0.8rem; color:var(--text-muted);"><i class="fa-solid fa-location-dot"></i> <?php echo e($rep['location']); ?></div>
                                        </td>
                                        <td><span class="amenity-tag"><?php echo e($rep['sharing_details']); ?></span></td>
                                        <td><strong><?php echo format_inr($rep['rent']); ?></strong></td>
                                        <td><?php echo date('d M Y', strtotime($rep['available_from'])); ?></td>
                                        <td>
                                            <span style="background:#DCFCE7; color:#15803D; padding:3px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">
                                                <?php echo e($rep['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="delete-post.php?type=replacement&id=<?php echo $rep['id']; ?>&token=<?php echo csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this replacement listing?');">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section 2: My Roommate Posts -->
        <div class="card" style="margin-bottom: 36px;">
            <div class="card-header">
                <div>
                    <h2 style="font-size: 1.25rem;"><i class="fa-solid fa-user-group"></i> My Roommate Postings</h2>
                    <span class="text-muted" style="font-size: 0.85rem;">Active roommate searches you published</span>
                </div>
                <a href="roommates.php" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Post Requirement
                </a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($roommate_posts)): ?>
                    <div style="padding: 35px 20px; text-align: center;">
                        <i class="fa-solid fa-users" style="font-size: 2.2rem; color: var(--text-light); margin-bottom: 10px;"></i>
                        <h4 style="margin-bottom: 6px;">No roommate postings active</h4>
                        <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 14px;">Need a flatmate to split rent? Post your preferences to match with compatible seekers.</p>
                        <a href="roommates.php" class="btn btn-primary btn-sm">Post Roommate Search</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Preferred Location</th>
                                    <th>Budget</th>
                                    <th>Sharing</th>
                                    <th>Move-in Date</th>
                                    <th>Habits</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roommate_posts as $rm): ?>
                                    <tr>
                                        <td><strong><i class="fa-solid fa-location-dot" style="color:var(--secondary);"></i> <?php echo e($rm['preferred_location']); ?></strong></td>
                                        <td><strong><?php echo format_inr($rm['budget']); ?></strong></td>
                                        <td><span class="amenity-tag"><?php echo e($rm['sharing_preference']); ?></span></td>
                                        <td><?php echo date('d M Y', strtotime($rm['move_in_date'])); ?></td>
                                        <td style="font-size:0.85rem; max-width:200px;"><?php echo e($rm['lifestyle_preferences']); ?></td>
                                        <td>
                                            <a href="delete-post.php?type=roommate&id=<?php echo $rm['id']; ?>&token=<?php echo csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this roommate post?');">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section 3: Received Enquiries -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 style="font-size: 1.25rem;"><i class="fa-regular fa-paper-plane"></i> Enquiries Received</h2>
                    <span class="text-muted" style="font-size: 0.85rem;">Seekers interested in your replacement flats or roommate requirements</span>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($enquiries)): ?>
                    <div style="padding: 35px 20px; text-align: center;">
                        <i class="fa-regular fa-comments" style="font-size: 2.2rem; color: var(--text-light); margin-bottom: 10px;"></i>
                        <h4>No enquiries received yet</h4>
                        <p class="text-muted" style="font-size: 0.9rem;">Incoming queries from seekers will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Sender Details</th>
                                    <th>Post Topic</th>
                                    <th>Message</th>
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
                                        </td>
                                        <td>
                                            <div style="font-weight:600; font-size:0.85rem;">
                                                <?php echo e($enq['replacement_title'] ?: ('Roommate (' . $enq['roommate_location'] . ')')); ?>
                                            </div>
                                            <small class="text-muted"><?php echo date('d M Y', strtotime($enq['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div style="font-size:0.9rem; line-height:1.4; color:var(--text-dark); max-width:300px;">
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
                                                <span style="background:#DCFCE7; color:#15803D; padding:3px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">Replied</span>
                                            <?php else: ?>
                                                <span style="background:#FEF3C7; color:#B45309; padding:3px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">Pending</span>
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
            <h3 style="font-size:1.15rem; color:var(--primary);"><i class="fa-solid fa-reply"></i> Reply to Seeker</h3>
            <button type="button" class="modal-close" onclick="closeModal('replyModal');">&times;</button>
        </div>
        <form action="enquiry.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="enquiry_id" id="modal_enquiry_id" value="">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="redirect_url" value="tenant-dashboard.php">

                <div style="background:#F8FAFC; padding:12px; border-radius:var(--radius-sm); margin-bottom:14px; font-size:0.875rem;">
                    <div>Enquiry from: <strong id="modal_seeker_name"></strong></div>
                    <div style="font-style:italic; color:var(--text-muted); margin-top:4px;" id="modal_enquiry_text"></div>
                </div>

                <div class="form-group">
                    <label for="response">Your Response Message</label>
                    <textarea id="response" name="response" class="form-control" rows="4" placeholder="Hi! Yes, the vacancy is still open. Let's talk on phone..." required></textarea>
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
