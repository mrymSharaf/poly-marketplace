<?php
session_start();
include "../../config/db.php";
include "../../includes/seller_guard.php";

$pageTitle = 'Creator Dashboard';
$dbc = getDB();
$userID = $_SESSION['user_id'];

$statsResult = mysqli_query($dbc, "SELECT COUNT(*) AS total,
    SUM(Status = 'published') AS published,
    SUM(Status = 'draft') AS drafts
    FROM pm_listings WHERE UserID = '$userID'");
$stats = mysqli_fetch_assoc($statsResult);

$recentResult = mysqli_query($dbc, "SELECT l.ListingID, l.Title, l.Price, l.Status, l.CreatedAt, c.CategoryName
    FROM pm_listings l
    JOIN pm_categories c ON l.CategoryID = c.CategoryID
    WHERE l.UserID = '$userID'
    ORDER BY l.CreatedAt DESC LIMIT 5");
$recentListings = [];
if ($recentResult) {
    while ($row = mysqli_fetch_assoc($recentResult)) $recentListings[] = $row;
}
mysqli_close($dbc);
?>

<?php include "../../includes/header.php"; ?>
<?php include "../../includes/navbar.php"; ?>

<div class="page-header">
    <div class="container">
        <h2 class="fw-bold mb-1"><i class="bi bi-shop-window me-2"></i>My Dashboard</h2>
        <p class="mb-0 opacity-75 small">Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? '') ?>!</p>
    </div>
</div>

<div class="container page-content pb-5">

    <!-- Stats row -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card stat-card-navy h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <i class="bi bi-collection fs-1 text-navy"></i>
                    <div>
                        <div class="fs-2 fw-bold text-navy"><?= (int)$stats['total'] ?></div>
                        <div class="text-muted small">Total Listings</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card stat-card-green h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <i class="bi bi-check-circle fs-1" style="color:#22c55e;"></i>
                    <div>
                        <div class="fs-2 fw-bold" style="color:#22c55e;"><?= (int)$stats['published'] ?></div>
                        <div class="text-muted small">Published</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card stat-card-gray h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <i class="bi bi-pencil-square fs-1 text-secondary"></i>
                    <div>
                        <div class="fs-2 fw-bold text-secondary"><?= (int)$stats['drafts'] ?></div>
                        <div class="text-muted small">Drafts</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="d-flex gap-3 mb-4 flex-wrap">
        <a href="create_listing.php" class="btn btn-navy btn-lg">
            <i class="bi bi-plus-circle me-2"></i>Create New Listing
        </a>
        <a href="my_listings.php" class="btn btn-outline-navy btn-lg">
            <i class="bi bi-list-ul me-2"></i>View All Listings
        </a>
    </div>

    <!-- Recent listings table -->
    <div class="card pm-table-card">
        <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
            <h5 class="fw-bold text-navy mb-0">
                <i class="bi bi-clock-history me-2"></i>Recent Listings
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentListings)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 icon-empty-state d-block mb-3"></i>
                    <p class="text-muted mb-3">No listings yet.</p>
                    <a href="create_listing.php" class="btn btn-navy rounded-pill px-4">Create your first listing</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Title</th>
                                <th>Category</th>
                                <th>Price (BHD)</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentListings as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?= htmlspecialchars($row['Title']) ?></td>
                                    <td><span class="category-badge"><?= htmlspecialchars($row['CategoryName']) ?></span></td>
                                    <td class="text-navy fw-semibold"><?= number_format($row['Price'], 3) ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?= $row['Status'] === 'published' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ucfirst($row['Status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?= date('d M Y', strtotime($row['CreatedAt'])) ?></td>
                                    <td class="pe-4">
                                        <div class="d-flex gap-2">
                                            <a href="edit_listing.php?id=<?= urlencode($row['ListingID']) ?>"
                                               class="btn btn-sm btn-outline-navy" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="toggle_status.php" class="d-inline">
                                                <input type="hidden" name="listing_id" value="<?= $row['ListingID'] ?>">
                                                <input type="hidden" name="redirect" value="dashboard.php">
                                                <button type="submit"
                                                    class="btn btn-sm <?= $row['Status'] === 'published' ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                                    title="<?= $row['Status'] === 'published' ? 'Unpublish' : 'Publish' ?>">
                                                    <i class="bi bi-<?= $row['Status'] === 'published' ? 'eye-slash' : 'eye' ?>"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end px-4 py-3 border-top">
                    <a href="my_listings.php" class="btn btn-outline-navy btn-sm rounded-pill px-3">
                        View all listings <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include "../../includes/footer.php"; ?>
