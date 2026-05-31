<?php
session_start();
include "config/db.php";

$dbc = getDB();
$listingID = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($dbc, "SELECT l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.MediaURL,
               l.CreatedAt, c.CategoryName, u.FullName AS CreatorName,
               AVG(r.RatingValue) AS AverageRating, COUNT(r.RatingID) AS RatingCount
        FROM pm_listings l
        JOIN pm_categories c ON l.CategoryID = c.CategoryID
        JOIN pm_users u ON l.UserID = u.UserID
        LEFT JOIN pm_ratings r ON l.ListingID = r.ListingID
        WHERE l.ListingID = ? AND l.Status = 'published'
        GROUP BY l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.MediaURL,
                 l.CreatedAt, c.CategoryName, u.FullName");
mysqli_stmt_bind_param($stmt, "i", $listingID);
mysqli_stmt_execute($stmt);
$listing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$commentStmt = mysqli_prepare($dbc, "SELECT cm.CommentID, cm.Content, cm.CreatedAt, u.FullName
               FROM pm_comments cm
               JOIN pm_users u ON cm.UserID = u.UserID
               WHERE cm.ListingID = ? AND cm.IsDeleted = 0
               ORDER BY cm.CreatedAt DESC");
mysqli_stmt_bind_param($commentStmt, "i", $listingID);
mysqli_stmt_execute($commentStmt);
$commentResult = mysqli_stmt_get_result($commentStmt);
?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>

<?php if (!$listing): ?>
    <div class="container text-center py-5">
        <i class="bi bi-exclamation-circle display-1 icon-empty-state"></i>
        <h3 class="mt-3 text-navy fw-bold">Listing Not Found</h3>
        <p class="text-muted">This listing may have been removed or is no longer available.</p>
        <a href="index.php" class="btn btn-navy rounded-pill px-4 mt-2">
            <i class="bi bi-arrow-left me-2"></i>Browse Listings
        </a>
    </div>
<?php else:
    $avgRating    = (float)$listing['AverageRating'];
    $ratingCount  = (int)$listing['RatingCount'];
    $ratingRounded = (int)round($avgRating);
    $commentCount = mysqli_num_rows($commentResult);
?>

<!-- Page header -->
<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="index.php" class="text-white-50 text-decoration-none">Home</a>
                </li>
                <li class="breadcrumb-item active text-white">
                    <?= htmlspecialchars($listing['CategoryName']) ?>
                </li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold mb-0"><?= htmlspecialchars($listing['Title']) ?></h1>
    </div>
</div>

<main class="container py-5">

    <!-- ── Listing detail ── -->
    <div class="row g-5 mb-5">

        <!-- Image -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <?php if (!empty($listing['ImageURL'])): ?>
                    <img src="<?= htmlspecialchars($listing['ImageURL']) ?>"
                         class="w-100 object-fit-cover"
                         style="max-height:440px;"
                         alt="<?= htmlspecialchars($listing['Title']) ?>">
                <?php else: ?>
                    <div class="card-img-placeholder" style="height:440px;">
                        <i class="bi bi-image fs-1 text-secondary opacity-50"></i>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($listing['MediaURL'])): ?>
                <div class="card border-0 shadow-sm rounded-4 p-3 mt-3">
                    <h6 class="fw-semibold text-navy mb-2">
                        <i class="bi bi-play-circle me-2"></i>Media
                    </h6>
                    <audio controls class="w-100">
                        <source src="<?= htmlspecialchars($listing['MediaURL']) ?>">
                        Your browser does not support audio playback.
                    </audio>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info panel -->
        <div class="col-lg-6">

            <span class="category-badge mb-3 d-inline-block">
                <?= htmlspecialchars($listing['CategoryName']) ?>
            </span>

            <h2 class="fw-bold text-navy mb-2"><?= htmlspecialchars($listing['Title']) ?></h2>

            <div class="mb-3">
                <span class="price-tag" style="font-size:1.8rem;">
                    <?= number_format($listing['Price'], 3) ?>
                </span>
                <span class="text-muted ms-1">BHD</span>
            </div>

            <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-circle">
                        <?= strtoupper(substr($listing['CreatorName'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="small fw-semibold text-navy"><?= htmlspecialchars($listing['CreatorName']) ?></div>
                        <div class="text-muted" style="font-size:.72rem;">
                            <i class="bi bi-clock me-1"></i><?= date('d M Y', strtotime($listing['CreatedAt'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 mb-4">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi <?= $i <= $ratingRounded ? 'bi-star-fill star-filled' : 'bi-star star-empty' ?> star-icon-lg"></i>
                <?php endfor; ?>
                <span class="text-muted small ms-1">
                    <?php if ($ratingCount > 0): ?>
                        <?= number_format($avgRating, 1) ?> / 5
                        <span class="ms-1 text-muted">(avg of <?= $ratingCount ?> rating<?= $ratingCount !== 1 ? 's' : '' ?>)</span>
                    <?php else: ?>
                        No ratings yet
                    <?php endif; ?>
                </span>
            </div>

            <hr class="mb-4">

            <h6 class="fw-semibold text-navy mb-2">
                <i class="bi bi-file-text me-2"></i>Description
            </h6>
            <p class="text-muted lh-lg"><?= nl2br(htmlspecialchars($listing['Description'])) ?></p>

        </div>
    </div>

    <hr class="my-5">

    <!-- ── Reviews ── -->
    <div class="row g-4">

        <!-- Post review -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-navy mb-3">
                        <i class="bi bi-pencil-square me-2"></i>Write a Review
                    </h5>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-info border-0 rounded-3 mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Please <a href="pages/login.php" class="fw-semibold">login</a> to leave a review.
                        </div>
                    <?php else: ?>
                        <form id="commentForm">
                            <input type="hidden" name="listing_id" value="<?= (int)$listing['ListingID'] ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Rating</label>
                                <select name="rating" id="rating" class="form-select" required>
                                    <option value="">Choose a rating…</option>
                                    <option value="5">★★★★★  Excellent</option>
                                    <option value="4">★★★★☆  Good</option>
                                    <option value="3">★★★☆☆  Average</option>
                                    <option value="2">★★☆☆☆  Poor</option>
                                    <option value="1">★☆☆☆☆  Terrible</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Comment</label>
                                <textarea name="content" id="content" class="form-control" rows="4"
                                          placeholder="Share your thoughts…" required></textarea>
                            </div>

                            <button type="submit" id="postCommentBtn" class="btn btn-navy w-100">
                                <i class="bi bi-send me-2"></i>Post Review
                            </button>
                            <div id="commentMessage" class="small mt-2 text-center"></div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reviews list -->
        <div class="col-lg-7">
            <h5 class="fw-bold text-navy mb-3">
                <i class="bi bi-chat-square-text me-2"></i>Reviews
                <span class="badge rounded-pill bg-primary-subtle text-primary fw-normal ms-1">
                    <?= $commentCount ?>
                </span>
            </h5>

            <div id="commentsList">
                <?php if ($commentCount === 0): ?>
                    <div class="text-center py-4" id="noCommentsAlert">
                        <i class="bi bi-chat-square display-4 icon-empty-state d-block mb-2"></i>
                        <p class="text-muted mb-0">No reviews yet. Be the first!</p>
                    </div>
                <?php else: ?>
                    <?php while ($comment = mysqli_fetch_assoc($commentResult)): ?>
                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="avatar-circle">
                                        <?= strtoupper(substr($comment['FullName'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small text-navy">
                                            <?= htmlspecialchars($comment['FullName']) ?>
                                        </div>
                                        <div class="text-muted" style="font-size:.72rem;">
                                            <?= date('d M Y, H:i', strtotime($comment['CreatedAt'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <p class="mb-0 small text-muted lh-base">
                                    <?= nl2br(htmlspecialchars($comment['Content'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function () {
    $('#commentForm').on('submit', function (e) {
        e.preventDefault();
        const btn = $('#postCommentBtn');
        btn.prop('disabled', true);
        $('#commentMessage').removeClass('text-success text-danger').text('Saving…');

        $.ajax({
            url: 'ajax_comment.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $('#noCommentsAlert').remove();
                    $('#commentsList').prepend(res.html);
                    $('#commentForm')[0].reset();
                    $('#commentMessage').addClass('text-success').text('Review posted!');
                } else {
                    $('#commentMessage').addClass('text-danger').text(res.message);
                }
            },
            error: function () {
                $('#commentMessage').addClass('text-danger').text('An error occurred. Please try again.');
            },
            complete: function () { btn.prop('disabled', false); }
        });
    });
});
</script>

<?php include "includes/footer.php"; ?>
