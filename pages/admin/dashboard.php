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

// Count published listings
$result = mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_listings WHERE Status = 'published'");
$publishedCount = mysqli_fetch_assoc($result)['total'];

// Count comments (not deleted)
$result = mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_comments WHERE IsDeleted = 0");
$commentCount = mysqli_fetch_assoc($result)['total'];

// Count creators
$result = mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_users WHERE Role = 'creator'");
$creatorCount = mysqli_fetch_assoc($result)['total'];
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Admin Dashboard</h2>
                    <p class="text-center">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?></p>

                    <!-- Stats Row -->
                    <div class="row text-center mb-4">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Total Users</h5>
                                    <p class="display-4"><?php echo $userCount; ?></p>
                                    <small><?php echo $creatorCount; ?> creators</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Listings</h5>
                                    <p class="display-4"><?php echo $listingCount; ?></p>
                                    <small><?php echo $publishedCount; ?> published</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">Comments</h5>
                                    <p class="display-4"><?php echo $commentCount; ?></p>
                                    <small>active comments</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card text-white bg-secondary">
                                <div class="card-body">
                                    <h5 class="card-title">Creators</h5>
                                    <p class="display-4"><?php echo $creatorCount; ?></p>
                                    <small>content creators</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Actions -->
                    <h5 class="mb-3">Admin Actions</h5>
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-2">
                            <a href="manage_users.php" class="btn btn-outline-primary w-100">📋 Manage Users</a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <a href="manage_listings.php" class="btn btn-outline-primary w-100">📦 Manage Listings</a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <a href="manage_comments.php" class="btn btn-outline-primary w-100">💬 Manage Comments</a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <a href="reports.php" class="btn btn-outline-primary w-100">📊 Reports</a>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="../../pages/logout.php" class="btn btn-danger">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>