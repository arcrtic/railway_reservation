// =============================================
// RAILWAY RESERVATION SYSTEM - MAIN JS
// =============================================

// ---- TOAST NOTIFICATIONS ----
function showToast(msg, type = 'info', duration = 3500) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span class="toast-icon">${icons[type] || 'ℹ️'}</span><span class="toast-msg">${msg}</span><button class="toast-close" onclick="this.parentElement.remove()">✕</button>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ---- MODAL ----
function openModal(id) {
    const overlay = document.getElementById(id);
    if (overlay) {
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}
function closeModal(id) {
    const overlay = document.getElementById(id);
    if (overlay) {
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }
}
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

// ---- HAMBURGER MENU ----
document.addEventListener('DOMContentLoaded', () => {
    const ham = document.getElementById('hamburger');
    const menu = document.getElementById('mobile-menu');
    if (ham && menu) {
        ham.addEventListener('click', () => {
            const isOpen = menu.classList.toggle('open');
            ham.classList.toggle('active');
            // Override inline style so CSS class can take effect
            menu.style.display = isOpen ? 'flex' : 'none';
        });
    }
    // Close mobile menu when clicking a link inside it
    document.querySelectorAll('#mobile-menu a').forEach(link => {
        link.addEventListener('click', () => {
            const menu = document.getElementById('mobile-menu');
            const ham  = document.getElementById('hamburger');
            if (menu) { menu.classList.remove('open'); menu.style.display = 'none'; }
            if (ham)  { ham.classList.remove('active'); }
        });
    });
});

// ---- TRAIN SEARCH ----
function swapCities() {
    const from = document.getElementById('from_city');
    const to = document.getElementById('to_city');
    if (from && to) {
        [from.value, to.value] = [to.value, from.value];
        const btn = document.querySelector('.swap-btn');
        if (btn) { btn.style.transform = 'translateY(-50%) rotate(180deg)'; setTimeout(() => btn.style.transform = 'translateY(-50%)', 400); }
    }
}

// Live train filter
function filterTrains() {
    const query = (document.getElementById('train-filter')?.value || '').toLowerCase();
    const cards = document.querySelectorAll('.train-card');
    let visible = 0;
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        const show = !query || text.includes(query);
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const empty = document.getElementById('no-trains');
    if (empty) empty.style.display = visible === 0 ? '' : 'none';
}

// Select fare class on train card
function selectFare(el, trainId, cls, price, seats) {
    document.querySelectorAll(`.train-card[data-train="${trainId}"] .fare-item`).forEach(f => f.classList.remove('selected'));
    el.classList.add('selected');
    const card = el.closest('.train-card');
    if (card) {
        card.dataset.selectedClass = cls;
        card.dataset.selectedPrice = price;
        card.dataset.selectedSeats = seats;
    }
}

// ---- SEAT MAP ----
let selectedSeats = [];
const MAX_SEATS = 6;

function toggleSeat(el) {
    if (el.classList.contains('booked')) return;
    const seatId = el.dataset.seat;
    if (el.classList.contains('selected')) {
        el.classList.remove('selected');
        el.classList.add('available');
        selectedSeats = selectedSeats.filter(s => s !== seatId);
    } else {
        const maxAllowed = parseInt(document.getElementById('passengers')?.value || 1);
        if (selectedSeats.length >= maxAllowed) {
            showToast(`You can only select ${maxAllowed} seat(s)`, 'warning');
            return;
        }
        el.classList.remove('available');
        el.classList.add('selected');
        selectedSeats.push(seatId);
    }
    updateSeatSummary();
}

function updateSeatSummary() {
    const el = document.getElementById('selected-seats-display');
    if (el) el.textContent = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'None';
    const inp = document.getElementById('seat_numbers');
    if (inp) inp.value = selectedSeats.join(',');
    const cnt = document.getElementById('seat-count');
    if (cnt) cnt.textContent = selectedSeats.length;
}

function generateSeatMap(containerId, totalSeats = 72, bookedSeats = []) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';
    const perCoach = 24;
    const coaches = Math.ceil(totalSeats / perCoach);
    for (let c = 0; c < coaches; c++) {
        const coach = document.createElement('div');
        coach.className = 'seat-coach';
        const label = document.createElement('div');
        label.className = 'coach-label';
        label.textContent = `Coach ${String.fromCharCode(65 + c)}`;
        const grid = document.createElement('div');
        grid.className = 'seat-grid';
        for (let s = 1; s <= perCoach; s++) {
            const num = c * perCoach + s;
            if (num > totalSeats) break;
            const seat = document.createElement('div');
            seat.className = `seat ${bookedSeats.includes(num) ? 'booked' : 'available'}`;
            seat.dataset.seat = `${String.fromCharCode(65 + c)}${s}`;
            seat.textContent = s;
            if (!bookedSeats.includes(num)) seat.onclick = () => toggleSeat(seat);
            grid.appendChild(seat);
        }
        coach.appendChild(label);
        coach.appendChild(grid);
        container.appendChild(coach);
    }
}

// ---- STEPPER ----
function goToStep(step) {
    document.querySelectorAll('.step-panel').forEach((p, i) => {
        p.classList.toggle('hidden', i + 1 !== step);
    });
    document.querySelectorAll('.step-wrap').forEach((s, i) => {
        const circle = s.querySelector('.step-circle');
        const line = s.nextElementSibling;
        if (i + 1 < step) {
            s.querySelector('.step-circle').textContent = '✓';
            circle.parentElement.classList.add('done');
            circle.parentElement.classList.remove('active');
            if (line && line.classList.contains('step-line')) line.classList.add('done');
        } else if (i + 1 === step) {
            circle.parentElement.classList.add('active');
            circle.parentElement.classList.remove('done');
        } else {
            circle.parentElement.classList.remove('active','done');
            if (line && line.classList.contains('step-line')) line.classList.remove('done');
        }
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ---- BOOKING FLOW ----
let bookingData = {};

function proceedToPassenger(trainId, trainName, cls, price, fromCity, toCity, date) {
    if (!cls || !price) { showToast('Please select a travel class first', 'warning'); return; }
    bookingData = { trainId, trainName, cls, price, fromCity, toCity, date };
    document.getElementById('summary-train').textContent = trainName;
    document.getElementById('summary-class').textContent = cls;
    document.getElementById('summary-price').textContent = '₹' + price;
    document.getElementById('summary-route').textContent = `${fromCity} → ${toCity}`;
    document.getElementById('summary-date').textContent = date;
    document.getElementById('hidden-train-id').value = trainId;
    document.getElementById('hidden-class').value = cls;
    document.getElementById('hidden-price').value = price;
    goToStep(2);
}

function proceedToSeat() {
    const form = document.getElementById('passenger-form');
    const name = document.getElementById('passenger_name')?.value;
    const age  = document.getElementById('passenger_age')?.value;
    const gender = document.getElementById('passenger_gender')?.value;
    const contact = document.getElementById('contact_number')?.value;
    if (!name || !age || !gender || !contact) { showToast('Please fill all passenger details', 'warning'); return; }
    if (!/^\d{10}$/.test(contact)) { showToast('Enter a valid 10-digit contact number', 'error'); return; }
    const trainId = bookingData.trainId;
    const cls = bookingData.cls;
    generateSeatMap('seat-map', 72, [3,7,14,22,25,31,36,40,41,48,55,60]);
    goToStep(3);
}

function proceedToPayment() {
    const seats = document.getElementById('seat_numbers')?.value;
    if (!seats) { showToast('Please select at least one seat', 'warning'); return; }
    document.getElementById('pay-train').textContent = bookingData.trainName;
    document.getElementById('pay-class').textContent = bookingData.cls;
    document.getElementById('pay-route').textContent = `${bookingData.fromCity} → ${bookingData.toCity}`;
    document.getElementById('pay-date').textContent = bookingData.date;
    document.getElementById('pay-seats').textContent = seats;
    document.getElementById('pay-amount').textContent = '₹' + bookingData.price;
    goToStep(4);
}

function simulatePayment() {
    const btn = document.getElementById('pay-btn');
    btn.disabled = true; btn.textContent = '⏳ Processing...';
    setTimeout(() => {
        document.getElementById('book-form').submit();
    }, 1800);
}

// ---- HERO ANIMATED COUNTER ----
function animateCounter(el, target, duration = 1500) {
    const start = 0; const step = target / (duration / 16);
    let current = start;
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = Math.floor(current).toLocaleString();
        if (current >= target) clearInterval(timer);
    }, 16);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-counter]').forEach(el => {
        const target = parseInt(el.dataset.counter);
        const observer = new IntersectionObserver(entries => {
            entries.forEach(e => { if (e.isIntersecting) { animateCounter(el, target); observer.disconnect(); } });
        });
        observer.observe(el);
    });
});

// ---- SEARCH TABS ----
function switchTab(tab) {
    document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
}

// ---- DATE PICKER MIN DATE ----
document.addEventListener('DOMContentLoaded', () => {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(inp => { if (!inp.min) inp.min = today; });
});

// ---- FORM VALIDATION HELPERS ----
function validateBookingForm() {
    let valid = true;
    const required = document.querySelectorAll('#book-form [required]');
    required.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--red-500)';
            valid = false;
        } else {
            field.style.borderColor = '';
        }
    });
    return valid;
}

// ---- PASSWORD VISIBILITY TOGGLE ----
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling || document.getElementById(btn.dataset.target);
            if (input) {
                input.type = input.type === 'password' ? 'text' : 'password';
                btn.textContent = input.type === 'password' ? '👁️' : '🙈';
            }
        });
    });
});

// ---- AUTOCOMPLETE FOR CITIES ----
const CITIES = ['New Delhi','Mumbai','Kolkata','Chennai','Bangalore','Hyderabad','Pune','Ahmedabad','Surat','Jaipur','Lucknow','Kanpur','Nagpur','Patna','Bhopal','Indore','Vadodara','Agra','Varanasi','Mysore'];

function initCityAutocomplete(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const wrapper = input.parentElement;
    wrapper.style.position = 'relative';
    const dropdown = document.createElement('div');
    dropdown.className = 'autocomplete-dropdown';
    dropdown.style.cssText = 'position:absolute;top:100%;left:0;right:0;background:white;border:1px solid var(--border);border-radius:var(--radius-sm);box-shadow:var(--shadow-lg);z-index:100;max-height:200px;overflow-y:auto;display:none;';
    wrapper.appendChild(dropdown);

    input.addEventListener('input', () => {
        const val = input.value.toLowerCase();
        const matches = CITIES.filter(c => c.toLowerCase().includes(val));
        if (val && matches.length) {
            dropdown.innerHTML = matches.map(c => `<div style="padding:0.6rem 1rem;cursor:pointer;font-size:0.9rem;border-bottom:1px solid var(--border);transition:background 0.15s;" onmouseenter="this.style.background='var(--blue-50)'" onmouseleave="this.style.background=''" onclick="document.getElementById('${inputId}').value='${c}';this.parentElement.style.display='none'">${c}</div>`).join('');
            dropdown.style.display = 'block';
        } else {
            dropdown.style.display = 'none';
        }
    });
    document.addEventListener('click', (e) => { if (!wrapper.contains(e.target)) dropdown.style.display = 'none'; });
}

document.addEventListener('DOMContentLoaded', () => {
    initCityAutocomplete('from_city');
    initCityAutocomplete('to_city');
    initCityAutocomplete('search_from');
    initCityAutocomplete('search_to');
});

// ---- PRINT TICKET ----
function printTicket() {
    const content = document.getElementById('ticket-print').innerHTML;
    const win = window.open('', '_blank');
    win.document.write(`<html><head><title>Ticket</title><style>body{font-family:sans-serif;padding:20px;} .pnr-display{font-size:2rem;font-weight:900;letter-spacing:5px;text-align:center;border:2px dashed #2563EB;padding:1rem;margin:1rem 0;border-radius:8px;color:#2563EB;} table{width:100%;border-collapse:collapse;} td,th{padding:8px 12px;border:1px solid #ddd;}</style></head><body>${content}</body></html>`);
    win.document.close();
    win.print();
}

// ---- LOGOUT CONFIRM ----
function confirmLogout(url) {
    if (confirm('Are you sure you want to logout?')) window.location.href = url;
}
