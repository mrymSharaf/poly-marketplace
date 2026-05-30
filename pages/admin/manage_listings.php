<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();
$message = $_GET['msg'] ?? '';
$error = $_GET['err'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';

$sql = "SELECT l.ListingID, l.Title, l.Price, l.Status, l.CreatedAt, u.FullName AS CreatorName, c.CategoryName
        FROM pm_listings l 
        JOIN pm_users u ON l.UserID = u.UserID 
        JOIN pm_categories c ON l.CategoryID = c.CategoryID";
if ($statusFilter != 'all') {
    $sql .= " WHERE l.Status = '" . mysqli_real_escape_string($dbc, $statusFilter) . "'";
}
$sql .= " ORDER BY l.CreatedAt DESC";
$listings = mysqli_query($dbc, $sql)->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">Manage Listings</h2>
            <?php if($message): ?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

            <!-- Navigation to other admin sections -->
            <div class="mb-3">
                <a href="dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
                <a href="manage_users.php" class="btn btn-outline-primary btn-sm">Users</a>
                <a href="manage_comments.php" class="btn btn-outline-primary btn-sm">Comments</a>
                <a href="reports.php" class="btn btn-outline-primary btn-sm">Reports</a>
            </div>

            <!-- Status filter buttons (right-aligned, no label) -->
            <div class="mb-4 pb-2 border-bottom text-end">
                <div class="btn-group" role="group">
                    <a href="?status=all" class="btn btn-sm <?=$statusFilter=='all'?'btn-primary':'btn-outline-secondary'?>">All</a>
                    <a href="?status=draft" class="btn btn-sm <?=$statusFilter=='draft'?'btn-warning':'btn-outline-secondary'?>">Draft</a>
                    <a href="?status=published" class="btn btn-sm <?=$statusFilter=='published'?'btn-success':'btn-outline-secondary'?>">Published</a>
                    <a href="?status=removed" class="btn btn-sm <?=$statusFilter=='removed'?'btn-danger':'btn-outline-secondary'?>">Removed</a>
                </div>
            </div>

            <!-- Listings table -->
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
                        <?php foreach($listings as $row): ?>
                        <tr id="listing-<?=$row['ListingID']?>">
                            <td><?=$row['ListingID']?></td>
                            <td><?=htmlspecialchars($row['Title'])?></td>
                            <td><?=htmlspecialchars($row['CreatorName'])?></td>
                            <td><?=htmlspecialchars($row['CategoryName'])?></td>
                            <td><?=number_format($row['Price'],3)?> BHD</td>
                            <td><span class="badge bg-<?=$row['Status']=='published'?'success':($row['Status']=='draft'?'warning':'danger')?>"><?=$row['Status']?></span></td>
                            <td><?=date('d M Y',strtotime($row['CreatedAt']))?></td>
                            <td>
                                <a href="../../details.php?id=<?=$row['ListingID']?>" class="btn btn-sm btn-info" target="_blank">View</a>
                                <?php if($row['Status'] != 'removed'): ?>
                                    <button class="btn btn-sm btn-danger action-btn" data-id="<?=$row['ListingID']?>" data-action="delete" data-status="<?=$statusFilter?>">Remove</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary action-btn" data-id="<?=$row['ListingID']?>" data-action="restore" data-status="<?=$statusFilter?>">Restore</button>
                                <?php endif; ?>
                                <?php if($row['Status'] == 'draft'): ?>
                                    <button class="btn btn-sm btn-success action-btn" data-id="<?=$row['ListingID']?>" data-action="publish" data-status="<?=$statusFilter?>">Publish</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for confirmation -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmActionLink" class="btn btn-primary">Proceed</a>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        let id = btn.dataset.id;
        let action = btn.dataset.action;
        let status = btn.dataset.status;
        let text = action === 'delete' ? 'remove' : (action === 'restore' ? 'restore' : 'publish');
        document.getElementById('modalBody').innerText = `Are you sure you want to ${text} this listing?`;
        let link = document.getElementById('confirmActionLink');
        link.href = `listing_action.php?action=${action}&id=${id}&status=${status}`;
        new bootstrap.Modal(document.getElementById('actionModal')).show();
    });
});
</script>

<?php include '../../includes/footer.php'; ?>