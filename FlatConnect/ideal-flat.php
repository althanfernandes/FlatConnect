<?php
/**
 * FlatConnect - Find My Ideal Flat (Interactive Recommendation Wizard)
 * 
 * Interactive, step-by-step accommodation recommendation wizard for students and seekers.
 * Evaluates real-time listings in Pune based on Budget, Locality, Type, Amenities, Furnishing, and Sharing.
 */

define('PAGE_TITLE', 'Find My Ideal Flat - Student Recommendation Wizard | FlatConnect');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$logged_in = is_logged_in();
$user = current_user();

// Fetch saved property IDs for current user to show wishlist state
$saved_ids = [];
if ($logged_in) {
    $saved_stmt = $pdo->prepare("SELECT property_id FROM saved_properties WHERE user_id = ?");
    $saved_stmt->execute([$user['id']]);
    $saved_ids = $saved_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Pre-fill defaults from seeker profile if available
$default_budget = 10000;
$default_locality = 'Any';
$default_type = 'Any';
$default_furnishing = 'Any';
$default_sharing = 'any';

if ($logged_in) {
    $prof_stmt = $pdo->prepare("SELECT preferred_location, max_budget, preferred_type, preferred_furnishing FROM users WHERE id = ?");
    $prof_stmt->execute([$user['id']]);
    $prof = $prof_stmt->fetch();
    if ($prof) {
        if (!empty($prof['max_budget'])) $default_budget = (int) $prof['max_budget'];
        if (!empty($prof['preferred_location'])) $default_locality = $prof['preferred_location'];
        if (!empty($prof['preferred_type'])) $default_type = $prof['preferred_type'];
        if (!empty($prof['preferred_furnishing'])) $default_furnishing = $prof['preferred_furnishing'];
    }
}

// Check if form was submitted
$has_searched = isset($_GET['submitted']) || isset($_POST['submitted']);

$input_budget     = isset($_REQUEST['budget']) ? (int) $_REQUEST['budget'] : $default_budget;
$input_type       = isset($_REQUEST['type']) ? trim($_REQUEST['type']) : $default_type;
$input_locality   = isset($_REQUEST['locality']) ? trim($_REQUEST['locality']) : $default_locality;
$input_furnishing = isset($_REQUEST['furnishing']) ? trim($_REQUEST['furnishing']) : $default_furnishing;
$input_sharing    = isset($_REQUEST['sharing']) ? trim($_REQUEST['sharing']) : $default_sharing;
$input_amenities  = isset($_REQUEST['amenities']) && is_array($_REQUEST['amenities']) ? $_REQUEST['amenities'] : [];

// If string passed via GET e.g. amenities=WiFi,Geyser
if (isset($_GET['amenities']) && is_string($_GET['amenities'])) {
    $input_amenities = array_filter(array_map('trim', explode(',', $_GET['amenities'])));
}

/**
 * Accommodation Matching Algorithm
 * Evaluates a property listing against student preferences and returns:
 * - score (0 to 100%)
 * - reasons (array of human-readable matching explanations)
 */
function calculate_property_match(array $prop, int $budget, string $type, string $locality, string $furnishing, string $sharing, array $amenities): array {
    $score = 0;
    $reasons = [];

    $rent = (int) $prop['rent'];
    $prop_loc = trim($prop['location']);
    $prop_type = trim($prop['property_type']);
    $prop_furnish = trim($prop['furnishing']);
    $facilities_str = strtolower($prop['facilities'] . ' ' . $prop['description']);
    $bachelor = trim($prop['bachelor_allowed'] ?? 'Yes');

    // 1. Monthly Rent Budget (Max 30 Points)
    if ($budget > 0) {
        if ($rent <= $budget) {
            $score += 30;
            $savings = $budget - $rent;
            if ($savings > 0) {
                $reasons[] = "Within budget: ₹" . number_format($rent) . "/mo (saves ₹" . number_format($savings) . "/mo on your ₹" . number_format($budget) . " budget)";
            } else {
                $reasons[] = "Exact match for your ₹" . number_format($budget) . "/month target budget";
            }
        } elseif ($rent <= ($budget * 1.15)) {
            $score += 18;
            $diff = $rent - $budget;
            $reasons[] = "Close to budget: ₹" . number_format($rent) . "/mo (+₹" . number_format($diff) . "/mo, within 15% range)";
        } elseif ($rent <= ($budget * 1.30)) {
            $score += 8;
            $diff = $rent - $budget;
            $reasons[] = "Slight budget stretch: ₹" . number_format($rent) . "/mo (+₹" . number_format($diff) . "/mo)";
        }
    }

    // 2. Locality Preference (Max 25 Points)
    $nearby_clusters = [
        'Hinjewadi' => ['Wakad', 'Tathawade', 'Punawale'],
        'Wakad'     => ['Hinjewadi', 'Tathawade', 'Punawale', 'Baner'],
        'Tathawade' => ['Wakad', 'Punawale', 'Hinjewadi'],
        'Punawale'  => ['Tathawade', 'Wakad', 'Hinjewadi'],
        'Baner'     => ['Wakad', 'Pimpri'],
        'Pimpri'    => ['Baner', 'Wakad']
    ];

    if ($locality === 'Any' || empty($locality)) {
        $score += 25;
        $reasons[] = "Located in prime Pune student & tech hub: " . htmlspecialchars($prop_loc);
    } elseif (strcasecmp($prop_loc, $locality) === 0) {
        $score += 25;
        $reasons[] = "Exact locality match: Located right in " . htmlspecialchars($prop_loc);
    } elseif (isset($nearby_clusters[$locality]) && in_array($prop_loc, $nearby_clusters[$locality])) {
        $score += 15;
        $reasons[] = "Convenient adjacent locality: Located in " . htmlspecialchars($prop_loc) . " (easy commute to " . htmlspecialchars($locality) . ")";
    }

    // 3. Accommodation Type (Max 20 Points)
    if ($type === 'Any' || empty($type)) {
        $score += 20;
        $reasons[] = "Verified " . htmlspecialchars($prop_type) . " accommodation suited for students";
    } elseif (strcasecmp($prop_type, $type) === 0) {
        $score += 20;
        $reasons[] = "Matches preferred accommodation type: " . htmlspecialchars($prop_type);
    } elseif (($type === 'Room' && $prop_type === 'PG') || ($type === 'PG' && $prop_type === 'Room')) {
        $score += 12;
        $reasons[] = "Practical student alternative: " . htmlspecialchars($prop_type) . " offering similar amenities to " . htmlspecialchars($type);
    }

    // 4. Required Amenities (Max 15 Points)
    if (!empty($amenities)) {
        $amenity_synonyms = [
            'WiFi'              => ['wifi', 'wi-fi', 'internet'],
            'Food Included'     => ['food', 'tiffin', 'meals', 'mess', 'breakfast', 'dinner'],
            'Washing Machine'   => ['washing machine', 'laundry'],
            'Geyser'            => ['geyser', 'hot water', 'solar water'],
            'AC'                => ['ac', 'air conditioner'],
            'Power Backup'      => ['power backup', 'inverter', 'generator'],
            'RO Water'          => ['ro water', 'water purifier', 'purifier'],
            'Lift'              => ['lift', 'elevator'],
            'Parking'           => ['parking', 'bike parking', 'car parking'],
            'CCTV & Security'   => ['cctv', 'security', 'guard', '24x7 security', 'warden'],
            'Attached Washroom' => ['attached washroom', 'attached bathroom', 'attached bath'],
            'Study Table'       => ['study table', 'desk', 'study desk']
        ];

        $matched_amenities = [];
        foreach ($amenities as $requested) {
            $keywords = $amenity_synonyms[$requested] ?? [strtolower($requested)];
            foreach ($keywords as $kw) {
                if (strpos($facilities_str, $kw) !== false) {
                    $matched_amenities[] = $requested;
                    break;
                }
            }
        }

        $total_requested = count($amenities);
        $total_matched = count($matched_amenities);

        if ($total_matched > 0) {
            $amenity_pts = (int) round(($total_matched / $total_requested) * 15);
            $score += $amenity_pts;
            if ($total_matched === $total_requested) {
                $reasons[] = "All " . $total_matched . " requested amenities available (" . implode(', ', array_slice($matched_amenities, 0, 4)) . (count($matched_amenities) > 4 ? ' + more' : '') . ")";
            } else {
                $reasons[] = "Offers " . $total_matched . " of " . $total_requested . " requested amenities: " . implode(', ', $matched_amenities);
            }
        } else {
            $reasons[] = "Equipped with property's standard facilities: " . htmlspecialchars(substr($prop['facilities'], 0, 40)) . '...';
        }
    } else {
        $score += 15;
        $reasons[] = "Includes essential amenities: " . htmlspecialchars($prop['facilities']);
    }

    // 5. Furnishing & Room Sharing Fit (Max 10 Points)
    // Furnishing (5 pts)
    if ($furnishing === 'Any' || empty($furnishing)) {
        $score += 5;
        $reasons[] = "Furnishing status: " . htmlspecialchars($prop_furnish);
    } elseif (strcasecmp($prop_furnish, $furnishing) === 0) {
        $score += 5;
        $reasons[] = "Furnishing match: " . htmlspecialchars($prop_furnish) . " ready to move";
    } elseif (($furnishing === 'Fully Furnished' && $prop_furnish === 'Semi-Furnished') || ($furnishing === 'Semi-Furnished' && $prop_furnish === 'Fully Furnished')) {
        $score += 3;
        $reasons[] = "Good furnishing fit: " . htmlspecialchars($prop_furnish);
    }

    // Room Sharing (5 pts)
    if ($sharing === 'single') {
        if ($prop_type === 'Room' || strpos($facilities_str, 'single') !== false || strpos($facilities_str, 'private') !== false || strpos(strtolower($prop['title']), '1rk') !== false) {
            $score += 5;
            $reasons[] = "Single / private room available for uninterrupted study";
        } else {
            $score += 3;
        }
    } elseif ($sharing === 'shared') {
        if ($prop_type === 'PG' || strpos($facilities_str, 'sharing') !== false || strpos($facilities_str, 'double') !== false || strpos($facilities_str, 'triple') !== false) {
            $score += 5;
            $reasons[] = "Shared room option available to split living expenses";
        } else {
            $score += 3;
        }
    } else {
        $score += 5;
    }

    // Student & Bachelor Friendly Policy
    if ($bachelor === 'Family Only') {
        $score = min($score, 25);
        $reasons[] = "⚠️ Family Only: Owner restricts bachelor/student tenants";
    } else {
        $reasons[] = "Student friendly: Bachelors and students are welcome (No family restrictions)";
    }

    // Affordability Check: If rent severely exceeds student budget, cap the match score
    if ($budget > 0) {
        if ($rent > ($budget * 1.8)) {
            $score = min($score, 20);
            $reasons[] = "⚠️ Rent (₹" . number_format($rent) . ") significantly exceeds your ₹" . number_format($budget) . " budget";
        } elseif ($rent > ($budget * 1.35)) {
            $score = min($score, 30);
            $reasons[] = "⚠️ Rent is beyond your comfortable budget range (+₹" . number_format($rent - $budget) . "/mo)";
        }
    }

    // Clamp score to 100%
    $final_score = min(100, max(0, $score));

    return [
        'score'   => $final_score,
        'reasons' => array_slice(array_unique($reasons), 0, 5)
    ];
}

// Fetch all available properties
$all_props_stmt = $pdo->prepare("
    SELECT p.*, COALESCE(pi.image_path, 'assets/images/properties/prop1.svg') AS primary_image 
    FROM properties p 
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE p.status = 'Available'
");
$all_props_stmt->execute();
$candidate_properties = $all_props_stmt->fetchAll();

// Calculate matching scores
$recommendations = [];
foreach ($candidate_properties as $prop) {
    $eval = calculate_property_match(
        $prop,
        $input_budget,
        $input_type,
        $input_locality,
        $input_furnishing,
        $input_sharing,
        $input_amenities
    );

    $prop['match_score'] = $eval['score'];
    $prop['match_reasons'] = $eval['reasons'];
    $recommendations[] = $prop;
}

// Sort by match score descending, then rent ascending
usort($recommendations, function ($a, $b) {
    if ($b['match_score'] === $a['match_score']) {
        return $a['rent'] <=> $b['rent'];
    }
    return $b['match_score'] <=> $a['match_score'];
});

// Filter out extremely poor matches (< 35%) so the empty state triggers when appropriate
$filtered_recommendations = array_filter($recommendations, function ($p) {
    return $p['match_score'] >= 35;
});

// If AJAX request is made (for dynamic rendering or live updates), return JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'success',
        'count'  => count($filtered_recommendations),
        'results'=> array_values($filtered_recommendations)
    ]);
    exit;
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Breadcrumb & Header -->
        <div style="margin-bottom: 24px;">
            <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 6px;">
                <a href="home.php">Home</a> / <a href="properties.php">Properties</a> / <span>Find My Ideal Flat</span>
            </div>
        </div>

        <!-- Wizard Card -->
        <div class="wizard-card" id="wizardCard">
            <div class="wizard-header">
                <span class="wizard-badge">
                    <i class="fa-solid fa-graduation-cap"></i> Student Accommodation Recommendation Wizard
                </span>
                <h1 class="wizard-title">Find My Ideal Flat</h1>
                <p class="wizard-subtitle">
                    Answer 6 quick questions about your budget, locality, and lifestyle. We will match you directly with the best verified flats, PGs, and rooms in Pune with zero brokerage.
                </p>
            </div>

            <!-- Stepper Progress Bar -->
            <div class="wizard-stepper">
                <div class="wizard-stepper-line"></div>
                <div class="wizard-stepper-progress" id="stepperProgress" style="width: 0%;"></div>

                <!-- Step 1 Indicator -->
                <button type="button" class="stepper-item active" id="stepIndicator1" onclick="jumpToStep(1)">
                    <div class="stepper-circle">1</div>
                    <span class="stepper-label">Budget & Room</span>
                </button>

                <!-- Step 2 Indicator -->
                <button type="button" class="stepper-item" id="stepIndicator2" onclick="jumpToStep(2)">
                    <div class="stepper-circle">2</div>
                    <span class="stepper-label">Type & Locality</span>
                </button>

                <!-- Step 3 Indicator -->
                <button type="button" class="stepper-item" id="stepIndicator3" onclick="jumpToStep(3)">
                    <div class="stepper-circle">3</div>
                    <span class="stepper-label">Furnishing & Amenities</span>
                </button>
            </div>

            <!-- Multi-step Form -->
            <form id="idealFlatForm" action="ideal-flat.php" method="GET">
                <input type="hidden" name="submitted" value="1">

                <!-- STEP 1: Monthly Rent Budget & Room Sharing -->
                <div class="wizard-step active" id="stepContainer1">
                    <div class="step-heading">
                        <i class="fa-solid fa-wallet" style="color:var(--secondary);"></i> Step 1: Budget & Room Sharing
                    </div>
                    <p class="step-desc">Specify your target monthly budget and room sharing preference.</p>

                    <!-- Question 1: Monthly Rent Budget -->
                    <div class="form-group">
                        <label for="budgetInput">1. Monthly Rent Budget (₹)</label>
                        <div class="budget-card-wrapper">
                            <div class="budget-val-display">
                                <span class="text-muted" style="font-size:0.9rem;">Maximum Monthly Rent:</span>
                                <span class="budget-amount" id="budgetDisplay">₹<?php echo number_format($input_budget); ?></span>
                            </div>

                            <input type="range" class="budget-slider" id="budgetSlider" min="3000" max="35000" step="500" value="<?php echo $input_budget; ?>">

                            <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                                <div style="position:relative; width:180px;">
                                    <span style="position:absolute; left:12px; top:11px; font-weight:700; color:var(--text-muted);">₹</span>
                                    <input type="number" name="budget" id="budgetInput" class="form-control" style="padding-left:28px; font-weight:700;" min="1000" max="60000" value="<?php echo $input_budget; ?>">
                                </div>
                                <span class="text-muted" style="font-size:0.85rem;">Or click a student quick-preset below:</span>
                            </div>

                            <!-- Student Budget Preset Chips -->
                            <div class="budget-chips">
                                <button type="button" class="budget-chip" data-amount="6000">₹6,000 (Budget PG)</button>
                                <button type="button" class="budget-chip" data-amount="8000">₹8,000 (Student PG)</button>
                                <button type="button" class="budget-chip" data-amount="12000">₹12,000 (1RK / 1BHK)</button>
                                <button type="button" class="budget-chip" data-amount="16000">₹16,000 (2BHK Share)</button>
                                <button type="button" class="budget-chip" data-amount="22000">₹22,000+ (Premium)</button>
                            </div>
                        </div>
                    </div>

                    <!-- Question 6: Single or Shared Room -->
                    <div class="form-group" style="margin-top: 24px;">
                        <label>6. Room Sharing Preference</label>
                        <p class="form-help" style="margin-bottom:10px;">Do you prefer a private bedroom to study, or a shared room to split living costs?</p>
                        
                        <div class="preference-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            <!-- Single Room -->
                            <label class="preference-option">
                                <input type="radio" name="sharing" value="single" <?php echo ($input_sharing === 'single') ? 'checked' : ''; ?>>
                                <div class="preference-box">
                                    <i class="fa-solid fa-bed"></i>
                                    <span class="pref-title">Single / Private Room</span>
                                    <span class="pref-desc">Dedicated room for quiet study & personal privacy</span>
                                </div>
                            </label>

                            <!-- Shared Room -->
                            <label class="preference-option">
                                <input type="radio" name="sharing" value="shared" <?php echo ($input_sharing === 'shared') ? 'checked' : ''; ?>>
                                <div class="preference-box">
                                    <i class="fa-solid fa-people-roof"></i>
                                    <span class="pref-title">Shared Room</span>
                                    <span class="pref-desc">2 or 3 sharing to split rent, electricity & food costs</span>
                                </div>
                            </label>

                            <!-- Any / Flexible -->
                            <label class="preference-option">
                                <input type="radio" name="sharing" value="any" <?php echo ($input_sharing === 'any' || empty($input_sharing)) ? 'checked' : ''; ?>>
                                <div class="preference-box">
                                    <i class="fa-solid fa-shuffle"></i>
                                    <span class="pref-title">Any / Flexible</span>
                                    <span class="pref-desc">Open to both private and shared accommodation</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Accommodation Type & Locality -->
                <div class="wizard-step" id="stepContainer2">
                    <div class="step-heading">
                        <i class="fa-solid fa-map-location-dot" style="color:var(--secondary);"></i> Step 2: Accommodation Type & Locality
                    </div>
                    <p class="step-desc">Select the type of rental property and your preferred area in Pune.</p>

                    <!-- Question 2: Preferred Accommodation Type -->
                    <div class="form-group">
                        <label>2. Preferred Accommodation Type</label>
                        <div class="preference-grid">
                            <!-- Flat / Apartment -->
                            <label class="preference-option">
                                <input type="radio" name="type" value="Flat" <?php echo ($input_type === 'Flat') ? 'checked' : ''; ?>>
                                <div class="preference-box">
                                    <i class="fa-solid fa-building"></i>
                                    <span class="pref-title">Flat / Apartment</span>
                                    <span class="pref-desc">1BHK, 2BHK or 3BHK for group living</span>
                                </div>
                            </label>

                            <!-- PG / Hostel -->
                            <label class="preference-option">
                                <input type="radio" name="type" value="PG" <?php echo ($input_type === 'PG') ? 'checked' : ''; ?>>
                                <div class="preference-box">
                                    <i class="fa-solid fa-hotel"></i>
                                    <span class="pref-title">PG / Hostel</span>
                                    <span class="pref-desc">Hassle-free stay with meals & housekeeping</span>
                                </div>
                            </label>

                            <!-- Independent Room / 1RK -->
                            <label class="preference-option">
                                <input type="radio" name="type" value="Room" <?php echo ($input_type === 'Room') ? 'checked' : ''; ?>>
                                <div class="preference-box">
                                    <i class="fa-solid fa-door-open"></i>
                                    <span class="pref-title">Private Room / 1RK</span>
                                    <span class="pref-desc">Affordable independent compact room</span>
                                </div>
                            </label>

                            <!-- Any Type -->
                            <label class="preference-option">
                                <input type="radio" name="type" value="Any" <?php echo ($input_type === 'Any' || empty($input_type)) ? 'checked' : ''; ?>>
                                <div class="preference-box">
                                    <i class="fa-solid fa-layer-group"></i>
                                    <span class="pref-title">Any Accommodation</span>
                                    <span class="pref-desc">Show top matches from all categories</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Question 3: Preferred Locality -->
                    <div class="form-group" style="margin-top: 24px;">
                        <label for="localitySelect">3. Preferred Locality in Pune</label>
                        <select name="locality" id="localitySelect" class="form-control" style="font-size:1rem; padding:12px;">
                            <option value="Any" <?php echo ($input_locality === 'Any' || empty($input_locality)) ? 'selected' : ''; ?>>🌐 Any Locality in Pune (Show Best City-wide Matches)</option>
                            <option value="Hinjewadi" <?php echo ($input_locality === 'Hinjewadi') ? 'selected' : ''; ?>>📍 Hinjewadi (Phase 1, 2 & 3 • Major IT Hub)</option>
                            <option value="Wakad" <?php echo ($input_locality === 'Wakad') ? 'selected' : ''; ?>>📍 Wakad (Datta Mandir, Kaspate Vasti • Highly Connected)</option>
                            <option value="Tathawade" <?php echo ($input_locality === 'Tathawade') ? 'selected' : ''; ?>>📍 Tathawade (Near JSPM, Indira, DY Patil Campuses)</option>
                            <option value="Baner" <?php echo ($input_locality === 'Baner') ? 'selected' : ''; ?>>📍 Baner (Balewadi High Street, Cafes & Metro Corridor)</option>
                            <option value="Punawale" <?php echo ($input_locality === 'Punawale') ? 'selected' : ''; ?>>📍 Punawale (Affordable Student Gated Societies)</option>
                            <option value="Pimpri" <?php echo ($input_locality === 'Pimpri') ? 'selected' : ''; ?>>📍 Pimpri (Near Metro Station & Commercial Market)</option>
                        </select>
                        <p class="form-help">Tip: Tathawade and Wakad are the top choices for college students due to college proximity and low rents.</p>
                    </div>
                </div>

                <!-- STEP 3: Furnishing Preference & Required Amenities -->
                <div class="wizard-step" id="stepContainer3">
                    <div class="step-heading">
                        <i class="fa-solid fa-couch" style="color:var(--secondary);"></i> Step 3: Furnishing & Required Amenities
                    </div>
                    <p class="step-desc">Pick your furnishing expectation and select the must-have amenities for your student stay.</p>

                    <!-- Question 4: Furnishing Preference -->
                    <div class="form-group">
                        <label>4. Furnishing Preference</label>
                        <div class="preference-grid">
                            <label class="preference-option">
                                <input type="radio" name="furnishing" value="Fully Furnished" <?php echo ($input_furnishing === 'Fully Furnished') ? 'checked' : ''; ?>>
                                <div class="preference-box">
                                    <i class="fa-solid fa-couch"></i>
                                    <span class="pref-title">Fully Furnished</span>
                                    <span class="pref-desc">Beds, wardrobes, appliances ready to move in</span>
                                </div>
                            </label>

                            <label class="preference-option">
                                <input type="radio" name="furnishing" value="Semi-Furnished" <?php echo ($input_furnishing === 'Semi-Furnished') ? 'checked' : ''; ?>>
                                <div class="preference-box">
                                    <i class="fa-solid fa-kitchen-set"></i>
                                    <span class="pref-title">Semi-Furnished</span>
                                    <span class="pref-desc">Geyser, kitchen cabinets, lights & fans</span>
                                </div>
                            </label>

                            <label class="preference-option">
                                <input type="radio" name="furnishing" value="Unfurnished" <?php echo ($input_furnishing === 'Unfurnished') ? 'checked' : ''; ?>>
                                <div class="preference-box">
                                    <i class="fa-solid fa-box-open"></i>
                                    <span class="pref-title">Unfurnished</span>
                                    <span class="pref-desc">Maximum rent savings; bring own items</span>
                                </div>
                            </label>

                            <label class="preference-option">
                                <input type="radio" name="furnishing" value="Any" <?php echo ($input_furnishing === 'Any' || empty($input_furnishing)) ? 'checked' : ''; ?>>
                                <div class="preference-box">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span class="pref-title">Any Furnishing</span>
                                    <span class="pref-desc">No specific furnishing restriction</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Question 5: Required Amenities -->
                    <div class="form-group" style="margin-top: 24px;">
                        <label>5. Required Amenities (Select all that you need)</label>
                        <p class="form-help" style="margin-bottom:12px;">Properties matching these facilities will receive a boost in recommendation scores.</p>
                        
                        <div class="amenity-checkbox-grid">
                            <?php 
                            $available_amenities = [
                                ['name' => 'WiFi', 'icon' => 'fa-wifi', 'desc' => 'High-speed Internet'],
                                ['name' => 'Food Included', 'icon' => 'fa-utensils', 'desc' => 'Mess / Daily Food'],
                                ['name' => 'Washing Machine', 'icon' => 'fa-soap', 'desc' => 'Laundry Facility'],
                                ['name' => 'Geyser', 'icon' => 'fa-shower', 'desc' => 'Hot Water Supply'],
                                ['name' => 'AC', 'icon' => 'fa-snowflake', 'desc' => 'Air Conditioner'],
                                ['name' => 'Power Backup', 'icon' => 'fa-bolt', 'desc' => 'Inverter / Generator'],
                                ['name' => 'RO Water', 'icon' => 'fa-faucet-drip', 'desc' => 'Purified Drinking Water'],
                                ['name' => 'Lift', 'icon' => 'fa-elevator', 'desc' => 'Elevator Access'],
                                ['name' => 'Parking', 'icon' => 'fa-square-parking', 'desc' => 'Covered / Bike Parking'],
                                ['name' => 'CCTV & Security', 'icon' => 'fa-shield-halved', 'desc' => '24x7 Guard / CCTV'],
                                ['name' => 'Attached Washroom', 'icon' => 'fa-toilet', 'desc' => 'Private Washroom'],
                                ['name' => 'Study Table', 'icon' => 'fa-book-open-reader', 'desc' => 'Student Study Desk']
                            ];

                            foreach ($available_amenities as $am):
                                $is_checked = in_array($am['name'], $input_amenities);
                            ?>
                                <label class="amenity-checkbox-card">
                                    <input type="checkbox" name="amenities[]" value="<?php echo htmlspecialchars($am['name']); ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                                    <div class="amenity-checkbox-box">
                                        <i class="fa-solid <?php echo $am['icon']; ?>"></i>
                                        <span><?php echo htmlspecialchars($am['name']); ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Wizard Navigation & Controls -->
                <div class="wizard-actions">
                    <div class="wizard-actions-left">
                        <button type="button" class="btn btn-outline" id="prevBtn" onclick="navigateStep(-1)" style="display:none;">
                            <i class="fa-solid fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-outline" id="resetBtn" onclick="resetWizard()">
                            <i class="fa-solid fa-arrows-rotate"></i> Reset
                        </button>
                    </div>

                    <div class="wizard-actions-right">
                        <button type="button" class="btn btn-secondary" id="nextBtn" onclick="navigateStep(1)">
                            Next <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitWizardBtn" style="display:none;">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Find My Ideal Flat
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- RECOMMENDATION RESULTS SECTION -->
        <section id="resultsSection" style="scroll-margin-top: 40px;">
            <div class="results-header-box">
                <div class="results-meta">
                    <div style="font-size: 1.35rem; font-weight: 800; color: var(--primary);">
                        <?php if (empty($filtered_recommendations)): ?>
                            No Properties Matched Your Exact Criteria
                        <?php else: ?>
                            Top Recommendations (<?php echo count($filtered_recommendations); ?> <?php echo (count($filtered_recommendations) === 1) ? 'Property' : 'Properties'; ?> Found)
                        <?php endif; ?>
                    </div>
                    <div class="results-tags-summary">
                        <span class="summary-pill"><i class="fa-solid fa-wallet"></i> Budget: Max ₹<?php echo number_format($input_budget); ?>/mo</span>
                        <span class="summary-pill"><i class="fa-solid fa-location-dot"></i> Locality: <?php echo htmlspecialchars($input_locality); ?></span>
                        <span class="summary-pill"><i class="fa-solid fa-building"></i> Type: <?php echo htmlspecialchars($input_type); ?></span>
                        <span class="summary-pill"><i class="fa-solid fa-bed"></i> Sharing: <?php echo ucfirst(htmlspecialchars($input_sharing)); ?></span>
                        <?php if (!empty($input_amenities)): ?>
                            <span class="summary-pill"><i class="fa-solid fa-check"></i> <?php echo count($input_amenities); ?> Amenities Requested</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:flex; gap:10px;">
                    <a href="#wizardCard" class="btn btn-outline btn-sm" onclick="jumpToStep(1)">
                        <i class="fa-solid fa-sliders"></i> Modify Preferences
                    </a>
                </div>
            </div>

            <!-- EMPTY STATE -->
            <?php if (empty($filtered_recommendations)): ?>
                <div class="card text-center" style="padding: 50px 24px; border: 1.5px dashed var(--border-color); border-radius: var(--radius-lg); background: var(--bg-white);">
                    <div style="width:72px; height:72px; border-radius:50%; background:#FEE2E2; color:#DC2626; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 20px;">
                        <i class="fa-solid fa-house-circle-exclamation"></i>
                    </div>
                    <h3 style="font-size: 1.45rem; font-weight: 800; color: var(--primary); margin-bottom: 10px;">
                        No Direct Matches Found For Your Strict Filters
                    </h3>
                    <p class="text-muted" style="max-width: 540px; margin: 0 auto 20px; line-height: 1.6;">
                        We couldn't find an available property within <strong>₹<?php echo number_format($input_budget); ?>/month</strong> in <strong><?php echo htmlspecialchars($input_locality); ?></strong> that provides all your selected requirements.
                    </p>

                    <!-- Helpful Suggestions Box -->
                    <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px 20px; max-width: 540px; margin: 0 auto 24px; text-align: left;">
                        <div style="font-weight: 700; color: var(--primary); margin-bottom: 8px;">
                            <i class="fa-solid fa-lightbulb" style="color:var(--warning);"></i> Helpful Tips to Find a Match:
                        </div>
                        <ul style="padding-left: 20px; font-size: 0.875rem; color: var(--text-dark); line-height: 1.6;">
                            <li>Increase your monthly budget slightly by <strong>₹1,500 – ₹2,500</strong>.</li>
                            <li>Select <strong>"Any Locality in Pune"</strong> to explore adjacent college hubs like Tathawade or Wakad.</li>
                            <li>Try choosing <strong>"PG / Hostel"</strong> or <strong>"Shared Room"</strong> for maximum cost efficiency.</li>
                            <li>Uncheck one or two non-essential amenities (such as AC or Private Washroom).</li>
                        </ul>
                    </div>

                    <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary" onclick="relaxFiltersAndRetry()">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Relax Budget & Re-calculate
                        </button>
                        <button type="button" class="btn btn-outline" onclick="resetWizard()">
                            <i class="fa-solid fa-arrows-rotate"></i> Reset Wizard
                        </button>
                        <a href="properties.php" class="btn btn-outline">
                            <i class="fa-solid fa-list"></i> Browse All Properties
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- RECOMMENDATION CARDS GRID -->
                <div class="property-grid">
                    <?php foreach ($filtered_recommendations as $prop): 
                        $score = $prop['match_score'];
                        $score_class = ($score >= 80) ? 'high' : (($score >= 60) ? 'medium' : 'fair');
                        $is_saved = in_array($prop['id'], $saved_ids);
                        $fac_list = array_filter(array_map('trim', explode(',', $prop['facilities'])));
                    ?>
                        <div class="property-card" style="box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
                            <div class="property-img-wrapper">
                                <img src="<?php echo e($prop['primary_image']); ?>" alt="<?php echo e($prop['title']); ?>" loading="lazy">
                                
                                <?php if (!empty($prop['is_urgent'])): ?>
                                    <span class="urgent-badge"><i class="fa-solid fa-bolt"></i> Urgent Vacancy</span>
                                <?php endif; ?>
                                
                                <span class="type-pill"><?php echo e($prop['property_type']); ?></span>

                                <!-- Quick heart toggle on card image -->
                                <button type="button" class="save-btn <?php echo $is_saved ? 'active' : ''; ?>" data-id="<?php echo $prop['id']; ?>" title="<?php echo $is_saved ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                    <i class="fa-solid fa-heart"></i>
                                </button>
                            </div>

                            <div class="property-details">
                                <!-- Top Row: Match Score & Location -->
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                                    <span class="match-score-pill <?php echo $score_class; ?>">
                                        <i class="fa-solid fa-circle-check"></i> <?php echo $score; ?>% Match
                                    </span>
                                    <div class="property-location" style="margin-bottom:0; font-size:0.85rem;">
                                        <i class="fa-solid fa-location-dot"></i> <?php echo e($prop['location']); ?>, Pune
                                    </div>
                                </div>

                                <!-- Title -->
                                <h3 class="property-title" style="font-size:1.15rem; margin-bottom:8px;">
                                    <a href="property-details.php?id=<?php echo $prop['id']; ?>" style="color:var(--primary);">
                                        <?php echo e($prop['title']); ?>
                                    </a>
                                </h3>

                                <!-- Rent Display -->
                                <div class="property-price" style="margin-bottom:10px;">
                                    <?php echo format_inr($prop['rent']); ?> <span>/ month</span>
                                    <span style="font-size:0.75rem; color:var(--secondary); background:var(--secondary-light); padding:2px 8px; border-radius:12px; margin-left:8px; font-weight:700;">
                                        Zero Brokerage
                                    </span>
                                </div>

                                <!-- Available Amenities Tags -->
                                <div style="margin-bottom: 12px;">
                                    <div style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.5px;">
                                        Available Amenities:
                                    </div>
                                    <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                        <span class="amenity-tag" style="font-size:0.75rem; padding:3px 8px;">
                                            <i class="fa-solid fa-couch"></i> <?php echo e($prop['furnishing']); ?>
                                        </span>
                                        <?php foreach (array_slice($fac_list, 0, 4) as $fac): ?>
                                            <span class="amenity-tag" style="font-size:0.75rem; padding:3px 8px;">
                                                <i class="fa-solid fa-check" style="color:var(--secondary);"></i> <?php echo e($fac); ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($fac_list) > 4): ?>
                                            <span class="amenity-tag" style="font-size:0.75rem; padding:3px 8px; color:var(--text-muted);">
                                                +<?php echo (count($fac_list) - 4); ?> more
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Reasons for the match breakdown -->
                                <div class="match-reasons-box">
                                    <div class="match-reasons-title">
                                        <i class="fa-solid fa-sparkles"></i> Why this flat matches you:
                                    </div>
                                    <ul class="match-reasons-list">
                                        <?php foreach ($prop['match_reasons'] as $reason): ?>
                                            <li>
                                                <i class="fa-solid fa-check-circle"></i>
                                                <span><?php echo htmlspecialchars($reason); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <!-- Action Buttons: View Details & Add to Wishlist -->
                                <div style="display:flex; align-items:center; gap:8px; margin-top:auto; padding-top:12px; border-top:1px solid var(--border-color);">
                                    <a href="property-details.php?id=<?php echo $prop['id']; ?>" class="btn btn-secondary btn-sm" style="flex:1;">
                                        <i class="fa-solid fa-eye"></i> View Details
                                    </a>
                                    
                                    <button type="button" class="wishlist-toggle-btn <?php echo $is_saved ? 'saved' : ''; ?> ideal-wishlist-action" data-id="<?php echo $prop['id']; ?>" title="<?php echo $is_saved ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                        <i class="<?php echo $is_saved ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                                        <span class="wishlist-label"><?php echo $is_saved ? 'Wishlisted' : 'Wishlist'; ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<!-- Floating Toast Notification for Wishlist Actions -->
<div id="toastNotification" class="toast-notice" style="display:none;">
    <i class="fa-solid fa-circle-check" style="color:#5EEAD4;"></i>
    <span id="toastMessage">Saved to your wishlist!</span>
</div>

<script>
/**
 * Interactive Step-by-step Wizard Controller
 */
let currentStep = 1;
const totalSteps = 3;

function updateStepperUI() {
    // Update step containers visibility
    for (let i = 1; i <= totalSteps; i++) {
        const stepContainer = document.getElementById('stepContainer' + i);
        const indicator = document.getElementById('stepIndicator' + i);
        
        if (stepContainer) {
            stepContainer.classList.toggle('active', i === currentStep);
        }
        
        if (indicator) {
            indicator.classList.remove('active', 'completed');
            if (i === currentStep) {
                indicator.classList.add('active');
            } else if (i < currentStep) {
                indicator.classList.add('completed');
            }
        }
    }

    // Update progress bar width
    const progressBar = document.getElementById('stepperProgress');
    if (progressBar) {
        const percentage = ((currentStep - 1) / (totalSteps - 1)) * 100;
        progressBar.style.width = percentage + '%';
    }

    // Update buttons visibility
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitWizardBtn');

    if (prevBtn) prevBtn.style.display = (currentStep > 1) ? 'inline-flex' : 'none';
    if (nextBtn) nextBtn.style.display = (currentStep < totalSteps) ? 'inline-flex' : 'none';
    if (submitBtn) submitBtn.style.display = (currentStep === totalSteps) ? 'inline-flex' : 'none';
}

function validateCurrentStep(step) {
    if (step === 1) {
        const budgetInput = document.getElementById('budgetInput');
        const budgetVal = parseInt(budgetInput.value, 10);
        if (isNaN(budgetVal) || budgetVal < 1000) {
            alert('Please enter a valid monthly rent budget of at least ₹1,000.');
            budgetInput.focus();
            return false;
        }
        
        const sharingChecked = document.querySelector('input[name="sharing"]:checked');
        if (!sharingChecked) {
            alert('Please select whether you prefer a single or shared room.');
            return false;
        }
    } else if (step === 2) {
        const typeChecked = document.querySelector('input[name="type"]:checked');
        if (!typeChecked) {
            alert('Please select a preferred accommodation type.');
            return false;
        }
    } else if (step === 3) {
        const furnishChecked = document.querySelector('input[name="furnishing"]:checked');
        if (!furnishChecked) {
            alert('Please choose your furnishing preference.');
            return false;
        }
    }
    return true;
}

function navigateStep(direction) {
    if (direction > 0) {
        if (!validateCurrentStep(currentStep)) return;
    }
    
    const newStep = currentStep + direction;
    if (newStep >= 1 && newStep <= totalSteps) {
        currentStep = newStep;
        updateStepperUI();
    }
}

function jumpToStep(targetStep) {
    if (targetStep < currentStep) {
        currentStep = targetStep;
        updateStepperUI();
    } else if (targetStep > currentStep) {
        // Validate preceding step
        if (validateCurrentStep(currentStep)) {
            currentStep = targetStep;
            updateStepperUI();
        }
    }
}

function resetWizard() {
    document.getElementById('idealFlatForm').reset();
    document.getElementById('budgetSlider').value = 10000;
    document.getElementById('budgetInput').value = 10000;
    document.getElementById('budgetDisplay').innerText = '₹10,000';
    
    // Reset preset chips active state
    document.querySelectorAll('.budget-chip').forEach(c => c.classList.remove('active'));
    
    // Check default radios
    const defSharing = document.querySelector('input[name="sharing"][value="any"]');
    if (defSharing) defSharing.checked = true;

    const defType = document.querySelector('input[name="type"][value="Any"]');
    if (defType) defType.checked = true;

    const defFurnish = document.querySelector('input[name="furnishing"][value="Any"]');
    if (defFurnish) defFurnish.checked = true;

    const defLoc = document.getElementById('localitySelect');
    if (defLoc) defLoc.value = 'Any';

    currentStep = 1;
    updateStepperUI();

    showToast('Wizard reset to student default preferences.');
}

function relaxFiltersAndRetry() {
    const budgetInput = document.getElementById('budgetInput');
    const budgetSlider = document.getElementById('budgetSlider');
    const localitySelect = document.getElementById('localitySelect');
    
    // Increase budget by ₹4,000 and select Any Locality
    const newBudget = Math.max(12000, parseInt(budgetInput.value, 10) + 4000);
    budgetInput.value = newBudget;
    budgetSlider.value = newBudget;
    document.getElementById('budgetDisplay').innerText = '₹' + newBudget.toLocaleString('en-IN');
    
    if (localitySelect) localitySelect.value = 'Any';
    
    // Uncheck rare amenities
    document.querySelectorAll('input[name="amenities[]"]').forEach(cb => {
        if (cb.value === 'AC' || cb.value === 'Attached Washroom') {
            cb.checked = false;
        }
    });

    // Submit form automatically
    document.getElementById('idealFlatForm').submit();
}

// Synchronize Budget Slider & Input
document.addEventListener('DOMContentLoaded', function () {
    const slider = document.getElementById('budgetSlider');
    const input = document.getElementById('budgetInput');
    const display = document.getElementById('budgetDisplay');
    const chips = document.querySelectorAll('.budget-chip');

    function syncBudget(val) {
        val = parseInt(val, 10) || 10000;
        slider.value = val;
        input.value = val;
        display.innerText = '₹' + val.toLocaleString('en-IN');

        chips.forEach(chip => {
            if (parseInt(chip.getAttribute('data-amount'), 10) === val) {
                chip.classList.add('active');
            } else {
                chip.classList.remove('active');
            }
        });
    }

    if (slider && input) {
        slider.addEventListener('input', function () {
            syncBudget(this.value);
        });

        input.addEventListener('input', function () {
            syncBudget(this.value);
        });

        chips.forEach(chip => {
            chip.addEventListener('click', function () {
                const amt = this.getAttribute('data-amount');
                syncBudget(amt);
            });
        });
    }

    // Interactive Wishlist Buttons (AJAX toggle)
    initWishlistButtons();
});

function initWishlistButtons() {
    const wishlistBtns = document.querySelectorAll('.ideal-wishlist-action');
    wishlistBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const propId = this.getAttribute('data-id');
            toggleWishlist(propId, this);
        });
    });
}

function toggleWishlist(propId, btnElem) {
    if (!propId) return;

    fetch('save-property.php?id=' + encodeURIComponent(propId), {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const icon = btnElem.querySelector('i');
            const label = btnElem.querySelector('.wishlist-label');
            const cardSaveBtn = btnElem.closest('.property-card')?.querySelector('.save-btn');

            if (data.saved) {
                btnElem.classList.add('saved');
                if (icon) {
                    icon.classList.remove('fa-regular');
                    icon.classList.add('fa-solid');
                }
                if (label) label.innerText = 'Wishlisted';
                if (cardSaveBtn) cardSaveBtn.classList.add('active');
                showToast('Property added to your wishlist!');
            } else {
                btnElem.classList.remove('saved');
                if (icon) {
                    icon.classList.remove('fa-solid');
                    icon.classList.add('fa-regular');
                }
                if (label) label.innerText = 'Wishlist';
                if (cardSaveBtn) cardSaveBtn.classList.remove('active');
                showToast('Property removed from your wishlist.');
            }
        } else if (data.redirect) {
            window.location.href = data.redirect;
        } else {
            alert(data.message || 'Could not update wishlist.');
        }
    })
    .catch(err => {
        console.error('Wishlist error:', err);
    });
}

function showToast(message) {
    const toast = document.getElementById('toastNotification');
    const msgElem = document.getElementById('toastMessage');
    if (toast && msgElem) {
        msgElem.innerText = message;
        toast.style.display = 'flex';
        clearTimeout(window.toastTimer);
        window.toastTimer = setTimeout(() => {
            toast.style.display = 'none';
        }, 3200);
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
