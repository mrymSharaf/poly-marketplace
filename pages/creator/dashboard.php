<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/seller_guard.php';

$pageTitle = 'Seller Dashboard';
$dbc      = getDB();
$userID   = $_SESSION['user_id'];

// Stats
$stmt = $dbc->prepare(
    "SELECT COUNT(*) AS total,
            SUM(Status = 'published') AS published,
            SUM(Status = 'draft')     AS drafts
     FROM pm_listings WHERE UserID = ?"
);
$stmt->bind_param('s', $userID);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 5 most recent listings
$stmt = $dbc->prepare(
    "SELECT l.ListingID, l.Title, l.Price, l.Status, l.CreatedAt, c.CategoryName
     FROM pm_listings l
     JOIN pm_categories c ON l.CategoryID = c.CategoryID
     WHERE l.UserID = ?
     ORDER BY l.CreatedAt DESC
     LIMIT 5"
);
$stmt->bind_param('s', $userID);
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
mysqli_close($dbc);
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>

<div class="page-header">
    <div class="container">
        <h2 class="mb-1"><i class="bi bi-shop-window me-2"></i>My Stall Dashboard</h2>
        <p class="mb-0" style="opacity:.75;">Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? '') ?>!</p>
    </div>
</div>

<div class="container pb-5">

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-pm h-100" style="border-left:4px solid var(--pm-accent)">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-collection fs-1 text-primary"></i>
                    <div>
                        <div class="fs-2 fw-bold"><?= (int)$stats['total'] ?></div>
                        <div class="text-muted small">Total Listings</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-pm h-100" style="border-left:4px solid #22c55e">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-check-circle fs-1" style="color:#22c55e"></i>
                    <div>
                        <div class="fs-2 fw-bold"><?= (int)$stats['published'] ?></div>
                        <div class="text-muted small">Published</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-pm h-100" style="border-left:4px solid #94a3b8">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-pencil-square fs-1 text-secondary"></i>
                    <div>
                        <div class="fs-2 fw-bold"><?= (int)$stats['drafts'] ?></div>
                        <div class="text-muted small">Drafts</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="d-flex gap-3 mb-4 flex-wrap">
        <a href="create_listing.php" class="btn btn-accent btn-lg">
            <i class="bi bi-plus-circle me-2"></i>Create New Listing
        </a>
        <a href="my_listings.php" class="btn btn-outline-primary btn-lg">
            <i class="bi bi-list-ul me-2"></i>View All Listings
        </a>
    </div>

    <!-- Recent listings -->
    <div class="card card-pm">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
            <h5 class="fw-semibold mb-0">Recent Listings</h5>
        </div>
        <div class="card-body px-4">
            <?php if (empty($recent)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <p>No listings yet. <a href="create_listing.php">Create your first!</a></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Price (BD)</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recent as $row): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($row['Title']) ?></td>
                                <td><?= htmlspecialchars($row['CategoryName']) ?></td>
                                <td><?= number_format($row['Price'], 3) ?></td>
                                <td>
                                    <span class="badge <?= $row['Status'] === 'published' ? 'badge-published' : 'badge-draft' ?>">
                                        <?= ucfirst($row['Status']) ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($row['CreatedAt'])) ?></td>
                                <td>
                                    <a href="edit_listing.php?id=<?= urlencode($row['ListingID']) ?>"
                                       class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="toggle_status.php" class="d-inline">
                                        <input type="hidden" name="listing_id" value="<?= htmlspecialchars($row['ListingID']) ?>">
                                        <input type="hidden" name="redirect"    value="dashboard.php">
                                        <button type="submit"
                                                class="btn btn-sm <?= $row['Status'] === 'published' ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                                title="<?= $row['Status'] === 'published' ? 'Unpublish' : 'Publish' ?>">
                                            <i class="bi bi-<?= $row['Status'] === 'published' ? 'eye-slash' : 'eye' ?>"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end">
                    <a href="my_listings.php" class="btn btn-link text-decoration-none">
                        View all listings <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
