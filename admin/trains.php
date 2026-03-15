<?php
require_once '../config.php';
requireAdmin();
$conn = getDB();

// Add train
if (isset($_POST['add_train'])) {
    $stmt = $conn->prepare("INSERT INTO trains (train_number,train_name,from_city,to_city,departure_time,arrival_time,duration,total_seats,available_seats,fare_ac1,fare_ac3,fare_sleeper,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $ts = (int)$_POST['total_seats'];
    $stmt->bind_param("sssssssiiidds",
        $_POST['train_number'],$_POST['train_name'],$_POST['from_city'],$_POST['to_city'],
        $_POST['departure_time'],$_POST['arrival_time'],$_POST['duration'],
        $ts,$ts,$_POST['fare_ac1'],$_POST['fare_ac3'],$_POST['fare_sleeper'],$_POST['status']);
    $stmt->execute(); $stmt->close();
    header("Location: trains.php?msg=added"); exit;
}

// Delete train
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM trains WHERE id=$id");
    header("Location: trains.php?msg=deleted"); exit;
}

// Update status
if (isset($_POST['update_train_status'])) {
    $id = (int)$_POST['train_id']; $st = clean($_POST['status']);
    $conn->query("UPDATE trains SET status='$st' WHERE id=$id");
    header("Location: trains.php?msg=updated"); exit;
}

$trains = $conn->query("SELECT * FROM trains ORDER BY train_number")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Trains — Admin</title><link rel="stylesheet" href="../assets/css/style.css"></head>
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
            <a href="bookings.php" class="sidebar-link"><span class="icon">🎫</span>Bookings</a>
            <a href="trains.php" class="sidebar-link active"><span class="icon">🚆</span>Trains</a>
            <a href="users.php" class="sidebar-link"><span class="icon">👥</span>Users</a>
            <div class="sidebar-section">Communication</div>
            <a href="messages.php" class="sidebar-link"><span class="icon">📬</span>Messages</a>
            <div class="sidebar-section">Account</div>
            <a href="../index.php" class="sidebar-link"><span class="icon">🏠</span>View Site</a>
            <a href="../logout.php" class="sidebar-link"><span class="icon">🚪</span>Logout</a>
        </nav>
    </aside>
<div class="admin-main">
<div class="admin-topbar"><h3>Train Management</h3><button class="btn btn-primary btn-sm" onclick="openModal('addTrainModal')">+ Add Train</button></div>
<div class="admin-content">
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success mb-3">✅ <?= ucfirst($_GET['msg']) ?> successfully.</div><?php endif; ?>
<div class="card">
<div class="card-header">All Trains <span class="badge badge-info"><?= count($trains) ?></span></div>
<div class="table-wrap">
<table>
<thead><tr><th>No.</th><th>Train</th><th>Route</th><th>Dep.</th><th>Arr.</th><th>Seats</th><th>AC1</th><th>AC3</th><th>SL</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($trains as $t): ?>
<tr>
<td style="font-family:'JetBrains Mono',monospace;font-weight:700;"><?= $t['train_number'] ?></td>
<td style="font-weight:700;font-size:0.9rem;"><?= htmlspecialchars($t['train_name']) ?></td>
<td style="font-size:0.85rem;"><?= htmlspecialchars($t['from_city']) ?> → <?= htmlspecialchars($t['to_city']) ?></td>
<td style="font-family:'JetBrains Mono',monospace;color:var(--c-brand);font-weight:700;"><?= date('H:i',strtotime($t['departure_time'])) ?></td>
<td style="font-family:'JetBrains Mono',monospace;"><?= date('H:i',strtotime($t['arrival_time'])) ?></td>
<td><?= $t['available_seats'] ?>/<?= $t['total_seats'] ?></td>
<td>₹<?= number_format($t['fare_ac1'],0) ?></td>
<td>₹<?= number_format($t['fare_ac3'],0) ?></td>
<td>₹<?= number_format($t['fare_sleeper'],0) ?></td>
<td>
<form method="POST" style="display:flex;gap:0.3rem;align-items:center;">
<input type="hidden" name="train_id" value="<?= $t['id'] ?>">
<select name="status" class="form-control" style="padding:0.3rem;font-size:0.8rem;width:auto;">
<option value="active" <?= $t['status']==='active'?'selected':'' ?>>Active</option>
<option value="delayed" <?= $t['status']==='delayed'?'selected':'' ?>>Delayed</option>
<option value="cancelled" <?= $t['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
</select>
<button type="submit" name="update_train_status" class="btn btn-sm btn-primary" style="padding:0.3rem 0.6rem;">✔</button>
</form>
</td>
<td><a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this train?')">🗑</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>

<!-- ADD TRAIN MODAL -->
<div class="modal-overlay" id="addTrainModal">
<div class="modal" style="max-width:700px;">
<div class="modal-header"><div class="modal-title">🚆 Add New Train</div><button class="modal-close" onclick="closeModal('addTrainModal')">✕</button></div>
<form method="POST">
<div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
<div class="form-group"><label class="form-label">Train Number *</label><input type="text" name="train_number" class="form-control" required></div>
<div class="form-group"><label class="form-label">Train Name *</label><input type="text" name="train_name" class="form-control" required></div>
<div class="form-group"><label class="form-label">From City *</label><input type="text" name="from_city" class="form-control" required></div>
<div class="form-group"><label class="form-label">To City *</label><input type="text" name="to_city" class="form-control" required></div>
<div class="form-group"><label class="form-label">Departure Time *</label><input type="time" name="departure_time" class="form-control" required></div>
<div class="form-group"><label class="form-label">Arrival Time *</label><input type="time" name="arrival_time" class="form-control" required></div>
<div class="form-group"><label class="form-label">Duration (e.g. 8h 30m) *</label><input type="text" name="duration" class="form-control" required></div>
<div class="form-group"><label class="form-label">Total Seats *</label><input type="number" name="total_seats" class="form-control" value="100" required></div>
<div class="form-group"><label class="form-label">AC First Class Fare (₹)</label><input type="number" name="fare_ac1" class="form-control" value="2500"></div>
<div class="form-group"><label class="form-label">AC 3 Tier Fare (₹)</label><input type="number" name="fare_ac3" class="form-control" value="1200"></div>
<div class="form-group"><label class="form-label">Sleeper Fare (₹)</label><input type="number" name="fare_sleeper" class="form-control" value="500"></div>
<div class="form-group"><label class="form-label">Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="delayed">Delayed</option><option value="cancelled">Cancelled</option></select></div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-ghost" onclick="closeModal('addTrainModal')">Cancel</button>
<button type="submit" name="add_train" class="btn btn-primary">🚆 Add Train</button>
</div>
</form>
</div>
</div>

<div id="toast-container"></div>
<script src="../assets/js/app.js"></script>
</body></html>
