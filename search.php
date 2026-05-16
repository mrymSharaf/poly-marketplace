<?php
session_start();
include "config/db.php";

$dbc = getDB();
$keyword = trim($_GET['keyword'] ?? '');
$categoryID = $_GET['category_id'] ?? 0;
$message = "";
$categoryName = "";

// 1. If a category is selected, get its name first
if ($categoryID > 0) {
    $catQuery = "SELECT CategoryName FROM pm_categories WHERE CategoryID = '$categoryID'";
    $catResult = mysqli_query($dbc, $catQuery);
    if ($catResult) {
        $catRow = mysqli_fetch_assoc($catResult);
        $categoryName = $catRow['CategoryName'] ?? "";
    }
}

// 2. Collect listings into a simple array based on search criteria
$listingsList = [];

if ($keyword != '') {
    $sql = "CALL SearchListings('$keyword')";
    $result = mysqli_query($dbc, $sql);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            if ($categoryID == 0 || $row['CategoryName'] == $categoryName) {
                $listingsList[] = $row;
            }
        }
    }
} else {
    // If no keyword, build a standard SELECT query to show listings
    $sql = "SELECT l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.CreatedAt,
                   c.CategoryName, u.FullName AS CreatorName, AVG(r.RatingValue) AS AverageRating
            FROM pm_listings l
            JOIN pm_categories c ON l.CategoryID = c.CategoryID
            JOIN pm_users u ON l.UserID = u.UserID
            LEFT JOIN pm_ratings r ON l.ListingID = r.ListingID
            WHERE l.Status = 'published'";

    if ($categoryID > 0) {
        $sql .= " AND l.CategoryID = '$categoryID'";
    }

    $sql .= " GROUP BY l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.CreatedAt, c.CategoryName, u.FullName
              ORDER BY l.CreatedAt DESC
              LIMIT 30";

    $result = mysqli_query($dbc, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $listingsList[] = $row;
        }
    }
}

if ($keyword == '' && $categoryID == 0) {
    $message = "Showing latest listings.";
}

mysqli_close($dbc);

function ratingStars($averageRating)
{
    $rating = round($averageRating);
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            echo '<i class="bi bi-star-fill text-warning"></i>';
        } else {
            echo '<i class="bi bi-star text-muted"></i>';
        }
    }
}
?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>

<div class="p-5 bg-light border-bottom mb-4">
    <div class="container">
        <h2 class="mb-1">Search Results</h2>
        <p class="text-muted mb-0">
            <?php
            if ($keyword != '') {
                echo 'Results for "' . htmlspecialchars($keyword) . '"';
            } else {
                echo 'Browse listings';
            }
            ?>
        </p>
    </div>
</div>

<main class="container pb-5">
    <?php if ($message != ""): ?>
        <div class="alert alert-secondary"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (empty($listingsList)): ?>
        <div class="alert alert-warning">No listings found.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($listingsList as $listing): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card h-100">

                        <?php if ($listing['ImageURL'] != ''): ?>
                            <img src="<?php echo htmlspecialchars($listing['ImageURL']); ?>"
                                class="card-img-top"
                                style="height:200px; object-fit:cover;"
                                alt="<?php echo htmlspecialchars($listing['Title']); ?>">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                                <span class="text-muted">No Image</span>
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-primary align-self-start mb-2"><?php echo htmlspecialchars($listing['CategoryName']); ?></span>
                            <h5 class="card-title"><?php echo htmlspecialchars($listing['Title']); ?></h5>

                            <p class="card-text text-muted small">
                                <?php
                                $desc = htmlspecialchars($listing['Description']);
                                if (strlen($desc) > 110) {
                                    echo substr($desc, 0, 110) . '...';
                                } else {
                                    echo $desc;
                                }
                                ?>
                            </p>

                            <div class="small mb-2">
                                <?php ratingStars($listing['AverageRating']); ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="fw-bold text-primary"><?php echo number_format($listing['Price'], 3); ?> BHD</span>
                                <a href="details.php?id=<?php echo $listing['ListingID']; ?>" class="btn btn-sm btn-success">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include "includes/footer.php"; ?>