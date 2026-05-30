<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();
$message = '';
$error = '';

// Handle status change (delete, restore, publish)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $listingID = (int)$_GET['id'];
    $action = $_GET['action'];
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

    if ($action == 'delete') {
        $newStatus = 'removed';
        $msgText = 'Listing removed.';
    } elseif ($action == 'restore') {
        $newStatus = 'draft';
        $msgText = 'Listing restored to draft.';
    } elseif ($action == 'publish') {
        $newStatus = 'published';
        $msgText = 'Listing published.';
    } else {
        $error = 'Invalid action.';
    }

    if (!isset($error)) {
        $stmt = mysqli_prepare($dbc, "UPDATE pm_listings SET Status = ? WHERE ListingID = ?");
        mysqli_stmt_bind_param($stmt, "si", $newStatus, $listingID);
        if (mysqli_stmt_execute($stmt)) {
            $message = $msgText;
        } else {
            $error = 'Database error: ' . mysqli_error($dbc);
        }
    }

    // Redirect to clear the URL parameters and show message
    $redirect = "manage_listings.php?status=" . urlencode($statusFilter);
    if ($message) $redirect .= "&msg=" . urlencode($message);
    if ($error) $redirect .= "&error=" . urlencode($error);
    header("Location: $redirect");
    exit;
}

// Get message/error from redirect
$message = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Filter by status
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
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

<link rel="stylesheet" href="../../assets/css/auth.css">

<main class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card auth-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h2 class="auth-title">Manage Listings</h2>
                            <p class="auth-subtitle">View, filter, and manage all listings</p>
                        </div>

                        <?php if ($message): ?>
                            <div class="alert alert-success text-center"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <div class="mb-3 d-flex justify-content-between flex-wrap gap-2">
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
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Creator</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) == 0): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No listings found.</td>
                                        </tr>
                                    <?php else: ?>
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
                                                <td>
                                                    <a href="../../details.php?id=<?php echo $row['ListingID']; ?>" class="btn btn-sm btn-info" target="_blank">View</a>
                                                    <?php if ($row['Status'] != 'removed'): ?>
                                                        <a href="?action=delete&id=<?php echo $row['ListingID']; ?>&status=<?php echo $statusFilter; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove this listing?')">Remove</a>
                                                    <?php else: ?>
                                                        <a href="?action=restore&id=<?php echo $row['ListingID']; ?>&status=<?php echo $statusFilter; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Restore this listing?')">Restore</a>
                                                    <?php endif; ?>
                                                    <?php if ($row['Status'] == 'draft'): ?>
                                                        <a href="?action=publish&id=<?php echo $row['ListingID']; ?>&status=<?php echo $statusFilter; ?>" class="btn btn-sm btn-success" onclick="return confirm('Publish this listing?')">Publish</a>
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
</main>

<?php include '../../includes/footer.php'; ?>