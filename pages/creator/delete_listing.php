<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/seller_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_listings.php');
    exit;
}

$listingID = trim($_POST['listing_id'] ?? '');
$userID    = $_SESSION['user_id'];

if (empty($listingID)) {
    header('Location: my_listings.php?error=' . urlencode('Invalid listing.'));
    exit;
}

$dbc = getDB();

// Verify ownership and get file paths for cleanup
$stmt = $dbc->prepare("SELECT ImageURL, MediaURL FROM pm_listings WHERE ListingID = ? AND UserID = ?");
$stmt->bind_param('ss', $listingID, $userID);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    mysqli_close($dbc);
    header('Location: my_listings.php?error=' . urlencode('Listing not found or access denied.'));
    exit;
}

// Delete from DB
$stmt = $dbc->prepare("DELETE FROM pm_listings WHERE ListingID = ? AND UserID = ?");
$stmt->bind_param('ss', $listingID, $userID);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
mysqli_close($dbc);

if ($affected > 0) {
    // Remove uploaded files from disk
    $base = __DIR__ . '/../../';
    if ($row['ImageURL'] && file_exists($base . $row['ImageURL'])) {
        unlink($base . $row['ImageURL']);
    }
    if ($row['MediaURL'] && file_exists($base . $row['MediaURL'])) {
        unlink($base . $row['MediaURL']);
    }
    header('Location: my_listings.php?success=' . urlencode('Listing deleted successfully.'));
} else {
    header('Location: my_listings.php?error=' . urlencode('Could not delete listing.'));
}
exit;
