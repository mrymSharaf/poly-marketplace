<?php
session_start();
// Include the database configuration and check authorization using relative paths
include "../../config/db.php";
include "../../includes/seller_guard.php";

// Redirect if the page was accessed without a POST form submission
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: my_listings.php');
    exit;
}

$listingID = isset($_POST['listing_id']) ? trim($_POST['listing_id']) : '';
$userID    = $_SESSION['user_id'];

if (empty($listingID)) {
    header('Location: my_listings.php?error=' . urlencode('Invalid listing.'));
    exit;
}

$dbc = getDB();

// Escape user inputs to protect database structure integrity
$safeListingID = mysqli_real_escape_string($dbc, $listingID);

// Verify record ownership and extract file paths before running the deletion sequence
$selectQuery = "SELECT ImageURL, MediaURL FROM pm_listings WHERE ListingID = '$safeListingID' AND UserID = '$userID'";
$selectResult = mysqli_query($dbc, $selectQuery);
$row = mysqli_fetch_assoc($selectResult);

if (!$row) {
    mysqli_close($dbc);
    header('Location: my_listings.php?error=' . urlencode('Listing not found or access denied.'));
    exit;
}

// Execute the query to delete the listing from the marketplace table
$deleteQuery = "DELETE FROM pm_listings WHERE ListingID = '$safeListingID' AND UserID = '$userID'";
$deleteResult = mysqli_query($dbc, $deleteQuery);

// Capture the number of modified rows before terminating the connection handle
$affectedRows = mysqli_affected_rows($dbc);

mysqli_close($dbc);

// Proceed with file management cleanups only if a database entry was successfully removed
if ($deleteResult && $affectedRows > 0) {

    // Use local relative paths to delete physical image and media file assets from the disk
    $basePath = "../../";

    if ($row['ImageURL'] != '' && file_exists($basePath . $row['ImageURL'])) {
        unlink($basePath . $row['ImageURL']);
    }

    if ($row['MediaURL'] != '' && file_exists($basePath . $row['MediaURL'])) {
        unlink($basePath . $row['MediaURL']);
    }

    header('Location: my_listings.php?success=' . urlencode('Listing deleted successfully.'));
} else {
    header('Location: my_listings.php?error=' . urlencode('Could not delete listing.'));
}
exit;
