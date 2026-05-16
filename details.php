<?php
session_start();
include "config/db.php";

$dbc = getDB();
$listingID = $_GET['id'] ?? 0;

// Fetch listing details with a direct query
$query = "SELECT l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.MediaURL,
                 l.CreatedAt, c.CategoryName, u.FullName AS CreatorName,
                 AVG(r.RatingValue) AS AverageRating, COUNT(r.RatingID) AS RatingCount
          FROM pm_listings l
          JOIN pm_categories c ON l.CategoryID = c.CategoryID
          JOIN pm_users u ON l.UserID = u.UserID
          LEFT JOIN pm_ratings r ON l.ListingID = r.ListingID
          WHERE l.ListingID = '$listingID' AND l.Status = 'published'
          GROUP BY l.ListingID, l.Title, l.Description, l.Price, l.ImageURL, l.MediaURL,
                   l.CreatedAt, c.CategoryName, u.FullName";

$result = mysqli_query($dbc, $query);
$listing = mysqli_fetch_assoc($result);

// Fetch comments with a direct query
$commentQuery = "SELECT cm.CommentID, cm.Content, cm.CreatedAt, u.FullName
                 FROM pm_comments cm
                 JOIN pm_users u ON cm.UserID = u.UserID
                 WHERE cm.ListingID = '$listingID' AND cm.IsDeleted = 0
                 ORDER BY cm.CreatedAt DESC";
$commentResult = mysqli_query($dbc, $commentQuery);

function showStars($averageRating)
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

<main class="container py-5">
    <?php if (!$listing): ?>
        <div class="alert alert-danger">Listing not found.</div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card overflow-hidden">
                    <?php if ($listing['ImageURL'] != ''): ?>
                        <img src="<?php echo htmlspecialchars($listing['ImageURL']); ?>"
                            class="w-100"
                            style="height:420px; object-fit:cover;"
                            alt="<?php echo htmlspecialchars($listing['Title']); ?>">
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height:420px;">
                            <span class="text-muted">No Image Available</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <span class="badge bg-primary mb-3"><?php echo htmlspecialchars($listing['CategoryName']); ?></span>
                <h1 class="h2 fw-bold"><?php echo htmlspecialchars($listing['Title']); ?></h1>
                <p class="fs-4 fw-bold text-primary"><?php echo number_format($listing['Price'], 3); ?> BHD</p>
                <p class="text-muted">Created by <?php echo htmlspecialchars($listing['CreatorName']); ?></p>

                <div class="mb-4">
                    <?php showStars($listing['AverageRating']); ?>
                    <span class="text-muted small ms-2">
                        <?php
                        if ($listing['RatingCount'] > 0) {
                            echo number_format($listing['AverageRating'], 1) . ' average rating';
                        } else {
                            echo 'No ratings yet';
                        }
                        ?>
                    </span>
                </div>
                <p><?php echo nl2br(htmlspecialchars($listing['Description'])); ?></p>
            </div>
        </div>

        <hr class="my-5">

        <section class="row g-4">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <h3 class="h5 fw-bold mb-3">Post a Comment</h3>

                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-info mb-0">
                                Please <a href="pages/login.php">login</a> first to comment.
                            </div>
                        <?php else: ?>
                            <form id="commentForm">
                                <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($listing['ListingID']); ?>">

                                <div class="mb-3">
                                    <label for="rating" class="form-label">Rating</label>
                                    <select name="rating" id="rating" class="form-select" required>
                                        <option value="">Choose rating</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="content" class="form-label">Comment</label>
                                    <textarea name="content" id="content" class="form-control" rows="4" required></textarea>
                                </div>

                                <button type="submit" id="postCommentBtn" class="btn btn-success">Post Comment</button>
                                <div id="commentMessage" class="small mt-3"></div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <h3 class="h5 fw-bold mb-3">Comments</h3>
                <div id="commentsList">
                    <?php if (mysqli_num_rows($commentResult) == 0): ?>
                        <div class="alert alert-secondary" id="noCommentsAlert">No comments yet.</div>
                    <?php else: ?>
                        <?php while ($comment = mysqli_fetch_assoc($commentResult)): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between gap-3 mb-2">
                                        <strong><?php echo htmlspecialchars($comment['FullName']); ?></strong>
                                        <small class="text-muted"><?php echo date('d M Y H:i', strtotime($comment['CreatedAt'])); ?></small>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['Content'])); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        $('#commentForm').on('submit', function(event) {
            event.preventDefault();

            $('#commentMessage').text('Saving...');

            $.ajax({
                url: 'ajax_comment.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#noCommentsAlert').remove();
                        $('#commentsList').prepend(response.html);
                        $('#content').val('');
                        $('#rating').val('');
                        $('#commentMessage').css('color', 'green').text('Saved successfully.');
                    } else {
                        $('#commentMessage').css('color', 'red').text(response.message);
                    }
                },
                error: function() {
                    $('#commentMessage').css('color', 'red').text('An error occurred.');
                }
            });
        });
    });
</script>

<?php include "includes/footer.php"; ?>