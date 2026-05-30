<?php
require_once 'admin_guard.php';
require_once '../../config/db.php';

$dbc = getDB();

$r = mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_users");
$userCount = (int)mysqli_fetch_assoc($r)['total'];

$r = mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_listings");
$listingCount = (int)mysqli_fetch_assoc($r)['total'];

$r = mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_listings WHERE Status = 'published'");
$publishedCount = (int)mysqli_fetch_assoc($r)['total'];

$r = mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_comments WHERE IsDeleted = 0");
$commentCount = (int)mysqli_fetch_assoc($r)['total'];

$r = mysqli_query($dbc, "SELECT COUNT(*) AS total FROM pm_users WHERE Role = 'creator'");
$creatorCount = (int)mysqli_fetch_assoc($r)['total'];

// Chart: listings per category
$catLabels = [];
$catData   = [];
$r = mysqli_query($dbc, "SELECT c.CategoryName, COUNT(l.ListingID) AS cnt
    FROM pm_categories c
    LEFT JOIN pm_listings l ON c.CategoryID = l.CategoryID AND l.Status = 'published'
    GROUP BY c.CategoryID, c.CategoryName ORDER BY cnt DESC");
while ($row = mysqli_fetch_assoc($r)) {
    $catLabels[] = $row['CategoryName'];
    $catData[]   = (int)$row['cnt'];
}

// Chart: users by role
$roleLabels = [];
$roleData   = [];
$r = mysqli_query($dbc, "SELECT Role, COUNT(*) AS cnt FROM pm_users GROUP BY Role ORDER BY cnt DESC");
while ($row = mysqli_fetch_assoc($r)) {
    $roleLabels[] = ucfirst($row['Role']);
    $roleData[]   = (int)$row['cnt'];
}

// Chart: listings by status
$statusLabels = ['Published', 'Draft', 'Removed'];
$statusData   = [];
foreach (['published', 'draft', 'removed'] as $s) {
    $r2 = mysqli_query($dbc, "SELECT COUNT(*) AS cnt FROM pm_listings WHERE Status = '$s'");
    $statusData[] = (int)mysqli_fetch_assoc($r2)['cnt'];
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

<div class="page-header">
    <div class="container">
        <h2 class="fw-bold mb-1"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h2>
        <p class="mb-0 opacity-75 small">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>!</p>
    </div>
</div>

<div class="container page-content pb-5">

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card stat-card-navy h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <i class="bi bi-people-fill fs-1 text-navy"></i>
                    <div>
                        <div class="fs-2 fw-bold text-navy"><?= $userCount ?></div>
                        <div class="text-muted small">Total Users</div>
                        <div class="text-muted" style="font-size:.72rem;"><?= $creatorCount ?> creators</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card stat-card-cyan h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <i class="bi bi-box-seam fs-1" style="color:var(--pm-cyan);"></i>
                    <div>
                        <div class="fs-2 fw-bold" style="color:var(--pm-cyan);"><?= $listingCount ?></div>
                        <div class="text-muted small">Total Listings</div>
                        <div class="text-muted" style="font-size:.72rem;"><?= $publishedCount ?> published</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card stat-card-orange h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <i class="bi bi-chat-dots-fill fs-1" style="color:var(--pm-orange);"></i>
                    <div>
                        <div class="fs-2 fw-bold" style="color:var(--pm-orange);"><?= $commentCount ?></div>
                        <div class="text-muted small">Active Comments</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card stat-card-green h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <i class="bi bi-person-video3 fs-1" style="color:#22c55e;"></i>
                    <div>
                        <div class="fs-2 fw-bold" style="color:#22c55e;"><?= $creatorCount ?></div>
                        <div class="text-muted small">Creators</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card pm-table-card h-100">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                    <h6 class="fw-bold text-navy mb-0">
                        <i class="bi bi-bar-chart me-2" style="color:var(--pm-cyan);"></i>Published Listings by Category
                    </h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="row g-4 h-100">
                <div class="col-12">
                    <div class="card pm-table-card h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                            <h6 class="fw-bold text-navy mb-0">
                                <i class="bi bi-pie-chart me-2" style="color:var(--pm-orange);"></i>Users by Role
                            </h6>
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center px-4 pb-4">
                            <canvas id="roleChart" style="max-height:160px;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card pm-table-card h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                            <h6 class="fw-bold text-navy mb-0">
                                <i class="bi bi-pie-chart me-2" style="color:var(--pm-navy);"></i>Listings by Status
                            </h6>
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center px-4 pb-4">
                            <canvas id="statusChart" style="max-height:160px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="card pm-table-card">
        <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
            <h5 class="fw-bold text-navy mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="row g-3">
                <div class="col-sm-6 col-lg-3">
                    <a href="manage_users.php" class="btn btn-outline-navy w-100 py-3 rounded-3 text-center">
                        <i class="bi bi-people-fill d-block fs-3 mb-1"></i>Manage Users
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="manage_listings.php" class="btn btn-outline-navy w-100 py-3 rounded-3 text-center">
                        <i class="bi bi-box-seam d-block fs-3 mb-1"></i>Manage Listings
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="manage_comments.php" class="btn btn-outline-navy w-100 py-3 rounded-3 text-center">
                        <i class="bi bi-chat-dots d-block fs-3 mb-1"></i>Manage Comments
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="reports.php" class="btn btn-outline-navy w-100 py-3 rounded-3 text-center">
                        <i class="bi bi-bar-chart-line d-block fs-3 mb-1"></i>Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const navy  = '#1a2e6b';
const cyan  = '#00c4d6';
const orng  = '#f97316';
const green = '#22c55e';
const gray  = '#94a3b8';

// Listings by category
new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{
            label: 'Published Listings',
            data: <?= json_encode($catData) ?>,
            backgroundColor: navy,
            borderRadius: 6,
            hoverBackgroundColor: cyan,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: '#f0f4ff' } },
                  x: { grid: { display: false } } }
    }
});

// Users by role
new Chart(document.getElementById('roleChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($roleLabels) ?>,
        datasets: [{
            data: <?= json_encode($roleData) ?>,
            backgroundColor: [navy, cyan, orng, green],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } } }
    }
});

// Listings by status
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Published', 'Draft', 'Removed'],
        datasets: [{
            data: <?= json_encode($statusData) ?>,
            backgroundColor: [green, gray, '#ef4444'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } } }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
