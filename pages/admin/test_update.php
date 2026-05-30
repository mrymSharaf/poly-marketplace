<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';
$dbc = getDB();

$listingID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($listingID) {
    $newStatus = 'removed';
    $stmt = mysqli_prepare($dbc, "UPDATE pm_listings SET Status = ? WHERE ListingID = ?");
    mysqli_stmt_bind_param($stmt, "si", $newStatus, $listingID);
    if (mysqli_stmt_execute($stmt)) {
        echo "Success: Listing $listingID updated to 'removed'.";
    } else {
        echo "Error: " . mysqli_error($dbc);
    }
} else {
    echo "No ID provided. Use ?id=123";
}
?>