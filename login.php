<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $conn = getDB();
        $stmt = $conn->prepare("SELECT id, full_name, username, password, role FROM users WHERE username=? OR email=?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $conn->close();
        if ($user && $password === $user['password']) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            header('Location: ' . ($user['role']==='admin' ? 'admin/index.php' : 'index.php'));
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — RailBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-left">
        <div class="auth-left-bg"></div>
        <div class="auth-logo">
            <div class="auth-logo-icon">🚆</div>
            <div class="auth-logo-text">RailBook</div>
        </div>
        <h1 class="auth-headline">Travel Smarter,<br>Book <span>Faster</span></h1>
        <p class="auth-desc">Join millions of travellers who book their railway tickets online with RailBook — India's most reliable platform.</p>
        <div class="auth-features">
            <div class="auth-feature"><div class="auth-feature-icon">⚡</div>Instant booking confirmation</div>
            <div class="auth-feature"><div class="auth-feature-icon">💺</div>Interactive seat selection</div>
            <div class="auth-feature"><div class="auth-feature-icon">📊</div>Live train tracking & status</div>
            <div class="auth-feature"><div class="auth-feature-icon">🔒</div>100% secure transactions</div>
        </div>
    </div>
    <div class="auth-right">
        <h2 class="auth-form-title">Welcome back 👋</h2>
        <p class="auth-form-sub">Sign in to your RailBook account</p>
        <?php if($error): ?>
        <div class="alert alert-error mb-2">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['registered'])): ?>
        <div class="alert alert-success mb-2">✅ Account created! Please sign in.</div>
        <?php endif; ?>
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label class="form-label">Username or Email</label>
                <div class="input-icon-wrap">
                    <span class="input-icon">👤</span>
                    <input type="text" name="username" class="form-control" placeholder="Enter your username or email" value="<?= htmlspecialchars($_POST['username']??'') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-icon-wrap" style="position:relative;">
                    <span class="input-icon">🔑</span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" data-target="password">👁️</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-lg">🚆 Sign In</button>
        </form>
        <p style="text-align:center;margin-top:1.5rem;font-size:0.9rem;color:var(--ink-4);">
            Don't have an account? <a href="signup.php" style="font-weight:700;color:var(--c-brand);">Create one free →</a>
        </p>
        <p style="text-align:center;margin-top:0.75rem;font-size:0.82rem;color:var(--ink-4);">
            Admin demo: <code style="background:var(--surface-2);padding:2px 6px;border-radius:4px;">admin / admin123</code>
        </p>
    </div>
</div>
<div id="toast-container"></div>
<script src="assets/js/app.js"></script>
</body>
</html>
