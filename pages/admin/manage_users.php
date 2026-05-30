<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();
$message = '';
$error = '';

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $userID = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    $stmt = mysqli_prepare($dbc, "UPDATE pm_users SET Role = ? WHERE UserID = ?");
    mysqli_stmt_bind_param($stmt, "si", $newRole, $userID);
    if (mysqli_stmt_execute($stmt)) {
        $message = "User role updated successfully.";
    } else {
        $error = "Error updating role.";
    }
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $userID = (int)$_GET['delete'];
    if ($userID != $_SESSION['user_id']) {
        $stmt = mysqli_prepare($dbc, "DELETE FROM pm_users WHERE UserID = ?");
        mysqli_stmt_bind_param($stmt, "i", $userID);
        if (mysqli_stmt_execute($stmt)) {
            $message = "User deleted successfully.";
        } else {
            $error = "Error deleting user.";
        }
    } else {
        $error = "You cannot delete your own account.";
    }
}

// ---------- Search, filter, sort ----------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role_filter']) ? $_GET['role_filter'] : 'all';
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'userid_asc';

// Build WHERE conditions
$where = "1=1";
$params = [];
$types = "";

if ($search !== '') {
    $where .= " AND (FullName LIKE ? OR Email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}
if ($roleFilter !== 'all') {
    $where .= " AND Role = ?";
    $params[] = $roleFilter;
    $types .= "s";
}

// Build ORDER BY
$orderMap = [
    'userid_asc'  => "UserID ASC",
    'userid_desc' => "UserID DESC",
    'name_asc'    => "FullName ASC",
    'name_desc'   => "FullName DESC",
    'role_asc'    => "Role ASC",
    'role_desc'   => "Role DESC",
];
$orderSQL = $orderMap[$sortBy] ?? "UserID ASC";

// Prepare and execute query
$sql = "SELECT UserID, FullName, Email, Role FROM pm_users WHERE $where ORDER BY $orderSQL";
$stmt = mysqli_prepare($dbc, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Keep current filter values for the form
$currentSearch = htmlspecialchars($search);
$currentRoleFilter = $roleFilter;
$currentSortBy = $sortBy;
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Manage Users</h2>
                    <p class="text-center">View, edit roles, and delete users</p>

                    <?php if ($message): ?>
                        <div class="alert alert-success text-center"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <a href="dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
                        <a href="manage_listings.php" class="btn btn-outline-primary btn-sm">Manage Listings</a>
                        <a href="manage_comments.php" class="btn btn-outline-primary btn-sm">Manage Comments</a>
                        <a href="reports.php" class="btn btn-outline-primary btn-sm">Reports</a>
                    </div>

                    <!-- Search, filter, sort form -->
                    <form method="get" class="row g-2 mb-4 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Search (name / email)</label>
                            <input type="text" name="search" class="form-control" placeholder="Type to search..." value="<?php echo $currentSearch; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Role</label>
                            <select name="role_filter" class="form-select">
                                <option value="all" <?php echo $currentRoleFilter == 'all' ? 'selected' : ''; ?>>All roles</option>
                                <option value="viewer" <?php echo $currentRoleFilter == 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                                <option value="creator" <?php echo $currentRoleFilter == 'creator' ? 'selected' : ''; ?>>Creator</option>
                                <option value="admin" <?php echo $currentRoleFilter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sort by</label>
                            <select name="sort_by" class="form-select">
                                <option value="userid_asc" <?php echo $currentSortBy == 'userid_asc' ? 'selected' : ''; ?>>ID (ascending)</option>
                                <option value="userid_desc" <?php echo $currentSortBy == 'userid_desc' ? 'selected' : ''; ?>>ID (descending)</option>
                                <option value="name_asc" <?php echo $currentSortBy == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="name_desc" <?php echo $currentSortBy == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                                <option value="role_asc" <?php echo $currentSortBy == 'role_asc' ? 'selected' : ''; ?>>Role (A-Z)</option>
                                <option value="role_desc" <?php echo $currentSortBy == 'role_desc' ? 'selected' : ''; ?>>Role (Z-A)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                            <a href="manage_users.php" class="btn btn-outline-secondary w-100 mt-1">Reset</a>
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
                                <?php if (mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="5" class="text-center">No users found.</td></tr>
                                <?php else: ?>
                                    <?php while ($user = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $user['UserID']; ?></td>
                                            <td><?php echo htmlspecialchars($user['FullName']); ?></td>
                                            <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                            <td>
                                                <form method="post" class="d-flex gap-2">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
                                                    <select name="role" class="form-select form-select-sm" style="width: auto;">
                                                        <option value="viewer" <?php echo $user['Role'] == 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                                                        <option value="creator" <?php echo $user['Role'] == 'creator' ? 'selected' : ''; ?>>Creator</option>
                                                        <option value="admin" <?php echo $user['Role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                    </select>
                                                    <button type="submit" name="update_role" class="btn btn-sm btn-primary">Update</button>
                                                </form>
                                            </td>
                                            <td>
                                                <?php if ($user['UserID'] != $_SESSION['user_id']): ?>
                                                    <a href="?delete=<?php echo $user['UserID']; ?>&search=<?php echo urlencode($currentSearch); ?>&role_filter=<?php echo $currentRoleFilter; ?>&sort_by=<?php echo $currentSortBy; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Current</span>
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