<?php
session_start();
include "config/db.php";

header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

$dbc = getDB();

$listingID = $_POST['listing_id'] ?? 0;
$content = trim($_POST['content'] ?? '');
$rating = $_POST['rating'] ?? 0;
$userID = $_SESSION['user_id'];
$fullName = $_SESSION['name'] ?? 'User';

// Simple validation checks
if ($listingID == 0 || $content == '' || $rating < 1 || $rating > 5) {
    echo json_encode(["success" => false, "message" => "Enter a comment and rating"]);
    exit;
}

$checkQuery = "SELECT ListingID FROM pm_listings WHERE ListingID = '$listingID' AND Status = 'published'";
$checkResult = mysqli_query($dbc, $checkQuery);

if (mysqli_num_rows($checkResult) == 0) {
    echo json_encode(["success" => false, "message" => "Listing not found"]);
    exit;
}

$commentQuery = "INSERT INTO pm_comments (Content, ListingID, UserID) VALUES ('$content', '$listingID', '$userID')";
$commentSaved = mysqli_query($dbc, $commentQuery);

if ($commentSaved) {
    $deleteOldRating = "DELETE FROM pm_ratings WHERE ListingID = '$listingID' AND UserID = '$userID'";
    mysqli_query($dbc, $deleteOldRating);

    $ratingQuery = "INSERT INTO pm_ratings (RatingValue, ListingID, UserID) VALUES ('$rating', '$listingID', '$userID')";
    mysqli_query($dbc, $ratingQuery);
}

mysqli_close($dbc);

if (!$commentSaved) {
    echo json_encode(["success" => false, "message" => "Comment was not saved"]);
    exit;
}

$safeName = htmlspecialchars($fullName);
$safeContent = nl2br(htmlspecialchars($content));
$safeDate = date('d M Y H:i');

$initial = strtoupper(substr($fullName, 0, 1));
$html = '<div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-circle">' . htmlspecialchars($initial) . '</div>
                    <div>
                        <div class="fw-semibold small text-navy">' . $safeName . '</div>
                        <div class="text-muted" style="font-size:.72rem;">' . $safeDate . '</div>
                    </div>
                </div>
                <p class="mb-0 small text-muted lh-base">' . $safeContent . '</p>
            </div>
        </div>';

echo json_encode(["success" => true, "html" => $html]);
