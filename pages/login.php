<?php
session_start();
require_once "../../config/db.php";

$dbc = getDB();
$message = "";

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        $stmt = mysqli_prepare($dbc, "SELECT * FROM pm_users WHERE Email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['PasswordHash'])) {
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['email']   = $user['Email'];
                $_SESSION['name']    = $user['FullName'];
                $_SESSION['role']    = $user['Role'];

                if ($user['Role'] == "admin") {
                    header("Location: ../pages/admin/dashboard.php");
                } elseif ($user['Role'] == "creator") {
                    header("Location: ../pages/creator/dashboard.php");
                } else {
                    header("Location: ../index.php");
                }
                exit;
            } else {
                $message = "Incorrect password.";
            }
        } else {
            $message = "No account found with that email.";
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
                                <i class="bi bi-person-fill text-white fs-3"></i>
                            </div>
                            <h2 class="fw-bold text-navy mb-1">Welcome Back</h2>
                            <p class="text-muted small mb-0">Sign in to your PolyMarketplace account</p>
                        </div>

                        <?php if ($message !== ""): ?>
                            <div class="alert alert-danger border-0 rounded-3 small">
                                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Email Address</label>
                                <input type="email" name="email" class="form-control form-control-lg rounded-3"
                                       placeholder="you@example.com" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold small">Password</label>
                                <input type="password" name="password" class="form-control form-control-lg rounded-3"
                                       placeholder="Enter your password" required>
                            </div>
                            <button type="submit" class="btn btn-navy btn-lg w-100 rounded-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </button>
                        </form>

                        <hr class="my-4">

                        <p class="text-center small mb-0 text-muted">
                            Don't have an account?
                            <a href="register.php" class="fw-semibold text-navy text-decoration-none">Register here</a>
                        </p>

                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
