<?php
// navbar.php - include in every page
if (session_status() === PHP_SESSION_NONE) session_start();
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="index.php" class="navbar-brand">
            <div class="brand-icon">🚆</div>
            RailBook
        </a>
        <ul class="navbar-nav">
            <li><a href="index.php" class="<?= $current==='index.php'?'active':'' ?>">Home</a></li>
            <li><a href="search.php" class="<?= $current==='search.php'?'active':'' ?>">Search Trains</a></li>
            <li><a href="timetable.php" class="<?= $current==='timetable.php'?'active':'' ?>">Timetable</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="my-bookings.php" class="<?= $current==='my-bookings.php'?'active':'' ?>">My Bookings</a></li>
            <?php endif; ?>
            <li><a href="contact.php" class="<?= $current==='contact.php'?'active':'' ?>">Contact</a></li>
        </ul>
        <div class="navbar-actions">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="profile.php" style="display:flex;align-items:center;gap:0.5rem;font-size:0.88rem;color:var(--text-secondary);font-weight:700;text-decoration:none;padding:0.3rem 0.7rem;border-radius:8px;transition:all 0.2s;" onmouseover="this.style.background='var(--blue-50)';this.style.color='var(--primary)'" onmouseout="this.style.background='';this.style.color='var(--text-secondary)'">
                    <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--blue-600),var(--sky-500));display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:800;color:white;flex-shrink:0;"><?= strtoupper(substr($_SESSION['full_name']??$_SESSION['username']??'U',0,1)) ?></div>
                    <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?>
                </a>
                <?php if($_SESSION['role']==='admin'): ?>
                <a href="admin/index.php" class="btn btn-sm btn-outline">⚙️ Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-sm btn-ghost" onclick="return confirm('Logout?')">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-sm btn-outline">Login</a>
                <a href="signup.php" class="btn btn-sm btn-primary">Sign Up</a>
            <?php endif; ?>
        </div>
        <div class="hamburger" id="hamburger">
            <span></span><span></span><span></span>
        </div>
    </div>
</nav>
<!-- Mobile Menu -->
<div class="mobile-menu" id="mobile-menu" style="display:none;">
    <a href="index.php" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);padding:0.5rem 0;">Home</a>
    <a href="search.php" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);padding:0.5rem 0;">Search Trains</a>
    <a href="timetable.php" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);padding:0.5rem 0;">Timetable</a>
    <?php if(isset($_SESSION['user_id'])): ?>
    <a href="my-bookings.php" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);padding:0.5rem 0;">My Bookings</a>
    <?php endif; ?>
    <a href="contact.php" style="font-size:1.1rem;font-weight:700;color:var(--text-primary);padding:0.5rem 0;">Contact</a>
    <div style="margin-top:1rem;display:flex;flex-direction:column;gap:0.75rem;">
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="logout.php" class="btn btn-ghost btn-full">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline btn-full">Login</a>
            <a href="signup.php" class="btn btn-primary btn-full">Sign Up</a>
        <?php endif; ?>
    </div>
</div>
