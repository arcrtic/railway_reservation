<?php
require_once '../config.php';
requireAdmin();
$conn = getDB();

$totalUsers    = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$totalBookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$totalRevenue  = $conn->query("SELECT COALESCE(SUM(fare),0) FROM bookings WHERE booking_status='confirmed'")->fetch_row()[0];
$totalTrains   = $conn->query("SELECT COUNT(*) FROM trains")->fetch_row()[0];
$newMessages   = $conn->query("SELECT COUNT(*) FROM contacts WHERE status='new'")->fetch_row()[0];
$recentBookings = $conn->query("SELECT b.*,t.train_name,t.train_number,u.username FROM bookings b JOIN trains t ON b.train_id=t.id JOIN users u ON b.user_id=u.id ORDER BY b.booked_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Chart data - bookings per day (last 7 days)
$chartDays = []; $chartBookings = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartDays[] = date('D', strtotime($d));
    $cnt = $conn->query("SELECT COUNT(*) FROM bookings WHERE DATE(booked_at)='$d'")->fetch_row()[0];
    $chartBookings[] = (int)$cnt;
}

// Class distribution
$ac1 = $conn->query("SELECT COUNT(*) FROM bookings WHERE ticket_class='AC First Class'")->fetch_row()[0];
$ac3 = $conn->query("SELECT COUNT(*) FROM bookings WHERE ticket_class='AC 3 Tier'")->fetch_row()[0];
$sl  = $conn->query("SELECT COUNT(*) FROM bookings WHERE ticket_class='Sleeper'")->fetch_row()[0];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Admin Dashboard — RailBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="admin-layout">
    <!-- SIDEBAR -->
        <aside class="sidebar">
        <div class="sidebar-logo">
            <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#2563EB,#0EA5E9);display:flex;align-items:center;justify-content:center;font-size:1.1rem;">🚆</div>
            <span>RailBook Admin</span>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">Main</div>
            <a href="index.php" class="sidebar-link active"><span class="icon">📊</span>Dashboard</a>
            <a href="bookings.php" class="sidebar-link"><span class="icon">🎫</span>Bookings</a>
            <a href="trains.php" class="sidebar-link"><span class="icon">🚆</span>Trains</a>
            <a href="users.php" class="sidebar-link"><span class="icon">👥</span>Users</a>
            <div class="sidebar-section">Communication</div>
            <a href="messages.php" class="sidebar-link"><span class="icon">📬</span>Messages</a>
            <div class="sidebar-section">Account</div>
            <a href="../index.php" class="sidebar-link"><span class="icon">🏠</span>View Site</a>
            <a href="../logout.php" class="sidebar-link"><span class="icon">🚪</span>Logout</a>
        </nav>
    </aside>

    <div class="admin-main">
        <!-- TOP BAR -->
        <div class="admin-topbar">
            <div>
                <h3 style="margin-bottom:0;">Dashboard Overview</h3>
                <p style="font-size:0.82rem;color:var(--ink-4);margin:0;"><?= date('l, d M Y') ?></p>
            </div>
            <div style="display:flex;align-items:center;gap:1rem;">
                <div style="font-size:0.88rem;font-weight:600;color:var(--ink-2);">👋 <?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <a href="../logout.php" class="btn btn-sm btn-ghost">Logout</a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="admin-content">
            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon blue">👥</div><div><div class="stat-value" data-counter="<?= $totalUsers ?>"><?= $totalUsers ?></div><div class="stat-label">Registered Users</div><div class="stat-change up">↑ Active accounts</div></div></div>
                <div class="stat-card"><div class="stat-icon green">🎫</div><div><div class="stat-value" data-counter="<?= $totalBookings ?>"><?= $totalBookings ?></div><div class="stat-label">Total Bookings</div></div></div>
                <div class="stat-card"><div class="stat-icon amber">💰</div><div><div class="stat-value">₹<?= number_format($totalRevenue,0) ?></div><div class="stat-label">Total Revenue</div><div class="stat-change up">↑ Confirmed only</div></div></div>
                <div class="stat-card"><div class="stat-icon red">🚆</div><div><div class="stat-value"><?= $totalTrains ?></div><div class="stat-label">Trains in System</div></div></div>
            </div>

            <!-- CHARTS ROW -->
            <div class="grid-2 mb-3">
                <div class="chart-card">
                    <div class="chart-title">Bookings — Last 7 Days</div>
                    <div style="position:relative;height:220px;width:100%;">
                        <canvas id="bookingsChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-title">Ticket Class Distribution</div>
                    <div style="position:relative;height:220px;width:100%;">
                        <canvas id="classChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- RECENT BOOKINGS -->
            <div class="card">
                <div class="card-header">
                    Recent Bookings
                    <a href="bookings.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>PNR</th><th>User</th><th>Train</th><th>Route</th><th>Date</th><th>Class</th><th>Fare</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach($recentBookings as $b): ?>
                            <tr>
                                <td><code style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--c-brand);font-size:0.85rem;"><?= $b['pnr'] ?></code></td>
                                <td style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($b['username']) ?></td>
                                <td style="font-size:0.88rem;"><?= htmlspecialchars($b['train_name']) ?></td>
                                <td style="font-size:0.88rem;"><?= htmlspecialchars($b['from_city']) ?> → <?= htmlspecialchars($b['to_city']) ?></td>
                                <td style="font-size:0.85rem;"><?= date('d M Y',strtotime($b['journey_date'])) ?></td>
                                <td><span class="badge badge-info" style="font-size:0.72rem;"><?= $b['ticket_class'] ?></span></td>
                                <td style="font-weight:700;color:var(--c-brand);">₹<?= number_format($b['fare'],0) ?></td>
                                <td>
                                    <?php if($b['booking_status']==='confirmed'): ?><span class="badge badge-success">✔ Confirmed</span>
                                    <?php elseif($b['booking_status']==='cancelled'): ?><span class="badge badge-danger">Cancelled</span>
                                    <?php else: ?><span class="badge badge-warning">Waitlist</span><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="toast-container"></div>
<script src="../assets/js/app.js"></script>
<script>
const DAYS   = <?= json_encode($chartDays) ?>;
const COUNTS = <?= json_encode($chartBookings) ?>;

// Bookings bar chart — fixed height via parent div
new Chart(document.getElementById('bookingsChart'), {
    type: 'bar',
    data: {
        labels: DAYS,
        datasets: [{
            label: 'Bookings',
            data: COUNTS,
            backgroundColor: 'rgba(37,99,235,0.15)',
            borderColor: '#2563EB',
            borderWidth: 2,
            borderRadius: 6,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, maxTicksLimit: 6 },
                grid: { color: 'rgba(0,0,0,0.04)' }
            },
            x: { grid: { display: false } }
        }
    }
});

// Class donut chart
new Chart(document.getElementById('classChart'), {
    type: 'doughnut',
    data: {
        labels: ['AC First Class', 'AC 3 Tier', 'Sleeper'],
        datasets: [{
            data: [<?= $ac1 ?>, <?= $ac3 ?>, <?= $sl ?>],
            backgroundColor: ['#2563EB','#0EA5E9','#22C55E'],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 14, font: { size: 12 }, boxWidth: 12 }
            }
        },
        cutout: '68%'
    }
});
</script>
</body></html>
