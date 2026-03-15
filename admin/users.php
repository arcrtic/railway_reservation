<?php
require_once '../config.php';
requireAdmin();
$conn = getDB();

// Toggle user status / delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id AND role!='admin'");
    header("Location: users.php?msg=deleted"); exit;
}
if (isset($_POST['update_role'])) {
    $id   = (int)$_POST['user_id'];
    $role = clean($_POST['role']);
    $conn->query("UPDATE users SET role='$role' WHERE id=$id AND id!=1");
    header("Location: users.php?msg=updated"); exit;
}

$users = $conn->query("SELECT u.*, (SELECT COUNT(*) FROM bookings WHERE user_id=u.id) as booking_count, (SELECT COALESCE(SUM(fare),0) FROM bookings WHERE user_id=u.id AND booking_status='confirmed') as total_spent FROM users u ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Users — RailBook Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
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
            <a href="trains.php" class="sidebar-link"><span class="icon">🚆</span>Trains</a>
            <a href="users.php" class="sidebar-link active"><span class="icon">👥</span>Users</a>
            <div class="sidebar-section">Communication</div>
            <a href="messages.php" class="sidebar-link"><span class="icon">📬</span>Messages</a>
            <div class="sidebar-section">Account</div>
            <a href="../index.php" class="sidebar-link"><span class="icon">🏠</span>View Site</a>
            <a href="../logout.php" class="sidebar-link"><span class="icon">🚪</span>Logout</a>
        </nav>
    </aside>
    <div class="admin-main">
        <div class="admin-topbar">
            <h3>User Management</h3>
            <div style="display:flex;align-items:center;gap:1rem;">
                <input type="text" id="user-filter" class="form-control" style="width:220px;" placeholder="🔍 Search users..." oninput="filterUsers()">
                <span class="badge badge-info"><?= count($users) ?> users</span>
            </div>
        </div>
        <div class="admin-content">
            <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success mb-3">✅ <?= ucfirst($_GET['msg']) ?> successfully.</div>
            <?php endif; ?>

            <!-- User Stats -->
            <div class="stats-grid mb-3" style="grid-template-columns:repeat(3,1fr);">
                <div class="stat-card">
                    <div class="stat-icon blue">👥</div>
                    <div>
                        <div class="stat-value"><?= count(array_filter($users, fn($u)=>$u['role']==='user')) ?></div>
                        <div class="stat-label">Regular Users</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber">👑</div>
                    <div>
                        <div class="stat-value"><?= count(array_filter($users, fn($u)=>$u['role']==='admin')) ?></div>
                        <div class="stat-label">Admins</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">🎫</div>
                    <div>
                        <div class="stat-value"><?= array_sum(array_column($users,'booking_count')) ?></div>
                        <div class="stat-label">Total Bookings Made</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">All Registered Users</div>
                <div class="table-wrap">
                    <table id="users-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Bookings</th>
                                <th>Total Spent</th>
                                <th>Joined</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $i => $u): ?>
                            <tr data-search="<?= strtolower($u['full_name'].' '.$u['username'].' '.$u['email']) ?>">
                                <td style="color:var(--ink-4);font-size:0.85rem;"><?= $i+1 ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:0.75rem;">
                                        <div style="width:36px;height:36px;border-radius:50%;background:#DBEAFE;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.85rem;color:var(--blue-700);flex-shrink:0;">
                                            <?= strtoupper(substr($u['full_name'],0,1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:700;font-size:0.9rem;color:var(--ink-1);"><?= htmlspecialchars($u['full_name']) ?></div>
                                            <div style="font-size:0.75rem;color:var(--ink-4);">@<?= htmlspecialchars($u['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size:0.85rem;"><?= htmlspecialchars($u['email']) ?></td>
                                <td style="font-size:0.85rem;font-family:'JetBrains Mono',monospace;"><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                                <td>
                                    <span class="badge badge-info"><?= $u['booking_count'] ?> trips</span>
                                </td>
                                <td style="font-weight:700;color:var(--green-700);">₹<?= number_format($u['total_spent'],0) ?></td>
                                <td style="font-size:0.82rem;color:var(--ink-4);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <?php if($u['id']==1): ?>
                                        <span class="badge badge-warning">👑 Super Admin</span>
                                    <?php else: ?>
                                    <form method="POST" style="display:flex;gap:0.3rem;">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <select name="role" class="form-control" style="padding:0.3rem;font-size:0.8rem;width:auto;">
                                            <option value="user"  <?= $u['role']==='user'?'selected':'' ?>>User</option>
                                            <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                                        </select>
                                        <button type="submit" name="update_role" class="btn btn-sm btn-primary" style="padding:0.3rem 0.6rem;">✔</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($u['id']!=1): ?>
                                    <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete user <?= htmlspecialchars($u['username']) ?>? This cannot be undone.')">🗑</a>
                                    <?php endif; ?>
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
function filterUsers() {
    const q = document.getElementById('user-filter').value.toLowerCase();
    document.querySelectorAll('#users-table tbody tr').forEach(r => {
        r.style.display = !q || r.dataset.search.includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
