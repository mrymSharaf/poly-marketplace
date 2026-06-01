<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';
require_once '../../includes/pagination.php';

$dbc          = getDB();
$message      = $_GET['msg'] ?? '';
$error        = $_GET['err'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';

// ── Pagination setup ──────────────────────────────────────────────────────────
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));

$whereClause = '';
if ($statusFilter !== 'all') {
    $whereClause = " WHERE l.Status = '" . mysqli_real_escape_string($dbc, $statusFilter) . "'";
}

// COUNT total matching records
$countSQL    = "SELECT COUNT(*) AS total
                FROM pm_listings l
                JOIN pm_users u    ON l.UserID = u.UserID
                JOIN pm_categories c ON l.CategoryID = c.CategoryID
                $whereClause";
$countRow    = mysqli_query($dbc, $countSQL)->fetch_assoc();
$totalRecords = (int)($countRow['total'] ?? 0);
$totalPages   = max(1, (int)ceil($totalRecords / $perPage));
$page         = min($page, $totalPages);
$offset       = ($page - 1) * $perPage;

// Fetch current page of listings
$sql = "SELECT l.ListingID, l.Title, l.Price, l.Status, l.CreatedAt,
               u.FullName AS CreatorName, c.CategoryName
        FROM pm_listings l
        JOIN pm_users u    ON l.UserID = u.UserID
        JOIN pm_categories c ON l.CategoryID = c.CategoryID
        $whereClause
        ORDER BY l.CreatedAt DESC
        LIMIT $perPage OFFSET $offset";
$listings = mysqli_query($dbc, $sql)->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="page-header pb-3">
    <div class="container">
        <h2 class="fw-bold mb-1"><i class="bi bi-box-seam me-2"></i>Manage Listings</h2>
        <p class="mb-2 opacity-75 small">
            <?= $totalRecords ?> listing<?= $totalRecords !== 1 ? 's' : '' ?> shown
            <?php if ($totalPages > 1): ?>
                &nbsp;&middot;&nbsp; Page <?= $page ?> of <?= $totalPages ?>
            <?php endif; ?>
        </p>
        <div class="d-flex gap-2 flex-wrap align-items-center justify-content-between pt-1">
            <div class="d-flex gap-2 flex-wrap">
                <a href="dashboard.php"       class="btn btn-sm btn-outline-light rounded-pill"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
                <a href="manage_users.php"    class="btn btn-sm btn-outline-light rounded-pill">Users</a>
                <a href="manage_comments.php" class="btn btn-sm btn-outline-light rounded-pill">Comments</a>
                <a href="reports.php"          class="btn btn-sm btn-outline-light rounded-pill">Reports</a>
            </div>
            <div class="btn-group" role="group">
                <a href="?status=all"       class="btn btn-sm <?= $statusFilter == 'all'       ? 'btn-light' : 'btn-outline-light' ?>">All</a>
                <a href="?status=published" class="btn btn-sm <?= $statusFilter == 'published' ? 'btn-light' : 'btn-outline-light' ?>">Published</a>
                <a href="?status=draft"     class="btn btn-sm <?= $statusFilter == 'draft'     ? 'btn-light' : 'btn-outline-light' ?>">Draft</a>
                <a href="?status=removed"   class="btn btn-sm <?= $statusFilter == 'removed'   ? 'btn-light' : 'btn-outline-light' ?>">Removed</a>
            </div>
        </div>
    </div>
</div>

<div class="container page-content pb-5">

    <?php if ($message): ?>
        <div class="alert alert-success border-0 rounded-3 small mb-3">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger border-0 rounded-3 small mb-3">
            <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card pm-table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Title</th>
                        <th>Creator</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listings)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No listings found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($listings as $row): ?>
                            <tr id="listing-<?= $row['ListingID'] ?>">
                                <td class="ps-4 fw-semibold"><?= htmlspecialchars($row['Title']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle" style="width:28px;height:28px;font-size:.7rem;">
                                            <?= strtoupper(substr($row['CreatorName'], 0, 1)) ?>
                                        </div>
                                        <span class="small"><?= htmlspecialchars($row['CreatorName']) ?></span>
                                    </div>
                                </td>
                                <td><span class="category-badge"><?= htmlspecialchars($row['CategoryName']) ?></span></td>
                                <td class="fw-semibold text-navy"><?= number_format($row['Price'], 3) ?> BHD</td>
                                <td>
                                    <?php
                                    $badgeColor = match($row['Status']) {
                                        'published' => 'bg-success',
                                        'draft'     => 'bg-warning text-dark',
                                        default     => 'bg-danger',
                                    };
                                    ?>
                                    <span class="badge rounded-pill <?= $badgeColor ?>">
                                        <?= ucfirst($row['Status']) ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($row['CreatedAt'])) ?></td>
                                <td class="pe-4">
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="../../details.php?id=<?= $row['ListingID'] ?>"
                                           class="btn btn-sm btn-outline-navy" target="_blank" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($row['Status'] !== 'removed'): ?>
                                            <button class="btn btn-sm btn-outline-danger action-btn"
                                                    data-id="<?= $row['ListingID'] ?>"
                                                    data-action="delete"
                                                    data-status="<?= $statusFilter ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success action-btn"
                                                    data-id="<?= $row['ListingID'] ?>"
                                                    data-action="restore"
                                                    data-status="<?= $statusFilter ?>">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($row['Status'] === 'draft'): ?>
                                            <button class="btn btn-sm btn-outline-success action-btn"
                                                    data-id="<?= $row['ListingID'] ?>"
                                                    data-action="publish"
                                                    data-status="<?= $statusFilter ?>">
                                                <i class="bi bi-send"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
        renderPagination($page, $totalPages, function(int $p) use ($statusFilter): string {
            $q = ['page' => $p];
            if ($statusFilter !== 'all') $q['status'] = $statusFilter;
            return '?' . http_build_query($q);
        });
        ?>
    </div>
</div>

<!-- Confirm modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmActionLink" class="btn btn-navy rounded-pill">Proceed</a>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id, action = btn.dataset.action, status = btn.dataset.status;
        const label = action === 'delete' ? 'remove' : action === 'restore' ? 'restore' : 'publish';
        document.getElementById('modalBody').textContent = `Are you sure you want to ${label} this listing?`;
        document.getElementById('confirmActionLink').href = `listing_action.php?action=${action}&id=${id}&status=${status}`;
        new bootstrap.Modal(document.getElementById('actionModal')).show();
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
