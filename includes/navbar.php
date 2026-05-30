<?php
// Absolute directory because navbar is included in multiple pages with different relative paths
$root = "/~u202301956/poly-marketplace";

// Instantiate a separate local connection handle to prevent parent page query collisions
$dbc = getDB();
$navbarCategories = [];

$categoryResult = mysqli_query($dbc, "SELECT CategoryID, CategoryName FROM pm_categories ORDER BY CategoryName");

if ($categoryResult) {
    while ($categoryRow = mysqli_fetch_assoc($categoryResult)) {
        $navbarCategories[] = $categoryRow;
    }
}
mysqli_close($dbc);
?>

<style>
    .navbar-search-input {
        background: rgba(255, 255, 255, 0.12) !important;
        color: #fff !important;
        border: 1px solid rgba(255, 255, 255, 0.25) !important;
        min-width: 200px;
    }

    .navbar-search-input::placeholder {
        color: rgba(255, 255, 255, 0.55);
    }

    .navbar-search-input:focus {
        background: rgba(255, 255, 255, 0.2) !important;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.4);
        border-color: var(--pm-accent) !important;
        color: #fff !important;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-pm navbar-dark">
    <div class="container">

        <a class="navbar-brand" href="<?php echo $root; ?>/index.php">
            <i class="bi bi-shop me-2"></i>PolyMarketplace
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
            aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">

            <ul class="navbar-nav me-3 mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $root; ?>/index.php">
                        <i class="bi bi-house me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $root; ?>/search.php">
                        <i class="bi bi-grid me-1"></i>Browse
                    </a>
                </li>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'creator'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $root; ?>/pages/creator/dashboard.php">
                            <i class="bi bi-shop-window me-1"></i>My Stall
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $root; ?>/pages/admin/dashboard.php">
                            <i class="bi bi-gear me-1"></i>Admin
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <form class="d-flex flex-grow-1 me-3 my-2 my-lg-0" method="GET" action="<?php echo $root; ?>/search.php">
                <div class="input-group input-group-sm w-100">
                    <input
                        type="search"
                        name="keyword"
                        class="form-control navbar-search-input"
                        placeholder="Search listings"
                        value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>"
                        aria-label="Search listings">
                    <select name="category_id" class="form-select" style="max-width:180px;" aria-label="Category">
                        <option value="">All Categories</option>
                        <?php foreach ($navbarCategories as $category): ?>
                            <option value="<?php echo $category['CategoryID']; ?>"
                                <?php if (($_GET['category_id'] ?? '') == $category['CategoryID']) {
                                    echo 'selected';
                                } ?>>
                                <?php echo htmlspecialchars($category['CategoryName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-accent" type="submit" title="Search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                            href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['name'] ?? 'Account'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (($_SESSION['role'] ?? '') == 'creator'): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo $root; ?>/pages/creator/dashboard.php">
                                            <i class="bi bi-speedometer2 me-2"></i>Creator Dashboard
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php elseif (($_SESSION['role'] ?? '') == 'admin'): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo $root; ?>/pages/admin/dashboard.php">
                                            <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo $root; ?>/pages/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $root; ?>/pages/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-accent px-3" href="<?php echo $root; ?>/pages/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>

        </div>
    </div>
</nav>