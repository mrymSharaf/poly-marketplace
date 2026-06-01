<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once "../config/db.php";
require_once "../includes/pagination.php";

$dbc    = getDB();
$userID = (int)$_SESSION['user_id'];
$message     = '';
$messageType = '';

// ── Handle profile update ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName = trim($_POST['fullname'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $curPass = $_POST['current_password'] ?? '';

    if (empty($newName)) {
        $message     = "Name cannot be empty.";
        $messageType = 'danger';
    } else {
        $s = mysqli_prepare($dbc, "SELECT PasswordHash FROM pm_users WHERE UserID = ?");
        mysqli_stmt_bind_param($s, "i", $userID);
        mysqli_stmt_execute($s);
        $hashRow = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

        if (!empty($newPass)) {
            if (!password_verify($curPass, $hashRow['PasswordHash'])) {
                $message     = "Current password is incorrect.";
                $messageType = 'danger';
            } elseif (strlen($newPass) < 6) {
                $message     = "New password must be at least 6 characters.";
                $messageType = 'danger';
            } else {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $u = mysqli_prepare($dbc, "UPDATE pm_users SET FullName = ?, PasswordHash = ? WHERE UserID = ?");
                mysqli_stmt_bind_param($u, "ssi", $newName, $hash, $userID);
                mysqli_stmt_execute($u);
                $_SESSION['name'] = $newName;
                $message     = "Profile updated successfully.";
                $messageType = 'success';
            }
        } else {
            $u = mysqli_prepare($dbc, "UPDATE pm_users SET FullName = ? WHERE UserID = ?");
            mysqli_stmt_bind_param($u, "si", $newName, $userID);
            mysqli_stmt_execute($u);
            $_SESSION['name'] = $newName;
            $message     = "Profile updated successfully.";
            $messageType = 'success';
        }
    }
}

// ── Fetch user ────────────────────────────────────────────────────────────────
$s = mysqli_prepare($dbc, "SELECT UserID, FullName, Email, Role FROM pm_users WHERE UserID = ?");
mysqli_stmt_bind_param($s, "i", $userID);
mysqli_stmt_execute($s);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Listings pagination (creators only)
$listingsPerPage   = 10;
$listingsPage      = max(1, (int)($_GET['lp'] ?? 1));
$totalListings     = 0;
$totalListingPages = 1;
$listings          = [];

if ($user['Role'] === 'creator') {
    $lCount = mysqli_prepare($dbc, "SELECT COUNT(*) AS total FROM pm_listings WHERE UserID = ?");
    mysqli_stmt_bind_param($lCount, "i", $userID);
    mysqli_stmt_execute($lCount);
    $totalListings     = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($lCount))['total'] ?? 0);
    $totalListingPages = max(1, (int)ceil($totalListings / $listingsPerPage));
    $listingsPage      = min($listingsPage, $totalListingPages);
    $listingsOffset    = ($listingsPage - 1) * $listingsPerPage;

    $lData = mysqli_prepare($dbc, "SELECT l.ListingID, l.Title, l.Price, l.Status, l.CreatedAt, c.CategoryName
        FROM pm_listings l
        JOIN pm_categories c ON l.CategoryID = c.CategoryID
        WHERE l.UserID = ?
        ORDER BY l.CreatedAt DESC
        LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($lData, "iii", $userID, $listingsPerPage, $listingsOffset);
    mysqli_stmt_execute($lData);
    $r = mysqli_stmt_get_result($lData);
    while ($row = mysqli_fetch_assoc($r)) $listings[] = $row;
}

// ── Comments/reviews pagination ───────────────────────────────────────────────
$commentsPerPage   = 5;
$commentsPage      = max(1, (int)($_GET['cp'] ?? 1));

$cCount = mysqli_prepare($dbc, "SELECT COUNT(*) AS total FROM pm_comments WHERE UserID = ? AND IsDeleted = 0");
mysqli_stmt_bind_param($cCount, "i", $userID);
mysqli_stmt_execute($cCount);
$totalComments     = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($cCount))['total'] ?? 0);
$totalCommentPages = max(1, (int)ceil($totalComments / $commentsPerPage));
$commentsPage      = min($commentsPage, $totalCommentPages);
$commentsOffset    = ($commentsPage - 1) * $commentsPerPage;

$cData = mysqli_prepare($dbc, "SELECT cm.Content, cm.CreatedAt, l.Title AS ListingTitle, l.ListingID
    FROM pm_comments cm
    JOIN pm_listings l ON cm.ListingID = l.ListingID
    WHERE cm.UserID = ? AND cm.IsDeleted = 0
    ORDER BY cm.CreatedAt DESC
    LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($cData, "iii", $userID, $commentsPerPage, $commentsOffset);
mysqli_stmt_execute($cData);
$r2 = mysqli_stmt_get_result($cData);
$comments = [];
while ($row = mysqli_fetch_assoc($r2)) $comments[] = $row;

// ── Rating stats ──────────────────────────────────────────────────────────────
$ratingStats = ['given' => 0, 'avg' => 0];
$s4 = mysqli_prepare($dbc, "SELECT COUNT(*) AS cnt, COALESCE(AVG(RatingValue),0) AS avg FROM pm_ratings WHERE UserID = ?");
mysqli_stmt_bind_param($s4, "i", $userID);
mysqli_stmt_execute($s4);
$rs = mysqli_fetch_assoc(mysqli_stmt_get_result($s4));
$ratingStats = ['given' => (int)$rs['cnt'], 'avg' => round((float)$rs['avg'], 1)];

$roleColors = ['admin' => 'bg-danger', 'creator' => 'bg-success', 'viewer' => 'bg-primary'];
$roleColor  = $roleColors[$user['Role']] ?? 'bg-secondary';

// ── Pagination URL builders (preserve both lp and cp) ─────────────────────────
function profileListingsLink(int $p): string {
    $q       = $_GET;
    $q['lp'] = $p;
    return '?' . http_build_query($q);
}
function profileCommentsLink(int $p): string {
    $q       = $_GET;
    $q['cp'] = $p;
    return '?' . http_build_query($q);
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>

<div class="page-header">
    <div class="container">
        <h2 class="fw-bold mb-1"><i class="bi bi-person-circle me-2"></i>My Profile</h2>
        <p class="mb-0 opacity-75 small">Manage your account and view your activity</p>
    </div>
</div>

<div class="container page-content pb-5">

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> border-0 rounded-3 small mb-4">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ── Left column ── -->
        <div class="col-lg-4">

            <!-- Avatar + info -->
            <div class="card pm-table-card mb-4 text-center">
                <div class="card-body py-4 px-4">
                    <div class="avatar-circle mx-auto mb-3" style="width:72px;height:72px;font-size:1.8rem;">
                        <?= strtoupper(substr($user['FullName'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold text-navy mb-1"><?= htmlspecialchars($user['FullName']) ?></h5>
                    <p class="text-muted small mb-2"><?= htmlspecialchars($user['Email']) ?></p>
                    <span class="badge rounded-pill <?= $roleColor ?> mb-3"><?= ucfirst($user['Role']) ?></span>

                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <div class="bg-light rounded-3 py-2">
                                <div class="fw-bold text-navy"><?= $totalComments ?></div>
                                <div class="text-muted" style="font-size:.72rem;">Reviews</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded-3 py-2">
                                <div class="fw-bold" style="color:var(--pm-orange);"><?= $ratingStats['given'] > 0 ? $ratingStats['avg'] . '★' : '—' ?></div>
                                <div class="text-muted" style="font-size:.72rem;">Avg Rating Given</div>
                            </div>
                        </div>
                        <?php if ($user['Role'] === 'creator'): ?>
                            <div class="col-12">
                                <div class="bg-light rounded-3 py-2">
                                    <div class="fw-bold text-navy"><?= $totalListings ?></div>
                                    <div class="text-muted" style="font-size:.72rem;">Listings</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Edit form -->
            <div class="card pm-table-card">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-pencil me-2"></i>Edit Profile</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Full Name</label>
                            <input type="text" name="fullname" class="form-control"
                                   value="<?= htmlspecialchars($user['FullName']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" class="form-control"
                                   value="<?= htmlspecialchars($user['Email']) ?>" disabled>
                            <div class="form-text">Email cannot be changed.</div>
                        </div>
                        <hr class="my-3">
                        <p class="small text-muted mb-2 fw-semibold">Change Password</p>
                        <div class="mb-3">
                            <label class="form-label small">Current Password</label>
                            <input type="password" name="current_password" class="form-control"
                                   placeholder="Required to set new password">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small">New Password</label>
                            <input type="password" name="new_password" class="form-control"
                                   placeholder="Min 6 characters">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-navy w-100 rounded-3">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <!-- ── Right column ── -->
        <div class="col-lg-8">

            <!-- Creator listings -->
            <?php if ($user['Role'] === 'creator'): ?>
                <div class="card pm-table-card mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-navy mb-0">
                            <i class="bi bi-box-seam me-2"></i>My Listings
                        </h6>
                        <div class="d-flex align-items-center gap-3">
                            <?php if ($totalListingPages > 1): ?>
                                <small class="text-muted">Page <?= $listingsPage ?> of <?= $totalListingPages ?></small>
                            <?php endif; ?>
                            <a href="creator/my_listings.php" class="btn btn-outline-navy btn-sm rounded-pill px-3">
                                Manage <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($listings)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox icon-empty-state fs-1 d-block mb-2"></i>
                                <p class="text-muted small mb-2">No listings yet.</p>
                                <a href="creator/create_listing.php" class="btn btn-navy btn-sm rounded-pill px-3">Create one</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Title</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th class="pe-4">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listings as $row): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <a href="../details.php?id=<?= $row['ListingID'] ?>"
                                                       class="fw-semibold text-navy text-decoration-none">
                                                        <?= htmlspecialchars($row['Title']) ?>
                                                    </a>
                                                </td>
                                                <td><span class="category-badge"><?= htmlspecialchars($row['CategoryName']) ?></span></td>
                                                <td class="fw-semibold text-navy"><?= number_format($row['Price'], 3) ?> BHD</td>
                                                <td>
                                                    <span class="badge rounded-pill <?= $row['Status'] === 'published' ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= ucfirst($row['Status']) ?>
                                                    </span>
                                                </td>
                                                <td class="pe-4 text-muted small"><?= date('d M Y', strtotime($row['CreatedAt'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php renderPagination($listingsPage, $totalListingPages, 'profileListingsLink'); ?>

                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Reviews / comments -->
            <div class="card pm-table-card">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-navy mb-0">
                        <i class="bi bi-chat-square-text me-2"></i>My Reviews
                        <?php if ($totalComments > 0): ?>
                            <span class="badge rounded-pill bg-primary-subtle text-primary fw-normal ms-1"><?= $totalComments ?></span>
                        <?php endif; ?>
                    </h6>
                    <?php if ($totalCommentPages > 1): ?>
                        <small class="text-muted">Page <?= $commentsPage ?> of <?= $totalCommentPages ?></small>
                    <?php endif; ?>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if (empty($comments)): ?>
                        <div class="text-center py-3">
                            <i class="bi bi-chat-square icon-empty-state fs-1 d-block mb-2"></i>
                            <p class="text-muted small mb-0">No reviews posted yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($comments as $c): ?>
                            <div class="d-flex gap-3 py-3 border-bottom">
                                <div class="avatar-circle flex-shrink-0" style="width:36px;height:36px;font-size:.85rem;">
                                    <?= strtoupper(substr($user['FullName'], 0, 1)) ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <a href="../details.php?id=<?= $c['ListingID'] ?>"
                                           class="fw-semibold text-navy small text-decoration-none">
                                            <?= htmlspecialchars($c['ListingTitle']) ?>
                                            <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.65rem;"></i>
                                        </a>
                                        <span class="text-muted" style="font-size:.72rem;">
                                            <?= date('d M Y', strtotime($c['CreatedAt'])) ?>
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-0"><?= htmlspecialchars($c['Content']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php renderPagination($commentsPage, $totalCommentPages, 'profileCommentsLink'); ?>

                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
