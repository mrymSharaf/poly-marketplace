<?php

require_once __DIR__ . '/../includes/db.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/~u202301956/poly-marketplace');
}

$keyword   = trim($_GET['keyword']   ?? '');
$creator   = trim($_GET['creator']   ?? '');
$startDate = trim($_GET['startDate'] ?? '');
$endDate   = trim($_GET['endDate']   ?? '');
$sortBy    = trim($_GET['sortBy']    ?? 'newest');

$sortMap = [
    'newest'  => 'l.CreatedAt DESC',
    'rating'  => 'AverageRating DESC',
    'popular' => 'RatingCount DESC',
];
$orderSQL = $sortMap[$sortBy] ?? 'l.CreatedAt DESC';

$conditions = ["l.Status = 'published'"];
$bindTypes  = '';
$bindValues = [];

if ($keyword !== '') {
    if (mb_strlen($keyword) >= 3) {
        $conditions[] = 'MATCH(l.Title, l.Description) AGAINST (? IN NATURAL LANGUAGE MODE)';
        $bindValues[] = $keyword;
        $bindTypes   .= 's';
    } else {
        $like         = '%' . $keyword . '%';
        $conditions[] = '(l.Title LIKE ? OR l.Description LIKE ?)';
        $bindValues[] = $like;
        $bindValues[] = $like;
        $bindTypes   .= 'ss';
    }
}

if ($creator !== '') {
    $conditions[] = 'u.FullName LIKE ?';
    $bindValues[] = '%' . $creator . '%';
    $bindTypes   .= 's';
}

if ($startDate !== '') {
    $conditions[] = 'l.CreatedAt >= ?';
    $bindValues[] = $startDate . ' 00:00:00';
    $bindTypes   .= 's';
}
if ($endDate !== '') {
    $conditions[] = 'l.CreatedAt <= ?';
    $bindValues[] = $endDate . ' 23:59:59';
    $bindTypes   .= 's';
}

$whereSQL = implode(' AND ', $conditions);

$sql = "
    SELECT
        l.ListingID,
        l.Title,
        l.Description,
        l.Price,
        l.ImageURL,
        l.CreatedAt,
        c.CategoryName,
        u.FullName          AS CreatorName,
        AVG(r.RatingValue)  AS AverageRating,
        COUNT(r.RatingID)   AS RatingCount
    FROM pm_listings l
    JOIN  pm_categories c ON l.CategoryID = c.CategoryID
    JOIN  pm_users      u ON l.UserID     = u.UserID
    LEFT JOIN pm_ratings r ON l.ListingID = r.ListingID
    WHERE {$whereSQL}
    GROUP BY
        l.ListingID,
        l.Title,
        l.Description,
        l.Price,
        l.ImageURL,
        l.CreatedAt,
        c.CategoryName,
        u.FullName
    ORDER BY {$orderSQL}
    LIMIT 60
";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo '<div class="col-12"><div class="alert alert-danger">'
    . 'Query preparation failed: ' . htmlspecialchars($conn->error)
    . '</div></div>';
    exit;
}

if (!empty($bindValues)) {
    $stmt->bind_param($bindTypes, ...$bindValues);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '
    <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-3" style="opacity:.35;"></i>
        <p class="mb-1 fw-semibold">No listings found.</p>
        <p class="small">Try different keywords, a broader date range, or clear the filters.</p>
    </div>';
} else {
    while ($row = $result->fetch_assoc()) {

        $title       = htmlspecialchars($row['Title']);
        $description = htmlspecialchars($row['Description'] ?? '');
        $price       = number_format((float)$row['Price'], 3);
        $category    = htmlspecialchars($row['CategoryName']);
        $creator     = htmlspecialchars($row['CreatorName']);
        $createdAt   = date('d M Y', strtotime($row['CreatedAt']));

        $shortDesc = mb_strlen($description) > 130
            ? mb_substr($description, 0, 130) . '…'
            : $description;

        $ratingCount = (int)$row['RatingCount'];
        $avgRating   = round((float)($row['AverageRating'] ?? 0), 1);

        if ($ratingCount > 0) {
            $fullStars  = (int)floor($avgRating);
            $halfStar   = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
            $emptyStars = max(0, 5 - $fullStars - $halfStar);

            $starsHtml  = str_repeat('<i class="bi bi-star-fill text-warning"></i>', $fullStars);
            if ($halfStar) {
                $starsHtml .= '<i class="bi bi-star-half text-warning"></i>';
            }
            $starsHtml .= str_repeat('<i class="bi bi-star text-muted opacity-50"></i>', $emptyStars);

            $ratingHtml = $starsHtml
                        . ' <span class="text-muted small">'
                        . $avgRating . ' (' . $ratingCount . ')</span>';
        } else {
            $ratingHtml = '<span class="text-muted small">'
                        . '<i class="bi bi-star me-1 opacity-50"></i>No ratings yet</span>';
        }

        if (!empty($row['ImageURL'])) {
            $imgSrc    = htmlspecialchars(BASE_URL . '/' . $row['ImageURL']);
            $imgBlock  = '<img src="' . $imgSrc . '" class="card-img-top"
                            style="height:190px;object-fit:cover;"
                            alt="' . $title . '"
                            onerror="this.parentElement.innerHTML=\'<div class=&quot;bg-light d-flex align-items-center justify-content-center&quot; style=&quot;height:190px;&quot;><i class=&quot;bi bi-image text-muted fs-1&quot;></i></div>\'">';
        } else {
            $imgBlock  = '<div class="bg-light d-flex align-items-center justify-content-center"
                            style="height:190px;">
                            <i class="bi bi-image text-muted fs-1"></i>
                        </div>';
        }

        echo '
        <div class="col-sm-6 col-lg-4 listing-card">
            <div class="card card-pm h-100">

                ' . $imgBlock . '

                <div class="card-body d-flex flex-column">

                    <!-- Category badge -->
                    <span class="badge bg-primary mb-2 align-self-start">'
                        . $category .
                    '</span>

                    <!-- Title -->
                    <h5 class="card-title fw-semibold mb-1">' . $title . '</h5>

                    <!-- Short description -->
                    <p class="card-text text-muted small flex-grow-1 mb-3">'
                        . $shortDesc .
                    '</p>

                    <!-- Creator + date row -->
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span title="Creator">
                            <i class="bi bi-person-fill me-1"></i>' . $creator . '
                        </span>
                        <span title="Published on">
                            <i class="bi bi-calendar3 me-1"></i>' . $createdAt . '
                        </span>
                    </div>

                    <!-- Rating + price row -->
                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2
                                border-top border-light">
                        <div>' . $ratingHtml . '</div>
                        <span class="fw-bold text-primary fs-5">BD ' . $price . '</span>
                    </div>

                </div><!-- /.card-body -->
            </div><!-- /.card -->
        </div><!-- /.col -->
        ';
    }
}

$stmt->close();
$conn->close();
