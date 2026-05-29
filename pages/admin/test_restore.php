<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';
$dbc = getDB();

$listingID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($listingID && $action == 'restore') {
    $newStatus = 'draft';
    $stmt = mysqli_prepare($dbc, "UPDATE pm_listings SET Status = ? WHERE ListingID = ?");
    mysqli_stmt_bind_param($stmt, "si", $newStatus, $listingID);
    if (mysqli_stmt_execute($stmt)) {
        echo "Success: Listing $listingID restored to 'draft'.";
    } else {
        echo "Error: " . mysqli_error($dbc);
    }
} else {
    echo "Use ?id=123&action=restore";
}
?>