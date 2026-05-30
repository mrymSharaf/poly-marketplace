<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config/db.php";

$dbc = getDB();
$message = "";

// already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "Please fill all fields.";
    } else {

        $query = "SELECT * FROM pm_users WHERE Email = '$email' LIMIT 1";
        $result = mysqli_query($dbc, $query);

        if ($result && mysqli_num_rows($result) == 1) {

            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['PasswordHash'])) {

                
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['name'] = $user['FullName'];
                $_SESSION['role'] = $user['Role'];

                // page navigation based on role
                if ($user['Role'] == "admin") {
                    header("Location: ../pages/admin/dashboard.php");
                } elseif ($user['Role'] == "creator") {
                    header("Location: ../pages/creator/dashboard.php");
                } else {
                    header("Location: ../index.php");
                }

                exit;

            } else {
                $message = "Wrong password.";
            }

        } else {
            $message = "User not found.";
        }
    }
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>
<!-- keeping only bootstrap classes for responsive design -->
<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                <div class="card border-0 shadow rounded-4">
                    <div class="card-body p-4 p-md-5">

                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">Login</h2>
                            <p class="text-muted small">Welcome back to PolyMarketplace!</p>
                        </div>

                        <?php if ($message != ""): ?>
                            <div class="alert alert-danger text-center rounded-3">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    class="form-control form-control-lg rounded-3" 
                                    placeholder="Enter your email" 
                                    required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    name="password" 
                                    class="form-control form-control-lg rounded-3" 
                                    placeholder="Enter your password" 
                                    required>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100 rounded-3">
                                Login
                            </button>

                        </form>

                        <p class="text-center mt-4 mb-0">
                            Don’t have an account?
                            <a href="register.php" class="link-primary text-decoration-none fw-semibold">
                                Register here
                            </a>
                        </p>

                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>