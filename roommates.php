<?php
/**
 * FlatConnect - Roommate Finder Page
 * 
 * Features:
 * - Search roommate requirements by location and budget
 * - Innovative Roommate Compatibility Score calculation
 * - Direct enquiry and contact options
 * - Form to post new roommate requirements
 */

define('PAGE_TITLE', 'Find Compatible Roommates in Pune - FlatConnect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

// Fetch current user details for compatibility calculation
$user_stmt = $pdo->prepare("SELECT preferred_location, max_budget, preferred_type, preferred_furnishing FROM users WHERE id = ?");
$user_stmt->execute([$user['id']]);
$user_profile = $user_stmt->fetch() ?: [];

$user_loc    = $user_profile['preferred_location'] ?? 'Hinjewadi';
$user_budget = (int) ($user_profile['max_budget'] ?? 10000);

// Filtering
$filter_loc    = trim($_GET['location'] ?? '');
$filter_budget = !empty($_GET['budget']) ? (int) $_GET['budget'] : null;
$filter_occ    = trim($_GET['occupation'] ?? '');

$query = "
    SELECT r.*, u.name AS poster_name, u.email AS poster_email, u.mobile AS poster_mobile 
    FROM roommate_posts r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'Active'
";
$params = [];

if (!empty($filter_loc)) {
    $query .= " AND r.preferred_location LIKE :loc";
    $params[':loc'] = '%' . $filter_loc . '%';
}

if ($filter_budget !== null) {
    $query .= " AND r.budget <= :budget";
    $params[':budget'] = $filter_budget;
}

if (!empty($filter_occ)) {
    $query .= " AND r.occupation = :occ";
    $params[':occ'] = $filter_occ;
}

$query .= " ORDER BY r.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$roommates = $stmt->fetchAll();

/**
 * Innovative Feature B: Roommate Compatibility Score Calculator
 * Evaluates rule-based match percentage between seeker and roommate post
 */
function calculate_roommate_compatibility(array $roommate, string $user_loc, int $user_budget): int {
    $score = 50; // Baseline compatibility

    // 1. Location match: +25%
    if (!empty($user_loc) && stripos($roommate['preferred_location'], $user_loc) !== false) {
        $score += 25;
    }

    // 2. Budget similarity: +15%
    if ($user_budget > 0) {
        $diff = abs($roommate['budget'] - $user_budget);
        if ($diff <= 1500) {
            $score += 15;
        } elseif ($diff <= 3000) {
            $score += 8;
        }
    }

    // 3. Sharing preference: +10%
    if (in_array($roommate['sharing_preference'], ['2 Sharing', 'Single Room'])) {
        $score += 10;
    }

    return min(100, max(55, $score));
}

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
                    <a href="home.php">Home</a> / <span>Roommates</span>
                </div>
                <h1 style="font-size: 2rem;"><i class="fa-solid fa-user-group" style="color: var(--secondary);"></i> Find Roommates in Pune</h1>
                <p class="text-muted">Connect directly with students and professionals looking for flatmates.</p>
            </div>
            <button type="button" class="btn btn-secondary" onclick="openModal('postRoommateModal');">
                <i class="fa-solid fa-plus"></i> Post Roommate Requirement
            </button>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo e($flash['message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-body" style="padding: 18px 24px;">
                <form action="roommates.php" method="GET" style="display: grid; grid-template-columns: 1.2fr 1fr 1fr auto; gap: 12px; align-items: center;">
                    <div>
                        <select name="location" class="form-control">
                            <option value="">All Locations in Pune</option>
                            <option value="Hinjewadi" <?php echo ($filter_loc === 'Hinjewadi') ? 'selected' : ''; ?>>Hinjewadi</option>
                            <option value="Wakad" <?php echo ($filter_loc === 'Wakad') ? 'selected' : ''; ?>>Wakad</option>
                            <option value="Tathawade" <?php echo ($filter_loc === 'Tathawade') ? 'selected' : ''; ?>>Tathawade</option>
                            <option value="Baner" <?php echo ($filter_loc === 'Baner') ? 'selected' : ''; ?>>Baner</option>
                            <option value="Punawale" <?php echo ($filter_loc === 'Punawale') ? 'selected' : ''; ?>>Punawale</option>
                        </select>
                    </div>

                    <div>
                        <select name="occupation" class="form-control">
                            <option value="">Any Occupation</option>
                            <option value="Student" <?php echo ($filter_occ === 'Student') ? 'selected' : ''; ?>>Student</option>
                            <option value="Working Professional" <?php echo ($filter_occ === 'Working Professional') ? 'selected' : ''; ?>>Working Professional</option>
                        </select>
                    </div>

                    <div>
                        <select name="budget" class="form-control">
                            <option value="">Max Budget</option>
                            <option value="6000" <?php echo ($filter_budget === 6000) ? 'selected' : ''; ?>>Up to ₹6,000</option>
                            <option value="8000" <?php echo ($filter_budget === 8000) ? 'selected' : ''; ?>>Up to ₹8,000</option>
                            <option value="10000" <?php echo ($filter_budget === 10000) ? 'selected' : ''; ?>>Up to ₹10,000</option>
                            <option value="15000" <?php echo ($filter_budget === 15000) ? 'selected' : ''; ?>>Up to ₹15,000</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>
                        <a href="roommates.php" class="btn btn-outline" title="Reset">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Roommates Grid -->
        <?php if (empty($roommates)): ?>
            <div class="card text-center" style="padding: 50px 20px;">
                <div style="font-size: 3rem; color: var(--text-light); margin-bottom: 16px;">
                    <i class="fa-solid fa-users-slash"></i>
                </div>
                <h3>No roommate requirements found</h3>
                <p class="text-muted" style="max-width: 440px; margin: 8px auto 20px;">
                    Be the first to post a roommate requirement in this area!
                </p>
                <button type="button" class="btn btn-secondary" onclick="openModal('postRoommateModal');">
                    <i class="fa-solid fa-plus"></i> Post Requirement Now
                </button>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 24px;">
                <?php foreach ($roommates as $r): 
                    $compat = calculate_roommate_compatibility($r, $user_loc, $user_budget);
                    $is_my_post = ($r['user_id'] == $user['id']);
                ?>
                    <div class="roommate-card">
                        <div class="roommate-header">
                            <div class="avatar-circle">
                                <?php echo strtoupper(substr($r['poster_name'], 0, 1)); ?>
                            </div>
                            <div class="roommate-meta" style="flex:1;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <h3 style="font-size:1.15rem; color:var(--primary);"><?php echo e($r['poster_name']); ?></h3>
                                    <span class="compat-score">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo $compat; ?>% Match
                                    </span>
                                </div>
                                <div style="font-size:0.85rem; color:var(--text-muted);">
                                    <i class="fa-solid fa-briefcase"></i> <?php echo e($r['occupation']); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; background: var(--bg-light); padding: 14px; border-radius: var(--radius-md);">
                            <div>
                                <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Location</span>
                                <div style="font-weight:700; color:var(--secondary-hover); font-size:0.95rem;">
                                    <i class="fa-solid fa-location-dot"></i> <?php echo e($r['preferred_location']); ?>
                                </div>
                            </div>
                            <div>
                                <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Monthly Budget</span>
                                <div style="font-weight:800; color:var(--primary); font-size:1rem;">
                                    <?php echo format_inr($r['budget']); ?>
                                </div>
                            </div>
                            <div>
                                <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Sharing Preference</span>
                                <div style="font-weight:600; font-size:0.9rem; color:var(--text-dark);">
                                    <?php echo e($r['sharing_preference']); ?>
                                </div>
                            </div>
                            <div>
                                <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Move-in Date</span>
                                <div style="font-weight:600; font-size:0.9rem; color:var(--text-dark);">
                                    <?php echo date('d M Y', strtotime($r['move_in_date'])); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Lifestyle Tags -->
                        <div style="margin-bottom: 14px;">
                            <span style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; display:block; margin-bottom:6px;">Habits & Lifestyle</span>
                            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                <?php 
                                $habits = array_map('trim', explode(',', $r['lifestyle_preferences']));
                                foreach ($habits as $h):
                                    if (empty($h)) continue;
                                ?>
                                    <span class="amenity-tag" style="background:#EEF2F6; color:var(--primary);">
                                        <i class="fa-solid fa-check" style="color:var(--secondary); font-size:0.7rem;"></i> <?php echo e($h); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Notes -->
                        <?php if (!empty($r['notes'])): ?>
                            <p style="font-size:0.875rem; color:var(--text-muted); margin-bottom:18px; line-height:1.5; font-style:italic;">
                                "<?php echo e($r['notes']); ?>"
                            </p>
                        <?php endif; ?>

                        <!-- Actions -->
                        <div style="margin-top:auto; padding-top:14px; border-top:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                            <div style="font-size:0.85rem; color:var(--text-muted);">
                                <i class="fa-solid fa-phone"></i> <?php echo e($r['contact_number']); ?>
                            </div>

                            <?php if (!$is_my_post): ?>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openRoommateEnquiry(<?php echo $r['id']; ?>, <?php echo $r['user_id']; ?>, '<?php echo addslashes($r['poster_name']); ?>');">
                                    <i class="fa-regular fa-paper-plane"></i> Contact
                                </button>
                            <?php else: ?>
                                <a href="delete-post.php?type=roommate&id=<?php echo $r['id']; ?>&token=<?php echo csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this roommate post?');">
                                    <i class="fa-solid fa-trash"></i> Delete Post
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal: Post Roommate Requirement -->
<div id="postRoommateModal" class="modal-backdrop">
    <div class="modal-content" style="max-width: 580px;">
        <div class="modal-header">
            <h3 style="font-size:1.2rem; color:var(--primary);"><i class="fa-solid fa-user-plus"></i> Post Roommate Requirement</h3>
            <button type="button" class="modal-close" onclick="closeModal('postRoommateModal');">&times;</button>
        </div>
        <form action="post-roommate.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="post_occ">Occupation *</label>
                        <select name="occupation" id="post_occ" class="form-control" required>
                            <option value="Working Professional">Working Professional</option>
                            <option value="Student">Student</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="post_loc">Preferred Locality *</label>
                        <select name="preferred_location" id="post_loc" class="form-control" required>
                            <option value="Hinjewadi">Hinjewadi</option>
                            <option value="Wakad">Wakad</option>
                            <option value="Tathawade">Tathawade</option>
                            <option value="Baner">Baner</option>
                            <option value="Punawale">Punawale</option>
                            <option value="Pimpri">Pimpri</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="post_budget">Monthly Budget (₹) *</label>
                        <input type="number" id="post_budget" name="budget" class="form-control" placeholder="e.g. 7500" required min="1000">
                    </div>
                    <div class="form-group">
                        <label for="post_share">Sharing Preference *</label>
                        <select name="sharing_preference" id="post_share" class="form-control" required>
                            <option value="Single Room">Single Room (Private)</option>
                            <option value="2 Sharing">2 Sharing (Double)</option>
                            <option value="3 Sharing">3 Sharing (Triple)</option>
                            <option value="Any">Any Sharing</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="post_date">Target Move-in Date *</label>
                        <input type="date" id="post_date" name="move_in_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="post_phone">Contact Number *</label>
                        <input type="text" id="post_phone" name="contact_number" class="form-control" value="<?php echo e($user['mobile']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="post_habits">Habits & Lifestyle (comma-separated)</label>
                    <input type="text" id="post_habits" name="lifestyle_preferences" class="form-control" placeholder="e.g. Non-smoker, Vegetarian, Early riser, Clean" required value="Non-smoker, Clean habits">
                </div>

                <div class="form-group">
                    <label for="post_notes">Additional Details / Bio</label>
                    <textarea id="post_notes" name="notes" class="form-control" rows="3" placeholder="Describe yourself, your work/college, and what kind of flatmate you are seeking..."></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('postRoommateModal');">Cancel</button>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-check"></i> Publish Requirement
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Send Roommate Enquiry -->
<div id="roommateEnquiryModal" class="modal-backdrop">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size:1.15rem; color:var(--primary);"><i class="fa-regular fa-paper-plane"></i> Contact Roommate</h3>
            <button type="button" class="modal-close" onclick="closeModal('roommateEnquiryModal');">&times;</button>
        </div>
        <form action="enquiry.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="send">
                <input type="hidden" name="roommate_post_id" id="modal_roommate_post_id" value="">
                <input type="hidden" name="receiver_id" id="modal_roommate_receiver_id" value="">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="redirect_url" value="roommates.php">

                <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:14px;">
                    Send a message to: <strong id="modal_roommate_name"></strong>
                </p>

                <div class="form-group">
                    <label for="roommate_enquiry_msg">Your Message</label>
                    <textarea id="roommate_enquiry_msg" name="message" class="form-control" rows="4" placeholder="Hi! I saw your roommate requirement. I am also looking for a flat in this area. Would you like to connect?" required></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('roommateEnquiryModal');">Cancel</button>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-paper-plane"></i> Send Enquiry
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRoommateEnquiry(postId, receiverId, name) {
    document.getElementById('modal_roommate_post_id').value = postId;
    document.getElementById('modal_roommate_receiver_id').value = receiverId;
    document.getElementById('modal_roommate_name').textContent = name;
    openModal('roommateEnquiryModal');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
