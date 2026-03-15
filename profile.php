<?php
require_once 'config.php';
requireLogin();

$conn   = getDB();
$userId = (int)$_SESSION['user_id'];
$msg    = ''; $msgType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = clean($_POST['full_name'] ?? '');
    $phone     = clean($_POST['phone']     ?? '');
    $dob       = clean($_POST['dob']       ?? '');
    $address   = clean($_POST['address']   ?? '');
    $email     = clean($_POST['email']     ?? '');

    if ($full_name && $email) {
        $stmt = $conn->prepare("UPDATE users SET full_name=?,phone=?,date_of_birth=?,address=?,email=? WHERE id=?");
        $dobVal = $dob ?: null;
        $stmt->bind_param("sssssi", $full_name, $phone, $dobVal, $address, $email, $userId);
        $stmt->execute(); $stmt->close();
        $_SESSION['full_name'] = $full_name;
        $msg = 'Profile updated successfully!'; $msgType = 'success';
    } else {
        $msg = 'Full name and email are required.'; $msgType = 'error';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password']  ?? '';
    $newPass  = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    $row = $conn->query("SELECT password FROM users WHERE id=$userId")->fetch_assoc();
    if ($row['password'] !== $current) {
        $msg = 'Current password is incorrect.'; $msgType = 'error';
    } elseif (strlen($newPass) < 6) {
        $msg = 'New password must be at least 6 characters.'; $msgType = 'error';
    } elseif ($newPass !== $confirm) {
        $msg = 'Passwords do not match.'; $msgType = 'error';
    } else {
        $conn->query("UPDATE users SET password='$newPass' WHERE id=$userId");
        $msg = 'Password changed successfully!'; $msgType = 'success';
    }
}

// Fetch user data
$user = $conn->query("SELECT * FROM users WHERE id=$userId")->fetch_assoc();

// Fetch booking stats
$totalBookings   = $conn->query("SELECT COUNT(*) FROM bookings WHERE user_id=$userId")->fetch_row()[0];
$confirmedCount  = $conn->query("SELECT COUNT(*) FROM bookings WHERE user_id=$userId AND booking_status='confirmed'")->fetch_row()[0];
$totalSpent      = $conn->query("SELECT COALESCE(SUM(fare),0) FROM bookings WHERE user_id=$userId AND booking_status='confirmed'")->fetch_row()[0];
$cancelledCount  = $conn->query("SELECT COUNT(*) FROM bookings WHERE user_id=$userId AND booking_status='cancelled'")->fetch_row()[0];

// Fetch recent bookings
$recentBookings  = $conn->query("SELECT b.*,t.train_name,t.train_number,t.departure_time,t.arrival_time FROM bookings b JOIN trains t ON b.train_id=t.id WHERE b.user_id=$userId ORDER BY b.booked_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Most visited routes
$routes = $conn->query("SELECT from_city, to_city, COUNT(*) as cnt FROM bookings WHERE user_id=$userId GROUP BY from_city,to_city ORDER BY cnt DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

$conn->close();
$initials = strtoupper(substr($user['full_name'],0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Profile — RailBook</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="page-wrapper">

    <!-- PROFILE HERO -->
    <div class="profile-hero">
        <div style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap;position:relative;z-index:1;">
            <div style="position:relative;">
                <div class="profile-avatar"><?= $initials ?></div>
                <div class="profile-avatar-upload" title="Change photo">📷</div>
            </div>
            <div style="flex:1;">
                <div class="profile-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="profile-meta">@<?= htmlspecialchars($user['username']) ?> &nbsp;•&nbsp; <?= htmlspecialchars($user['email']) ?></div>
                <div style="display:flex;gap:0.75rem;margin-top:1rem;flex-wrap:wrap;">
                    <div class="profile-stat-pill">🎫 <?= $totalBookings ?> Trips</div>
                    <div class="profile-stat-pill">✅ <?= $confirmedCount ?> Confirmed</div>
                    <div class="profile-stat-pill">💰 ₹<?= number_format($totalSpent,0) ?> Spent</div>
                    <div class="profile-stat-pill">📅 Joined <?= date('M Y', strtotime($user['created_at'])) ?></div>
                </div>
            </div>
            <a href="my-bookings.php" class="btn btn-outline" style="border-color:rgba(255,255,255,0.4);color:white;background:rgba(255,255,255,0.1);">📋 My Bookings</a>
        </div>
        <!-- 3D floating elements in hero bg -->
        <div style="position:absolute;top:10px;right:200px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);animation:orbFloat 6s ease-in-out infinite;"></div>
        <div style="position:absolute;bottom:10px;right:80px;width:50px;height:50px;border-radius:50%;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);animation:orbFloat 4s ease-in-out infinite 1s;"></div>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msgType ?> mb-3"><?= $msgType==='success'?'✅':'❌' ?> <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- TABS -->
    <div style="display:flex;gap:0.5rem;margin-bottom:2rem;border-bottom:2px solid var(--border);padding-bottom:0;">
        <?php $tabs=[['overview','📊 Overview'],['edit','✏️ Edit Profile'],['security','🔒 Security'],['stats','📈 Travel Stats']]; ?>
        <?php foreach($tabs as [$id,$label]): ?>
        <button class="profile-tab <?= $id==='overview'?'active':'' ?>" onclick="switchTab(this,'pane-<?= $id ?>')"><?= $label ?></button>
        <?php endforeach; ?>
    </div>

    <!-- OVERVIEW PANE -->
    <div id="pane-overview">
        <div class="grid-3" style="gap:1.25rem;margin-bottom:2rem;">
            <div class="stat-card"><div class="stat-icon blue">🎫</div><div><div class="stat-value"><?= $totalBookings ?></div><div class="stat-label">Total Bookings</div></div></div>
            <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-value"><?= $confirmedCount ?></div><div class="stat-label">Confirmed Trips</div></div></div>
            <div class="stat-card"><div class="stat-icon red">❌</div><div><div class="stat-value"><?= $cancelledCount ?></div><div class="stat-label">Cancelled</div></div></div>
            <div class="stat-card"><div class="stat-icon amber">💰</div><div><div class="stat-value">₹<?= number_format($totalSpent,0) ?></div><div class="stat-label">Total Spent</div></div></div>
            <div class="stat-card"><div class="stat-icon purple">⭐</div><div><div class="stat-value"><?= $confirmedCount > 10 ? 'Gold' : ($confirmedCount > 3 ? 'Silver' : 'Bronze') ?></div><div class="stat-label">Member Tier</div></div></div>
            <div class="stat-card"><div class="stat-icon blue">📅</div><div><div class="stat-value"><?= date('d M', strtotime($user['created_at'])) ?></div><div class="stat-label">Member Since</div></div></div>
        </div>

        <!-- Recent Bookings -->
        <div class="card mb-3">
            <div class="card-header">🕐 Recent Trips <a href="my-bookings.php" class="btn btn-sm btn-outline">View All</a></div>
            <?php if(empty($recentBookings)): ?>
            <div class="card-body" style="text-align:center;padding:3rem;"><div style="font-size:3rem;margin-bottom:1rem;">🚆</div><p>No bookings yet. <a href="search.php">Search for trains →</a></p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>PNR</th><th>Train</th><th>Route</th><th>Date</th><th>Class</th><th>Fare</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($recentBookings as $b): ?>
                        <tr>
                            <td><a href="booking-confirm.php?pnr=<?= $b['pnr'] ?>" style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--c-brand);"><?= $b['pnr'] ?></a></td>
                            <td style="font-weight:700;font-size:0.88rem;"><?= htmlspecialchars($b['train_name']) ?></td>
                            <td style="font-size:0.85rem;"><?= htmlspecialchars($b['from_city']) ?> → <?= htmlspecialchars($b['to_city']) ?></td>
                            <td style="font-size:0.85rem;"><?= date('d M Y',strtotime($b['journey_date'])) ?></td>
                            <td><span class="badge badge-info" style="font-size:0.7rem;"><?= $b['ticket_class'] ?></span></td>
                            <td style="font-weight:700;color:var(--c-brand);">₹<?= number_format($b['fare'],0) ?></td>
                            <td><?php if($b['booking_status']==='confirmed'): ?><span class="badge badge-success">Confirmed</span><?php elseif($b['booking_status']==='cancelled'): ?><span class="badge badge-danger">Cancelled</span><?php else: ?><span class="badge badge-warning">Waitlist</span><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Favourite Routes -->
        <?php if(!empty($routes)): ?>
        <div class="card">
            <div class="card-header">❤️ Favourite Routes</div>
            <div class="card-body" style="display:flex;gap:1rem;flex-wrap:wrap;">
                <?php foreach($routes as $r): ?>
                <a href="search.php?from=<?= urlencode($r['from_city']) ?>&to=<?= urlencode($r['to_city']) ?>&date=<?= date('Y-m-d') ?>"
                   style="display:flex;align-items:center;gap:0.75rem;background:var(--c-brand-light);border:1.5px solid #BFDBFE;border-radius:var(--r-lg);padding:0.85rem 1.25rem;text-decoration:none;transition:var(--t);"
                   onmouseover="this.style.background='#DBEAFE';this.style.transform='translateY(-2px)'"
                   onmouseout="this.style.background='var(--c-brand-light)';this.style.transform=''">
                    <span style="font-size:1.2rem;">🚆</span>
                    <div>
                        <div style="font-weight:800;font-size:0.9rem;color:var(--ink-1);"><?= htmlspecialchars($r['from_city']) ?> → <?= htmlspecialchars($r['to_city']) ?></div>
                        <div style="font-size:0.75rem;color:var(--ink-4);"><?= $r['cnt'] ?> trip<?= $r['cnt']>1?'s':'' ?></div>
                    </div>
                    <span style="margin-left:auto;color:var(--c-brand);font-size:0.85rem;font-weight:700;">Book →</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- EDIT PROFILE PANE -->
    <div id="pane-edit" class="hidden">
        <div class="grid-2" style="gap:2rem;align-items:start;">
            <div class="card">
                <div class="card-header">✏️ Personal Information</div>
                <div class="card-body">
                    <form method="POST" style="display:flex;flex-direction:column;gap:1.1rem;">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <div class="input-icon-wrap"><span class="input-icon">👤</span>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address *</label>
                            <div class="input-icon-wrap"><span class="input-icon">📧</span>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <div class="input-icon-wrap"><span class="input-icon">📱</span>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']??'') ?>" placeholder="10-digit mobile"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($user['date_of_birth']??'') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" placeholder="Your address" style="min-height:80px;"><?= htmlspecialchars($user['address']??'') ?></textarea>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary btn-full">💾 Save Changes</button>
                    </form>
                </div>
            </div>
            <div>
                <div class="card mb-3">
                    <div class="card-header">👤 Account Info</div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:1rem;">
                        <?php $info=[['Username','@'.$user['username'],'🏷️'],['Role',ucfirst($user['role']),'👑'],['Member Since',date('d M Y',strtotime($user['created_at'])),'📅']]; ?>
                        <?php foreach($info as $i): ?>
                        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem;background:var(--surface-1);border-radius:var(--r-sm);">
                            <span style="font-size:1.2rem;"><?= $i[2] ?></span>
                            <div><div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--ink-4);font-weight:800;"><?= $i[0] ?></div>
                            <div style="font-weight:700;color:var(--ink-1);"><?= $i[1] ?></div></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card" style="border:2px dashed var(--border);background:var(--surface-1);">
                    <div class="card-body" style="text-align:center;padding:2rem;">
                        <div style="font-size:3rem;margin-bottom:0.75rem;">📷</div>
                        <div style="font-weight:700;margin-bottom:0.5rem;">Profile Photo</div>
                        <p style="font-size:0.85rem;color:var(--ink-4);">Photo upload coming soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECURITY PANE -->
    <div id="pane-security" class="hidden">
        <div style="max-width:500px;">
            <div class="card">
                <div class="card-header">🔒 Change Password</div>
                <div class="card-body">
                    <form method="POST" style="display:flex;flex-direction:column;gap:1.1rem;">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <div class="input-icon-wrap" style="position:relative;"><span class="input-icon">🔑</span>
                            <input type="password" name="current_password" id="cp1" class="form-control" required>
                            <button type="button" class="password-toggle" data-target="cp1">👁️</button></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <div class="input-icon-wrap" style="position:relative;"><span class="input-icon">🔐</span>
                            <input type="password" name="new_password" id="cp2" class="form-control" placeholder="Minimum 6 characters" required>
                            <button type="button" class="password-toggle" data-target="cp2">👁️</button></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-icon-wrap" style="position:relative;"><span class="input-icon">🔏</span>
                            <input type="password" name="confirm_password" id="cp3" class="form-control" required>
                            <button type="button" class="password-toggle" data-target="cp3">👁️</button></div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary btn-full">🔒 Update Password</button>
                    </form>
                </div>
            </div>
            <div class="card mt-3" style="border-color:var(--red-200);">
                <div class="card-header" style="color:var(--c-red);">⚠️ Danger Zone</div>
                <div class="card-body">
                    <p style="font-size:0.9rem;margin-bottom:1rem;">Deleting your account is permanent and cannot be undone. All your bookings will be lost.</p>
                    <button class="btn btn-danger" onclick="return confirm('Are you absolutely sure? This cannot be undone!')">🗑️ Delete Account</button>
                </div>
            </div>
        </div>
    </div>

    <!-- TRAVEL STATS PANE -->
    <div id="pane-stats" class="hidden">
        <div class="grid-2" style="gap:1.5rem;margin-bottom:2rem;">
            <div class="chart-card">
                <div class="chart-title">Booking History (Last 6 Months)</div>
                <canvas id="bookChart" height="200"></canvas>
            </div>
            <div class="chart-card">
                <div class="chart-title">Class Preference</div>
                <canvas id="classChart" height="200"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header">🏆 Travel Achievements</div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;">
                <?php $achievements=[
                    ['🌟','First Journey',$totalBookings>=1],
                    ['🎯','5 Trips',$totalBookings>=5],
                    ['🚀','10 Trips',$totalBookings>=10],
                    ['💎','Big Spender',$totalSpent>=5000],
                    ['🗺️','Explorer',$totalBookings>=3],
                    ['⭐','Loyal Traveller',$confirmedCount>=8],
                ]; ?>
                <?php foreach($achievements as $a): ?>
                <div style="text-align:center;padding:1.25rem;background:<?= $a[2]?'linear-gradient(135deg,var(--c-brand-light),#DBEAFE)':'var(--surface-1)' ?>;border-radius:var(--r-lg);border:1.5px solid <?= $a[2]?'#BFDBFE':'var(--border)' ?>;opacity:<?= $a[2]?'1':'0.5' ?>;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;"><?= $a[0] ?></div>
                    <div style="font-size:0.82rem;font-weight:800;color:<?= $a[2]?'var(--c-brand)':'var(--ink-4)' ?>;"><?= $a[1] ?></div>
                    <div style="font-size:0.72rem;color:var(--ink-4);margin-top:0.2rem;"><?= $a[2]?'✅ Earned':'🔒 Locked' ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div><!-- /page-wrapper -->

<?php include 'includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
function switchTab(btn, paneId) {
    document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    ['pane-overview','pane-edit','pane-security','pane-stats'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('hidden', id !== paneId);
    });
    if (paneId === 'pane-stats') initCharts();
}

let chartsInited = false;
function initCharts() {
    if (chartsInited) return;
    chartsInited = true;
    const months = [];
    for (let i = 5; i >= 0; i--) {
        const d = new Date(); d.setMonth(d.getMonth() - i);
        months.push(d.toLocaleString('default',{month:'short'}));
    }
    new Chart(document.getElementById('bookChart'), {
        type: 'line',
        data: { labels: months, datasets: [{ label:'Bookings', data:[0,0,0,0,0,<?= $totalBookings ?>], borderColor:'#2563EB', backgroundColor:'rgba(37,99,235,0.1)', tension:0.4, fill:true, pointBackgroundColor:'#2563EB', pointRadius:5 }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true,ticks:{stepSize:1}}, x:{grid:{display:false}} } }
    });
    new Chart(document.getElementById('classChart'), {
        type: 'doughnut',
        data: { labels:['AC First Class','AC 3 Tier','Sleeper'], datasets:[{ data:[0,0,0], backgroundColor:['#2563EB','#0EA5E9','#22C55E'], borderWidth:0, hoverOffset:8 }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{ padding:16, font:{size:13} } } }, cutout:'65%' }
    });
}
</script>
</body>
</html>
