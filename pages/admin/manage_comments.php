<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();
$message = '';
$error = '';

if (isset($_GET['delete_comment'])) {
    $commentID = (int)$_GET['delete_comment'];
    $stmt = mysqli_prepare($dbc, "UPDATE pm_comments SET IsDeleted = 1 WHERE CommentID = ?");
    mysqli_stmt_bind_param($stmt, "i", $commentID);
    mysqli_stmt_execute($stmt) ? $message = "Comment removed." : $error = "DB error.";
    header("Location: manage_comments.php?msg=" . urlencode($message) . "&err=" . urlencode($error));
    exit;
}
$message = $_GET['msg'] ?? '';
$error = $_GET['err'] ?? '';

$sql = "SELECT c.CommentID, c.Content, c.CreatedAt, l.Title AS ListingTitle, l.ListingID, u.FullName AS CommenterName
        FROM pm_comments c JOIN pm_listings l ON c.ListingID = l.ListingID JOIN pm_users u ON c.UserID = u.UserID
        WHERE c.IsDeleted = 0 ORDER BY c.CreatedAt DESC";
$comments = mysqli_query($dbc, $sql)->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">Manage Comments</h2>
            <?php if($message): ?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

            <div class="mb-3">
                <a href="dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
                <a href="manage_users.php" class="btn btn-outline-primary btn-sm">Users</a>
                <a href="manage_listings.php" class="btn btn-outline-primary btn-sm">Listings</a>
                <a href="reports.php" class="btn btn-outline-primary btn-sm">Reports</a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light"><tr><th>ID</th><th>Comment</th><th>Listing</th><th>Commenter</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($comments as $row): ?>
                        <tr>
                            <td><?=$row['CommentID']?></td>
                            <td><?=htmlspecialchars(mb_strlen($row['Content'])>100?mb_substr($row['Content'],0,100).'…':$row['Content'])?></td>
                            <td><a href="../../details.php?id=<?=$row['ListingID']?>" target="_blank"><?=htmlspecialchars($row['ListingTitle'])?></a></td>
                            <td><?=htmlspecialchars($row['CommenterName'])?></td>
                            <td><?=date('d M Y H:i',strtotime($row['CreatedAt']))?></td>
                            <td><button class="btn btn-sm btn-danger delete-comment-btn" data-id="<?=$row['CommentID']?>">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="deleteCommentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Confirm Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">Are you sure you want to remove this comment?</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><a href="#" id="confirmDeleteCommentLink" class="btn btn-danger">Remove</a></div></div></div>
</div>

<script>
document.querySelectorAll('.delete-comment-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        let id = btn.dataset.id;
        let link = document.getElementById('confirmDeleteCommentLink');
        link.href = `manage_comments.php?delete_comment=${id}`;
        new bootstrap.Modal(document.getElementById('deleteCommentModal')).show();
    });
});
</script>

<?php include '../../includes/footer.php'; ?>