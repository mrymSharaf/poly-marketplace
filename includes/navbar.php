<?php
$root = "/~u202301956/poly-marketplace";

$dbc = getDB();
$navbarCategories = [];
$categoryResult = mysqli_query($dbc, "SELECT CategoryID, CategoryName FROM pm_categories ORDER BY CategoryName");
if ($categoryResult) {
    while ($row = mysqli_fetch_assoc($categoryResult)) $navbarCategories[] = $row;
}
mysqli_close($dbc);

$userInitial = strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1));
?>

<style>
    /* Inline user avatar in navbar */
    .nav-avatar {
        width: 30px; height: 30px; font-size: .75rem; flex-shrink: 0;
        border-radius: 50%; background: var(--pm-cyan);
        color: var(--pm-navy); display: inline-flex; align-items: center;
        justify-content: center; font-weight: 700;
    }

    /* Logout button pill */
    .btn-nav-logout {
        border: 1.5px solid rgba(255,255,255,.35);
        color: rgba(255,255,255,.85);
        background: transparent;
        border-radius: 50px;
        padding: .25rem .9rem;
        font-size: .83rem;
        transition: all .15s;
    }
    .btn-nav-logout:hover { background: rgba(255,255,255,.12); color: #fff; border-color: #fff; }
</style>

<nav class="navbar navbar-expand-lg navbar-pm navbar-dark">
    <div class="container">

        <a class="navbar-brand fw-bold" href="<?= $root ?>/index.php">
            <i class="bi bi-shop me-2"></i>PolyMarketplace
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain" aria-controls="navbarMain"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">

            <!-- Right: visible buttons, no dropdown -->
            <ul class="navbar-nav ms-auto align-items-center gap-1 flex-row flex-lg-row">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $root ?>/search.php">
                        <i class="bi bi-grid me-1"></i>Listings
                    </a>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>

                    <!-- Profile -->
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2"
                           href="<?= $root ?>/pages/profile.php">
                            <span class="nav-avatar"><?= $userInitial ?></span>
                            <span class="d-none d-lg-inline small">
                                <?= htmlspecialchars($_SESSION['name'] ?? 'Me') ?>
                            </span>
                        </a>
                    </li>

                    <!-- Creator: My Stall -->
                    <?php if (($_SESSION['role'] ?? '') === 'creator'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $root ?>/pages/creator/dashboard.php">
                                <i class="bi bi-shop-window me-1"></i>My Stall
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Admin panel -->
                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $root ?>/pages/admin/dashboard.php">
                                <i class="bi bi-gear me-1"></i>Admin
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Logout -->
                    <li class="nav-item ms-1">
                        <a class="btn-nav-logout btn" href="<?= $root ?>/pages/logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $root ?>/pages/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-accent px-3 rounded-pill" href="<?= $root ?>/pages/register.php">
                            Register
                        </a>
                    </li>

                <?php endif; ?>

            </ul>

        </div>
    </div>
</nav>
