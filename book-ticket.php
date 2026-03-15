<?php
require_once 'config.php';
// Allow browsing but require login to submit
if (session_status() === PHP_SESSION_NONE) session_start();

// Handle POST submission — requires login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=book-ticket.php');
        exit;
    }
    $conn    = getDB();
    $userId  = (int)$_SESSION['user_id'];
    $train_id= (int)($_POST['train_id'] ?? 0);
    $cls     = clean($_POST['ticket_class'] ?? '');
    $fare    = (float)($_POST['fare']       ?? 0);
    $date    = clean($_POST['journey_date'] ?? '');
    $seat    = clean($_POST['seat_selection'] ?? '');

    // Primary passenger
    $p_name   = clean($_POST['passenger_name']   ?? '');
    $p_age    = (int)($_POST['passenger_age']    ?? 0);
    $p_gender = clean($_POST['passenger_gender'] ?? '');
    $contact  = clean($_POST['contact_number']   ?? '');

    // Additional passengers (JSON array)
    $extraPax = json_decode($_POST['extra_passengers'] ?? '[]', true) ?: [];

    $errors = [];
    if (!$p_name)   $errors[] = 'Primary passenger name required.';
    if (!$p_age)    $errors[] = 'Primary passenger age required.';
    if (!$p_gender) $errors[] = 'Primary passenger gender required.';
    if (!preg_match('/^\d{10}$/', $contact)) $errors[] = 'Valid 10-digit contact number required.';
    if (!$date)     $errors[] = 'Journey date required.';
    if (!$cls)      $errors[] = 'Travel class required.';
    if (!$train_id) $errors[] = 'No train selected.';
    if ($fare <= 0) $errors[] = 'Invalid fare.';

    if (empty($errors)) {
        $tr   = $conn->query("SELECT from_city,to_city,available_seats FROM trains WHERE id=$train_id LIMIT 1")->fetch_assoc();
        $from = $tr['from_city'] ?? ''; $to = $tr['to_city'] ?? '';
        $totalPax = 1 + count($extraPax);

        if ($tr['available_seats'] < $totalPax) {
            $errors[] = "Only {$tr['available_seats']} seat(s) available, you need $totalPax.";
        } else {
            // Generate unique PNR
            do { $pnr = generatePNR(); $r = $conn->query("SELECT id FROM bookings WHERE pnr='$pnr'"); } while ($r && $r->num_rows > 0);

            $totalFare = $fare * $totalPax;
            $stmt = $conn->prepare("INSERT INTO bookings (pnr,user_id,train_id,passenger_name,passenger_age,passenger_gender,contact_number,from_city,to_city,journey_date,ticket_class,seat_number,fare) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("siisisssssssd",$pnr,$userId,$train_id,$p_name,$p_age,$p_gender,$contact,$from,$to,$date,$cls,$seat,$totalFare);

            if ($stmt->execute()) {
                $bookingId = $conn->insert_id;
                // Insert primary passenger into booking_passengers
                $ps = $conn->prepare("INSERT INTO booking_passengers (booking_id,passenger_name,passenger_age,passenger_gender) VALUES (?,?,?,?)");
                $ps->bind_param("isis",$bookingId,$p_name,$p_age,$p_gender);
                $ps->execute(); $ps->close();

                // Insert extra passengers
                foreach ($extraPax as $ep) {
                    $epName   = clean($ep['name']   ?? '');
                    $epAge    = (int)($ep['age']    ?? 0);
                    $epGender = clean($ep['gender'] ?? '');
                    if ($epName && $epAge) {
                        $ps2 = $conn->prepare("INSERT INTO booking_passengers (booking_id,passenger_name,passenger_age,passenger_gender) VALUES (?,?,?,?)");
                        $ps2->bind_param("isis",$bookingId,$epName,$epAge,$epGender);
                        $ps2->execute(); $ps2->close();
                    }
                }

                // Decrement seats
                $conn->query("UPDATE trains SET available_seats=available_seats-$totalPax WHERE id=$train_id AND available_seats>=$totalPax");
                $stmt->close(); $conn->close();
                header("Location: booking-confirm.php?pnr=$pnr");
                exit;
            } else { $errors[] = 'Booking failed: '.$stmt->error; $stmt->close(); }
        }
    }
    $conn->close();
}

$train_id = (int)($_GET['train_id'] ?? $_POST['train_id'] ?? 0);
$date     = clean($_GET['date']  ?? date('Y-m-d'));
$cls      = clean($_GET['class'] ?? '');
$train    = null;
if ($train_id) { $conn = getDB(); $train = $conn->query("SELECT * FROM trains WHERE id=$train_id AND status='active' LIMIT 1")->fetch_assoc(); $conn->close(); }
$errors   = $errors ?? [];
$fareMap  = ['AC First Class'=>'fare_ac1','AC 3 Tier'=>'fare_ac3','Sleeper'=>'fare_sleeper'];
$initFare = ($cls && $train) ? ($train[$fareMap[$cls] ?? ''] ?? 0) : 0;
$isLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Book Ticket — RailBook</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<?php if (!$isLoggedIn): ?>
<!-- LOGIN PROMPT BANNER -->
<div style="background:linear-gradient(135deg,var(--amber-500),var(--c-amber));padding:1rem 2rem;text-align:center;">
    <div style="max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:center;gap:1rem;flex-wrap:wrap;">
        <span style="color:white;font-weight:700;font-size:0.95rem;">👋 You can browse trains as a guest, but you'll need to <strong>sign in to complete a booking</strong>.</span>
        <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-sm" style="background:white;color:var(--c-amber);">Login Now</a>
        <a href="signup.php" class="btn btn-sm" style="background:rgba(255,255,255,0.2);color:white;border:1.5px solid rgba(255,255,255,0.5);">Sign Up Free</a>
    </div>
</div>
<?php endif; ?>

<div class="page-wrapper" style="max-width:960px;">
<div class="page-header">
    <span class="section-label">🎫 Ticket Booking</span>
    <h1 class="page-title">Book Your Ticket</h1>
    <p class="page-subtitle">Complete 4 steps — supports up to 6 passengers per booking</p>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error mb-3">
    <div><strong>Please fix:</strong><ul style="margin:0.4rem 0 0 1rem;"><?php foreach($errors as $e): ?><li style="font-size:0.88rem;"><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
</div>
<?php endif; ?>

<!-- STEPPER -->
<div class="stepper mb-3">
    <div class="step-wrap active" id="sw-1"><div class="step-circle" id="sc-1">1</div><div class="step-label">Train & Class</div></div>
    <div class="step-line" id="sl-1"></div>
    <div class="step-wrap" id="sw-2"><div class="step-circle" id="sc-2">2</div><div class="step-label">Passengers</div></div>
    <div class="step-line" id="sl-2"></div>
    <div class="step-wrap" id="sw-3"><div class="step-circle" id="sc-3">3</div><div class="step-label">Seats</div></div>
    <div class="step-line" id="sl-3"></div>
    <div class="step-wrap" id="sw-4"><div class="step-circle" id="sc-4">4</div><div class="step-label">Payment</div></div>
</div>

<form method="POST" id="booking-form" novalidate>
    <input type="hidden" name="submit_booking"  value="1">
    <input type="hidden" name="train_id"         id="f-train-id"  value="<?= $train_id ?>">
    <input type="hidden" name="ticket_class"     id="f-class"     value="<?= htmlspecialchars($cls) ?>">
    <input type="hidden" name="fare"             id="f-fare"      value="<?= $initFare ?>">
    <input type="hidden" name="journey_date"     id="f-date"      value="<?= htmlspecialchars($date) ?>">
    <input type="hidden" name="seat_selection"   id="f-seat"      value="">
    <input type="hidden" name="extra_passengers" id="f-extra-pax" value="[]">

    <!-- ═══ STEP 1: TRAIN & CLASS ═══ -->
    <div class="step-panel" id="panel-1">
        <div class="card mb-3">
            <div class="card-header">🔍 Search a Different Train</div>
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:0.85rem;align-items:end;">
                <div class="form-group"><label class="form-label">From</label><input type="text" id="qs-from" class="form-control" placeholder="Departure city" value="<?= htmlspecialchars($train['from_city']??'') ?>" autocomplete="off"></div>
                <div class="form-group"><label class="form-label">To</label><input type="text" id="qs-to" class="form-control" placeholder="Destination" value="<?= htmlspecialchars($train['to_city']??'') ?>" autocomplete="off"></div>
                <div class="form-group"><label class="form-label">Date</label><input type="date" id="qs-date" class="form-control" value="<?= htmlspecialchars($date) ?>"></div>
                <button type="button" class="btn btn-outline" onclick="doSearch()">🔍 Search</button>
            </div>
        </div>

        <?php if ($train): ?>
        <div class="train-card selected" id="sel-card" data-train="<?= $train['id'] ?>">
            <div class="train-route">
                <div class="route-city">
                    <div class="route-time"><?= date('H:i',strtotime($train['departure_time'])) ?></div>
                    <div class="route-name"><?= htmlspecialchars($train['from_city']) ?></div>
                </div>
                <div class="route-line">
                    <div class="route-duration">⏱ <?= $train['duration'] ?></div>
                    <div class="route-bar"></div>
                    <div style="font-size:0.7rem;color:var(--ink-4);"><?= $train['train_number'] ?></div>
                </div>
                <div class="route-city" style="text-align:right;">
                    <div class="route-time"><?= date('H:i',strtotime($train['arrival_time'])) ?></div>
                    <div class="route-name"><?= htmlspecialchars($train['to_city']) ?></div>
                </div>
            </div>
            <div class="train-divider"></div>
            <div class="train-meta">
                <div>
                    <div class="train-name-num" id="train-display-name"><?= htmlspecialchars($train['train_name']) ?></div>
                    <div class="train-num-tag"># <?= $train['train_number'] ?></div>
                    <?php if($train['available_seats']>0): ?>
                    <span class="badge badge-success" style="margin-top:0.4rem;">✔ <?= $train['available_seats'] ?> seats</span>
                    <?php else: ?>
                    <span class="badge badge-danger" style="margin-top:0.4rem;">Waitlist only</span>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--ink-4);margin-bottom:0.5rem;">Select Class *</div>
                    <div class="fare-grid" id="fare-grid">
                        <div class="fare-item <?= $cls==='AC First Class'?'selected':'' ?>" onclick="pickFare(this,'<?= $train['id'] ?>','AC First Class',<?= $train['fare_ac1'] ?>)">
                            <div class="fare-class">AC 1st</div><div class="fare-price">₹<?= number_format($train['fare_ac1'],0) ?></div>
                            <div class="fare-seats"><?= $train['available_seats']>0?'<span style="color:var(--c-green)">Avail.</span>':'<span style="color:var(--red-500)">WL</span>' ?></div>
                        </div>
                        <div class="fare-item <?= $cls==='AC 3 Tier'?'selected':'' ?>" onclick="pickFare(this,'<?= $train['id'] ?>','AC 3 Tier',<?= $train['fare_ac3'] ?>)">
                            <div class="fare-class">AC 3T</div><div class="fare-price">₹<?= number_format($train['fare_ac3'],0) ?></div>
                            <div class="fare-seats"><?= $train['available_seats']>0?'<span style="color:var(--c-green)">Avail.</span>':'<span style="color:var(--red-500)">WL</span>' ?></div>
                        </div>
                        <?php if($train['fare_sleeper']>0): ?>
                        <div class="fare-item <?= $cls==='Sleeper'?'selected':'' ?>" onclick="pickFare(this,'<?= $train['id'] ?>','Sleeper',<?= $train['fare_sleeper'] ?>)">
                            <div class="fare-class">Sleeper</div><div class="fare-price">₹<?= number_format($train['fare_sleeper'],0) ?></div>
                            <div class="fare-seats"><?= $train['available_seats']>0?'<span style="color:var(--c-green)">Avail.</span>':'<span style="color:var(--red-500)">WL</span>' ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div id="class-error" style="color:var(--c-red);font-size:0.8rem;font-weight:700;margin-top:0.5rem;display:none;">⚠ Please select a class</div>
                </div>
            </div>
        </div>
        <div class="card mt-3"><div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:center;">
            <div class="form-group">
                <label class="form-label">Journey Date *</label>
                <input type="date" id="vis-date" class="form-control" value="<?= htmlspecialchars($date) ?>" min="<?= date('Y-m-d') ?>" onchange="document.getElementById('f-date').value=this.value;state.date=this.value;">
            </div>
            <div style="background:var(--c-brand-light);border-radius:var(--r-sm);padding:0.85rem 1rem;border:1px solid #DBEAFE;">
                <div style="font-size:0.82rem;font-weight:700;color:var(--ink-4);">Class: <span id="cls-lbl" style="color:var(--c-brand);"><?= $cls?:'—' ?></span></div>
                <div style="font-size:0.82rem;font-weight:700;color:var(--ink-4);margin-top:0.25rem;">Per person: <span id="fare-lbl" style="color:#15803D;"><?= $initFare?'₹'.number_format($initFare,0):'—' ?></span></div>
                <div style="font-size:0.82rem;font-weight:700;color:var(--ink-4);margin-top:0.25rem;">Total: <span id="total-fare-lbl" style="color:#15803D;font-weight:800;"><?= $initFare?'₹'.number_format($initFare,0):'—' ?></span></div>
            </div>
        </div></div>
        <?php else: ?>
        <div class="card" style="text-align:center;padding:3rem 2rem;">
            <div style="font-size:3rem;margin-bottom:1rem;">🚆</div>
            <h3>No train selected</h3>
            <p style="margin:0.5rem 0 1.5rem;">Search above or browse the timetable.</p>
            <a href="search.php" class="btn btn-primary">Search Trains</a>
        </div>
        <?php endif; ?>
        <div style="display:flex;justify-content:flex-end;margin-top:1.5rem;">
            <button type="button" class="btn btn-primary btn-lg" onclick="step1Next()">Next: Add Passengers →</button>
        </div>
    </div>

    <!-- ═══ STEP 2: PASSENGERS ═══ -->
    <div class="step-panel hidden" id="panel-2">
        <!-- Primary passenger -->
        <div class="pax-card primary-pax mb-3" id="pax-primary">
            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem;">
                <div class="pax-number">1</div>
                <div>
                    <div style="font-family:'Sora',sans-serif;font-weight:800;font-size:0.95rem;">Primary Passenger</div>
                    <div style="font-size:0.78rem;color:var(--ink-4);">Contact details will be linked to this passenger</div>
                </div>
                <span class="badge badge-info" style="margin-left:auto;">Primary</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">Full Name *</label>
                    <div class="input-icon-wrap"><span class="input-icon">👤</span>
                    <input type="text" name="passenger_name" id="pax-name" class="form-control" placeholder="Name as on ID" value="<?= htmlspecialchars($_POST['passenger_name']??'') ?>"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Age *</label>
                    <input type="number" name="passenger_age" id="pax-age" class="form-control" placeholder="Age" min="1" max="120" value="<?= htmlspecialchars($_POST['passenger_age']??'') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Gender *</label>
                    <select name="passenger_gender" id="pax-gender" class="form-control">
                        <option value="">Select</option>
                        <option value="male"   <?= ($_POST['passenger_gender']??'')==='male'?'selected':'' ?>>Male</option>
                        <option value="female" <?= ($_POST['passenger_gender']??'')==='female'?'selected':'' ?>>Female</option>
                        <option value="other"  <?= ($_POST['passenger_gender']??'')==='other'?'selected':'' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">Contact Number *</label>
                    <div class="input-icon-wrap"><span class="input-icon">📱</span>
                    <input type="tel" name="contact_number" id="pax-phone" class="form-control" placeholder="10-digit mobile number" value="<?= htmlspecialchars($_POST['contact_number']??'') ?>"></div>
                </div>
            </div>
        </div>

        <!-- Additional passengers container -->
        <div id="extra-pax-container"></div>

        <!-- Add passenger button -->
        <div style="margin-bottom:1.5rem;">
            <button type="button" class="btn btn-outline" id="add-pax-btn" onclick="addPassenger()" style="width:100%;border-style:dashed;justify-content:center;gap:0.5rem;">
                <span style="font-size:1.2rem;">+</span> Add Another Passenger
                <span id="pax-counter" style="font-size:0.78rem;color:var(--ink-4);">(1/6 added)</span>
            </button>
        </div>

        <!-- Summary -->
        <div style="background:var(--c-brand-light);border-radius:var(--r-md);padding:0.9rem 1.25rem;margin-bottom:1rem;border:1px solid #DBEAFE;font-size:0.85rem;font-weight:700;color:var(--ink-4);display:flex;gap:2rem;flex-wrap:wrap;">
            <span>🚆 <span id="s2-train" style="color:var(--ink-1);">—</span></span>
            <span>🎟 <span id="s2-class" style="color:var(--c-brand);">—</span></span>
            <span>📅 <span id="s2-date">—</span></span>
            <span>👥 <span id="s2-pax" style="color:var(--c-brand);">1</span> passenger(s)</span>
            <span>💰 Total: <span id="s2-fare" style="color:#15803D;font-weight:800;">—</span></span>
        </div>
        <div style="display:flex;justify-content:space-between;">
            <button type="button" class="btn btn-ghost" onclick="gotoStep(1)">← Back</button>
            <button type="button" class="btn btn-primary btn-lg" onclick="step2Next()">Next: Select Seats →</button>
        </div>
    </div>

    <!-- ═══ STEP 3: SEAT MAP ═══ -->
    <div class="step-panel hidden" id="panel-3">
        <div style="background:var(--c-brand-light);border:1px solid #DBEAFE;border-radius:var(--r-md);padding:0.9rem 1.25rem;margin-bottom:1.25rem;font-size:0.88rem;font-weight:700;color:var(--ink-4);">
            💡 Select <strong id="seats-needed" style="color:var(--c-brand);">1</strong> seat(s) — one per passenger
        </div>
        <div class="seat-map-wrap mb-3">
            <div class="seat-legend">
                <div class="legend-item"><div class="legend-dot" style="background:linear-gradient(180deg,#dbeafe,#bfdbfe);border:2px solid #93c5fd;"></div>Available</div>
                <div class="legend-item"><div class="legend-dot" style="background:linear-gradient(180deg,#3b82f6,#2563eb);"></div>Your Seat</div>
                <div class="legend-item"><div class="legend-dot" style="background:linear-gradient(180deg,#f1f5f9,#e2e8f0);border:2px solid #cbd5e1;opacity:0.65;"></div>Booked</div>
                <div class="legend-item"><div class="legend-dot" style="background:linear-gradient(180deg,#fdf2f8,#fce7f3);border:2px solid #f9a8d4;"></div>Ladies</div>
            </div>
            <div id="seat-map-container"></div>
            <div style="margin-top:1.25rem;padding:0.9rem 1.25rem;background:white;border-radius:var(--r-md);border:1px solid #DBEAFE;">
                <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;font-size:0.88rem;">
                    <span>Selected: <strong id="seat-display" style="color:var(--c-brand);font-family:'JetBrains Mono',monospace;"> None</strong></span>
                    <span>Count: <strong id="seat-count-display" style="color:var(--c-brand);"> 0</strong> / <strong id="seats-max">1</strong></span>
                </div>
            </div>
        </div>
        <div style="display:flex;justify-content:space-between;">
            <button type="button" class="btn btn-ghost" onclick="gotoStep(2)">← Back</button>
            <button type="button" class="btn btn-primary btn-lg" onclick="step3Next()">Next: Review & Pay →</button>
        </div>
    </div>

    <!-- ═══ STEP 4: REVIEW + PAYMENT ═══ -->
    <div class="step-panel hidden" id="panel-4">
        <div class="card mb-3" style="border:2px solid #DBEAFE;">
            <div class="card-header" style="background:linear-gradient(135deg,var(--c-brand-light),white);">📋 Booking Summary</div>
            <div class="card-body">
                <div class="ticket-details">
                    <div class="ticket-detail-item"><div class="detail-label">Train</div><div class="detail-value" id="rev-train">—</div></div>
                    <div class="ticket-detail-item"><div class="detail-label">Class</div><div class="detail-value" id="rev-class">—</div></div>
                    <div class="ticket-detail-item"><div class="detail-label">Route</div><div class="detail-value" id="rev-route">—</div></div>
                    <div class="ticket-detail-item"><div class="detail-label">Date</div><div class="detail-value" id="rev-date">—</div></div>
                    <div class="ticket-detail-item"><div class="detail-label">Passengers</div><div class="detail-value" id="rev-pax-list">—</div></div>
                    <div class="ticket-detail-item"><div class="detail-label">Seats</div><div class="detail-value" id="rev-seat">—</div></div>
                    <div class="ticket-detail-item" style="grid-column:span 2;background:linear-gradient(135deg,var(--c-brand-light),white);border-color:#BFDBFE;">
                        <div class="detail-label">Total Fare (All Passengers)</div>
                        <div class="detail-value" id="rev-fare" style="font-size:1.4rem;color:var(--c-brand);">—</div>
                    </div>
                </div>
                <!-- Passenger list preview -->
                <div id="pax-review-list" style="margin-top:1rem;display:flex;flex-direction:column;gap:0.5rem;"></div>
            </div>
        </div>

        <?php if (!$isLoggedIn): ?>
        <div class="alert alert-warning mb-3">
            🔑 You need to <a href="login.php" style="font-weight:800;color:var(--amber-700);">log in</a> or <a href="signup.php" style="font-weight:800;color:var(--amber-700);">create an account</a> to confirm this booking.
        </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-header">💳 Payment Method</div>
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:0.85rem;">
                <?php foreach([['💳','Credit / Debit Card','Visa, Mastercard, RuPay'],['📱','UPI','GPay, PhonePe, Paytm'],['🏦','Net Banking','All major banks'],['💰','Wallet','Paytm, Amazon Pay']] as $m): ?>
                <label class="payment-method" onclick="document.querySelectorAll('.payment-method').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">
                    <input type="radio" name="payment_method" value="<?= htmlspecialchars($m[1]) ?>">
                    <span style="font-size:1.5rem;"><?= $m[0] ?></span>
                    <div><div style="font-weight:800;font-size:0.9rem;color:var(--ink-1);"><?= $m[1] ?></div><div style="font-size:0.75rem;color:var(--ink-4);"><?= $m[2] ?></div></div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:0.6rem;background:var(--green-50);border:1px solid #86EFAC;border-radius:var(--r-sm);padding:0.85rem 1rem;margin-bottom:1.5rem;">
            <span style="font-size:1.2rem;">🔒</span><span style="font-size:0.85rem;color:#15803D;font-weight:700;">Secured by 256-bit SSL encryption. Your payment is safe.</span>
        </div>
        <div style="display:flex;justify-content:space-between;">
            <button type="button" class="btn btn-ghost" onclick="gotoStep(3)">← Back</button>
            <button type="button" class="btn btn-success btn-xl" id="pay-btn" onclick="submitBooking()">✅ Confirm & Pay</button>
        </div>
    </div>
</form>
</div><!-- /page-wrapper -->

<?php include 'includes/footer.php'; ?>
<script>
const MAX_PAX = 6;
const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
const state = {
    trainId: '<?= $train_id ?>',
    trainName: '<?= addslashes($train["train_name"] ?? "") ?>',
    fromCity: '<?= addslashes($train["from_city"] ?? "") ?>',
    toCity: '<?= addslashes($train["to_city"] ?? "") ?>',
    cls: '<?= addslashes($cls) ?>',
    fare: <?= $initFare ?: 0 ?>,
    date: '<?= $date ?>',
    seats: [],
    extraPassengers: []
};

// ── STEPPER ──
function gotoStep(n) {
    for (let i = 1; i <= 4; i++) {
        document.getElementById('panel-'+i)?.classList.toggle('hidden', i !== n);
        const sw = document.getElementById('sw-'+i), sc = document.getElementById('sc-'+i), sl = document.getElementById('sl-'+i);
        if (!sw) continue;
        sw.className = 'step-wrap' + (i === n ? ' active' : i < n ? ' done' : '');
        sc.textContent = i < n ? '✓' : i;
        if (sl) sl.classList.toggle('done', i < n);
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── FARE SELECTION ──
function pickFare(el, trainId, cls, fare) {
    document.querySelectorAll('#fare-grid .fare-item').forEach(f => f.classList.remove('selected'));
    el.classList.add('selected');
    state.trainId = trainId; state.cls = cls; state.fare = parseFloat(fare);
    document.getElementById('f-train-id').value = trainId;
    document.getElementById('f-class').value = cls;
    document.getElementById('f-fare').value = fare;
    document.getElementById('cls-lbl').textContent = cls;
    document.getElementById('class-error').style.display = 'none';
    updateTotalFare();
}

function updateTotalFare() {
    const total = state.fare * (1 + state.extraPassengers.length);
    document.getElementById('fare-lbl').textContent = state.fare > 0 ? '₹' + state.fare.toLocaleString('en-IN') : '—';
    document.getElementById('total-fare-lbl').textContent = state.fare > 0 ? '₹' + total.toLocaleString('en-IN') : '—';
    document.getElementById('s2-fare').textContent = state.fare > 0 ? '₹' + total.toLocaleString('en-IN') : '—';
}

// ── STEP 1 ──
function step1Next() {
    if (!state.cls) { document.getElementById('class-error').style.display = 'block'; showToast('Please select a travel class','warning'); return; }
    if (!document.getElementById('f-date').value) { showToast('Please select a journey date','warning'); return; }
    if (!state.trainId || state.trainId === '0') { showToast('No train selected','warning'); return; }
    const tName = document.getElementById('train-display-name')?.textContent || state.trainName || '—';
    document.getElementById('s2-train').textContent = tName;
    document.getElementById('s2-class').textContent = state.cls;
    document.getElementById('s2-date').textContent = document.getElementById('f-date').value;
    updateTotalFare();
    gotoStep(2);
}

// ── MULTI-PASSENGER ──
let paxCount = 1;
function addPassenger() {
    if (paxCount >= MAX_PAX) { showToast(`Maximum ${MAX_PAX} passengers allowed`, 'warning'); return; }
    paxCount++;
    const container = document.getElementById('extra-pax-container');
    const paxIndex = paxCount - 1; // 0-based extra index
    const card = document.createElement('div');
    card.className = 'pax-card mb-3';
    card.id = `pax-extra-${paxIndex}`;
    card.innerHTML = `
        <button type="button" class="pax-remove" onclick="removePassenger(${paxIndex}, this)">✕</button>
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem;">
            <div class="pax-number">${paxCount}</div>
            <div><div style="font-family:'Sora',sans-serif;font-weight:800;font-size:0.95rem;">Passenger ${paxCount}</div>
            <div style="font-size:0.78rem;color:var(--ink-4);">Additional traveller</div></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
            <div class="form-group" style="grid-column:span 3;">
                <label class="form-label">Full Name *</label>
                <div class="input-icon-wrap"><span class="input-icon">👤</span>
                <input type="text" class="form-control ep-name" placeholder="Name as on ID" oninput="syncExtraPax()"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Age *</label>
                <input type="number" class="form-control ep-age" placeholder="Age" min="1" max="120" oninput="syncExtraPax()">
            </div>
            <div class="form-group">
                <label class="form-label">Gender *</label>
                <select class="form-control ep-gender" onchange="syncExtraPax()">
                    <option value="">Select</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Berth Pref.</label>
                <select class="form-control ep-berth">
                    <option value="">Any</option>
                    <option value="lower">Lower</option>
                    <option value="middle">Middle</option>
                    <option value="upper">Upper</option>
                </select>
            </div>
        </div>`;
    container.appendChild(card);
    // Animate in
    card.style.opacity = '0'; card.style.transform = 'translateY(12px)';
    requestAnimationFrame(() => { card.style.transition = 'all 0.3s ease'; card.style.opacity = '1'; card.style.transform = 'translateY(0)'; });
    updatePaxCounter();
    updateTotalFare();
}

function removePassenger(index, btn) {
    const card = btn.closest('.pax-card');
    card.style.transition = 'all 0.25s ease'; card.style.opacity = '0'; card.style.transform = 'translateY(-8px)';
    setTimeout(() => { card.remove(); paxCount = Math.max(1, paxCount - 1); renumberPassengers(); syncExtraPax(); updatePaxCounter(); updateTotalFare(); }, 250);
}

function renumberPassengers() {
    document.querySelectorAll('#extra-pax-container .pax-card').forEach((card, i) => {
        const num = card.querySelector('.pax-number');
        if (num) num.textContent = i + 2;
        const title = card.querySelector('[style*="font-family"]');
        if (title) title.textContent = `Passenger ${i + 2}`;
    });
}

function syncExtraPax() {
    state.extraPassengers = [];
    document.querySelectorAll('#extra-pax-container .pax-card').forEach(card => {
        const name = card.querySelector('.ep-name')?.value?.trim() || '';
        const age  = parseInt(card.querySelector('.ep-age')?.value) || 0;
        const gender = card.querySelector('.ep-gender')?.value || '';
        state.extraPassengers.push({ name, age, gender });
    });
    document.getElementById('f-extra-pax').value = JSON.stringify(state.extraPassengers);
    document.getElementById('s2-pax').textContent = 1 + state.extraPassengers.length;
    updateTotalFare();
}

function updatePaxCounter() {
    const total = 1 + document.querySelectorAll('#extra-pax-container .pax-card').length;
    document.getElementById('pax-counter').textContent = `(${total}/${MAX_PAX} added)`;
    document.getElementById('add-pax-btn').disabled = total >= MAX_PAX;
    document.getElementById('seats-needed').textContent = total;
    document.getElementById('seats-max').textContent = total;
}

// ── STEP 2 ──
function step2Next() {
    const name = document.getElementById('pax-name').value.trim();
    const age  = document.getElementById('pax-age').value.trim();
    const gender = document.getElementById('pax-gender').value;
    const phone  = document.getElementById('pax-phone').value.trim();
    if (!name)   { showToast('Enter primary passenger name','warning');   document.getElementById('pax-name').focus(); return; }
    if (!age)    { showToast('Enter primary passenger age','warning');    document.getElementById('pax-age').focus();  return; }
    if (!gender) { showToast('Select primary passenger gender','warning'); document.getElementById('pax-gender').focus(); return; }
    if (!/^\d{10}$/.test(phone)) { showToast('Enter valid 10-digit phone number','error'); document.getElementById('pax-phone').focus(); return; }
    // Validate extra passengers
    for (let i = 0; i < state.extraPassengers.length; i++) {
        const ep = state.extraPassengers[i];
        if (!ep.name)   { showToast(`Enter name for Passenger ${i+2}`,'warning'); return; }
        if (!ep.age)    { showToast(`Enter age for Passenger ${i+2}`,'warning'); return; }
        if (!ep.gender) { showToast(`Select gender for Passenger ${i+2}`,'warning'); return; }
    }
    buildSeatMap(1 + state.extraPassengers.length);
    gotoStep(3);
}

// ── SEAT MAP ──
const bookedNums = [3,7,12,14,18,22,25,27,31,36,39,41,45,48,52,55,58,60,64,68];
function buildSeatMap(needed) {
    const container = document.getElementById('seat-map-container');
    container.innerHTML = ''; state.seats = []; updateSeatUI();
    const coaches = 3, perCoach = 24, letters = ['A','B','C'], ladies = [1,2,3,4];
    for (let c = 0; c < coaches; c++) {
        const div = document.createElement('div'); div.className = 'seat-coach';
        div.innerHTML = `<div class="coach-label">Coach ${letters[c]}</div><div class="seat-grid" id="cg-${c}"></div>`;
        container.appendChild(div);
        const grid = div.querySelector('.seat-grid');
        for (let s = 1; s <= perCoach; s++) {
            const gn = c * perCoach + s, sid = letters[c] + s;
            const isB = bookedNums.includes(gn), isL = ladies.includes(s);
            const seat = document.createElement('div');
            seat.className = `seat ${isB ? 'booked' : isL ? 'ladies' : 'available'}`;
            seat.dataset.id = sid; seat.textContent = s;
            if (!isB) seat.addEventListener('click', () => toggleSeat(seat, sid, needed));
            grid.appendChild(seat);
        }
    }
}

function toggleSeat(el, seatId, maxNeeded) {
    const needed = parseInt(document.getElementById('seats-max').textContent) || 1;
    if (el.classList.contains('selected')) {
        el.classList.remove('selected'); el.classList.add(el.dataset.orig || 'available');
        state.seats = state.seats.filter(s => s !== seatId);
    } else {
        if (state.seats.length >= needed) { showToast(`You can select max ${needed} seat(s) for your group`,'warning'); return; }
        el.dataset.orig = el.classList.contains('ladies') ? 'ladies' : 'available';
        el.classList.remove('available','ladies'); el.classList.add('selected');
        state.seats.push(seatId);
    }
    updateSeatUI();
}

function updateSeatUI() {
    document.getElementById('seat-display').textContent  = state.seats.length ? ' ' + state.seats.join(', ') : ' None';
    document.getElementById('seat-count-display').textContent = ' ' + state.seats.length;
    document.getElementById('f-seat').value = state.seats.join(',');
}

// ── STEP 3 ──
function step3Next() {
    const needed = parseInt(document.getElementById('seats-max').textContent) || 1;
    if (state.seats.length < needed) { showToast(`Please select ${needed} seat(s) for all passengers`,'warning'); return; }
    const tName = document.getElementById('train-display-name')?.textContent || state.trainName || '—';
    const totalPax = 1 + state.extraPassengers.length;
    const totalFare = state.fare * totalPax;
    document.getElementById('rev-train').textContent = tName;
    document.getElementById('rev-class').textContent = state.cls;
    document.getElementById('rev-route').textContent = (state.fromCity||'?') + ' → ' + (state.toCity||'?');
    document.getElementById('rev-date').textContent  = document.getElementById('f-date').value;
    document.getElementById('rev-pax-list').textContent = `${totalPax} passenger(s)`;
    document.getElementById('rev-seat').textContent  = state.seats.join(', ');
    document.getElementById('rev-fare').textContent  = '₹' + totalFare.toLocaleString('en-IN');
    // Passenger review list
    const revList = document.getElementById('pax-review-list');
    const paxName = document.getElementById('pax-name').value;
    const paxAge  = document.getElementById('pax-age').value;
    let html = `<div style="font-size:0.82rem;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--ink-4);margin-bottom:0.5rem;">Passenger List</div>`;
    html += `<div style="display:flex;gap:0.5rem;align-items:center;padding:0.5rem 0.75rem;background:var(--c-brand-light);border-radius:var(--r-sm);font-size:0.88rem;"><span class="pax-number" style="width:24px;height:24px;font-size:0.72rem;">1</span> <strong>${paxName}</strong>, ${paxAge}y <span class="badge badge-info" style="margin-left:auto;">Primary</span></div>`;
    state.extraPassengers.forEach((ep, i) => {
        html += `<div style="display:flex;gap:0.5rem;align-items:center;padding:0.5rem 0.75rem;background:var(--surface-1);border-radius:var(--r-sm);font-size:0.88rem;margin-top:0.3rem;"><span class="pax-number" style="width:24px;height:24px;font-size:0.72rem;background:var(--slate-600);">${i+2}</span> <strong>${ep.name}</strong>, ${ep.age}y</div>`;
    });
    revList.innerHTML = html;
    gotoStep(4);
}

// ── PAYMENT ──
function submitBooking() {
    if (!isLoggedIn) { showToast('Please log in to confirm booking','warning'); setTimeout(()=>window.location.href='login.php',1200); return; }
    const method = document.querySelector('input[name="payment_method"]:checked');
    if (!method) { showToast('Please select a payment method','warning'); return; }
    const btn = document.getElementById('pay-btn');
    btn.disabled = true; btn.innerHTML = '<span style="display:inline-block;animation:spin 0.8s linear infinite;margin-right:0.4rem;">⏳</span> Processing…';
    document.getElementById('f-class').value = state.cls;
    document.getElementById('f-fare').value  = state.fare * (1 + state.extraPassengers.length);
    document.getElementById('f-seat').value  = state.seats.join(',');
    document.getElementById('f-extra-pax').value = JSON.stringify(state.extraPassengers);
    setTimeout(() => document.getElementById('booking-form').submit(), 2000);
}

function doSearch() {
    const from = document.getElementById('qs-from').value.trim();
    const to   = document.getElementById('qs-to').value.trim();
    const date = document.getElementById('qs-date').value;
    window.location.href = `search.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&date=${encodeURIComponent(date)}`;
}

document.addEventListener('DOMContentLoaded', () => {
    gotoStep(1);
    initCityAutocomplete('qs-from'); initCityAutocomplete('qs-to');
    const visDate = document.getElementById('vis-date');
    if (visDate) visDate.addEventListener('change', () => { document.getElementById('f-date').value = visDate.value; state.date = visDate.value; });
    if (state.cls && state.fare) { updateTotalFare(); }
});
const sty = document.createElement('style');
sty.textContent = '@keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}';
document.head.appendChild(sty);
</script>
</body>
</html>
