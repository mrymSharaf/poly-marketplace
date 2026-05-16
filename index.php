<?php
session_start();
include "config/db.php";


$dbc = getDB();

$sql = "SELECT l.ListingID, l.Title, l.Price, l.ImageURL, l.CreatedAt, c.CategoryName
        FROM pm_listings l
        JOIN pm_categories c ON l.CategoryID = c.CategoryID
        WHERE l.Status = 'published'
        ORDER BY l.CreatedAt DESC
        LIMIT 6";
$result = mysqli_query($dbc, $sql);
?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>

<section class="py-5 bg-white border-bottom">
    <div class="container py-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h1 class="display-5 fw-bold text-dark mb-3">Welcome to PolyMarketplace</h1>
                <p class="lead text-muted mb-4">Browse items posted by students and creators.</p>

                <form class="d-flex flex-column flex-sm-row gap-2" method="GET" action="search.php">
                    <input type="search" name="keyword" class="form-control form-control-lg" placeholder="Search for listings">
                    <button class="btn btn-success btn-lg px-4" type="submit">
                        Search
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0">Latest 6 Listings</h2>
        <a href="search.php" class="btn btn-outline-primary btn-sm">Browse All</a>
    </div>

    <?php if (mysqli_num_rows($result) == 0): ?>
        <div class="alert alert-info">No listings available yet.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card h-100">

                        <?php if ($row['ImageURL'] != ''): ?>
                            <img src="<?php echo htmlspecialchars($row['ImageURL']); ?>"
                                class="card-img-top"
                                style="height:210px; object-fit:cover;"
                                alt="<?php echo htmlspecialchars($row['Title']); ?>">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height:210px;">
                                <span class="text-muted">No Image</span>
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-primary align-self-start mb-2"><?php echo htmlspecialchars($row['CategoryName']); ?></span>
                            <h5 class="card-title"><?php echo htmlspecialchars($row['Title']); ?></h5>
                            <p class="fw-bold text-primary mb-3"><?php echo number_format($row['Price'], 3); ?> BHD</p>

                            <a href="details.php?id=<?php echo $row['ListingID']; ?>" class="btn btn-success mt-auto">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</main>

<?php include "includes/footer.php"; ?>