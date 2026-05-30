<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();
$popularResult    = null;
$userListingsResult = null;
$popularMessage   = '';
$userMessage      = '';
$popularStartDate = '';
$popularEndDate   = '';
$selectedUserName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['popular_report'])) {
    $popularStartDate = $_POST['start_date'] ?? '';
    $popularEndDate   = $_POST['end_date']   ?? '';
    if ($popularStartDate && $popularEndDate) {
        $sql = "SELECT l.ListingID, l.Title, l.Price, l.Status,
                       COUNT(r.RatingID) AS ratingCount,
                       AVG(r.RatingValue) AS avgRating,
                       u.FullName AS CreatorName
                FROM pm_listings l
                JOIN pm_users u ON l.UserID = u.UserID
                LEFT JOIN pm_ratings r ON l.ListingID = r.ListingID
                WHERE r.CreatedAt BETWEEN '$popularStartDate' AND '$popularEndDate'
                GROUP BY l.ListingID
                HAVING ratingCount > 0
                ORDER BY ratingCount DESC, avgRating DESC
                LIMIT 10";
        $popularResult = mysqli_query($dbc, $sql);
        if (!$popularResult) {
            $popularMessage = "Query error: " . mysqli_error($dbc);
        } elseif (mysqli_num_rows($popularResult) == 0) {
            $popularMessage = "No ratings found in this period.";
        }
    } else {
        $popularMessage = "Please select both start and end dates.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_report'])) {
    $userID = (int)$_POST['user_id'];
    if ($userID > 0) {
        $uq = mysqli_query($dbc, "SELECT FullName FROM pm_users WHERE UserID = $userID");
        if ($uRow = mysqli_fetch_assoc($uq)) $selectedUserName = $uRow['FullName'];
        $sql = "SELECT l.ListingID, l.Title, l.Price, l.Status, l.CreatedAt, c.CategoryName
                FROM pm_listings l
                JOIN pm_categories c ON l.CategoryID = c.CategoryID
                WHERE l.UserID = $userID ORDER BY l.CreatedAt DESC";
        $userListingsResult = mysqli_query($dbc, $sql);
        if (!$userListingsResult) {
            $userMessage = "Query error: " . mysqli_error($dbc);
        } elseif (mysqli_num_rows($userListingsResult) == 0) {
            $userMessage = "This user has no listings.";
        }
    } else {
        $userMessage = "Please select a user.";
    }
}

$allUsers = mysqli_query($dbc, "SELECT UserID, FullName, Role FROM pm_users ORDER BY FullName");
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="page-header pb-3">
    <div class="container">
        <h2 class="fw-bold mb-1"><i class="bi bi-bar-chart-line me-2"></i>Reports</h2>
        <p class="mb-2 opacity-75 small">Generate analytics and export data</p>
        <div class="d-flex gap-2 flex-wrap pt-1">
            <a href="dashboard.php"       class="btn btn-sm btn-outline-light rounded-pill"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
            <a href="manage_users.php"    class="btn btn-sm btn-outline-light rounded-pill">Users</a>
            <a href="manage_listings.php" class="btn btn-sm btn-outline-light rounded-pill">Listings</a>
            <a href="manage_comments.php" class="btn btn-sm btn-outline-light rounded-pill">Comments</a>
        </div>
    </div>
</div>

<div class="container page-content pb-5">

    <div class="row g-4">

        <!-- Report 1 -->
        <div class="col-lg-6">
            <div class="card pm-table-card h-100">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-navy mb-0">
                        <i class="bi bi-trophy me-2" style="color:var(--pm-orange);"></i>Most Popular by Date Range
                    </h5>
                    <button class="btn btn-sm btn-outline-navy rounded-pill no-print"
                            onclick="printReport('popularReport','Most Popular Content','Date: <?= $popularStartDate ?: '—' ?> to <?= $popularEndDate ?: '—' ?>')">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                </div>
                <div class="card-body px-4">
                    <form method="post" class="row g-2 mb-3 no-print">
                        <div class="col-5">
                            <label class="form-label small fw-semibold">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm"
                                   value="<?= $popularStartDate ?>" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-semibold">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm"
                                   value="<?= $popularEndDate ?>" required>
                        </div>
                        <div class="col-2 align-self-end">
                            <button type="submit" name="popular_report" class="btn btn-navy btn-sm w-100">Go</button>
                        </div>
                    </form>

                    <?php if ($popularMessage): ?>
                        <div class="alert alert-info border-0 rounded-3 small"><?= htmlspecialchars($popularMessage) ?></div>
                    <?php endif; ?>

                    <div id="popularReport">
                        <?php if ($popularResult && mysqli_num_rows($popularResult) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Creator</th>
                                            <th>Ratings</th>
                                            <th>Avg</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $rank = 1; while ($row = mysqli_fetch_assoc($popularResult)): ?>
                                            <tr>
                                                <td class="text-muted small"><?= $rank++ ?></td>
                                                <td class="fw-semibold small"><?= htmlspecialchars($row['Title']) ?></td>
                                                <td class="text-muted small"><?= htmlspecialchars($row['CreatorName']) ?></td>
                                                <td><?= $row['ratingCount'] ?></td>
                                                <td>
                                                    <span class="fw-semibold" style="color:var(--pm-orange);">
                                                        <?= round($row['avgRating'], 1) ?>★
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report 2 -->
        <div class="col-lg-6">
            <div class="card pm-table-card h-100">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-navy mb-0">
                        <i class="bi bi-person-lines-fill me-2" style="color:var(--pm-cyan);"></i>Content by User
                    </h5>
                    <button class="btn btn-sm btn-outline-navy rounded-pill no-print"
                            onclick="printReport('userReport','Content by User','User: <?= htmlspecialchars($selectedUserName ?: '—') ?>')">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                </div>
                <div class="card-body px-4">
                    <form method="post" class="row g-2 mb-3 no-print">
                        <div class="col-8">
                            <label class="form-label small fw-semibold">Select User</label>
                            <select name="user_id" class="form-select form-select-sm" required>
                                <option value="">— Choose a user —</option>
                                <?php while ($u = mysqli_fetch_assoc($allUsers)): ?>
                                    <option value="<?= $u['UserID'] ?>"
                                        <?= ($selectedUserName && $u['FullName'] == $selectedUserName) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['FullName']) ?> (<?= $u['Role'] ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-4 align-self-end">
                            <button type="submit" name="user_report" class="btn btn-navy btn-sm w-100">Show</button>
                        </div>
                    </form>

                    <?php if ($userMessage): ?>
                        <div class="alert alert-info border-0 rounded-3 small"><?= htmlspecialchars($userMessage) ?></div>
                    <?php endif; ?>

                    <div id="userReport">
                        <?php if ($userListingsResult && mysqli_num_rows($userListingsResult) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($userListingsResult)): ?>
                                            <tr>
                                                <td class="fw-semibold small"><?= htmlspecialchars($row['Title']) ?></td>
                                                <td><span class="category-badge"><?= htmlspecialchars($row['CategoryName']) ?></span></td>
                                                <td class="text-navy fw-semibold small"><?= number_format($row['Price'], 3) ?> BHD</td>
                                                <td>
                                                    <span class="badge rounded-pill <?= $row['Status'] === 'published' ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= ucfirst($row['Status']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted small"><?= date('d M Y', strtotime($row['CreatedAt'])) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function printReport(reportId, title, context) {
    const content = document.getElementById(reportId).cloneNode(true);
    content.querySelectorAll('a').forEach(a => {
        const span = document.createElement('span');
        span.textContent = a.textContent;
        a.replaceWith(span);
    });
    const w = window.open('', '_blank');
    w.document.write(`<html><head><title>${title}</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <style>body{padding:24px} h2{margin-bottom:6px} .sub{color:#666;margin-bottom:20px;font-size:.9rem}</style>
        </head><body>
        <h2>${title}</h2><div class="sub">${context}</div>
        ${content.querySelector('table') ? content.querySelector('table').outerHTML : '<p>No data.</p>'}
        </body></html>`);
    w.document.close();
    w.print();
}
</script>

<?php include '../../includes/footer.php'; ?>
