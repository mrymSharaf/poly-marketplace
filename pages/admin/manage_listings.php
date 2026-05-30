<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();
$message = '';
$error = '';

// Handle POST actions (no redirect)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_listing'])) {
        $listingID = (int)$_POST['listing_id'];
        $stmt = mysqli_prepare($dbc, "UPDATE pm_listings SET Status = 'removed' WHERE ListingID = ?");
        mysqli_stmt_bind_param($stmt, "i", $listingID);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Listing removed.";
        } else {
            $error = "DB error: " . mysqli_error($dbc);
        }
    } elseif (isset($_POST['restore_listing'])) {
        $listingID = (int)$_POST['listing_id'];
        $stmt = mysqli_prepare($dbc, "UPDATE pm_listings SET Status = 'draft' WHERE ListingID = ?");
        mysqli_stmt_bind_param($stmt, "i", $listingID);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Listing restored to draft.";
        } else {
            $error = "DB error: " . mysqli_error($dbc);
        }
    } elseif (isset($_POST['publish_listing'])) {
        $listingID = (int)$_POST['listing_id'];
        $stmt = mysqli_prepare($dbc, "UPDATE pm_listings SET Status = 'published' WHERE ListingID = ?");
        mysqli_stmt_bind_param($stmt, "i", $listingID);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Listing published.";
        } else {
            $error = "DB error: " . mysqli_error($dbc);
        }
    }
}

// Filter by status (preserve filter after POST using hidden input)
$statusFilter = isset($_GET['status']) ? $_GET['status'] : (isset($_POST['status_filter']) ? $_POST['status_filter'] : 'all');
$sql = "SELECT l.ListingID, l.Title, l.Price, l.Status, l.CreatedAt,
               u.FullName AS CreatorName, c.CategoryName
        FROM pm_listings l
        JOIN pm_users u ON l.UserID = u.UserID
        JOIN pm_categories c ON l.CategoryID = c.CategoryID";
if ($statusFilter != 'all') {
    $sql .= " WHERE l.Status = '" . mysqli_real_escape_string($dbc, $statusFilter) . "'";
}
$sql .= " ORDER BY l.CreatedAt DESC";
$result = mysqli_query($dbc, $sql);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Manage Listings</h2>

                    <?php if ($message): ?>
                        <div class="alert alert-success text-center"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between flex-wrap mb-3">
                        <div>
                            <a href="dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
                            <a href="manage_users.php" class="btn btn-outline-primary btn-sm">Users</a>
                            <a href="manage_comments.php" class="btn btn-outline-primary btn-sm">Comments</a>
                            <a href="reports.php" class="btn btn-outline-primary btn-sm">Reports</a>
                        </div>
                        <div>
                            <a href="?status=all" class="btn btn-sm <?php echo $statusFilter == 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                            <a href="?status=draft" class="btn btn-sm <?php echo $statusFilter == 'draft' ? 'btn-warning' : 'btn-outline-secondary'; ?>">Draft</a>
                            <a href="?status=published" class="btn btn-sm <?php echo $statusFilter == 'published' ? 'btn-success' : 'btn-outline-secondary'; ?>">Published</a>
                            <a href="?status=removed" class="btn btn-sm <?php echo $statusFilter == 'removed' ? 'btn-danger' : 'btn-outline-secondary'; ?>">Removed</a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th><th>Title</th><th>Creator</th><th>Category</th><th>Price</th><th>Status</th><th>Created</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="8" class="text-center">No listings found.<?php else: ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $row['ListingID']; ?></td>
                                            <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                            <td><?php echo htmlspecialchars($row['CreatorName']); ?></td>
                                            <td><?php echo htmlspecialchars($row['CategoryName']); ?></td>
                                            <td><?php echo number_format($row['Price'], 3); ?> BHD</td>
                                            <td>
                                                <?php
                                                $badgeClass = '';
                                                if ($row['Status'] == 'published') $badgeClass = 'bg-success';
                                                elseif ($row['Status'] == 'draft') $badgeClass = 'bg-warning';
                                                else $badgeClass = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $row['Status']; ?></span>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($row['CreatedAt'])); ?></td>
                                            <td style="white-space: nowrap;">
                                                <a href="../../details.php?id=<?php echo $row['ListingID']; ?>" class="btn btn-sm btn-info" target="_blank">View</a>
                                                <?php if ($row['Status'] != 'removed'): ?>
                                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Remove this listing?')">
                                                        <input type="hidden" name="listing_id" value="<?php echo $row['ListingID']; ?>">
                                                        <button type="submit" name="delete_listing" class="btn btn-sm btn-danger">Remove</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Restore this listing?')">
                                                        <input type="hidden" name="listing_id" value="<?php echo $row['ListingID']; ?>">
                                                        <button type="submit" name="restore_listing" class="btn btn-sm btn-secondary">Restore</button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($row['Status'] == 'draft'): ?>
                                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Publish this listing?')">
                                                        <input type="hidden" name="listing_id" value="<?php echo $row['ListingID']; ?>">
                                                        <button type="submit" name="publish_listing" class="btn btn-sm btn-success">Publish</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>