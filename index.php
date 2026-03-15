<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = getDB();

// Stats for hero
$totalTrains   = $conn->query("SELECT COUNT(*) FROM trains")->fetch_row()[0];
$totalBookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$totalUsers    = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$trains        = $conn->query("SELECT * FROM trains WHERE status='active' LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RailBook — Book Train Tickets Online</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚆</text></svg>">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<!-- HERO SECTION -->
<section class="hero-section">
    <!-- Animated particles -->
    <div class="hero-particles" id="hero-particles"></div>
    
    <div class="hero-orbs">
        <div class="hero-orb hero-orb-1"></div>
        <div class="hero-orb hero-orb-2"></div>
        <div class="hero-orb hero-orb-3"></div>
    </div>
    <div class="hero-grid"></div>
    <div class="hero-grid-perspective"></div>
    <!-- 3D TRAIN SCENE - Enhanced -->
    <div class="hero-3d-scene">
        <div class="train-3d-wrap">
            <div style="position:relative;">
                <div class="train-smoke">
                    <div class="smoke-p"></div><div class="smoke-p"></div><div class="smoke-p"></div>
                </div>
                <div class="train-body-3d">
                    <div class="train-accent-stripe"></div>
                    <div class="train-stripe"></div>
                    <div class="train-windows">
                        <div class="train-win"><div class="train-win-light"></div></div>
                        <div class="train-win"><div class="train-win-light"></div></div>
                        <div class="train-win"><div class="train-win-light"></div></div>
                        <div class="train-win"></div>
                    </div>
                    <div class="train-nose"></div>
                    <div class="train-headlight"></div>
                </div>
                <div class="train-undercarriage"></div>
            </div>
            <div class="train-wheels-row">
                <div class="train-wheel-3d"></div><div class="train-wheel-3d"></div>
                <div class="train-wheel-3d"></div><div class="train-wheel-3d"></div>
            </div>
            <div class="train-track-3d"></div>
            <div class="train-track-ties">
                <div class="train-tie"></div><div class="train-tie"></div><div class="train-tie"></div>
                <div class="train-tie"></div><div class="train-tie"></div><div class="train-tie"></div>
                <div class="train-tie"></div>
            </div>
        </div>
    </div>
    <div class="hero-content">
        <div style="max-width:600px;">
            <div class="hero-tag">🚆 India's #1 Train Booking Platform</div>
            <h1 class="hero-title">Book Your <span class="underline-text">Journey</span><br>with <span class="highlight">RailBook</span></h1>
            <p class="hero-subtitle">Fast, secure, and hassle-free train ticket booking. Search from thousands of routes, choose your seat, and travel in comfort.</p>
            <!-- SEARCH BOX -->
            <div class="search-box">
                <div class="search-tabs">
                    <button class="search-tab active" onclick="switchSearchTab(this,'tab-train')">🎫 Book Ticket</button>
                    <button class="search-tab" onclick="switchSearchTab(this,'tab-pnr')">📋 PNR Status</button>
                </div>
                <div id="tab-train">
                <form action="search.php" method="GET">
                    <!-- Row 1: From / swap / To -->
                    <div style="display:grid;grid-template-columns:1fr 42px 1fr;gap:0.75rem;align-items:end;margin-bottom:0.75rem;">
                        <div class="form-group">
                            <label class="form-label">From</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon">🚉</span>
                                <input type="text" id="from_city" name="from" class="form-control" placeholder="Departure city" autocomplete="off" required>
                            </div>
                        </div>
                        <div style="display:flex;align-items:flex-end;justify-content:center;padding-bottom:1px;">
                            <button type="button" class="swap-btn" onclick="swapCities()" title="Swap cities" style="width:38px;height:42px;">⇄</button>
                        </div>
                        <div class="form-group">
                            <label class="form-label">To</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon">📍</span>
                                <input type="text" id="to_city" name="to" class="form-control" placeholder="Destination city" autocomplete="off" required>
                            </div>
                        </div>
                    </div>
                    <!-- Row 2: Date / Class / Search -->
                    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:0.75rem;align-items:end;">
                        <div class="form-group">
                            <label class="form-label">Journey Date</label>
                            <div class="input-icon-wrap">
                                <span class="input-icon">📅</span>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Class</label>
                            <select name="class" class="form-control">
                                <option value="">All Classes</option>
                                <option value="AC First Class">AC First Class</option>
                                <option value="AC 3 Tier">AC 3 Tier</option>
                                <option value="Sleeper">Sleeper</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg" style="white-space:nowrap;height:42px;">
                            🔍 Search Trains
                        </button>
                    </div>
                </form>
                </div><!-- /tab-train -->
            <!-- PNR STATUS PANEL -->
            <div id="tab-pnr" style="display:none;">
                <form action="my-bookings.php" method="GET">
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label class="form-label" style="color:var(--ink-1);font-size:0.78rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;display:block;margin-bottom:0.4rem;">PNR Number</label>
                        <div class="input-icon-wrap">
                            <span class="input-icon" style="color:#64748B;">🔖</span>
                            <input type="text" name="pnr" class="form-control"
                                   placeholder="e.g. AB12CD34"
                                   style="font-family:'JetBrains Mono',monospace;letter-spacing:0.15em;text-transform:uppercase;font-size:1.05rem;border:1.5px solid #E2E8F0;color:#0F172A;"
                                   maxlength="8" oninput="this.value=this.value.toUpperCase()" required>
                        </div>
                        <span style="font-size:0.76rem;color:#64748B;display:block;margin-top:0.35rem;">Enter your 8-character PNR from your booking confirmation.</span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full btn-lg" style="width:100%;justify-content:center;">🔍 Check PNR Status</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- STATS BAR -->
<div class="stats-bar">
    <div class="stats-bar-inner">
        <div class="stat-bar-item"><div class="stat-bar-val" data-counter="<?= $totalTrains * 10 ?>">0</div><div class="stat-bar-lbl">Daily Trains</div></div>
        <div class="stat-bar-item"><div class="stat-bar-val g" data-counter="<?= max($totalBookings,1248) ?>">0</div><div class="stat-bar-lbl">Tickets Booked</div></div>
        <div class="stat-bar-item"><div class="stat-bar-val a" data-counter="100">0</div><div class="stat-bar-lbl">Cities Connected</div></div>
        <div class="stat-bar-item"><div class="stat-bar-val r" data-counter="<?= max($totalUsers,3200) ?>">0</div><div class="stat-bar-lbl">Happy Travellers</div></div>
    </div>
</div>

<!-- POPULAR TRAINS -->
<div class="page-wrapper">
    <div style="margin-bottom:2rem;">
        <span class="section-label">🔥 Popular Routes</span>
        <h2 style="margin-bottom:0.5rem;">Frequently Booked Trains</h2>
        <p style="color:var(--ink-4);">Top trains across India's busiest corridors</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1.25rem;margin-bottom:3rem;">
        <?php foreach($trains as $i => $t): ?>
        <div class="train-card" style="animation-delay:<?= $i*0.08 ?>s;">
            <div class="train-route">
                <div class="route-city">
                    <div class="route-time"><?= date('H:i', strtotime($t['departure_time'])) ?></div>
                    <div class="route-name"><?= htmlspecialchars($t['from_city']) ?></div>
                </div>
                <div class="route-line">
                    <div class="route-duration">⏱ <?= $t['duration'] ?></div>
                    <div class="route-bar"></div>
                    <div style="font-size:0.7rem;color:var(--ink-4);"><?= $t['train_number'] ?></div>
                </div>
                <div class="route-city" style="text-align:right;">
                    <div class="route-time"><?= date('H:i', strtotime($t['arrival_time'])) ?></div>
                    <div class="route-name"><?= htmlspecialchars($t['to_city']) ?></div>
                </div>
            </div>
            <div class="train-meta">
                <div>
                    <div class="train-name-num"><?= htmlspecialchars($t['train_name']) ?></div>
                    <?php if($t['available_seats'] > 0): ?>
                        <span class="badge badge-success">✔ <?= $t['available_seats'] ?> seats left</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Waitlist Only</span>
                    <?php endif; ?>
                </div>
                <a href="search.php?from=<?= urlencode($t['from_city']) ?>&to=<?= urlencode($t['to_city']) ?>&date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-primary">Book →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- WHY CHOOSE US -->
    <div style="margin-bottom:3rem;">
        <span class="section-label">✨ Why RailBook</span>
        <h2 style="margin-bottom:2rem;">Everything you need for<br>a perfect journey</h2>
        <div class="grid-3" style="gap:1.5rem;">
            <?php $features = [
                ['🔍','Instant Search','Search across thousands of trains and routes in real-time with smart filters.'],
                ['💺','Seat Selection','Choose your exact seat from an interactive coach map before booking.'],
                ['🔒','Secure Payments','Bank-grade security on every transaction. Your data is always safe.'],
                ['📱','E-Tickets','Instant digital tickets delivered to your email and dashboard.'],
                ['↩️','Easy Cancellation','Hassle-free cancellations with transparent refund policies.'],
                ['🎧','24/7 Support','Round-the-clock customer support to help with any issue.'],
            ]; ?>
            <?php foreach($features as $i => $f): ?>
            <div class="card" style="animation-delay:<?= $i*0.08 ?>s;">
                <div class="card-body" style="display:flex;flex-direction:column;gap:0.75rem;">
                    <div style="font-size:2rem;"><?= $f[0] ?></div>
                    <h4><?= $f[1] ?></h4>
                    <p style="font-size:0.88rem;"><?= $f[2] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- POPULAR DESTINATIONS -->
    <div>
        <span class="section-label">🗺️ Destinations</span>
        <h2 style="margin-bottom:2rem;">Popular Destinations</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;">
            <?php $destinations = [
                ['New Delhi','🏛️','#EFF6FF'],['Mumbai','🌊','#F0FDF4'],
                ['Kolkata','🎨','#FEF9C3'],['Chennai','🌴','#FFF7ED'],
                ['Bangalore','🌳','#F0FDF4'],['Hyderabad','🕌','#FDF4FF'],
            ]; ?>
            <?php foreach($destinations as $d): ?>
            <a href="search.php?to=<?= urlencode($d[0]) ?>&date=<?= date('Y-m-d') ?>" style="display:block;background:<?= $d[2] ?>;border-radius:var(--r-lg);padding:1.5rem;text-align:center;border:1px solid var(--border);transition:var(--t);text-decoration:none;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--sh-md)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="font-size:2.2rem;margin-bottom:0.5rem;"><?= $d[1] ?></div>
                <div style="font-weight:700;color:var(--ink-1);font-family:'Sora',sans-serif;font-size:0.9rem;"><?= $d[0] ?></div>
                <div style="font-size:0.75rem;color:var(--ink-4);margin-top:0.2rem;">View Trains →</div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
// Generate floating particles
(function() {
    const container = document.getElementById('hero-particles');
    if (!container) return;
    for (let i = 0; i < 18; i++) {
        const p = document.createElement('div');
        p.className = 'hero-particle';
        p.style.cssText = `left:${Math.random()*100}%;width:${2+Math.random()*3}px;height:${2+Math.random()*3}px;animation-duration:${8+Math.random()*12}s;animation-delay:${Math.random()*10}s;opacity:${0.3+Math.random()*0.5}`;
        container.appendChild(p);
    }
})();

function switchSearchTab(btn, tabId) {
    // Update tab buttons
    document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    // Show/hide panels
    ['tab-train','tab-pnr'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = (id === tabId) ? 'block' : 'none';
    });
}
</script>
</body>
</html>
