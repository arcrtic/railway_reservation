<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean($_POST['full_name'] ?? '');
    $username  = clean($_POST['username']  ?? '');
    $email     = clean($_POST['email']     ?? '');
    $phone     = clean($_POST['phone']     ?? '');
    $password  = $_POST['password']  ?? '';
    $confirm   = $_POST['confirm']   ?? '';

    if (!$full_name||!$username||!$email||!$password) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $conn = getDB();
        $check = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, phone) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss", $full_name, $username, $email, $password, $phone);
            $stmt->execute();
            $success = 'Account created successfully!';
            header('Location: login.php?registered=1');
            exit;
        }
        $check->close(); $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — RailBook</title>
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
        <h1 class="auth-headline">Start Your<br><span>Journey</span> Today</h1>
        <p class="auth-desc">Create a free account and unlock instant access to thousands of train routes across India.</p>
        <div class="auth-features">
            <div class="auth-feature"><div class="auth-feature-icon">🎫</div>Book tickets in under 2 minutes</div>
            <div class="auth-feature"><div class="auth-feature-icon">📋</div>Manage all bookings in one place</div>
            <div class="auth-feature"><div class="auth-feature-icon">🔔</div>Real-time booking notifications</div>
            <div class="auth-feature"><div class="auth-feature-icon">💰</div>Exclusive member discounts</div>
        </div>
    </div>
    <div class="auth-right" style="overflow-y:auto;">
        <h2 class="auth-form-title">Create Account 🚀</h2>
        <p class="auth-form-sub">Join RailBook — it's free forever</p>
        <?php if($error): ?>
        <div class="alert alert-error mb-2">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <div class="input-icon-wrap">
                    <span class="input-icon">👤</span>
                    <input type="text" name="full_name" class="form-control" placeholder="Your full name" value="<?= htmlspecialchars($_POST['full_name']??'') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" placeholder="Choose username" value="<?= htmlspecialchars($_POST['username']??'') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control" placeholder="10-digit number" value="<?= htmlspecialchars($_POST['phone']??'') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <div class="input-icon-wrap">
                    <span class="input-icon">📧</span>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <div class="input-icon-wrap" style="position:relative;">
                        <span class="input-icon">🔑</span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Min. 6 characters" required>
                        <button type="button" class="password-toggle" data-target="password">👁️</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm *</label>
                    <div class="input-icon-wrap" style="position:relative;">
                        <span class="input-icon">🔐</span>
                        <input type="password" name="confirm" id="confirm" class="form-control" placeholder="Repeat password" required>
                        <button type="button" class="password-toggle" data-target="confirm">👁️</button>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-lg">🚆 Create My Account</button>
        </form>
        <p style="text-align:center;margin-top:1.5rem;font-size:0.9rem;color:var(--ink-4);">
            Already have an account? <a href="login.php" style="font-weight:700;color:var(--c-brand);">Sign in →</a>
        </p>
    </div>
</div>
<div id="toast-container"></div>
<script src="assets/js/app.js"></script>
</body>
</html>
