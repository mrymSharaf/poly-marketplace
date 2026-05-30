<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$statusFilter = $_GET['status'] ?? 'all';
$dbc = getDB();

if ($action == 'delete') $new = 'removed';
elseif ($action == 'restore') $new = 'draft';
elseif ($action == 'publish') $new = 'published';
else die('Invalid action');

$stmt = mysqli_prepare($dbc, "UPDATE pm_listings SET Status = ? WHERE ListingID = ?");
mysqli_stmt_bind_param($stmt, "si", $new, $id);
if (mysqli_stmt_execute($stmt)) {
    $msg = "Listing " . $action . " successful";
} else {
    $msg = "Database error: " . mysqli_error($dbc);
}
header("Location: manage_listings.php?status=$statusFilter&msg=" . urlencode($msg));