<?php
session_start();
include "config/db.php";

mysqli_report(MYSQLI_REPORT_OFF);

$dbc = getDB();

$keyword    = trim($_GET['keyword'] ?? '');
$categoryID = (int)($_GET['category_id'] ?? 0);
$categoryName = '';
$isFiltered = $keyword !== '' || $categoryID > 0;

// Categories for quick-filter pills
$catResult = mysqli_query($dbc, "SELECT CategoryID, CategoryName FROM pm_categories ORDER BY CategoryName");
$categories = [];
while ($cat = mysqli_fetch_assoc($catResult)) {
    $categories[] = $cat;
}

if ($categoryID > 0) {
    $stmt = mysqli_prepare($dbc, "SELECT CategoryName FROM pm_categories WHERE CategoryID = ?");
    mysqli_stmt_bind_param($stmt, "i", $categoryID);
    mysqli_stmt_execute($stmt);
    $catRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $categoryName = $catRow['CategoryName'] ?? '';
}

// Live stats for hero
$statsResult = mysqli_query($dbc, "SELECT
    (SELECT COUNT(*) FROM pm_listings WHERE Status = 'published') AS totalListings,
    (SELECT COUNT(DISTINCT CategoryID) FROM pm_listings WHERE Status = 'published') AS totalCategories,
    (SELECT COUNT(*) FROM pm_users WHERE Role = 'creator') AS totalCreators");
$stats = mysqli_fetch_assoc($statsResult);

// Build listing query based on search/filter or default latest
$listingsList = [];
if ($isFiltered) {
    if ($keyword !== '') {
        $safeKeyword = mysqli_real_escape_string($dbc, $keyword);
        $result = mysqli_query($dbc, "CALL SearchListings('$safeKeyword')");
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                if ($categoryID === 0 || $row['CategoryName'] === $categoryName) {
                    $listingsList[] = $row;
                }
            }
            mysqli_free_result($result);
        }
        while (mysqli_more_results($dbc) && mysqli_next_result($dbc)) {
            $extra = mysqli_store_result($dbc);
            if ($extra) mysqli_free_result($extra);
        }
        if (!$result) {
            $likeKeyword = "%" . $safeKeyword . "%";
            $sql = "SELECT l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.CreatedAt,
                           c.CategoryName, u.FullName AS CreatorName,
                           COALESCE(AVG(r.RatingValue), 0) AS AverageRating,
                           COUNT(r.RatingID) AS RatingCount
                    FROM pm_listings l
                    JOIN pm_categories c ON l.CategoryID = c.CategoryID
                    JOIN pm_users u ON l.UserID = u.UserID
                    LEFT JOIN pm_ratings r ON l.ListingID = r.ListingID
                    WHERE l.Status = 'published'
                    AND (l.Title LIKE '$likeKeyword' OR l.Description LIKE '$likeKeyword')";
            if ($categoryID > 0) $sql .= " AND l.CategoryID = '$categoryID'";
            $sql .= " GROUP BY l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.CreatedAt, c.CategoryName, u.FullName
                      ORDER BY l.CreatedAt DESC LIMIT 30";
            $result = mysqli_query($dbc, $sql);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) $listingsList[] = $row;
            }
        }
    } else {
        $sql = "SELECT l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.CreatedAt,
                       c.CategoryName, u.FullName AS CreatorName,
                       COALESCE(AVG(r.RatingValue), 0) AS AverageRating,
                       COUNT(r.RatingID) AS RatingCount
                FROM pm_listings l
                JOIN pm_categories c ON l.CategoryID = c.CategoryID
                JOIN pm_users u ON l.UserID = u.UserID
                LEFT JOIN pm_ratings r ON l.ListingID = r.ListingID
                WHERE l.Status = 'published' AND l.CategoryID = '$categoryID'
                GROUP BY l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.CreatedAt, c.CategoryName, u.FullName
                ORDER BY l.CreatedAt DESC LIMIT 30";
        $result = mysqli_query($dbc, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) $listingsList[] = $row;
        }
    }
} else {
    $sql = "SELECT l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.CreatedAt,
                   c.CategoryName, u.FullName AS CreatorName,
                   COALESCE(AVG(r.RatingValue), 0) AS AverageRating,
                   COUNT(r.RatingID) AS RatingCount
            FROM pm_listings l
            JOIN pm_categories c ON l.CategoryID = c.CategoryID
            JOIN pm_users u ON l.UserID = u.UserID
            LEFT JOIN pm_ratings r ON l.ListingID = r.ListingID
            WHERE l.Status = 'published'
            GROUP BY l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.CreatedAt, c.CategoryName, u.FullName
            ORDER BY l.CreatedAt DESC
            LIMIT 6";
    $result = mysqli_query($dbc, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) $listingsList[] = $row;
    }
}

mysqli_close($dbc);
?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>

<!-- ── Hero ── -->
<section class="hero-section">
    <div class="container position-relative">
        <div class="row justify-content-center text-center">
            <div class="col-lg-7 col-md-10">

                <p class="hero-eyebrow mb-2">
                    <i class="bi bi-shop me-1"></i>Bahrain Polytechnic Student Market
                </p>
                <h1 class="hero-title mb-3">Find, Buy &amp; Sell<br>on Campus</h1>
                <p class="hero-subtitle mb-4">
                    Browse listings posted by students and creators — from handmade crafts to tech gadgets.
                </p>

                <!-- Search bar -->
                <form class="d-flex hero-search justify-content-center mb-4" method="GET" action="index.php">
                    <?php if ($categoryID > 0): ?>
                        <input type="hidden" name="category_id" value="<?= $categoryID ?>">
                    <?php endif; ?>
                    <input type="search" name="keyword" class="form-control"
                           placeholder="Search listings…"
                           value="<?= htmlspecialchars($keyword) ?>"
                           aria-label="Search listings"
                           style="max-width:400px;">
                    <button class="hero-search-btn" type="submit">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                </form>

                <div class="d-flex justify-content-center align-items-center gap-4 flex-wrap mt-2">
                    <div class="text-center">
                        <div class="stat-number"><?= (int)($stats['totalListings'] ?? 0) ?>+</div>
                        <div class="stat-label">Active Listings</div>
                    </div>
                    <div class="stat-divider d-none d-sm-block"></div>
                    <div class="text-center">
                        <div class="stat-number"><?= (int)($stats['totalCategories'] ?? 0) ?>+</div>
                        <div class="stat-label">Categories</div>
                    </div>
                    <div class="stat-divider d-none d-sm-block"></div>
                    <div class="text-center">
                        <div class="stat-number"><?= (int)($stats['totalCreators'] ?? 0) ?>+</div>
                        <div class="stat-label">Creators</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- ── Category pills ── -->
<div class="category-bar bg-white">
    <div class="container">
        <div class="d-flex flex-wrap gap-2 py-3 align-items-center">
            <span class="text-muted small fw-semibold me-1">Browse by:</span>
            <a href="index.php" class="btn category-pill <?= !$isFiltered ? 'btn-navy' : 'btn-outline-navy' ?>">
                <i class="bi bi-grid me-1"></i>All
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?<?= $keyword ? 'keyword=' . urlencode($keyword) . '&' : '' ?>category_id=<?= (int)$cat['CategoryID'] ?>"
                   class="btn category-pill <?= $categoryID === (int)$cat['CategoryID'] ? 'btn-navy' : 'btn-outline-navy' ?>">
                    <?= htmlspecialchars($cat['CategoryName']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ── Latest listings ── -->
<main class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <?php if ($keyword !== ''): ?>
                <h2 class="h4 fw-bold mb-0 text-navy">Results for "<?= htmlspecialchars($keyword) ?>"</h2>
                <p class="text-muted small mb-0 mt-1"><?= count($listingsList) ?> listing<?= count($listingsList) !== 1 ? 's' : '' ?> found</p>
            <?php elseif ($categoryID > 0 && $categoryName): ?>
                <h2 class="h4 fw-bold mb-0 text-navy"><?= htmlspecialchars($categoryName) ?></h2>
                <p class="text-muted small mb-0 mt-1"><?= count($listingsList) ?> listing<?= count($listingsList) !== 1 ? 's' : '' ?> found</p>
            <?php else: ?>
                <h2 class="h4 fw-bold mb-0 text-navy">Latest Listings</h2>
                <p class="text-muted small mb-0 mt-1">Freshest items from our creators</p>
            <?php endif; ?>
        </div>
        <?php if ($isFiltered): ?>
            <a href="index.php" class="btn btn-outline-navy btn-sm rounded-pill px-4">
                Clear <i class="bi bi-x ms-1"></i>
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($listingsList)): ?>
        <div class="text-center py-5">
            <i class="bi bi-search display-1 icon-empty-state"></i>
            <p class="text-muted mt-3 mb-3">
                <?= $keyword ? 'No listings found for "' . htmlspecialchars($keyword) . '".' : 'No listings yet — check back soon!' ?>
            </p>
            <?php if ($isFiltered): ?>
                <a href="index.php" class="btn btn-outline-navy rounded-pill px-4">Clear Search</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($listingsList as $row):
                $rating = (int) round((float)($row['AverageRating'] ?? 0));
                $desc   = htmlspecialchars($row['Description'] ?? '');
                if (mb_strlen($desc) > 95) {
                    $desc = mb_substr($desc, 0, 95) . '…';
                }
            ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card listing-card h-100">

                        <?php if (!empty($row['ImageURL'])): ?>
                            <div class="listing-img-wrap">
                                <img src="<?= htmlspecialchars($row['ImageURL']) ?>"
                                     class="listing-img w-100 h-100 object-fit-cover"
                                     alt="<?= htmlspecialchars($row['Title']) ?>">
                            </div>
                        <?php else: ?>
                            <div class="card-img-placeholder">
                                <i class="bi bi-image fs-1 text-secondary opacity-50"></i>
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column p-3">

                            <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                                <span class="category-badge">
                                    <?= htmlspecialchars($row['CategoryName']) ?>
                                </span>
                                <span class="creator-chip" title="<?= htmlspecialchars($row['CreatorName'] ?? '') ?>">
                                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($row['CreatorName'] ?? '') ?>
                                </span>
                            </div>

                            <h5 class="card-listing-title fw-semibold mb-1">
                                <?= htmlspecialchars($row['Title']) ?>
                            </h5>

                            <p class="text-muted small lh-base flex-grow-1 mb-2">
                                <?= $desc ?>
                            </p>

                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= $rating ? '-fill star-icon star-filled' : ' star-icon star-empty' ?>"></i>
                                <?php endfor; ?>
                                <?php if (!empty($row['RatingCount']) && $row['RatingCount'] > 0): ?>
                                    <span class="small text-muted ms-1">(<?= (int)$row['RatingCount'] ?>)</span>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                                <span class="price-tag">
                                    <?= number_format($row['Price'], 3) ?>
                                    <span class="small text-muted fw-medium">BHD</span>
                                </span>
                                <a href="details.php?id=<?= (int)$row['ListingID'] ?>" class="btn-view">
                                    View <i class="bi bi-arrow-right"></i>
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
