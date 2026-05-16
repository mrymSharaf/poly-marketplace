<?php
// Prevent session initialization notices if a session block is already active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include files using normal relative paths
include "../../config/db.php";
include "../../includes/seller_guard.php";

$pageTitle = 'My Listings';
$dbc = getDB();
$userID = $_SESSION['user_id'];

// Whitelist configuration to check active filter parameters
$filter = (isset($_GET['filter']) && in_array($_GET['filter'], ['published', 'draft'])) ? $_GET['filter'] : 'all';

// Pagination layout variables
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Append a status constraint to the SQL string depending on selected tab selection
$statusClause = "";
if ($filter != 'all') {
    $statusClause = " AND l.Status = '$filter'";
}

// Query the total records matching the active filter parameters for pagination tracking
$countQuery = "SELECT COUNT(*) AS total FROM pm_listings l WHERE l.UserID = '$userID' $statusClause";
$countResult = mysqli_query($dbc, $countQuery);
$countRow = mysqli_fetch_assoc($countResult);
$totalRecords = $countRow['total'] ?? 0;

$totalPages = max(1, (int)ceil($totalRecords / $perPage));

// Pull the exact limited row segment for current paginated viewport rendering
$listings = [];
$query = "SELECT l.ListingID, l.Title, l.Price, l.Status, l.CreatedAt, l.ImageURL, c.CategoryName
          FROM pm_listings l
          JOIN pm_categories c ON l.CategoryID = c.CategoryID
          WHERE l.UserID = '$userID' $statusClause
          ORDER BY l.CreatedAt DESC
          LIMIT $perPage OFFSET $offset";

$result = mysqli_query($dbc, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $listings[] = $row;
    }
}
mysqli_close($dbc);

$successMsg = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$errorMsg   = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// Direct structural function to compile configuration query parameters for page links
function pageLink($p, $filter)
{
    return '?page=' . $p . ($filter != 'all' ? '&filter=' . urlencode($filter) : '');
}
?>

<?php include "../../includes/header.php"; ?>
<?php include "../../includes/navbar.php"; ?>

<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="mb-1"><i class="bi bi-list-ul me-2"></i>My Listings</h2>
            <p class="mb-0" style="opacity:.75;"><?php echo $totalRecords; ?> listing<?php echo ($totalRecords != 1) ? 's' : ''; ?></p>
        </div>
        <a href="create_listing.php" class="btn btn-accent btn-lg">
            <i class="bi bi-plus-circle me-2"></i>Create New
        </a>
    </div>
</div>

<div class="container pb-5">

    <?php if ($successMsg != ''): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $successMsg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg != ''): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $errorMsg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a href="my_listings.php" class="btn <?php echo ($filter == 'all') ? 'btn-accent' : 'btn-outline-secondary'; ?>">All</a>
        <a href="?filter=published" class="btn <?php echo ($filter == 'published') ? 'btn-accent' : 'btn-outline-secondary'; ?>">Published</a>
        <a href="?filter=draft" class="btn <?php echo ($filter == 'draft') ? 'btn-accent' : 'btn-outline-secondary'; ?>">Drafts</a>
    </div>

    <div class="card card-pm">
        <div class="card-body p-0">
            <?php if (empty($listings)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <p>No listings found. <a href="create_listing.php">Create your first!</a></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width:70px">Image</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Price (BD)</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listings as $row): ?>
                                <tr>
                                    <td class="ps-4">
                                        <?php if ($row['ImageURL'] != ''): ?>
                                            <img src="<?php echo htmlspecialchars('../../' . $row['ImageURL']); ?>"
                                                alt="" class="rounded"
                                                style="width:52px; height:52px; object-fit:cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:52px; height:52px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['Title']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($row['CategoryName']); ?></td>
                                    <td><?php echo number_format($row['Price'], 3); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($row['Status'] == 'published') ? 'badge-published' : 'badge-draft'; ?>">
                                            <?php echo ucfirst($row['Status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?php echo date('d M Y', strtotime($row['CreatedAt'])); ?></td>
                                    <td class="pe-4">
                                        <div class="d-flex gap-2">

                                            <a href="edit_listing.php?id=<?php echo urlencode($row['ListingID']); ?>"
                                                class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <form method="POST" action="toggle_status.php" class="d-inline">
                                                <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($row['ListingID']); ?>">
                                                <input type="hidden" name="redirect" value="my_listings.php<?php echo ($filter != 'all') ? '?filter=' . urlencode($filter) : ''; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo ($row['Status'] == 'published') ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo ($row['Status'] == 'published') ? 'Unpublish' : 'Publish'; ?>">
                                                    <i class="bi bi-<?php echo ($row['Status'] == 'published') ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                            </form>

                                            <form method="POST" action="delete_listing.php" class="d-inline"
                                                onsubmit="return confirm('Delete \'<?php echo htmlspecialchars(addslashes($row['Title'])); ?>\'? This cannot be undone.')">
                                                <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($row['ListingID']); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-center py-4">
                        <nav>
                            <ul class="pagination mb-0">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo pageLink($page - 1, $filter); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo pageLink($i, $filter); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo pageLink($page + 1, $filter); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

</div>

<?php include "../../includes/footer.php"; ?>