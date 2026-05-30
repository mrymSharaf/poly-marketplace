<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();
$message = '';
$error = '';

// Role update (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $userID = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    $stmt = mysqli_prepare($dbc, "UPDATE pm_users SET Role = ? WHERE UserID = ?");
    mysqli_stmt_bind_param($stmt, "si", $newRole, $userID);
    mysqli_stmt_execute($stmt) ? $message = "Role updated." : $error = "Database error.";
    header("Location: manage_users.php?" . http_build_query(array_merge($_GET, ['msg' => $message, 'err' => $error])));
    exit;
}

// Delete user (via GET with modal confirmation)
if (isset($_GET['delete_user'])) {
    $userID = (int)$_GET['delete_user'];
    if ($userID != $_SESSION['user_id']) {
        $stmt = mysqli_prepare($dbc, "DELETE FROM pm_users WHERE UserID = ?");
        mysqli_stmt_bind_param($stmt, "i", $userID);
        mysqli_stmt_execute($stmt);
        $message = "User deleted.";
    } else {
        $error = "Cannot delete yourself.";
    }
    header("Location: manage_users.php?" . http_build_query(array_merge($_GET, ['msg' => $message, 'err' => $error])));
    exit;
}

// Capture messages
$message = $_GET['msg'] ?? '';
$error = $_GET['err'] ?? '';

// Search/filter/sort
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role_filter'] ?? 'all';
$sortBy = $_GET['sort_by'] ?? 'userid_asc';
$where = "1=1";
$params = []; $types = "";
if ($search) { $where .= " AND (FullName LIKE ? OR Email LIKE ?)"; $like = "%$search%"; $params[] = $like; $params[] = $like; $types .= "ss"; }
if ($roleFilter != 'all') { $where .= " AND Role = ?"; $params[] = $roleFilter; $types .= "s"; }
$order = match($sortBy) {
    'userid_desc' => "UserID DESC", 'name_asc' => "FullName ASC", 'name_desc' => "FullName DESC",
    'role_asc' => "Role ASC", 'role_desc' => "Role DESC", default => "UserID ASC"
};
$sql = "SELECT UserID, FullName, Email, Role FROM pm_users WHERE $where ORDER BY $order";
$stmt = mysqli_prepare($dbc, $sql);
if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-body">
            <h2 class="card-title text-center mb-4"><i class="bi bi-people-fill me-2"></i>Manage Users</h2>
            <?php if($message): ?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

            <div class="mb-3">
                <a href="dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
                <a href="manage_listings.php" class="btn btn-outline-primary btn-sm">Listings</a>
                <a href="manage_comments.php" class="btn btn-outline-primary btn-sm">Comments</a>
                <a href="reports.php" class="btn btn-outline-primary btn-sm">Reports</a>
            </div>

            <form method="get" class="row g-2 mb-4">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search name/email" value="<?=htmlspecialchars($search)?>">
                </div>
                <div class="col-md-3">
                    <select name="role_filter" class="form-select">
                        <?php foreach(['all'=>'All roles','viewer'=>'Viewer','creator'=>'Creator','admin'=>'Admin'] as $val=>$label): ?>
                            <option value="<?=$val?>" <?=$roleFilter==$val?'selected':''?>><?=$label?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="sort_by" class="form-select">
                        <option value="userid_asc" <?=$sortBy=='userid_asc'?'selected':''?>>ID ↑</option>
                        <option value="userid_desc" <?=$sortBy=='userid_desc'?'selected':''?>>ID ↓</option>
                        <option value="name_asc" <?=$sortBy=='name_asc'?'selected':''?>>Name A-Z</option>
                        <option value="name_desc" <?=$sortBy=='name_desc'?'selected':''?>>Name Z-A</option>
                        <option value="role_asc" <?=$sortBy=='role_asc'?'selected':''?>>Role A-Z</option>
                        <option value="role_desc" <?=$sortBy=='role_desc'?'selected':''?>>Role Z-A</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Apply</button>
                    <a href="manage_users.php" class="btn btn-outline-secondary flex-fill">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr id="user-<?=$user['UserID']?>">
                            <td><?=$user['UserID']?></td>
                            <td><?=htmlspecialchars($user['FullName'])?></td>
                            <td><?=htmlspecialchars($user['Email'])?></td>
                            <td>
                                <form method="post" class="d-flex gap-2">
                                    <input type="hidden" name="user_id" value="<?=$user['UserID']?>">
                                    <select name="role" class="form-select form-select-sm" style="width:120px;">
                                        <option value="viewer" <?=$user['Role']=='viewer'?'selected':''?>>Viewer</option>
                                        <option value="creator" <?=$user['Role']=='creator'?'selected':''?>>Creator</option>
                                        <option value="admin" <?=$user['Role']=='admin'?'selected':''?>>Admin</option>
                                    </select>
                                    <button type="submit" name="update_role" class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                            <td>
                                <?php if($user['UserID'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-danger delete-user-btn" data-id="<?=$user['UserID']?>" data-name="<?=htmlspecialchars($user['FullName'])?>">Delete</button>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Current</span>
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

<!-- Modal for delete confirmation -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Are you sure you want to delete user <strong id="deleteUserName"></strong>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteLink" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.delete-user-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        let userId = btn.dataset.id;
        let userName = btn.dataset.name;
        document.getElementById('deleteUserName').innerText = userName;
        let link = document.getElementById('confirmDeleteLink');
        let params = new URLSearchParams(window.location.search);
        params.set('delete_user', userId);
        link.href = 'manage_users.php?' + params.toString();
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });
});
</script>

<?php include '../../includes/footer.php'; ?>