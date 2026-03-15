<?php
require_once 'config.php';
requireLogin();

// PNR lookup redirect
if (isset($_GET['pnr']) && !empty($_GET['pnr'])) {
    $pnr = strtoupper(clean($_GET['pnr']));
    $conn = getDB();
    $stmt = $conn->prepare("SELECT pnr FROM bookings WHERE pnr=? AND user_id=?");
    $stmt->bind_param("si", $pnr, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->store_result();
    $found = $stmt->num_rows > 0;
    $stmt->close(); $conn->close();
    if ($found) { header("Location: booking-confirm.php?pnr=$pnr"); exit; }
    else { $pnrError = "PNR <strong>$pnr</strong> not found in your bookings."; }
}

// Cancel booking
if (isset($_POST['cancel_pnr'])) {
    $pnr = clean($_POST['cancel_pnr']);
    $conn = getDB();
    $stmt = $conn->prepare("UPDATE bookings SET booking_status='cancelled' WHERE pnr=? AND user_id=?");
    $stmt->bind_param("si", $pnr, $_SESSION['user_id']);
    $stmt->execute();
    $conn->query("UPDATE trains t JOIN bookings b ON b.train_id=t.id SET t.available_seats=t.available_seats+1 WHERE b.pnr='$pnr'");
    $stmt->close(); $conn->close();
    header("Location: my-bookings.php?msg=cancelled");
    exit;
}

$conn = getDB();
$stmt = $conn->prepare("SELECT b.*, t.train_name, t.train_number, t.departure_time, t.arrival_time FROM bookings b JOIN trains t ON b.train_id=t.id WHERE b.user_id=? ORDER BY b.booked_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close(); $conn->close();

$total    = count($bookings);
$confirmed = count(array_filter($bookings, fn($b) => $b['booking_status']==='confirmed'));
$cancelled = count(array_filter($bookings, fn($b) => $b['booking_status']==='cancelled'));
$spent     = array_sum(array_column(array_filter($bookings, fn($b)=>$b['booking_status']==='confirmed'),'fare'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>My Bookings — RailBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="page-wrapper">
    <div class="page-header">
        <span class="section-label">👤 Dashboard</span>
        <h1 class="page-title">My Bookings</h1>
        <p class="page-subtitle">Manage your upcoming and past trips</p>
    </div>
    <?php if(isset($pnrError)): ?>
    <div class="alert alert-error mb-3">🔍 <?= $pnrError ?></div>
    <?php endif; ?>
    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-<?= $_GET['msg']==='cancelled'?'warning':'success' ?> mb-3">
        <?= $_GET['msg']==='cancelled' ? '⚠️ Booking cancelled. Refund will be processed in 5-7 days.' : '✅ Action completed.' ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid mb-3">
        <div class="stat-card"><div class="stat-icon blue">🎫</div><div><div class="stat-value"><?= $total ?></div><div class="stat-label">Total Bookings</div></div></div>
        <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-value"><?= $confirmed ?></div><div class="stat-label">Confirmed</div></div></div>
        <div class="stat-card"><div class="stat-icon red">❌</div><div><div class="stat-value"><?= $cancelled ?></div><div class="stat-label">Cancelled</div></div></div>
        <div class="stat-card"><div class="stat-icon amber">💰</div><div><div class="stat-value">₹<?= number_format($spent,0) ?></div><div class="stat-label">Total Spent</div></div></div>
    </div>

    <!-- Bookings Table -->
    <div class="card">
        <div class="card-header">
            All Trips
            <a href="search.php" class="btn btn-sm btn-primary">+ New Booking</a>
        </div>
        <?php if(empty($bookings)): ?>
        <div class="card-body" style="text-align:center;padding:4rem;">
            <div style="font-size:3rem;margin-bottom:1rem;">🎫</div>
            <h3>No bookings yet</h3>
            <p style="margin:0.5rem 0 1.5rem;">Start your journey — search and book your first ticket!</p>
            <a href="search.php" class="btn btn-primary">🔍 Search Trains</a>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>PNR</th><th>Train</th><th>Route</th><th>Date</th><th>Class</th><th>Fare</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach($bookings as $b): ?>
                    <tr>
                        <td><code style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--c-brand);"><?= htmlspecialchars($b['pnr']) ?></code></td>
                        <td>
                            <div style="font-weight:700;color:var(--ink-1);font-size:0.9rem;"><?= htmlspecialchars($b['train_name']) ?></div>
                            <div style="font-size:0.75rem;color:var(--ink-4);"><?= $b['train_number'] ?></div>
                        </td>
                        <td>
                            <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($b['from_city']) ?> → <?= htmlspecialchars($b['to_city']) ?></div>
                            <div style="font-size:0.75rem;color:var(--ink-4);"><?= date('H:i',strtotime($b['departure_time'])) ?> – <?= date('H:i',strtotime($b['arrival_time'])) ?></div>
                        </td>
                        <td style="white-space:nowrap;font-size:0.88rem;"><?= date('d M Y', strtotime($b['journey_date'])) ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($b['ticket_class']) ?></span></td>
                        <td style="font-weight:700;color:var(--c-brand);">₹<?= number_format($b['fare'],0) ?></td>
                        <td>
                            <?php if($b['booking_status']==='confirmed'): ?>
                            <span class="badge badge-success">✔ Confirmed</span>
                            <?php elseif($b['booking_status']==='cancelled'): ?>
                            <span class="badge badge-danger">✕ Cancelled</span>
                            <?php else: ?>
                            <span class="badge badge-warning">⏳ Waitlist</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:0.4rem;">
                                <a href="booking-confirm.php?pnr=<?= $b['pnr'] ?>" class="btn btn-sm btn-outline">View</a>
                                <a href="download-ticket.php?pnr=<?= $b['pnr'] ?>" class="btn btn-sm btn-purple" title="Download E-Ticket">⬇️</a>
                                <?php if($b['booking_status']==='confirmed' && strtotime($b['journey_date'])>time()): ?>
                                <form method="POST" onsubmit="return confirm('Cancel this booking? Refund will be processed.')">
                                    <input type="hidden" name="cancel_pnr" value="<?= $b['pnr'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body></html>
