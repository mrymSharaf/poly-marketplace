<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "../config/db.php";

$dbc = getDB();
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    //$role = $_POST['role']; 
    $role = $_POST['role'] ?? 'viewer';

    // SECURITY prevent fake admin role
    if ($role !== "viewer" && $role !== "creator") {
        $role = "viewer";
    }

    if (empty($fullname) || empty($email) || empty($password)) {
        $message = "All fields are required.";
    } else {

        // Check if email exists
        $check = "SELECT * FROM pm_users WHERE Email = '$email'";
        $result = mysqli_query($dbc, $check);

        if (mysqli_num_rows($result) > 0) {
            $message = "Email already exists!";
        } else {

            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user to the table
            $query = "INSERT INTO pm_users (Email, FullName, PasswordHash, Role)
                      VALUES ('$email', '$fullname', '$hashedPassword', '$role')";

            $insert = mysqli_query($dbc, $query);

            if ($insert) {
                $message = "Registration successful! You can now login.";
            } else {
                $message = "Error: " . mysqli_error($dbc);
            }
        }
    }
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>

<link rel="stylesheet" href="../assets/css/auth.css">

<main class="auth-page">
    <div class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                <div class="card auth-card">
                    <div class="card-body p-4 p-md-5">

                        <div class="text-center mb-4">
                            <h2 class="auth-title">Create Account</h2>
                            <p class="auth-subtitle">Join PolyMarketplace as a viewer or creator</p>
                        </div>

                        <?php if ($message != ""): ?>
                            <div class="alert alert-info text-center">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input 
                                    type="text" 
                                    name="fullname" 
                                    class="form-control" 
                                    placeholder="Enter your full name" 
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    class="form-control" 
                                    placeholder="Enter your email" 
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    name="password" 
                                    class="form-control" 
                                    placeholder="Create a password" 
                                    required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Account Type</label>
                                <select name="role" class="form-select" required>
                                    <option value="">-- Select Role --</option>
                                    <option value="viewer">Viewer</option>
                                    <option value="creator">Creator</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-2">
                                Register
                            </button>

                        </form>

                        <p class="text-center mt-4 mb-0">
                            Already have an account?
                            <a href="login.php" class="auth-link">Login here</a>
                        </p>

                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>