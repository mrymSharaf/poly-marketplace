<?php
require_once "config/auth.php";

requireLogin();
?>

<h2>Home Page</h2>
<p>Welcome <?php echo $_SESSION['name']; ?></p>
<a href="pages/logout.php"><button>Logout</button></a>
