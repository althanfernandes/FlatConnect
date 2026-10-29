<?php
/**
 * FlatConnect - Universal Footer Include
 */
?>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-brand">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                    <div style="width:34px; height:34px; background:var(--secondary); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff;">
                        <i class="fa-solid fa-house-chimney"></i>
                    </div>
                    <span style="font-size:1.4rem; font-weight:800; color:#fff;">Flat<span style="color:var(--secondary);">Connect</span></span>
                </div>
                <p style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 16px;">
                    Direct Flat, PG & Roommate Finder. Connecting property owners, existing tenants, and accommodation seekers directly without middlemen or brokerage.
                </p>
                <div style="font-size:0.85rem; color:#A0AEC0;">
                    <i class="fa-solid fa-bolt" style="color:var(--secondary);"></i> <strong>Tagline:</strong> Find a place. Find a roommate. Connect directly.
                </div>
            </div>

            <!-- Categories -->
            <div class="footer-col">
                <h4>Explore</h4>
                <ul class="footer-links">
                    <li><a href="properties.php?type=Flat"><i class="fa-solid fa-angle-right"></i> Flats in Pune</a></li>
                    <li><a href="properties.php?type=PG"><i class="fa-solid fa-angle-right"></i> PGs & Hostels</a></li>
                    <li><a href="properties.php?type=Room"><i class="fa-solid fa-angle-right"></i> Private Rooms</a></li>
                    <li><a href="ideal-flat.php"><i class="fa-solid fa-wand-magic-sparkles"></i> Find My Ideal Flat</a></li>
                    <li><a href="roommates.php"><i class="fa-solid fa-angle-right"></i> Roommate Finder</a></li>
                    <li><a href="replacement.php"><i class="fa-solid fa-angle-right"></i> Tenant Replacement</a></li>
                </ul>
            </div>

            <!-- Pune Localities -->
            <div class="footer-col">
                <h4>Top Localities</h4>
                <ul class="footer-links">
                    <li><a href="properties.php?location=Hinjewadi"><i class="fa-solid fa-location-dot"></i> Hinjewadi (Phase 1-3)</a></li>
                    <li><a href="properties.php?location=Wakad"><i class="fa-solid fa-location-dot"></i> Wakad</a></li>
                    <li><a href="properties.php?location=Tathawade"><i class="fa-solid fa-location-dot"></i> Tathawade</a></li>
                    <li><a href="properties.php?location=Baner"><i class="fa-solid fa-location-dot"></i> Baner</a></li>
                    <li><a href="properties.php?location=Punawale"><i class="fa-solid fa-location-dot"></i> Punawale</a></li>
                    <li><a href="properties.php?location=Pimpri"><i class="fa-solid fa-location-dot"></i> Pimpri</a></li>
                </ul>
            </div>

            <!-- Why FlatConnect -->
            <div class="footer-col">
                <h4>Platform Promise</h4>
                <ul class="footer-links" style="font-size:0.9rem;">
                    <li style="margin-bottom:10px;"><i class="fa-solid fa-check" style="color:var(--secondary);"></i> 100% Zero Brokerage</li>
                    <li style="margin-bottom:10px;"><i class="fa-solid fa-check" style="color:var(--secondary);"></i> Direct Owner & Tenant Chat</li>
                    <li style="margin-bottom:10px;"><i class="fa-solid fa-check" style="color:var(--secondary);"></i> Clear Bachelor Allowed Status</li>
                    <li style="margin-bottom:10px;"><i class="fa-solid fa-check" style="color:var(--secondary);"></i> Verified Urgent Vacancies</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> FlatConnect. Direct Flat, PG & Roommate Finder. Built with PHP & MySQL.</p>
        </div>
    </div>
</footer>

<!-- FlatConnect Main JavaScript -->
<script src="assets/js/main.js"></script>
</body>
</html>
