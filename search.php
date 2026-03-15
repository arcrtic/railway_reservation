<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
// Guest browsing allowed — login only needed to book
$conn = getDB();

$from  = clean($_GET['from']  ?? '');
$to    = clean($_GET['to']    ?? '');
$date  = clean($_GET['date']  ?? date('Y-m-d'));
$class = clean($_GET['class'] ?? '');

$trains = [];
if ($from || $to) {
    $sql = "SELECT * FROM trains WHERE status='active'";
    $params = []; $types = '';
    if ($from) { $sql .= " AND LOWER(from_city) LIKE ?"; $params[] = '%'.strtolower($from).'%'; $types .= 's'; }
    if ($to)   { $sql .= " AND LOWER(to_city) LIKE ?";   $params[] = '%'.strtolower($to).'%';   $types .= 's'; }
    $sql .= " ORDER BY departure_time ASC";
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $trains = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Trains — RailBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<?php if(!isLoggedIn()): ?>
<div style="background:linear-gradient(135deg,#F59E0B,#D97706);padding:.9rem 2rem;text-align:center;">
    <div style="max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:center;gap:1rem;flex-wrap:wrap;">
        <span style="color:white;font-weight:700;font-size:.9rem;">👋 Browse trains freely — <strong>sign in to book a ticket.</strong></span>
        <a href="login.php" class="btn btn-sm" style="background:white;color:#D97706;font-weight:800;">Login</a>
        <a href="signup.php" class="btn btn-sm" style="background:rgba(255,255,255,.18);color:white;border:1.5px solid rgba(255,255,255,.5);">Sign Up Free</a>
    </div>
</div>
<?php endif; ?>

<!-- SEARCH BAR TOP -->
<div style="background:var(--blue-900);padding:1.5rem 2rem;border-bottom:1px solid var(--blue-800);">
    <div style="max-width:1280px;margin:0 auto;">
        <form method="GET" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:0.75rem;align-items:end;">
            <div class="form-group">
                <label class="form-label" style="color:#BFDBFE;">From</label>
                <div class="input-icon-wrap">
                    <span class="input-icon">🚉</span>
                    <input type="text" id="search_from" name="from" class="form-control" placeholder="Departure city" value="<?= htmlspecialchars($from) ?>" autocomplete="off">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" style="color:#BFDBFE;">To</label>
                <div class="input-icon-wrap">
                    <span class="input-icon">📍</span>
                    <input type="text" id="search_to" name="to" class="form-control" placeholder="Destination city" value="<?= htmlspecialchars($to) ?>" autocomplete="off">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" style="color:#BFDBFE;">Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" style="color:#BFDBFE;">Class</label>
                <select name="class" class="form-control">
                    <option value="" <?= !$class?'selected':'' ?>>All Classes</option>
                    <option value="AC First Class" <?= $class==='AC First Class'?'selected':'' ?>>AC First Class</option>
                    <option value="AC 3 Tier" <?= $class==='AC 3 Tier'?'selected':'' ?>>AC 3 Tier</option>
                    <option value="Sleeper" <?= $class==='Sleeper'?'selected':'' ?>>Sleeper</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height:44px;">🔍 Search</button>
        </form>
    </div>
</div>

<div class="page-wrapper">
    <?php if($from || $to): ?>
    <!-- RESULTS HEADER -->
    <div class="flex-between mb-3" style="flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="margin-bottom:0.25rem;">
                <?= htmlspecialchars($from ?: 'Any') ?> → <?= htmlspecialchars($to ?: 'Any') ?>
            </h2>
            <p style="color:var(--ink-4);font-size:0.9rem;">
                📅 <?= date('D, d M Y', strtotime($date)) ?> &nbsp;•&nbsp;
                <strong><?= count($trains) ?></strong> train(s) found
            </p>
        </div>
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <div class="input-icon-wrap">
                <span class="input-icon">🔍</span>
                <input type="text" id="train-filter" class="form-control" placeholder="Filter by name / number..." oninput="filterTrains()" style="width:250px;">
            </div>
            <select class="form-control" style="width:160px;" onchange="sortTrains(this.value)">
                <option value="">Sort by</option>
                <option value="dep">Departure</option>
                <option value="dur">Duration</option>
                <option value="seats">Seats Available</option>
            </select>
        </div>
    </div>

    <!-- FILTER CHIPS -->
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
        <?php $filters = ['All','Available Only','AC First Class','AC 3 Tier','Sleeper']; ?>
        <?php foreach($filters as $i=>$f): ?>
        <button class="badge <?= $i===0?'badge-info':'badge-secondary' ?>" onclick="applyChip(this,'<?= $f ?>')" style="cursor:pointer;padding:0.35rem 0.9rem;font-size:0.8rem;"><?= $f ?></button>
        <?php endforeach; ?>
    </div>

    <!-- TRAIN RESULTS -->
    <div id="train-list" style="display:flex;flex-direction:column;gap:1rem;">
        <?php if(empty($trains)): ?>
        <div class="card" style="text-align:center;padding:4rem 2rem;">
            <div style="font-size:3rem;margin-bottom:1rem;">🚫</div>
            <h3>No trains found</h3>
            <p style="margin-top:0.5rem;">Try different cities or check the timetable for available routes.</p>
            <a href="timetable.php" class="btn btn-outline mt-2">View Timetable</a>
        </div>
        <?php else: ?>
        <?php foreach($trains as $i => $t): ?>
        <div class="train-card" data-train="<?= $t['id'] ?>" style="animation-delay:<?= $i*0.06 ?>s;"
             data-available="<?= $t['available_seats'] ?>"
             data-name="<?= strtolower($t['train_name'].' '.$t['train_number'].' '.$t['from_city'].' '.$t['to_city']) ?>">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <!-- Route -->
                <div class="train-route" style="flex:1;min-width:280px;">
                    <div class="route-city">
                        <div class="route-time"><?= date('H:i', strtotime($t['departure_time'])) ?></div>
                        <div class="route-name" style="font-weight:700;"><?= htmlspecialchars($t['from_city']) ?></div>
                    </div>
                    <div class="route-line">
                        <div class="route-duration">⏱ <?= $t['duration'] ?></div>
                        <div class="route-bar"></div>
                        <div style="font-size:0.7rem;color:var(--ink-4);">
                            <?php if($t['status']==='active'): ?>
                            <span style="color:var(--c-green);">●</span> On Time
                            <?php elseif($t['status']==='delayed'): ?>
                            <span style="color:var(--c-amber);">●</span> Delayed
                            <?php else: ?>
                            <span style="color:var(--red-500);">●</span> Cancelled
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="route-city" style="text-align:right;">
                        <div class="route-time"><?= date('H:i', strtotime($t['arrival_time'])) ?></div>
                        <div class="route-name" style="font-weight:700;"><?= htmlspecialchars($t['to_city']) ?></div>
                    </div>
                </div>
                <!-- Train Info -->
                <div style="min-width:180px;">
                    <div class="train-name-num"><?= htmlspecialchars($t['train_name']) ?></div>
                    <div class="train-num-tag"># <?= $t['train_number'] ?></div>
                    <div style="margin-top:0.5rem;">
                        <?php if($t['available_seats'] > 10): ?>
                        <span class="badge badge-success">✔ <?= $t['available_seats'] ?> seats</span>
                        <?php elseif($t['available_seats'] > 0): ?>
                        <span class="badge badge-warning">⚠ <?= $t['available_seats'] ?> seats left</span>
                        <?php else: ?>
                        <span class="badge badge-danger">Waitlist</span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Fare Classes -->
                <div>
                    <div style="font-size:0.75rem;color:var(--ink-4);font-weight:700;margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:0.05em;">Select Class</div>
                    <div class="fare-grid">
                        <?php if($t['fare_ac1']>0): ?>
                        <div class="fare-item" onclick="selectFare(this,<?= $t['id'] ?>,'AC First Class',<?= $t['fare_ac1'] ?>,<?= $t['available_seats'] ?>)">
                            <div class="fare-class">AC 1st</div>
                            <div class="fare-price">₹<?= number_format($t['fare_ac1'],0) ?></div>
                            <div class="fare-seats"><?= $t['available_seats'] > 0 ? 'Avail.':'WL' ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="fare-item" onclick="selectFare(this,<?= $t['id'] ?>,'AC 3 Tier',<?= $t['fare_ac3'] ?>,<?= $t['available_seats'] ?>)">
                            <div class="fare-class">AC 3T</div>
                            <div class="fare-price">₹<?= number_format($t['fare_ac3'],0) ?></div>
                            <div class="fare-seats"><?= $t['available_seats'] > 0 ? 'Avail.':'WL' ?></div>
                        </div>
                        <?php if($t['fare_sleeper']>0): ?>
                        <div class="fare-item" onclick="selectFare(this,<?= $t['id'] ?>,'Sleeper',<?= $t['fare_sleeper'] ?>,<?= $t['available_seats'] ?>)">
                            <div class="fare-class">SL</div>
                            <div class="fare-price">₹<?= number_format($t['fare_sleeper'],0) ?></div>
                            <div class="fare-seats"><?= $t['available_seats'] > 0 ? 'Avail.':'WL' ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Book Button -->
                <div style="display:flex;flex-direction:column;gap:0.5rem;align-items:center;">
                    <button class="btn btn-primary"
                        onclick="proceedToBook(<?= $t['id'] ?>,'<?= addslashes($t['train_name']) ?>','<?= $t['from_city'] ?>','<?= $t['to_city'] ?>','<?= $date ?>')">
                        Book Now →
                    </button>
                    <span style="font-size:0.72rem;color:var(--ink-4);">Runs: <?= $t['days_of_run'] ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div id="no-trains" class="hidden" style="text-align:center;padding:3rem;color:var(--ink-4);">
        <div style="font-size:2rem;margin-bottom:0.5rem;">🔍</div>
        No trains match your filter.
    </div>

    <?php else: ?>
    <!-- EMPTY STATE - No search yet -->
    <div style="text-align:center;padding:4rem 2rem;">
        <div style="font-size:4rem;margin-bottom:1rem;">🔍</div>
        <h2>Search for Trains</h2>
        <p style="color:var(--ink-4);margin-top:0.5rem;max-width:400px;margin-left:auto;margin-right:auto;">Enter your departure city, destination, and travel date above to find available trains.</p>
        <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:0.75rem;margin-top:2rem;">
            <?php $popular = [['New Delhi','Mumbai'],['Mumbai','Kolkata'],['Chennai','Bangalore'],['Hyderabad','Pune']]; ?>
            <?php foreach($popular as $r): ?>
            <a href="?from=<?= urlencode($r[0]) ?>&to=<?= urlencode($r[1]) ?>&date=<?= date('Y-m-d') ?>" class="badge badge-info" style="padding:0.5rem 1rem;font-size:0.85rem;">
                <?= $r[0] ?> → <?= $r[1] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- BOOK MODAL (opens when user clicks Book Now) -->
<div class="modal-overlay" id="bookModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">🎫 Book Ticket</div>
            <button class="modal-close" onclick="closeModal('bookModal')">✕</button>
        </div>
        <div class="modal-body">
            <div style="background:var(--c-brand-light);border-radius:var(--r-md);padding:1rem;margin-bottom:1.25rem;">
                <div style="font-family:'Sora',sans-serif;font-weight:700;font-size:1.05rem;" id="modal-train-name">—</div>
                <div style="color:var(--ink-4);font-size:0.85rem;margin-top:0.25rem;" id="modal-route">—</div>
            </div>
            <div id="modal-class-alert" class="alert alert-warning hidden mb-2">⚠️ Please select a travel class first (click a fare option on the card).</div>
            <form action="book-ticket.php" method="POST" id="modal-book-form">
                <input type="hidden" name="train_id" id="modal-train-id">
                <input type="hidden" name="journey_date" id="modal-date">
                <div class="form-group mb-2">
                    <label class="form-label">Passenger Name *</label>
                    <input type="text" name="passenger_name" class="form-control" placeholder="Full name as on ID" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Age *</label>
                        <input type="number" name="passenger_age" class="form-control" placeholder="Age" min="1" max="120" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender *</label>
                        <select name="passenger_gender" class="form-control" required>
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group mb-2">
                    <label class="form-label">Contact Number *</label>
                    <input type="tel" name="contact_number" class="form-control" placeholder="10-digit mobile number" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Class</label>
                        <input type="text" name="ticket_class" id="modal-class-display" class="form-control" readonly style="background:var(--surface-1);" placeholder="Select from card">
                        <input type="hidden" name="ticket_class_hidden" id="modal-class-val">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fare</label>
                        <input type="text" id="modal-fare-display" class="form-control" readonly style="background:var(--surface-1);">
                        <input type="hidden" name="fare" id="modal-fare-val">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Seat Preference</label>
                    <select name="seat_selection" class="form-control">
                        <option value="">No Preference</option>
                        <option value="window">🪟 Window</option>
                        <option value="aisle">🚶 Aisle</option>
                        <option value="middle">🔲 Middle</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('bookModal')">Cancel</button>
            <button class="btn btn-primary" onclick="submitBooking()">Confirm Booking 🎫</button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
function proceedToBook(trainId, trainName, from, to, date) {
    const card = document.querySelector(`.train-card[data-train="${trainId}"]`);
    const cls   = card?.dataset.selectedClass;
    const price = card?.dataset.selectedPrice;
    const alert = document.getElementById('modal-class-alert');

    document.getElementById('modal-train-name').textContent = trainName;
    document.getElementById('modal-route').textContent = `${from} → ${to} | 📅 ${date}`;
    document.getElementById('modal-train-id').value = trainId;
    document.getElementById('modal-date').value = date;

    if (cls) {
        document.getElementById('modal-class-display').value = cls;
        document.getElementById('modal-class-val').value = cls;
        document.getElementById('modal-fare-display').value = '₹' + parseFloat(price).toLocaleString();
        document.getElementById('modal-fare-val').value = price;
        alert?.classList.add('hidden');
    } else {
        document.getElementById('modal-class-display').value = '';
        document.getElementById('modal-class-val').value = '';
        document.getElementById('modal-fare-display').value = '';
        document.getElementById('modal-fare-val').value = '';
        alert?.classList.remove('hidden');
    }
    openModal('bookModal');
}

function submitBooking() {
    const cls = document.getElementById('modal-class-val').value;
    if (!cls) { document.getElementById('modal-class-alert').classList.remove('hidden'); return; }
    document.getElementById('modal-book-form').submit();
}

function sortTrains(val) {
    const list = document.getElementById('train-list');
    const cards = Array.from(list.querySelectorAll('.train-card'));
    if (val === 'seats') {
        cards.sort((a,b) => parseInt(b.dataset.available) - parseInt(a.dataset.available));
    }
    cards.forEach(c => list.appendChild(c));
}

function applyChip(el, filter) {
    document.querySelectorAll('.badge').forEach(b => { b.classList.remove('badge-info'); b.classList.add('badge-secondary'); });
    el.classList.remove('badge-secondary'); el.classList.add('badge-info');
    const cards = document.querySelectorAll('.train-card');
    cards.forEach(card => {
        if (filter === 'All') { card.style.display = ''; return; }
        if (filter === 'Available Only') { card.style.display = parseInt(card.dataset.available) > 0 ? '' : 'none'; return; }
        const name = card.dataset.name || '';
        card.style.display = name.includes(filter.toLowerCase()) || filter === 'All' ? '' : 'none';
    });
}
</script>
</body>
</html>
