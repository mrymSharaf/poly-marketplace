<?php


// Include database configuration and access guard components using relative paths
include "../../config/db.php";
include "../../includes/seller_guard.php";

// Redirect if this script is accessed without a POST form submission
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: my_listings.php');
    exit;
}

$listingID = isset($_POST['listing_id']) ? trim($_POST['listing_id']) : '';
$userID    = $_SESSION['user_id'];

// Grab the intended redirect path fallback onto my_listings if empty
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'my_listings.php';

// Simple validation step to ensure the user is only sent back to dashboards or listing arrays
if (strpos($redirect, 'my_listings.php') === false && strpos($redirect, 'dashboard.php') === false) {
    $redirect = 'my_listings.php';
}

if (empty($listingID)) {
    header('Location: ' . $redirect . '?error=' . urlencode('Invalid listing.'));
    exit;
}

$dbc = getDB();

// Escape data parameters to protect database stream properties
$safeListingID = mysqli_real_escape_string($dbc, $listingID);

// Execute query to flip the current publish status state back and forth
$sql = "UPDATE pm_listings 
        SET Status = IF(Status = 'published', 'draft', 'published') 
        WHERE ListingID = '$safeListingID' AND UserID = '$userID'";

$result = mysqli_query($dbc, $sql);

// Track modified rows before terminating database connection handle
$affectedRows = mysqli_affected_rows($dbc);

mysqli_close($dbc);

// Evaluate modification flags to return corresponding status messages
if ($result && $affectedRows > 0) {
    header('Location: ' . $redirect . '?success=' . urlencode('Listing status updated.'));
} else {
    header('Location: ' . $redirect . '?error=' . urlencode('Could not update listing status.'));
}
exit;
