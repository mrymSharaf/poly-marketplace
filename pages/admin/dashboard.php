<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();

// Count users
$result = mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_users");
$userCount = mysqli_fetch_assoc($result)['total'];

// Count listings
$result = mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_listings");
$listingCount = mysqli_fetch_assoc($result)['total'];

// Count comments
$result = mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_comments WHERE IsDeleted = 0");
$commentCount = mysqli_fetch_assoc($result)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - PolyMarketplace</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; flex: 1; }
        .stat-number { font-size: 2em; font-weight: bold; color: #28a745; }
        nav a { margin-right: 15px; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (<?php echo $_SESSION['role']; ?>)</p>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $userCount; ?></div>
                <div>Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $listingCount; ?></div>
                <div>Total Listings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $commentCount; ?></div>
                <div>Active Comments</div>
            </div>
        </div>

        <nav>
            <a href="manage_users.php">📋 Manage Users</a>
            <a href="manage_listings.php">📦 Manage Listings</a>
            <a href="manage_comments.php">💬 Manage Comments</a>
            <a href="reports.php">📊 Reports</a>
        </nav>
        <hr>
        <a href="../../pages/logout.php">Logout</a>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>