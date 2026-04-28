<?php
require_once "../../config/auth.php";

requireLogin();
requireRole("creator");
?>

<h2>Creator Dashboard</h2>
<p>Welcome <?php echo $_SESSION['name']; ?></p>
<a href="../logout.php"><button>Logout</button></a>
