<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';
require_once '../../includes/pagination.php';

$dbc     = getDB();
$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $userID  = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    $stmt = mysqli_prepare($dbc, "UPDATE pm_users SET Role = ? WHERE UserID = ?");
    mysqli_stmt_bind_param($stmt, "si", $newRole, $userID);
    mysqli_stmt_execute($stmt) ? $message = "User role updated." : $error = "Error updating role.";
}

if (isset($_GET['delete'])) {
    $userID = (int)$_GET['delete'];
    if ($userID != $_SESSION['user_id']) {
        $stmt = mysqli_prepare($dbc, "DELETE FROM pm_users WHERE UserID = ?");
        mysqli_stmt_bind_param($stmt, "i", $userID);
        mysqli_stmt_execute($stmt) ? $message = "User deleted." : $error = "Error deleting user.";
    } else {
        $error = "You cannot delete your own account.";
    }
}

$search     = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role_filter'] ?? 'all';
$sortBy     = $_GET['sort_by'] ?? 'userid_asc';
$where      = "1=1";
$params     = [];
$types      = "";

if ($search !== '') {
    $where   .= " AND (FullName LIKE ? OR Email LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}
if ($roleFilter !== 'all') {
    $where   .= " AND Role = ?";
    $params[] = $roleFilter;
    $types   .= "s";
}
$orderMap = [
    'userid_asc'  => "UserID ASC",  'userid_desc' => "UserID DESC",
    'name_asc'    => "FullName ASC", 'name_desc'   => "FullName DESC",
    'role_asc'    => "Role ASC",     'role_desc'   => "Role DESC",
];
$orderSQL = $orderMap[$sortBy] ?? "UserID ASC";

// ── Pagination setup ──────────────────────────────────────────────────────────
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));

// COUNT total matching users
$countStmt = mysqli_prepare($dbc, "SELECT COUNT(*) AS total FROM pm_users WHERE $where");
if ($types !== '') mysqli_stmt_bind_param($countStmt, $types, ...$params);
mysqli_stmt_execute($countStmt);
$totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);
$totalPages   = max(1, (int)ceil($totalRecords / $perPage));
$page         = min($page, $totalPages);
$offset       = ($page - 1) * $perPage;

// Fetch current page of users
$dataParams   = $params;
$dataTypes    = $types . "ii";
$dataParams[] = $perPage;
$dataParams[] = $offset;

$stmt = mysqli_prepare($dbc, "SELECT UserID, FullName, Email, Role
                               FROM pm_users
                               WHERE $where
                               ORDER BY $orderSQL
                               LIMIT ? OFFSET ?");
if ($dataTypes !== '') mysqli_stmt_bind_param($stmt, $dataTypes, ...$dataParams);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="page-header pb-3">
    <div class="container">
        <h2 class="fw-bold mb-1"><i class="bi bi-people me-2"></i>Manage Users</h2>
        <p class="mb-2 opacity-75 small">
            <?= $totalRecords ?> user<?= $totalRecords !== 1 ? 's' : '' ?> found
            <?php if ($totalPages > 1): ?>
                &nbsp;&middot;&nbsp; Page <?= $page ?> of <?= $totalPages ?>
            <?php endif; ?>
        </p>
        <div class="d-flex gap-2 flex-wrap pt-1">
            <a href="dashboard.php"       class="btn btn-sm btn-outline-light rounded-pill"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
            <a href="manage_listings.php" class="btn btn-sm btn-outline-light rounded-pill">Listings</a>
            <a href="manage_comments.php" class="btn btn-sm btn-outline-light rounded-pill">Comments</a>
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

    <div class="card pm-table-card mb-4">
        <div class="card-body p-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name or email…"
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Role</label>
                    <select name="role_filter" class="form-select">
                        <option value="all"     <?= $roleFilter == 'all'     ? 'selected' : '' ?>>All roles</option>
                        <option value="viewer"  <?= $roleFilter == 'viewer'  ? 'selected' : '' ?>>Viewer</option>
                        <option value="creator" <?= $roleFilter == 'creator' ? 'selected' : '' ?>>Creator</option>
                        <option value="admin"   <?= $roleFilter == 'admin'   ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Sort by</label>
                    <select name="sort_by" class="form-select">
                        <option value="userid_asc"  <?= $sortBy == 'userid_asc'  ? 'selected' : '' ?>>ID ↑</option>
                        <option value="userid_desc" <?= $sortBy == 'userid_desc' ? 'selected' : '' ?>>ID ↓</option>
                        <option value="name_asc"    <?= $sortBy == 'name_asc'    ? 'selected' : '' ?>>Name A–Z</option>
                        <option value="name_desc"   <?= $sortBy == 'name_desc'   ? 'selected' : '' ?>>Name Z–A</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-navy flex-grow-1">Filter</button>
                    <a href="manage_users.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card pm-table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="pe-4">Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No users found.</td></tr>
                    <?php else: ?>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle" style="width:32px;height:32px;font-size:.75rem;">
                                            <?= strtoupper(substr($user['FullName'], 0, 1)) ?>
                                        </div>
                                        <span class="fw-semibold"><?= htmlspecialchars($user['FullName']) ?></span>
                                        <?php if ($user['UserID'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size:.65rem;">You</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-muted small"><?= htmlspecialchars($user['Email']) ?></td>
                                <td>
                                    <form method="post" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="user_id" value="<?= $user['UserID'] ?>">
                                        <select name="role" class="form-select form-select-sm" style="width:auto;">
                                            <option value="viewer"  <?= $user['Role'] == 'viewer'  ? 'selected' : '' ?>>Viewer</option>
                                            <option value="creator" <?= $user['Role'] == 'creator' ? 'selected' : '' ?>>Creator</option>
                                            <option value="admin"   <?= $user['Role'] == 'admin'   ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                        <button type="submit" name="update_role" class="btn btn-sm btn-navy">Save</button>
                                    </form>
                                </td>
                                <td class="pe-4">
                                    <?php if ($user['UserID'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?= $user['UserID'] ?>&search=<?= urlencode($search) ?>&role_filter=<?= $roleFilter ?>&sort_by=<?= $sortBy ?>&page=<?= $page ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this user permanently?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
        renderPagination($page, $totalPages, function(int $p) use ($search, $roleFilter, $sortBy): string {
            $q = ['page' => $p];
            if ($search !== '')          $q['search']      = $search;
            if ($roleFilter !== 'all')   $q['role_filter'] = $roleFilter;
            if ($sortBy !== 'userid_asc') $q['sort_by']    = $sortBy;
            return '?' . http_build_query($q);
        });
        ?>
    </div>

</div>

<?php include '../../includes/footer.php'; ?>
