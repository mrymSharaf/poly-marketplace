<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/seller_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_listings.php');
    exit;
}

$listingID = trim($_POST['listing_id'] ?? '');
$userID    = $_SESSION['user_id'];

// Whitelist allowed redirect targets to prevent open redirect
$rawRedirect  = basename(parse_url($_POST['redirect'] ?? '', PHP_URL_PATH));
$allowed      = ['my_listings.php', 'dashboard.php'];
$redirectBase = in_array($rawRedirect, $allowed, true) ? $rawRedirect : 'my_listings.php';

if (empty($listingID)) {
    header('Location: ' . $redirectBase . '?error=' . urlencode('Invalid listing.'));
    exit;
}

$dbc  = getDB();
$stmt = $dbc->prepare(
    "UPDATE pm_listings
     SET Status = IF(Status = 'published', 'draft', 'published')
     WHERE ListingID = ? AND UserID = ?"
);
$stmt->bind_param('ss', $listingID, $userID);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
mysqli_close($dbc);

if ($affected > 0) {
    header('Location: ' . $redirectBase . '?success=' . urlencode('Listing status updated.'));
} else {
    header('Location: ' . $redirectBase . '?error=' . urlencode('Could not update listing status.'));
}
exit;
