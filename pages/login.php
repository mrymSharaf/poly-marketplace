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

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Login</h2>

<?php if ($message != "") echo "<p><b>$message</b></p>"; ?>

<form method="POST">

    <input type="email" name="email" placeholder="Email" required><br><br>

    <input type="password" name="password" placeholder="Password" required><br><br>

    <button type="submit">Login</button>

</form>

</body>
</html>