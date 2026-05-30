<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();
$message = '';
$error = '';

// Handle soft delete (set IsDeleted = 1)
if (isset($_GET['delete_comment'])) {
    $commentID = (int)$_GET['delete_comment'];
    $stmt = mysqli_prepare($dbc, "UPDATE pm_comments SET IsDeleted = 1 WHERE CommentID = ?");
    mysqli_stmt_bind_param($stmt, "i", $commentID);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Comment removed.";
    } else {
        $error = "Database error: " . mysqli_error($dbc);
    }
}

// Fetch comments that are not deleted, join with listings and users
$sql = "SELECT c.CommentID, c.Content, c.CreatedAt, c.IsDeleted,
               l.Title AS ListingTitle, l.ListingID,
               u.FullName AS CommenterName
        FROM pm_comments c
        JOIN pm_listings l ON c.ListingID = l.ListingID
        JOIN pm_users u ON c.UserID = u.UserID
        WHERE c.IsDeleted = 0
        ORDER BY c.CreatedAt DESC";
$result = mysqli_query($dbc, $sql);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Manage Comments</h2>
                    <p class="text-center">View and remove inappropriate comments</p>

                    <?php if ($message): ?>
                        <div class="alert alert-success text-center"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <a href="dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
                        <a href="manage_users.php" class="btn btn-outline-primary btn-sm">Users</a>
                        <a href="manage_listings.php" class="btn btn-outline-primary btn-sm">Listings</a>
                        <a href="reports.php" class="btn btn-outline-primary btn-sm">Reports</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Comment</th>
                                    <th>Listing</th>
                                    <th>Commenter</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="6" class="text-center">No comments found.<?php else: ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $row['CommentID']; ?></td>
                                            <td>
                                                <?php 
                                                // Truncate comment content safely (no stray PHP tags)
                                                $commentText = htmlspecialchars($row['Content']);
                                                if (mb_strlen($commentText) > 100) {
                                                    echo mb_substr($commentText, 0, 100) . '…';
                                                } else {
                                                    echo $commentText;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="../../details.php?id=<?php echo $row['ListingID']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars($row['ListingTitle']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['CommenterName']); ?></td>
                                            <td><?php echo date('d M Y H:i', strtotime($row['CreatedAt'])); ?></td>
                                            <td>
                                                <a href="?delete_comment=<?php echo $row['CommentID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove this comment?')">Remove</a>
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