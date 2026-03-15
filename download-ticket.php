<?php
require_once 'config.php';
requireLogin();
$pnr = strtoupper(clean($_GET['pnr'] ?? ''));
if (!$pnr) { header('Location: my-bookings.php'); exit; }
$conn = getDB();
$stmt = $conn->prepare("SELECT b.*,t.train_name,t.train_number,t.departure_time,t.arrival_time,t.duration,u.full_name,u.email,u.phone FROM bookings b JOIN trains t ON b.train_id=t.id JOIN users u ON b.user_id=u.id WHERE b.pnr=? AND b.user_id=?");
$stmt->bind_param("si",$pnr,(int)$_SESSION['user_id']);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get all passengers
$passengers = $conn->query("SELECT * FROM booking_passengers WHERE booking_id={$b['id']}")->fetch_all(MYSQLI_ASSOC);
$conn->close();
if (!$b) { header('Location: my-bookings.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>E-Ticket <?= $pnr ?> — RailBook</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
@media print {
    body { background: white !important; }
    .no-print { display: none !important; }
    .ticket-wrapper { box-shadow: none !important; margin: 0 !important; max-width: 100% !important; }
    @page { margin: 1cm; size: A4; }
}
.ticket-wrapper { max-width: 720px; margin: 2rem auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.ticket-header { background: linear-gradient(135deg, #060d1f, #1e3a8a, #1d4ed8); padding: 2rem; color: white; position: relative; overflow: hidden; }
.ticket-header::before { content: ''; position: absolute; inset: 0; background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.05) 1px, transparent 1px), radial-gradient(circle at 80% 20%, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 30px 30px; }
.ticket-pnr { font-family: 'JetBrains Mono', monospace; font-size: 1.8rem; font-weight: 700; letter-spacing: 0.2em; color: #38BDF8; text-shadow: 0 0 20px rgba(56,189,248,0.5); }
.ticket-route { display: flex; align-items: center; gap: 2rem; margin: 1.5rem 0; }
.r-city { text-align: center; }
.r-time { font-family: 'JetBrains Mono', monospace; font-size: 2.2rem; font-weight: 700; color: white; line-height: 1; }
.r-name { font-size: 0.9rem; color: rgba(255,255,255,0.7); margin-top: 0.3rem; }
.r-line { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.4rem; }
.r-bar { width: 100%; height: 2px; background: rgba(255,255,255,0.3); border-radius: 1px; position: relative; }
.r-bar::before, .r-bar::after { content: ''; position: absolute; top: 50%; transform: translateY(-50%); width: 10px; height: 10px; border-radius: 50%; background: #38BDF8; }
.r-bar::before { left: -5px; } .r-bar::after { right: -5px; }
.r-dur { font-size: 0.8rem; color: rgba(255,255,255,0.6); background: rgba(255,255,255,0.1); padding: 0.2rem 0.7rem; border-radius: 50px; }
.ticket-tear { background: repeating-linear-gradient(90deg, transparent, transparent 18px, white 18px, white 20px); height: 2px; position: relative; }
.ticket-tear::before { content: '●'; position: absolute; left: -12px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; background: var(--bg-page); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: transparent; }
.ticket-tear::after { content: '●'; position: absolute; right: -12px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; background: var(--bg-page); border-radius: 50%; color: transparent; }
.ticket-body { padding: 1.75rem; }
.ticket-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; }
.tk-item { background: #F8FAFC; border-radius: 8px; padding: 0.75rem; border: 1px solid #E2E8F0; }
.tk-label { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #94A3B8; margin-bottom: 0.3rem; }
.tk-value { font-size: 0.9rem; font-weight: 800; color: #0F172A; }
.pax-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; margin-top: 1rem; }
.pax-table th { background: #1E293B; color: #E2E8F0; padding: 0.6rem 0.85rem; text-align: left; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.07em; }
.pax-table td { padding: 0.6rem 0.85rem; border-bottom: 1px solid #E2E8F0; }
.pax-table tr:last-child td { border-bottom: none; }
.ticket-barcode { padding: 1.25rem; border-top: 1px solid #E2E8F0; text-align: center; background: #F8FAFC; }
.barcode-visual { display: flex; justify-content: center; align-items: flex-end; gap: 1px; height: 48px; margin: 0.75rem auto; width: 220px; }
.bc-bar { background: #1E293B; border-radius: 1px; }
.ticket-footer-note { font-size: 0.75rem; color: #94A3B8; margin-top: 0.5rem; }
.status-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.75rem; font-weight: 800; }
.status-confirmed { background: #DCFCE7; color: #15803D; }
.status-cancelled  { background: #FEE2E2; color: #DC2626; }
</style>
</head>
<body style="background:var(--bg-page);">
<?php include 'includes/navbar.php'; ?>
<div class="page-wrapper" style="padding-top:1.5rem;">

    <!-- Action buttons -->
    <div class="no-print flex-between mb-3" style="flex-wrap:wrap;gap:0.75rem;">
        <div>
            <span class="section-label">🎫 E-Ticket</span>
            <h2 style="margin:0;">Your Ticket</h2>
        </div>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
            <a href="my-bookings.php" class="btn btn-ghost">← My Bookings</a>
            <button onclick="window.print()" class="btn btn-outline">🖨️ Print Ticket</button>
            <button onclick="downloadPDF()" class="btn btn-primary">⬇️ Download PDF</button>
        </div>
    </div>

    <!-- TICKET STUB -->
    <div class="ticket-wrapper" id="ticket-content">
        <!-- Header -->
        <div class="ticket-header">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;position:relative;z-index:1;">
                <div>
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.6);font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:0.4rem;">🚆 RailBook E-Ticket</div>
                    <div class="ticket-pnr"><?= $pnr ?></div>
                    <div style="font-size:0.82rem;color:rgba(255,255,255,0.6);margin-top:0.3rem;">Booked on <?= date('d M Y, H:i', strtotime($b['booked_at'])) ?></div>
                </div>
                <div style="text-align:right;">
                    <div class="status-badge <?= $b['booking_status']==='confirmed'?'status-confirmed':'status-cancelled' ?> no-print" style="margin-bottom:0.5rem;">
                        <?= $b['booking_status']==='confirmed'?'✅ Confirmed':'❌ '.$b['booking_status'] ?>
                    </div>
                    <div style="font-size:1.4rem;font-weight:800;color:#38BDF8;font-family:'Sora',sans-serif;">₹<?= number_format($b['fare'],2) ?></div>
                    <div style="font-size:0.78rem;color:rgba(255,255,255,0.5);">Total Fare Paid</div>
                </div>
            </div>
            <div class="ticket-route" style="position:relative;z-index:1;">
                <div class="r-city">
                    <div class="r-time"><?= date('H:i',strtotime($b['departure_time'])) ?></div>
                    <div class="r-name"><?= htmlspecialchars($b['from_city']) ?></div>
                    <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);margin-top:0.2rem;">Departure</div>
                </div>
                <div class="r-line">
                    <div class="r-dur">⏱ <?= $b['duration'] ?? '—' ?></div>
                    <div class="r-bar"></div>
                    <div style="font-size:0.72rem;color:rgba(255,255,255,0.5);">🚆 <?= $b['train_number'] ?></div>
                </div>
                <div class="r-city" style="text-align:right;">
                    <div class="r-time"><?= date('H:i',strtotime($b['arrival_time'])) ?></div>
                    <div class="r-name"><?= htmlspecialchars($b['to_city']) ?></div>
                    <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);margin-top:0.2rem;">Arrival</div>
                </div>
            </div>
        </div>

        <!-- Tear line -->
        <div class="ticket-tear"></div>

        <!-- Body -->
        <div class="ticket-body">
            <div style="font-size:1.1rem;font-weight:800;font-family:'Sora',sans-serif;color:var(--ink-1);margin-bottom:1rem;"><?= htmlspecialchars($b['train_name']) ?></div>
            <div class="ticket-grid">
                <div class="tk-item"><div class="tk-label">Journey Date</div><div class="tk-value"><?= date('D, d M Y',strtotime($b['journey_date'])) ?></div></div>
                <div class="tk-item"><div class="tk-label">Class</div><div class="tk-value"><?= htmlspecialchars($b['ticket_class']) ?></div></div>
                <div class="tk-item"><div class="tk-label">Seat(s)</div><div class="tk-value" style="font-family:'JetBrains Mono',monospace;"><?= htmlspecialchars($b['seat_number'] ?: 'Auto') ?></div></div>
                <div class="tk-item"><div class="tk-label">Contact</div><div class="tk-value"><?= htmlspecialchars($b['contact_number']) ?></div></div>
                <div class="tk-item"><div class="tk-label">Booking Status</div><div class="tk-value" style="color:<?= $b['booking_status']==='confirmed'?'var(--green-700)':'var(--c-red)' ?>;"><?= ucfirst($b['booking_status']) ?></div></div>
                <div class="tk-item"><div class="tk-label">Payment</div><div class="tk-value" style="color:var(--green-700);">✅ Paid</div></div>
            </div>

            <!-- Passenger List -->
            <div style="font-size:0.8rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:var(--ink-4);margin-bottom:0.5rem;">👥 Passengers</div>
            <?php if (!empty($passengers)): ?>
            <table class="pax-table">
                <thead><tr><th>#</th><th>Name</th><th>Age</th><th>Gender</th><th>Seat</th></tr></thead>
                <tbody>
                    <?php foreach($passengers as $i=>$p): ?>
                    <tr>
                        <td style="font-weight:700;color:var(--ink-4);"><?= $i+1 ?></td>
                        <td style="font-weight:700;"><?= htmlspecialchars($p['passenger_name']) ?> <?= $i===0?'<span style="font-size:0.7rem;background:#DBEAFE;color:#1D4ED8;padding:2px 6px;border-radius:50px;font-weight:800;">PRIMARY</span>':'' ?></td>
                        <td><?= $p['passenger_age'] ?>y</td>
                        <td style="text-transform:capitalize;"><?= $p['passenger_gender'] ?></td>
                        <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--c-brand);"><?= $p['seat_number'] ?: '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="background:#F8FAFC;border-radius:8px;padding:0.85rem 1rem;border:1px solid #E2E8F0;">
                <div style="font-weight:700;"><?= htmlspecialchars($b['passenger_name']) ?></div>
                <div style="font-size:0.82rem;color:var(--ink-4);"><?= $b['passenger_age'] ?>y • <?= ucfirst($b['passenger_gender']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Barcode / bottom strip -->
        <div class="ticket-barcode">
            <div style="font-size:0.72rem;color:var(--ink-4);font-weight:700;margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:0.06em;">Scan at station</div>
            <div class="barcode-visual" id="barcode-visual"></div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:var(--ink-4);letter-spacing:0.12em;margin-top:0.4rem;"><?= $pnr ?></div>
            <div class="ticket-footer-note">Valid for travel on <?= date('d M Y',strtotime($b['journey_date'])) ?> only. Please carry a valid photo ID. This e-ticket is issued by RailBook.</div>
        </div>
    </div>

    <!-- Terms note -->
    <div class="no-print" style="max-width:720px;margin:1rem auto;font-size:0.82rem;color:var(--ink-4);text-align:center;line-height:1.6;">
        ℹ️ Please carry a valid government-issued photo ID along with this ticket. This e-ticket is valid only for the specified journey date and train.
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
// Generate barcode-like visual from PNR
document.addEventListener('DOMContentLoaded', () => {
    const bc = document.getElementById('barcode-visual');
    const pnr = '<?= $pnr ?>';
    const widths = [1,2,1,3,2,1,2,1,3,1,2,3,1,2,1,1,2,3,2,1,3,1,2,1,2,3,1,2,1,3,2,1,2,1,3];
    widths.forEach((w, i) => {
        const bar = document.createElement('div');
        bar.className = 'bc-bar';
        bar.style.width = w + 'px';
        bar.style.height = (24 + (pnr.charCodeAt(i % pnr.length) % 20)) + 'px';
        bc.appendChild(bar);
    });
});

function downloadPDF() {
    // Use browser print-to-PDF
    showToast('Opening print dialog — choose "Save as PDF"', 'info');
    setTimeout(() => window.print(), 800);
}
</script>
</body>
</html>
