<?php
// booking-confirm.php
require_once 'config.php';
requireLogin();
$pnr = clean($_GET['pnr'] ?? '');
if (!$pnr) { header('Location: index.php'); exit; }
$conn = getDB();
$stmt = $conn->prepare("SELECT b.*, t.train_name, t.train_number, t.departure_time, t.arrival_time, t.duration FROM bookings b JOIN trains t ON b.train_id=t.id WHERE b.pnr=? AND b.user_id=?");
$stmt->bind_param("si", $pnr, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close(); $conn->close();
if (!$booking) { header('Location: my-bookings.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed — RailBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="page-wrapper" style="max-width:680px;">
    <div class="card" id="ticket-print">
        <div class="card-body" style="text-align:center;padding:2.5rem;">
            <div class="confirmation-check">✅</div>
            <h2 style="color:var(--green-700);margin-bottom:0.5rem;">Booking Confirmed!</h2>
            <p style="color:var(--ink-4);">Your ticket has been booked successfully. Show your PNR at the station.</p>
            <div class="pnr-display"><?= htmlspecialchars($pnr) ?></div>
            <div class="ticket-details" style="text-align:left;margin-top:1.5rem;">
                <div class="ticket-detail-item"><div class="detail-label">Train</div><div class="detail-value"><?= htmlspecialchars($booking['train_name']) ?> (<?= $booking['train_number'] ?>)</div></div>
                <div class="ticket-detail-item"><div class="detail-label">Passenger</div><div class="detail-value"><?= htmlspecialchars($booking['passenger_name']) ?>, <?= $booking['passenger_age'] ?> yrs</div></div>
                <div class="ticket-detail-item"><div class="detail-label">Route</div><div class="detail-value"><?= htmlspecialchars($booking['from_city']) ?> → <?= htmlspecialchars($booking['to_city']) ?></div></div>
                <div class="ticket-detail-item"><div class="detail-label">Date</div><div class="detail-value"><?= date('D, d M Y', strtotime($booking['journey_date'])) ?></div></div>
                <div class="ticket-detail-item"><div class="detail-label">Departure</div><div class="detail-value"><?= date('H:i', strtotime($booking['departure_time'])) ?></div></div>
                <div class="ticket-detail-item"><div class="detail-label">Arrival</div><div class="detail-value"><?= date('H:i', strtotime($booking['arrival_time'])) ?></div></div>
                <div class="ticket-detail-item"><div class="detail-label">Class</div><div class="detail-value"><?= htmlspecialchars($booking['ticket_class']) ?></div></div>
                <div class="ticket-detail-item"><div class="detail-label">Seat</div><div class="detail-value"><?= htmlspecialchars($booking['seat_number'] ?: 'Auto-assigned') ?></div></div>
                <div class="ticket-detail-item" style="grid-column:span 2;background:var(--c-brand-light);border:1px solid #DBEAFE;">
                    <div class="detail-label">Total Fare Paid</div>
                    <div class="detail-value" style="font-size:1.4rem;color:var(--c-brand);">₹<?= number_format($booking['fare'],2) ?></div>
                </div>
            </div>
        </div>
        <div class="card-footer" style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
            <button class="btn btn-outline" onclick="window.print()">🖨️ Print Ticket</button>
            <a href="download-ticket.php?pnr=<?= $pnr ?>" class="btn btn-purple">⬇️ Download E-Ticket</a>
            <a href="my-bookings.php" class="btn btn-primary">📋 My Bookings</a>
            <a href="index.php" class="btn btn-ghost">🏠 Home</a>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body></html>
