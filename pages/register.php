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
    $role = $_POST['role']; 

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

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>

<h2>Register</h2>

<?php if ($message != "") echo "<p><b>$message</b></p>"; ?> <!-- for debug i will change it later -->

<form method="POST">

    <input type="text" name="fullname" placeholder="Full Name" required><br><br>

    <input type="email" name="email" placeholder="Email" required><br><br>

    <input type="password" name="password" placeholder="Password" required><br><br>

    <select name="role" required>
         <option>--Select Option--</option>
        <option value="viewer">Viewer</option>
        <option value="creator">Creator</option>
    </select><br><br>

    <button type="submit">Register</button>

</form>

</body>
</html>