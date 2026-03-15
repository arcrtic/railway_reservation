<?php
// admin/bookings.php
require_once '../config.php';
requireAdmin();
$conn = getDB();

// Handle status update
if (isset($_POST['update_status'])) {
    $pnr    = clean($_POST['pnr']);
    $status = clean($_POST['status']);
    $conn->query("UPDATE bookings SET booking_status='$status' WHERE pnr='$pnr'");
    header("Location: bookings.php?msg=updated"); exit;
}

$bookings = $conn->query("SELECT b.*,t.train_name,t.train_number,u.username,u.full_name FROM bookings b JOIN trains t ON b.train_id=t.id JOIN users u ON b.user_id=u.id ORDER BY b.booked_at DESC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Bookings — Admin</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#2563EB,#0EA5E9);display:flex;align-items:center;justify-content:center;font-size:1.1rem;">🚆</div>
            <span>RailBook Admin</span>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">Main</div>
            <a href="index.php" class="sidebar-link"><span class="icon">📊</span>Dashboard</a>
            <a href="bookings.php" class="sidebar-link active"><span class="icon">🎫</span>Bookings</a>
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
<div class="admin-topbar"><h3>All Bookings</h3><span class="badge badge-info"><?= count($bookings) ?> total</span></div>
<div class="admin-content">
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success mb-3">✅ Updated successfully.</div><?php endif; ?>
<div class="card">
<div class="card-header">Booking Management <input type="text" id="bk-filter" class="form-control" style="width:220px;" placeholder="🔍 Search..." oninput="filterTable('bk-table','bk-filter')"></div>
<div class="table-wrap">
<table id="bk-table">
<thead><tr><th>PNR</th><th>User</th><th>Train</th><th>Route</th><th>Date</th><th>Class</th><th>Fare</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($bookings as $b): ?>
<tr data-search="<?= strtolower($b['pnr'].' '.$b['username'].' '.$b['train_name'].' '.$b['from_city'].' '.$b['to_city']) ?>">
<td><code style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--c-brand);"><?= $b['pnr'] ?></code></td>
<td style="font-size:0.88rem;"><div style="font-weight:600;"><?= htmlspecialchars($b['full_name']) ?></div><div style="color:var(--ink-4);font-size:0.75rem;">@<?= $b['username'] ?></div></td>
<td style="font-size:0.88rem;"><?= htmlspecialchars($b['train_name']) ?></td>
<td style="font-size:0.85rem;"><?= htmlspecialchars($b['from_city']) ?> → <?= htmlspecialchars($b['to_city']) ?></td>
<td style="font-size:0.85rem;"><?= date('d M Y',strtotime($b['journey_date'])) ?></td>
<td><span class="badge badge-info" style="font-size:0.7rem;"><?= $b['ticket_class'] ?></span></td>
<td style="font-weight:700;color:var(--c-brand);">₹<?= number_format($b['fare'],0) ?></td>
<td><?php if($b['booking_status']==='confirmed'): ?><span class="badge badge-success">Confirmed</span><?php elseif($b['booking_status']==='cancelled'): ?><span class="badge badge-danger">Cancelled</span><?php else: ?><span class="badge badge-warning">Waitlist</span><?php endif; ?></td>
<td>
<form method="POST" style="display:flex;gap:0.4rem;">
<input type="hidden" name="pnr" value="<?= $b['pnr'] ?>">
<select name="status" class="form-control" style="padding:0.3rem 0.5rem;font-size:0.8rem;width:auto;">
<option value="confirmed" <?= $b['booking_status']==='confirmed'?'selected':'' ?>>Confirmed</option>
<option value="cancelled" <?= $b['booking_status']==='cancelled'?'selected':'' ?>>Cancelled</option>
<option value="waitlisted" <?= $b['booking_status']==='waitlisted'?'selected':'' ?>>Waitlist</option>
</select>
<button type="submit" name="update_status" class="btn btn-sm btn-primary">Save</button>
</form>
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
function filterTable(tableId, filterId) {
    const q = document.getElementById(filterId).value.toLowerCase();
    document.querySelectorAll(`#${tableId} tbody tr`).forEach(r => {
        r.style.display = !q || r.dataset.search.includes(q) ? '' : 'none';
    });
}
</script>
</body></html>
