<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = clean($_POST['name'] ?? '');
    $email   = clean($_POST['email'] ?? '');
    $subject = clean($_POST['subject'] ?? '');
    $message = clean($_POST['message'] ?? '');
    if ($name && $email && $subject && $message) {
        $conn = getDB();
        $stmt = $conn->prepare("INSERT INTO contacts (name,email,subject,message) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss",$name,$email,$subject,$message);
        $stmt->execute(); $stmt->close(); $conn->close();
        $msg = 'success';
    } else { $msg = 'error'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Contact Us — RailBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="page-wrapper" style="max-width:960px;">
    <div class="page-header text-center">
        <span class="section-label" style="display:block;text-align:center;">📬 Get in Touch</span>
        <h1 class="page-title">Contact Us</h1>
        <p class="page-subtitle">We're here 24/7 to help with your journey</p>
    </div>
    <div class="grid-2" style="gap:2.5rem;align-items:start;">
        <!-- Contact Info -->
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <?php $infos = [
                ['📍','Address','New Delhi Railway HQ, New Delhi, India 110001'],
                ['📞','Helpline','1800-000-1234 (Toll Free, 24×7)'],
                ['📧','Email','support@railbook.in'],
                ['🕐','Hours','Round the clock, 365 days'],
            ]; ?>
            <?php foreach($infos as $info): ?>
            <div class="card">
                <div class="card-body" style="display:flex;align-items:center;gap:1rem;padding:1.25rem;">
                    <div style="font-size:1.8rem;width:50px;text-align:center;"><?= $info[0] ?></div>
                    <div>
                        <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.08em;font-weight:800;color:var(--c-brand);margin-bottom:0.2rem;"><?= $info[1] ?></div>
                        <div style="font-weight:600;color:var(--ink-1);"><?= $info[2] ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Contact Form -->
        <div class="card">
            <div class="card-header">Send a Message</div>
            <div class="card-body">
                <?php if($msg==='success'): ?>
                <div class="alert alert-success mb-3">✅ Message sent! We'll get back to you within 24 hours.</div>
                <?php elseif($msg==='error'): ?>
                <div class="alert alert-error mb-3">❌ Please fill all fields.</div>
                <?php endif; ?>
                <form method="POST" style="display:flex;flex-direction:column;gap:1.1rem;">
                    <div class="form-group">
                        <label class="form-label">Your Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Full name" value="<?= htmlspecialchars($_POST['name']??$_SESSION['full_name']??'') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject *</label>
                        <select name="subject" class="form-control" required>
                            <option value="">Select a subject</option>
                            <option>Booking Issue</option>
                            <option>Cancellation & Refund</option>
                            <option>PNR Status</option>
                            <option>Payment Problem</option>
                            <option>General Enquiry</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Your Message *</label>
                        <textarea name="message" class="form-control" placeholder="Describe your issue in detail..." required><?= htmlspecialchars($_POST['message']??'') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full btn-lg">📨 Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body></html>
