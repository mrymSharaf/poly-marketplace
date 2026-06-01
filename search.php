<?php
session_start();
include "config/db.php";
include "includes/pagination.php";

mysqli_report(MYSQLI_REPORT_OFF);

$dbc        = getDB();
$keyword    = trim($_GET['keyword'] ?? '');
$categoryID = (int)($_GET['category_id'] ?? 0);
$categoryName = '';

if ($categoryID > 0) {
    $stmt = mysqli_prepare($dbc, "SELECT CategoryName FROM pm_categories WHERE CategoryID = ?");
    mysqli_stmt_bind_param($stmt, "i", $categoryID);
    mysqli_stmt_execute($stmt);
    $catRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $categoryName = $catRow['CategoryName'] ?? '';
}

// ── Pagination setup ──────────────────────────────────────────────────────────
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));

// ── Build WHERE clause ────────────────────────────────────────────────────────
$conditions = ["l.Status = 'published'"];
$params     = [];
$types      = "";

if ($keyword !== '') {
    $conditions[] = "(l.Title LIKE ? OR l.Description LIKE ?)";
    $likeKw   = "%" . $keyword . "%";
    $params[] = $likeKw;
    $params[] = $likeKw;
    $types   .= "ss";
}
if ($categoryID > 0) {
    $conditions[] = "l.CategoryID = ?";
    $params[] = $categoryID;
    $types   .= "i";
}
$where = implode(" AND ", $conditions);

// ── COUNT total matching records ──────────────────────────────────────────────
$countSQL  = "SELECT COUNT(DISTINCT l.ListingID) AS total
              FROM pm_listings l
              JOIN pm_categories c ON l.CategoryID = c.CategoryID
              JOIN pm_users u      ON l.UserID = u.UserID
              WHERE $where";
$countStmt = mysqli_prepare($dbc, $countSQL);
if ($types !== '') mysqli_stmt_bind_param($countStmt, $types, ...$params);
mysqli_stmt_execute($countStmt);
$totalRecords = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'] ?? 0);
$totalPages   = max(1, (int)ceil($totalRecords / $perPage));
$page         = min($page, $totalPages);
$offset       = ($page - 1) * $perPage;

// ── Fetch page of listings ────────────────────────────────────────────────────
$dataParams   = $params;
$dataTypes    = $types . "ii";
$dataParams[] = $perPage;
$dataParams[] = $offset;

$dataSQL = "SELECT l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.CreatedAt,
                   c.CategoryName, u.FullName AS CreatorName,
                   COALESCE(AVG(r.RatingValue), 0) AS AverageRating
            FROM pm_listings l
            JOIN pm_categories c ON l.CategoryID = c.CategoryID
            JOIN pm_users u      ON l.UserID = u.UserID
            LEFT JOIN pm_ratings r ON l.ListingID = r.ListingID
            WHERE $where
            GROUP BY l.ListingID, l.Title, l.Description, l.Price,
                     l.ImageURL, l.CreatedAt, c.CategoryName, u.FullName
            ORDER BY l.CreatedAt DESC
            LIMIT ? OFFSET ?";
$dataStmt = mysqli_prepare($dbc, $dataSQL);
mysqli_stmt_bind_param($dataStmt, $dataTypes, ...$dataParams);
mysqli_stmt_execute($dataStmt);
$listingsList = mysqli_fetch_all(mysqli_stmt_get_result($dataStmt), MYSQLI_ASSOC);

mysqli_close($dbc);

function searchPageLink(int $p): string {
    $q         = $_GET;
    $q['page'] = $p;
    return '?' . http_build_query($q);
}

function ratingStars($avg) {
    $r = (int) round((float)$avg);
    for ($i = 1; $i <= 5; $i++) {
        $cls = $i <= $r ? 'bi-star-fill star-filled' : 'bi-star star-empty';
        echo '<i class="bi ' . $cls . ' star-icon"></i>';
    }
}
?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>

<!-- Page header -->
<div class="page-header">
    <div class="container">
        <?php if ($keyword !== ''): ?>
            <h2 class="fw-bold mb-1">Results for "<?= htmlspecialchars($keyword) ?>"</h2>
        <?php elseif ($categoryID > 0 && $categoryName): ?>
            <h2 class="fw-bold mb-1"><?= htmlspecialchars($categoryName) ?></h2>
        <?php else: ?>
            <h2 class="fw-bold mb-1">Browse All Listings</h2>
        <?php endif; ?>
        <p class="mb-0 opacity-75 small">
            <?= $totalRecords ?> listing<?= $totalRecords !== 1 ? 's' : '' ?> found
            <?php if ($totalPages > 1): ?>
                &nbsp;&middot;&nbsp; Page <?= $page ?> of <?= $totalPages ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<main class="container py-5">

    <?php if (empty($listingsList)): ?>
        <div class="text-center py-5">
            <i class="bi bi-search display-1 icon-empty-state"></i>
            <p class="text-muted mt-3 mb-3">
                <?= $keyword ? 'No listings found for "' . htmlspecialchars($keyword) . '".' : 'No listings available yet.' ?>
            </p>
            <a href="search.php" class="btn btn-outline-navy rounded-pill px-4">Clear Search</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($listingsList as $listing):
                $rating = (int) round((float)($listing['AverageRating'] ?? 0));
                $desc   = htmlspecialchars($listing['Description'] ?? '');
                if (mb_strlen($desc) > 95) $desc = mb_substr($desc, 0, 95) . '…';
            ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card listing-card h-100">

                        <?php if (!empty($listing['ImageURL'])): ?>
                            <div class="listing-img-wrap">
                                <img src="<?= htmlspecialchars($listing['ImageURL']) ?>"
                                     class="listing-img w-100 h-100 object-fit-cover"
                                     alt="<?= htmlspecialchars($listing['Title']) ?>">
                            </div>
                        <?php else: ?>
                            <div class="card-img-placeholder">
                                <i class="bi bi-image fs-1 text-secondary opacity-50"></i>
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column p-3">

                            <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                                <span class="category-badge"><?= htmlspecialchars($listing['CategoryName']) ?></span>
                                <?php if (!empty($listing['CreatorName'])): ?>
                                    <span class="creator-chip" title="<?= htmlspecialchars($listing['CreatorName']) ?>">
                                        <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($listing['CreatorName']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h5 class="card-listing-title fw-semibold mb-1">
                                <?= htmlspecialchars($listing['Title']) ?>
                            </h5>

                            <p class="text-muted small lh-base flex-grow-1 mb-2"><?= $desc ?></p>

                            <div class="mb-2">
                                <?php ratingStars($listing['AverageRating'] ?? 0); ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                                <span class="price-tag">
                                    <?= number_format($listing['Price'], 3) ?>
                                    <span class="small text-muted fw-medium">BHD</span>
                                </span>
                                <a href="details.php?id=<?= (int)$listing['ListingID'] ?>" class="btn-view">
                                    View <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php renderPagination($page, $totalPages, 'searchPageLink'); ?>

    <?php endif; ?>

</main>

<?php include "includes/footer.php"; ?>
