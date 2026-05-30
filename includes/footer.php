<?php
$root = "/~u202301956/poly-marketplace";
?>

<!-- Footer gradient divider -->
<div style="height:3px; background: linear-gradient(90deg, var(--pm-navy) 0%, var(--pm-cyan) 50%, var(--pm-orange) 100%);"></div>

<footer class="footer-pm mt-0 pt-4 pb-3">
    <div class="container">
        <div class="row g-4 mb-3">

            <div class="col-md-5">
                <p class="fw-bold text-white mb-2" style="font-size:1.05rem; letter-spacing:-.2px;">
                    <i class="bi bi-shop me-2" style="color:var(--pm-cyan);"></i>PolyMarketplace
                </p>
                <p class="mb-0" style="color:rgba(255,255,255,.5); font-size:.85rem; line-height:1.6;">
                    A student marketplace built by Bahrain Polytechnic.<br>
                    Discover unique campus listings from creators near you.
                </p>
            </div>

            <div class="col-6 col-md-3 offset-md-1">
                <p class="fw-semibold text-uppercase mb-3" style="font-size:.7rem; letter-spacing:.12em; color:var(--pm-cyan);">Explore</p>
                <ul class="list-unstyled mb-0" style="font-size:.85rem;">
                    <li><a href="<?= $root ?>/index.php" class="footer-link">Home</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-3">
                <p class="fw-semibold text-uppercase mb-3" style="font-size:.7rem; letter-spacing:.12em; color:var(--pm-cyan);">Account</p>
                <ul class="list-unstyled mb-0" style="font-size:.85rem;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="<?= $root ?>/pages/profile.php" class="footer-link">My Profile</a></li>
                        <?php if (($_SESSION['role'] ?? '') === 'creator'): ?>
                            <li><a href="<?= $root ?>/pages/creator/dashboard.php" class="footer-link">My Dashboard</a></li>
                        <?php endif; ?>
                        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <li><a href="<?= $root ?>/pages/admin/dashboard.php" class="footer-link">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="<?= $root ?>/pages/logout.php" class="footer-link">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= $root ?>/pages/login.php" class="footer-link">Login</a></li>
                        <li><a href="<?= $root ?>/pages/register.php" class="footer-link">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

        <div style="height:1px; background:rgba(255,255,255,.08); margin-bottom:.75rem;"></div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small style="color:rgba(255,255,255,.35); font-size:.75rem;">
                &copy; <?= date('Y') ?> PolyMarketplace &mdash; IT8415 Database Programming 2
            </small>
            <small style="color:var(--pm-cyan); font-size:.75rem; font-weight:500; letter-spacing:.04em;">
                BAHRAIN POLYTECHNIC
            </small>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
