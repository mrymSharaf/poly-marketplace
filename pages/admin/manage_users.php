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

// Fetch all users
$result = mysqli_query($dbc, "SELECT UserID, FullName, Email, Role FROM pm_users ORDER BY UserID");
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<link rel="stylesheet" href="../../assets/css/auth.css"> <!-- reuse auth styling if needed -->

<main class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card auth-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h2 class="auth-title">Manage Users</h2>
                            <p class="auth-subtitle">View, edit roles, and delete users</p>
                        </div>

                        <?php if ($message): ?>
                            <div class="alert alert-success text-center"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back to Dashboard</a>
                            <a href="manage_listings.php" class="btn btn-outline-primary btn-sm">Manage Listings</a>
                            <a href="manage_comments.php" class="btn btn-outline-primary btn-sm">Manage Comments</a>
                            <a href="reports.php" class="btn btn-outline-primary btn-sm">Reports</a>
                        </div>

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
                                                    <a href="?delete=<?php echo $user['UserID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Current</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
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