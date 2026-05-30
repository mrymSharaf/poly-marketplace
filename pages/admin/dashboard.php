<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();

$userCount = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_users"))['total'];
$listingCount = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_listings"))['total'];
$publishedCount = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_listings WHERE Status = 'published'"))['total'];
$commentCount = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_comments WHERE IsDeleted = 0"))['total'];
$creatorCount = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_users WHERE Role = 'creator'"))['total'];
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100" style="border-left: 4px solid #0d6efd;">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-people-fill fs-1 text-primary"></i>
                    <div>
                        <div class="fs-2 fw-bold"><?= $userCount ?></div>
                        <div class="text-muted small">Total Users (<?= $creatorCount ?> creators)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100" style="border-left: 4px solid #22c55e;">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-grid-3x3-gap-fill fs-1" style="color:#22c55e"></i>
                    <div>
                        <div class="fs-2 fw-bold"><?= $listingCount ?></div>
                        <div class="text-muted small">Total Listings (<?= $publishedCount ?> published)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100" style="border-left: 4px solid #fd7e14;">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-chat-dots-fill fs-1" style="color:#fd7e14"></i>
                    <div>
                        <div class="fs-2 fw-bold"><?= $commentCount ?></div>
                        <div class="text-muted small">Active Comments</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100" style="border-left: 4px solid #6c757d;">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-person-badge fs-1 text-secondary"></i>
                    <div>
                        <div class="fs-2 fw-bold"><?= $creatorCount ?></div>
                        <div class="text-muted small">Content Creators</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Admin Actions</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><a href="manage_users.php" class="btn btn-outline-primary w-100 py-2"><i class="bi bi-people-fill me-2"></i>Manage Users</a></div>
                <div class="col-md-3"><a href="manage_listings.php" class="btn btn-outline-primary w-100 py-2"><i class="bi bi-box-seam me-2"></i>Manage Listings</a></div>
                <div class="col-md-3"><a href="manage_comments.php" class="btn btn-outline-primary w-100 py-2"><i class="bi bi-chat-dots me-2"></i>Manage Comments</a></div>
                <div class="col-md-3"><a href="reports.php" class="btn btn-outline-primary w-100 py-2"><i class="bi bi-bar-chart-steps me-2"></i>Reports</a></div>
            </div>
            <div class="text-center"><a href="../../pages/logout.php" class="btn btn-danger">Logout</a></div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>