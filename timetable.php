<?php
// timetable.php
require_once 'config.php';
$conn = getDB();
$trains = $conn->query("SELECT * FROM trains ORDER BY departure_time ASC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Train Timetable — RailBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<?php if(!isLoggedIn()): ?>
<div style="background:linear-gradient(135deg,#F59E0B,#D97706);padding:.9rem 2rem;text-align:center;">
    <div style="max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:center;gap:1rem;flex-wrap:wrap;">
        <span style="color:white;font-weight:700;font-size:.9rem;">👋 Browse trains freely — <strong>sign in to book a ticket.</strong></span>
        <a href="login.php" class="btn btn-sm" style="background:white;color:#D97706;font-weight:800;">Login</a>
        <a href="signup.php" class="btn btn-sm" style="background:rgba(255,255,255,.18);color:white;border:1.5px solid rgba(255,255,255,.5);">Sign Up Free</a>
    </div>
</div>
<?php endif; ?>
<div class="page-wrapper">
    <div class="page-header">
        <span class="section-label">🕐 Schedule</span>
        <h1 class="page-title">Train Timetable</h1>
        <p class="page-subtitle">Full schedule of all trains in the system</p>
    </div>
    <div class="card">
        <div class="card-header">
            All Trains
            <div style="display:flex;gap:0.75rem;align-items:center;">
                <input type="text" id="tt-filter" class="form-control" style="width:220px;" placeholder="🔍 Filter trains..." oninput="filterTimetable()">
                <span class="badge badge-info"><?= count($trains) ?> trains</span>
            </div>
        </div>
        <div class="table-wrap">
            <table id="tt-table">
                <thead>
                    <tr><th>#</th><th>Train</th><th>From</th><th>To</th><th>Departure</th><th>Arrival</th><th>Duration</th><th>AC 1st</th><th>AC 3T</th><th>Sleeper</th><th>Seats</th><th>Status</th><th>Book</th></tr>
                </thead>
                <tbody>
                    <?php foreach($trains as $i=>$t): ?>
                    <tr data-search="<?= strtolower($t['train_name'].' '.$t['train_number'].' '.$t['from_city'].' '.$t['to_city']) ?>">
                        <td style="color:var(--ink-4);font-size:0.85rem;"><?= $i+1 ?></td>
                        <td>
                            <div style="font-weight:700;color:var(--ink-1);"><?= htmlspecialchars($t['train_name']) ?></div>
                            <div style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:var(--ink-4);"><?= $t['train_number'] ?></div>
                        </td>
                        <td style="font-weight:600;"><?= htmlspecialchars($t['from_city']) ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($t['to_city']) ?></td>
                        <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--c-brand);"><?= date('H:i',strtotime($t['departure_time'])) ?></td>
                        <td style="font-family:'JetBrains Mono',monospace;"><?= date('H:i',strtotime($t['arrival_time'])) ?></td>
                        <td><span class="badge badge-secondary">⏱ <?= $t['duration'] ?></span></td>
                        <td style="font-weight:700;">₹<?= number_format($t['fare_ac1'],0) ?></td>
                        <td style="font-weight:700;">₹<?= number_format($t['fare_ac3'],0) ?></td>
                        <td><?= $t['fare_sleeper']>0 ? '₹'.number_format($t['fare_sleeper'],0) : '<span style="color:var(--ink-4);">—</span>' ?></td>
                        <td>
                            <?php if($t['available_seats']>10): ?><span class="badge badge-success"><?= $t['available_seats'] ?></span>
                            <?php elseif($t['available_seats']>0): ?><span class="badge badge-warning"><?= $t['available_seats'] ?></span>
                            <?php else: ?><span class="badge badge-danger">WL</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if($t['status']==='active'): ?><span class="badge badge-success">Active</span>
                            <?php elseif($t['status']==='delayed'): ?><span class="badge badge-warning">Delayed</span>
                            <?php else: ?><span class="badge badge-danger">Cancelled</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if($t['status']==='active' && $t['available_seats']>0): ?>
                            <a href="book-ticket.php?train_id=<?= $t['id'] ?>&date=<?= date('Y-m-d') ?>"
   style="display:inline-flex;align-items:center;gap:0.3rem;background:linear-gradient(135deg,#2563EB,#3B82F6);color:white;padding:0.42rem 0.9rem;border-radius:8px;font-size:0.82rem;font-weight:800;text-decoration:none;box-shadow:0 3px 10px rgba(37,99,235,0.4);white-space:nowrap;transition:all 0.2s;"
   onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 16px rgba(37,99,235,0.55)'"
   onmouseout="this.style.transform='';this.style.boxShadow='0 3px 10px rgba(37,99,235,0.4)'">
   🎫 Book
</a>
                            <?php else: ?>
                            <span style="color:var(--ink-4);font-size:0.8rem;">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script>
function filterTimetable() {
    const q = document.getElementById('tt-filter').value.toLowerCase();
    document.querySelectorAll('#tt-table tbody tr').forEach(row => {
        row.style.display = !q || row.dataset.search.includes(q) ? '' : 'none';
    });
}
</script>
</body></html>
