<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();
$message = '';
$error   = '';

if (isset($_GET['delete_comment'])) {
    $commentID = (int)$_GET['delete_comment'];
    $stmt = mysqli_prepare($dbc, "UPDATE pm_comments SET IsDeleted = 1 WHERE CommentID = ?");
    mysqli_stmt_bind_param($stmt, "i", $commentID);
    mysqli_stmt_execute($stmt) ? $message = "Comment removed." : $error = "DB error.";
    header("Location: manage_comments.php?msg=" . urlencode($message) . "&err=" . urlencode($error));
    exit;
}
$message = $_GET['msg'] ?? '';
$error   = $_GET['err'] ?? '';

$sql = "SELECT c.CommentID, c.Content, c.CreatedAt, l.Title AS ListingTitle, l.ListingID, u.FullName AS CommenterName
        FROM pm_comments c
        JOIN pm_listings l ON c.ListingID = l.ListingID
        JOIN pm_users u ON c.UserID = u.UserID
        WHERE c.IsDeleted = 0
        ORDER BY c.CreatedAt DESC";
$comments = mysqli_query($dbc, $sql)->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="page-header pb-3">
    <div class="container">
        <h2 class="fw-bold mb-1"><i class="bi bi-chat-dots me-2"></i>Manage Comments</h2>
        <p class="mb-2 opacity-75 small"><?= count($comments) ?> active comment<?= count($comments) !== 1 ? 's' : '' ?></p>
        <div class="d-flex gap-2 flex-wrap pt-1">
            <a href="dashboard.php"       class="btn btn-sm btn-outline-light rounded-pill"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
            <a href="manage_users.php"    class="btn btn-sm btn-outline-light rounded-pill">Users</a>
            <a href="manage_listings.php" class="btn btn-sm btn-outline-light rounded-pill">Listings</a>
            <a href="reports.php"          class="btn btn-sm btn-outline-light rounded-pill">Reports</a>
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
                        <th class="ps-4">Commenter</th>
                        <th>Comment</th>
                        <th>Listing</th>
                        <th>Date</th>
                        <th class="pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($comments)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No comments found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($comments as $row): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle" style="width:30px;height:30px;font-size:.72rem;">
                                            <?= strtoupper(substr($row['CommenterName'], 0, 1)) ?>
                                        </div>
                                        <span class="small fw-semibold"><?= htmlspecialchars($row['CommenterName']) ?></span>
                                    </div>
                                </td>
                                <td class="text-muted small" style="max-width:260px;">
                                    <?= htmlspecialchars(mb_strlen($row['Content']) > 100 ? mb_substr($row['Content'], 0, 100) . '…' : $row['Content']) ?>
                                </td>
                                <td>
                                    <a href="../../details.php?id=<?= $row['ListingID'] ?>" target="_blank"
                                       class="text-navy fw-semibold small text-decoration-none">
                                        <?= htmlspecialchars($row['ListingTitle']) ?>
                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                    </a>
                                </td>
                                <td class="text-muted small"><?= date('d M Y, H:i', strtotime($row['CreatedAt'])) ?></td>
                                <td class="pe-4">
                                    <button class="btn btn-sm btn-outline-danger delete-comment-btn"
                                            data-id="<?= $row['CommentID'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Confirm modal -->
<div class="modal fade" id="deleteCommentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy">Remove Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-muted">Are you sure you want to remove this comment?</div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteCommentLink" class="btn btn-danger rounded-pill">Remove</a>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.delete-comment-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('confirmDeleteCommentLink').href = `manage_comments.php?delete_comment=${btn.dataset.id}`;
        new bootstrap.Modal(document.getElementById('deleteCommentModal')).show();
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
