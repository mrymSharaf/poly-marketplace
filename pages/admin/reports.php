<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();
$popularResult = null;
$userListingsResult = null;
$popularMessage = '';
$userMessage = '';
$popularStartDate = '';
$popularEndDate = '';
$selectedUserName = '';

// Report 1: Most popular content within date range
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['popular_report'])) {
    $popularStartDate = $_POST['start_date'] ?? '';
    $popularEndDate = $_POST['end_date'] ?? '';
    if ($popularStartDate && $popularEndDate) {
        $sql = "SELECT l.ListingID, l.Title, l.Price, l.Status, COUNT(r.RatingID) AS ratingCount,
                       AVG(r.RatingValue) AS avgRating, u.FullName AS CreatorName
                FROM pm_listings l
                JOIN pm_users u ON l.UserID = u.UserID
                LEFT JOIN pm_ratings r ON l.ListingID = r.ListingID
                WHERE r.CreatedAt BETWEEN '$popularStartDate' AND '$popularEndDate' OR r.CreatedAt IS NULL
                GROUP BY l.ListingID
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

// Report 2: Content created by a specific user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_report'])) {
    $userID = (int)$_POST['user_id'];
    if ($userID > 0) {
        $userQuery = mysqli_query($dbc, "SELECT FullName FROM pm_users WHERE UserID = $userID");
        if ($userRow = mysqli_fetch_assoc($userQuery)) {
            $selectedUserName = $userRow['FullName'];
        }
        $sql = "SELECT l.ListingID, l.Title, l.Price, l.Status, l.CreatedAt, c.CategoryName
                FROM pm_listings l
                JOIN pm_categories c ON l.CategoryID = c.CategoryID
                WHERE l.UserID = $userID
                ORDER BY l.CreatedAt DESC";
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

<style>
    @media print {
        .no-print, .btn, .card-header .btn, .navbar, .footer, .alert, .table a {
            display: none !important;
        }
        .card, .card-body { border: none; padding: 0; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        .report-title { font-size: 18pt; margin-bottom: 10px; }
        .report-context { font-size: 12pt; margin-bottom: 20px; color: #555; }
    }
</style>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Admin Reports</h2>

                    <div class="row">
                        <!-- Report 1: Most popular content -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Most Popular Content (by ratings)</h5>
                                    <button class="btn btn-sm btn-light no-print" onclick="printReport('popularReport', 'Most Popular Content', 'Date range: <?php echo $popularStartDate ?: '—'; ?> to <?php echo $popularEndDate ?: '—'; ?>')">Print Report</button>
                                </div>
                                <div class="card-body">
                                    <form method="post" class="row g-2 mb-3 no-print">
                                        <div class="col-5">
                                            <label>Start Date</label>
                                            <input type="date" name="start_date" class="form-control" value="<?php echo $popularStartDate; ?>" required>
                                        </div>
                                        <div class="col-5">
                                            <label>End Date</label>
                                            <input type="date" name="end_date" class="form-control" value="<?php echo $popularEndDate; ?>" required>
                                        </div>
                                        <div class="col-2 align-self-end">
                                            <button type="submit" name="popular_report" class="btn btn-primary w-100">Generate</button>
                                        </div>
                                    </form>

                                    <?php if ($popularMessage): ?>
                                        <div class="alert alert-info"><?php echo $popularMessage; ?></div>
                                    <?php endif; ?>

                                    <div id="popularReport">
                                        <?php if ($popularResult && mysqli_num_rows($popularResult) > 0): ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr><th>Title</th><th>Creator</th><th>Ratings</th><th>Avg Rating</th></tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($row = mysqli_fetch_assoc($popularResult)): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                                                <td><?php echo htmlspecialchars($row['CreatorName']); ?></td>
                                                                <td><?php echo $row['ratingCount']; ?></td>
                                                                <td><?php echo round($row['avgRating'], 1) ?: 'N/A'; ?></td>
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

                        <!-- Report 2: Content by user -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Content Created by User</h5>
                                    <button class="btn btn-sm btn-light no-print" onclick="printReport('userReport', 'Content Created by User', 'User: <?php echo htmlspecialchars($selectedUserName ?: '—'); ?>')">Print Report</button>
                                </div>
                                <div class="card-body">
                                    <form method="post" class="row g-2 mb-3 no-print">
                                        <div class="col-8">
                                            <label>Select User</label>
                                            <select name="user_id" class="form-select" required>
                                                <option value="">-- Choose a user --</option>
                                                <?php while ($u = mysqli_fetch_assoc($allUsers)): ?>
                                                    <option value="<?php echo $u['UserID']; ?>" <?php echo ($selectedUserName && $u['FullName'] == $selectedUserName) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['FullName']) . " (" . $u['Role'] . ")"; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-4 align-self-end">
                                            <button type="submit" name="user_report" class="btn btn-success w-100">Show Listings</button>
                                        </div>
                                    </form>

                                    <?php if ($userMessage): ?>
                                        <div class="alert alert-info"><?php echo $userMessage; ?></div>
                                    <?php endif; ?>

                                    <div id="userReport">
                                        <?php if ($userListingsResult && mysqli_num_rows($userListingsResult) > 0): ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
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
                                                                <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                                                <td><?php echo htmlspecialchars($row['CategoryName']); ?></td>
                                                                <td><?php echo number_format($row['Price'], 3); ?> BHD</td>
                                                                <td><?php echo htmlspecialchars($row['Status']); ?></td>
                                                                <td><?php echo date('d M Y', strtotime($row['CreatedAt'])); ?></td>
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

                    <div class="mt-3 text-center no-print">
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printReport(reportId, title, context) {
    var content = document.getElementById(reportId).cloneNode(true);
    var links = content.querySelectorAll('a');
    links.forEach(function(link) {
        var text = link.innerText;
        var span = document.createElement('span');
        span.innerText = text;
        link.parentNode.replaceChild(span, link);
    });
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>' + title + '</title>');
    printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">');
    printWindow.document.write('<style>body { padding: 20px; } table { width: 100%; } .report-title { font-size: 20pt; font-weight: bold; margin-bottom: 10px; } .report-context { font-size: 12pt; margin-bottom: 20px; color: #555; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<div class="report-title">' + title + '</div>');
    printWindow.document.write('<div class="report-context">' + context + '</div>');
    var table = content.querySelector('table');
    if (table) {
        printWindow.document.write(table.outerHTML);
    } else {
        printWindow.document.write('<p>No data available for this report.</p>');
    }
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>

<?php include '../../includes/footer.php'; ?>