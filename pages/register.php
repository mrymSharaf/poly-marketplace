<?php
session_start();
require_once "../config/db.php";

$dbc = getDB();
$message = "";
$messageType = "info";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'] ?? 'viewer';

    if ($role !== "viewer" && $role !== "creator") {
        $role = "viewer";
    }

    if (empty($fullname) || empty($email) || empty($password)) {
        $message = "All fields are required.";
        $messageType = "danger";
    } else {
        $stmt = mysqli_prepare($dbc, "SELECT UserID FROM pm_users WHERE Email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $message = "An account with this email already exists.";
            $messageType = "danger";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = mysqli_prepare($dbc, "INSERT INTO pm_users (Email, FullName, PasswordHash, Role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($ins, "ssss", $email, $fullname, $hash, $role);
            if (mysqli_stmt_execute($ins)) {
                $message = "Account created! You can now login.";
                $messageType = "success";
            } else {
                $message = "Something went wrong. Please try again.";
                $messageType = "danger";
            }
        }
    }
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>

<main class="auth-bg d-flex align-items-center" style="min-height:calc(100vh - 62px);">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                <div class="text-center mb-3">
                    <a href="../index.php" class="text-white-50 text-decoration-none small">
                        <i class="bi bi-arrow-left me-1"></i>Back to marketplace
                    </a>
                </div>

                <div class="auth-card card">
                    <div class="card-body p-4 p-md-5">

                        <div class="text-center mb-4">
                            <div class="auth-icon-wrap mb-3">
                                <i class="bi bi-person-plus-fill text-white fs-3"></i>
                            </div>
                            <h2 class="fw-bold text-navy mb-1">Create Account</h2>
                            <p class="text-muted small mb-0">Join PolyMarketplace as a viewer or creator</p>
                        </div>

                        <?php if ($message !== ""): ?>
                            <div class="alert alert-<?= $messageType ?> border-0 rounded-3 small">
                                <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Full Name</label>
                                <input type="text" name="fullname" class="form-control form-control-lg rounded-3"
                                       placeholder="Your full name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Email Address</label>
                                <input type="email" name="email" class="form-control form-control-lg rounded-3"
                                       placeholder="you@example.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Password</label>
                                <input type="password" name="password" class="form-control form-control-lg rounded-3"
                                       placeholder="Create a strong password" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold small">Account Type</label>
                                <select name="role" class="form-select form-select-lg rounded-3" required>
                                    <option value="">Select a role…</option>
                                    <option value="viewer">Viewer — browse &amp; comment</option>
                                    <option value="creator">Creator — post listings</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-navy btn-lg w-100 rounded-3">
                                <i class="bi bi-person-check me-2"></i>Create Account
                            </button>
                        </form>

                        <hr class="my-4">

                        <p class="text-center small mb-0 text-muted">
                            Already have an account?
                            <a href="login.php" class="fw-semibold text-navy text-decoration-none">Login</a>
                        </p>

                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
