@extends('layout')

@section('content')

<h2>📖 Booking Kelas</h2>
<p>Pilih teacher dan lihat jadwal kelas yang tersedia untuk di-booking.</p>

<label>Pilih Teacher:
    <select id="sel-teacher" onchange="loadBookingData()">
        <option value="">-- Pilih Teacher --</option>
    </select>
</label>
<button onclick="loadBookingData()">Load</button>

<h3>Kelas Tersedia untuk Booking</h3>
<p id="booking-info" style="color:#888; font-size:0.9em;">Pilih teacher untuk melihat kelas yang bisa di-booking.</p>

<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:1000px; margin-top:10px;">
    <thead>
        <tr style="background:#e3f2fd;">
            <th>ID</th>
            <th>Kelas</th>
            <th>Jadwal</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody id="open-sessions-list">
        <tr><td colspan="5">Pilih teacher terlebih dahulu.</td></tr>
    </tbody>
</table>

<h3>Session yang Sudah Kamu Booking</h3>

<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:1000px; margin-top:10px;">
    <thead>
        <tr style="background:#fff3e0;">
            <th>ID</th>
            <th>Kelas</th>
            <th>Jadwal</th>
            <th>Status</th>
            <th>Booked At</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody id="my-bookings-list">
        <tr><td colspan="6">Pilih teacher terlebih dahulu.</td></tr>
    </tbody>
</table>

<span id="booking-msg" style="font-weight:bold;"></span>

<script>
let openSessions = [];
let myBookings = [];
let currentTeacherId = null;

async function loadTeachers() {
    const res  = await fetch('/api/teachers');
    const data = await res.json();
    const sel  = document.getElementById('sel-teacher');
    sel.innerHTML = '<option value="">-- Pilih Teacher --</option>';
    data.forEach(t => {
        sel.innerHTML += `<option value="${t.id}">${t.user ? t.user.name : 'Teacher #' + t.id} (ID: ${t.id})</option>`;
    });
}

async function loadBookingData() {
    currentTeacherId = document.getElementById('sel-teacher').value;
    if (!currentTeacherId) {
        document.getElementById('open-sessions-list').innerHTML = '<tr><td colspan="5">Pilih teacher terlebih dahulu.</td></tr>';
        document.getElementById('my-bookings-list').innerHTML = '<tr><td colspan="6">Pilih teacher terlebih dahulu.</td></tr>';
        return;
    }

    await Promise.all([
        loadOpenSessions(),
        loadMyBookings(),
    ]);
}

async function loadOpenSessions() {
    try {
        const res = await fetch('/api/sessions/open');
        const json = await res.json();
        openSessions = (json.data || []).filter(s => !s.is_booked); // hanya yang belum di-book
        renderOpenSessions();
    } catch (e) {
        document.getElementById('open-sessions-list').innerHTML = '<tr><td colspan="5">Gagal memuat data.</td></tr>';
    }
}

async function loadMyBookings() {
    try {
        const res = await fetch(`/api/teachers/${currentTeacherId}/sessions`);
        const json = await res.json();
        const allSessions = json.data || [];
        // Filter hanya session yang di-book oleh teacher ini (is_open_for_booking)
        myBookings = allSessions.filter(s => s.is_open_for_booking && s.booked_by_teacher_id == currentTeacherId);
        renderMyBookings();
    } catch (e) {
        document.getElementById('my-bookings-list').innerHTML = '<tr><td colspan="6">Gagal memuat data.</td></tr>';
    }
}

function renderOpenSessions() {
    const tbody = document.getElementById('open-sessions-list');
    const info  = document.getElementById('booking-info');

    if (openSessions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5">Tidak ada kelas tersedia untuk booking saat ini.</td></tr>';
        info.textContent = 'Tidak ada open session yang tersedia.';
        return;
    }

    info.textContent = `${openSessions.length} kelas tersedia untuk booking.`;

    let html = '';
    openSessions.forEach(s => {
        const startFmt = s.start_time ? s.start_time.substring(0, 16).replace('T', ' ') : '—';
        const endFmt   = s.end_time   ? s.end_time.substring(0, 16).replace('T', ' ')   : '—';

        html += `<tr>
            <td>${s.id}</td>
            <td><strong>${s.class_name}</strong>${s.class_desc ? '<br><small style="color:#666;">' + s.class_desc + '</small>' : ''}</td>
            <td>${startFmt}<br>s/d ${endFmt}</td>
            <td><span style="color:white; background:#FF9800; padding:2px 8px; border-radius:4px; font-size:0.85em;">📖 Available</span></td>
            <td><button onclick="bookSession(${s.id})" style="color:white; background:#4CAF50; border:none; padding:6px 14px; border-radius:4px; cursor:pointer; font-weight:bold;">📌 Book</button></td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function renderMyBookings() {
    const tbody = document.getElementById('my-bookings-list');

    if (myBookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">Belum ada session yang di-booking.</td></tr>';
        return;
    }

    let html = '';
    myBookings.forEach(s => {
        const className = s['class']?.name ?? `Kelas #${s.class_id}`;
        const startFmt  = s.start_time ? s.start_time.substring(0, 16).replace('T', ' ') : '—';
        const endFmt    = s.end_time   ? s.end_time.substring(0, 16).replace('T', ' ')   : '—';
        const bookedAt  = s.booked_at  ? new Date(s.booked_at).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—';

        // Check if can unbook (H-1)
        const sessionTime = new Date(s.start_time);
        const now = new Date();
        const canUnbook = (sessionTime - now) > 24 * 60 * 60 * 1000;

        html += `<tr>
            <td>${s.id}</td>
            <td><strong>${className}</strong></td>
            <td>${startFmt}<br>s/d ${endFmt}</td>
            <td><span style="color:white; background:#4CAF50; padding:2px 8px; border-radius:4px; font-size:0.85em;">✅ Booked</span></td>
            <td>${bookedAt}</td>
            <td>${canUnbook
                ? `<button onclick="unbookSession(${s.id})" style="color:white; background:#F44336; border:none; padding:6px 14px; border-radius:4px; cursor:pointer;">❌ Unbook</button>`
                : '<small style="color:#999;">Tidak bisa unbook (< H-1)</small>'
            }</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

async function bookSession(sessionId) {
    const msg = document.getElementById('booking-msg');
    msg.textContent = 'Memproses booking...';
    msg.style.color = '#333';

    try {
        const res = await fetch(`/api/sessions/${sessionId}/book`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ teacher_id: currentTeacherId })
        });

        const data = await res.json();
        if (!res.ok) {
            msg.textContent = '❌ ' + (data.error || 'Gagal booking.');
            msg.style.color = 'red';
            return;
        }

        msg.textContent = '✅ ' + data.message;
        msg.style.color = 'green';

        // Reload both lists
        await loadBookingData();
    } catch (e) {
        msg.textContent = '❌ Error: ' + e.message;
        msg.style.color = 'red';
    }
}

async function unbookSession(sessionId) {
    if (!confirm('Yakin ingin membatalkan booking session ini?')) return;

    const msg = document.getElementById('booking-msg');
    msg.textContent = 'Memproses pembatalan...';
    msg.style.color = '#333';

    try {
        const res = await fetch(`/api/sessions/${sessionId}/unbook`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ teacher_id: currentTeacherId })
        });

        const data = await res.json();
        if (!res.ok) {
            msg.textContent = '❌ ' + (data.error || 'Gagal membatalkan booking.');
            msg.style.color = 'red';
            return;
        }

        msg.textContent = '✅ ' + data.message;
        msg.style.color = 'green';

        // Reload both lists
        await loadBookingData();
    } catch (e) {
        msg.textContent = '❌ Error: ' + e.message;
        msg.style.color = 'red';
    }
}

loadTeachers();
</script>

@endsection
