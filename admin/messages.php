<?php
require_once '../config.php';
requireAdmin();
$conn = getDB();

if (isset($_POST['mark_read'])) {
    $id = (int)$_POST['msg_id'];
    $conn->query("UPDATE contacts SET status='read' WHERE id=$id");
    header("Location: messages.php"); exit;
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM contacts WHERE id=$id");
    header("Location: messages.php?msg=deleted"); exit;
}

$messages = $conn->query("SELECT * FROM contacts ORDER BY submitted_at DESC")->fetch_all(MYSQLI_ASSOC);
$newCount = count(array_filter($messages, fn($m) => $m['status']==='new'));
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Messages — RailBook Admin</title>
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
            <a href="users.php" class="sidebar-link"><span class="icon">👥</span>Users</a>
            <div class="sidebar-section">Communication</div>
            <a href="messages.php" class="sidebar-link active"><span class="icon">📬</span>Messages</a>
            <div class="sidebar-section">Account</div>
            <a href="../index.php" class="sidebar-link"><span class="icon">🏠</span>View Site</a>
            <a href="../logout.php" class="sidebar-link"><span class="icon">🚪</span>Logout</a>
        </nav>
    </aside>
    <div class="admin-main">
        <div class="admin-topbar">
            <h3>Contact Messages</h3>
            <div style="display:flex;gap:0.75rem;">
                <?php if($newCount>0): ?>
                <span class="badge badge-danger"><?= $newCount ?> unread</span>
                <?php endif; ?>
                <span class="badge badge-secondary"><?= count($messages) ?> total</span>
            </div>
        </div>
        <div class="admin-content">
            <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success mb-3">✅ Message deleted.</div>
            <?php endif; ?>

            <?php if(empty($messages)): ?>
            <div class="card" style="text-align:center;padding:4rem;">
                <div style="font-size:3rem;margin-bottom:1rem;">📭</div>
                <h3>No messages yet</h3>
                <p>Messages from the contact form will appear here.</p>
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:1rem;">
                <?php foreach($messages as $m): ?>
                <div class="card" style="border-left:4px solid <?= $m['status']==='new' ? 'var(--c-brand)' : 'var(--border)' ?>;">
                    <div class="card-body" style="padding:1.25rem;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                            <div style="flex:1;">
                                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                                    <div style="width:36px;height:36px;border-radius:50%;background:#DBEAFE;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem;color:var(--blue-700);flex-shrink:0;">
                                        <?= strtoupper(substr($m['name'],0,1)) ?>
                                    </div>
                                    <div>
                                        <span style="font-weight:700;color:var(--ink-1);"><?= htmlspecialchars($m['name']) ?></span>
                                        <span style="color:var(--ink-4);font-size:0.85rem;margin-left:0.5rem;"><?= htmlspecialchars($m['email']) ?></span>
                                    </div>
                                    <?php if($m['status']==='new'): ?>
                                    <span class="badge badge-info">🆕 New</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">✓ Read</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-weight:700;font-size:0.95rem;color:var(--ink-1);margin-bottom:0.4rem;">
                                    📌 <?= htmlspecialchars($m['subject']) ?>
                                </div>
                                <p style="font-size:0.9rem;color:var(--ink-2);line-height:1.6;margin:0;">
                                    <?= nl2br(htmlspecialchars($m['message'])) ?>
                                </p>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:0.5rem;align-items:flex-end;flex-shrink:0;">
                                <div style="font-size:0.78rem;color:var(--ink-4);white-space:nowrap;">
                                    🕐 <?= date('d M Y, H:i', strtotime($m['submitted_at'])) ?>
                                </div>
                                <div style="display:flex;gap:0.5rem;">
                                    <?php if($m['status']==='new'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="msg_id" value="<?= $m['id'] ?>">
                                        <button type="submit" name="mark_read" class="btn btn-sm btn-outline">✓ Mark Read</button>
                                    </form>
                                    <?php endif; ?>
                                    <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="btn btn-sm btn-primary">↩ Reply</a>
                                    <a href="?delete=<?= $m['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this message?')">🗑</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div id="toast-container"></div>
<script src="../assets/js/app.js"></script>
</body>
</html>
