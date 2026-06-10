@extends('layout')

@section('content')

<h2>Teacher Availability</h2>

<p><strong>Periode Target:</strong> <span id="target-period">Menghitung periode...</span></p>
<p><strong>Deadline:</strong> Jumat pukul 18:00 WIB pada minggu berjalan.</p>
<p><strong>Status Deadline:</strong> <span id="deadline-status">—</span></p>
<p><strong>Pembatalan Bulan Ini:</strong> <span id="cancel-count">—</span></p>

<label>Pilih Teacher:
    <select id="sel-teacher" onchange="onTeacherChange()">
        <option value="">-- Pilih Teacher --</option>
    </select>
</label>
<button onclick="loadAvailability()">Load Availability</button>

<!-- ============ SANCTION WARNING ============ -->
<div id="sanction-banner" style="display:none; background-color:#fce8e6; color:#c5221f; border:1px solid #fad2cf; padding:10px; border-radius:4px; margin:10px 0;">
    <strong>⚠️ Peringatan Sanksi:</strong> Anda telah membatalkan kelas lebih dari 2 kali bulan ini.
    <ul style="margin:6px 0 0 16px;">
        <li>Prioritas jadwal dikurangi</li>
        <li>Evaluasi kerja sama</li>
        <li>Kemungkinan kerja sama dihentikan</li>
    </ul>
</div>

<!-- ============ AVAILABILITY TABLE ============ -->
<h3>Availability Saat Ini</h3>

<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:900px;">
    <thead>
        <tr style="background:#eee;">
            <th>Tanggal</th>
            <th>Hari</th>
            <th>Minggu</th>
            <th>Status</th>
            <th>Tipe</th>
            <th>Jam</th>
            <th>Locked</th>
            <th>Dipakai Teacher Lain</th>
        </tr>
    </thead>
    <tbody id="avail-list">
        <tr><td colspan="8">Pilih teacher untuk melihat availability.</td></tr>
    </tbody>
</table>

<!-- ============ INPUT FORM ============ -->
<h3>Tambah / Update Availability (min. 2 slot tersedia)</h3>

<p id="session-counter" style="font-weight:bold;">Sesi tersedia: 0 / minimal 2</p>

<div id="slots-container" style="margin-bottom:8px;"></div>
<button onclick="addSlot()">+ Tambah Slot</button>
<button onclick="saveAvailability()" style="font-weight:bold; margin-left:8px;">Simpan Availability</button>
<p id="save-msg" style="margin-top:6px;"></p>

<!-- ============ CANCELLATION SECTION ============ -->
<h3>Pembatalan Kelas</h3>
<p>Kelas yang dapat dibatalkan (minimal H-1 sebelum kelas):</p>

<div id="cancel-sessions-list">
    <p>Pilih teacher untuk melihat sesi.</p>
</div>

<div id="cancel-form" style="display:none; margin-top:10px; padding:10px; border:1px solid #ccc; border-radius:4px; background:#f9f9f9;">
    <h4 style="margin:0 0 8px;">Form Pembatalan</h4>
    <input type="hidden" id="cancel-session-id">
    <label>Alasan (min 10 karakter):<br>
        <textarea id="cancel-reason" rows="3" style="width:100%; max-width:500px;"></textarea>
    </label><br>
    <label>Bukti (PDF/PNG/JPG, max 2MB):<br>
        <input type="file" id="cancel-proof" accept=".pdf,.png,.jpg,.jpeg">
    </label><br><br>
    <button onclick="submitCancellation()" style="color:red; font-weight:bold;">Batalkan Kelas</button>
    <button onclick="closeCancelForm()" style="margin-left:4px;">Batal</button>
    <p id="cancel-msg" style="margin-top:6px;"></p>
</div>

<!-- ============ RULES ============ -->
<h3>
    <button onclick="toggleRules()" style="cursor:pointer; background:none; border:1px solid #ccc; padding:4px 10px; border-radius:4px;">
        📋 Aturan Availability & Pembatalan <span id="rules-arrow">▼</span>
    </button>
</h3>

<div id="rules-content" style="display:none; padding:10px; border:1px solid #eee; border-radius:4px; background:#f9f9f9; font-size:0.92em; line-height:1.7;">
    <ol>
        <li><strong>Wajib mengisi jadwal kosong setiap minggu.</strong><br>
            Setiap hari Jumat sebelum pukul 18:00 WIB, kamu harus mengirimkan jadwal ketersediaan untuk 2 minggu ke depan melalui sistem Codeco.</li>
        <li><strong>Minimal menyediakan 2 sesi.</strong><br>
            Dalam periode 2 minggu, kamu harus menyediakan minimal 2 sesi mengajar di sistem.</li>
        <li><strong>Jadwal minggu pertama bersifat tetap.</strong><br>
            Jika jadwal mengajar untuk minggu depan sudah disepakati, maka jadwal tersebut final dan harus dijalankan. 🔒</li>
        <li><strong>Jadwal minggu kedua masih bisa berubah.</strong><br>
            Jadwal untuk minggu kedua masih sementara (tentatif) dan bisa disesuaikan jika mendapat persetujuan dari pihak pertama (Codeco).</li>
        <li><strong>Aturan mengubah jadwal.</strong><br>
            Perubahan jadwal hanya boleh dilakukan jika:
            <ul>
                <li>Diberitahukan maksimal H-1 sebelum kelas dimulai.</li>
                <li>Memiliki alasan yang jelas dan dapat dibuktikan.</li>
            </ul>
        </li>
        <li><strong>Jika tidak mengisi availability.</strong><br>
            Jika kamu tidak mengirim jadwal ketersediaan sesuai aturan, maka dianggap tidak tersedia untuk mengajar.</li>
        <li><strong>Jika sering membatalkan kelas.</strong><br>
            Apabila kamu membatalkan kelas secara sepihak tanpa alasan yang jelas atau terlalu sering (lebih dari 2 kali dalam 1 bulan), maka bisa dikenakan sanksi berupa:
            <ul>
                <li style="color:red; font-weight:bold;">Prioritas jadwal dikurangi.</li>
                <li style="color:red; font-weight:bold;">Evaluasi kerja sama.</li>
                <li style="color:red; font-weight:bold;">Kemungkinan kerja sama dihentikan.</li>
            </ul>
        </li>
    </ol>
</div>

<script>
// ============================================
// STATE
// ============================================
let periodStart = '', periodEnd = '', week1End = '';
let allOtherAvail = {};
let currentTeacherId = '';
const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// ============================================
// PERIOD CALCULATION
// ============================================
function calcPeriod() {
    const today = new Date();
    const day   = today.getDay();
    const diff  = day === 0 ? 1 : 8 - day;
    const mon   = new Date(today);
    mon.setDate(today.getDate() + diff);

    const sun = new Date(mon);
    sun.setDate(mon.getDate() + 13);

    const w1e = new Date(mon);
    w1e.setDate(mon.getDate() + 6);

    const fmt = d => d.toISOString().slice(0, 10);
    periodStart = fmt(mon);
    periodEnd   = fmt(sun);
    week1End    = fmt(w1e);

    document.getElementById('target-period').textContent = `${periodStart} s/d ${periodEnd}`;

    // Deadline status
    const todayDay = today.getDay();
    const daysToFriday = todayDay <= 5 ? (5 - todayDay) : (5 + 7 - todayDay);
    const friday = new Date(today);
    friday.setDate(today.getDate() + daysToFriday);
    friday.setHours(18, 0, 0, 0);

    const statusEl = document.getElementById('deadline-status');
    const now = new Date();

    // This week's Friday 18:00
    const diffToFri = todayDay <= 5 ? (5 - todayDay) : -(todayDay - 5);
    const thisWeekFriday = new Date(today);
    thisWeekFriday.setDate(today.getDate() + diffToFri);
    thisWeekFriday.setHours(18, 0, 0, 0);

    if (now > thisWeekFriday) {
        statusEl.textContent = '⏰ Lewat Deadline';
        statusEl.style.color = 'red';
    } else {
        const hoursLeft = Math.floor((thisWeekFriday - now) / 3600000);
        if (hoursLeft < 24) {
            statusEl.textContent = `⚠️ ${hoursLeft} jam lagi`;
            statusEl.style.color = 'orange';
        } else {
            const daysLeft = Math.floor(hoursLeft / 24);
            statusEl.textContent = `✅ ${daysLeft} hari lagi`;
            statusEl.style.color = 'green';
        }
    }
}

// ============================================
// LOAD TEACHERS
// ============================================
async function loadTeachers() {
    const res  = await fetch('/api/teachers');
    const data = await res.json();
    const sel  = document.getElementById('sel-teacher');
    sel.innerHTML = '<option value="">-- Pilih Teacher --</option>';
    data.forEach(t => {
        sel.innerHTML += `<option value="${t.id}">${t.user ? t.user.name : 'Teacher #' + t.id} (ID: ${t.id})</option>`;
    });
}

// ============================================
// ON TEACHER CHANGE
// ============================================
function onTeacherChange() {
    const tid = document.getElementById('sel-teacher').value;
    currentTeacherId = tid;
    if (tid) {
        loadAvailability();
        loadCancellationStats();
        loadTeacherSessions();
    }
}

// ============================================
// LOAD AVAILABILITY
// ============================================
async function loadAvailability() {
    const tid = currentTeacherId;
    if (!tid) return;

    const res1 = await fetch(`/api/teacher-availability/${tid}`);
    const own  = await res1.json();

    const res2   = await fetch('/api/teacher-availability');
    const allRes = (await res2.json()).data ?? [];

    allOtherAvail = {};
    allRes.forEach(a => {
        if (String(a.teacher_id) === String(tid)) return;
        if (!allOtherAvail[a.date]) allOtherAvail[a.date] = [];
        allOtherAvail[a.date].push(a);
    });

    const tbody = document.getElementById('avail-list');
    if (own.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8">Belum ada availability terdaftar.</td></tr>';
        return;
    }

    let html = '';
    own.forEach(a => {
        const date = new Date(a.date + 'T00:00:00');
        const dayName = DAYS[date.getDay()];
        const weekNum = a.week_number || (a.date <= week1End ? 1 : 2);
        const weekStatus = a.week_status || (weekNum === 1 ? 'confirmed' : 'tentative');
        const jam = a.type === 'time_range' ? `${a.start_time ?? ''} - ${a.end_time ?? ''}` : '—';
        const others = allOtherAvail[a.date] ?? [];
        const conflict = others.length > 0
            ? others.map(o => o.teacher_name).join(', ')
            : '—';
        const locked = a.is_locked ? '🔒 Ya' : '—';

        const weekLabel = weekNum === 1
            ? '<span style="color:green; font-weight:bold;">Minggu 1 (TETAP)</span>'
            : '<span style="color:orange; font-weight:bold;">Minggu 2 (TENTATIF)</span>';

        const typeLabel = a.type === 'full_day'
            ? '<span style="color:green;">Full Day</span>'
            : a.type === 'time_range'
            ? '<span style="color:blue;">Time Range</span>'
            : '<span style="color:red;">Unavailable</span>';

        html += `<tr>
            <td>${a.date}</td>
            <td>${dayName}</td>
            <td>${weekLabel}</td>
            <td>${weekStatus}</td>
            <td>${typeLabel}</td>
            <td>${jam}</td>
            <td>${locked}</td>
            <td>${conflict}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

// ============================================
// SLOT FORM
// ============================================
function addSlot() {
    const container = document.getElementById('slots-container');
    const id = 'slot-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.style.marginBottom = '4px';
    div.innerHTML = `
        <input type="date" class="slot-date" min="${periodStart}" max="${periodEnd}" onchange="checkSlotConflict(this.parentNode); updateCounter();">
        <select class="slot-type" onchange="toggleTime(this); checkSlotConflict(this.parentNode); updateCounter();">
            <option value="full_day">Full Day</option>
            <option value="time_range">Time Range</option>
            <option value="unavailable">Unavailable</option>
        </select>
        <input type="time" class="slot-start" style="display:none;" onchange="checkSlotConflict(this.parentNode)">
        s/d
        <input type="time" class="slot-end" style="display:none;" onchange="checkSlotConflict(this.parentNode)">
        <button onclick="document.getElementById('${id}').remove(); updateCounter();">Hapus</button>
        <span class="conflict-warn" style="color:red; font-weight:bold; margin-left:8px;"></span>
    `;
    container.appendChild(div);
    updateCounter();
}

function toggleTime(sel) {
    const row   = sel.parentNode;
    const show  = sel.value === 'time_range';
    row.querySelector('.slot-start').style.display = show ? 'inline' : 'none';
    row.querySelector('.slot-end').style.display   = show ? 'inline' : 'none';
}

function checkSlotConflict(row) {
    const warn  = row.querySelector('.conflict-warn');
    const date  = row.querySelector('.slot-date').value;
    const type  = row.querySelector('.slot-type').value;
    const start = row.querySelector('.slot-start').value;
    const end   = row.querySelector('.slot-end').value;

    warn.textContent = '';
    if (!date || type === 'unavailable') return;

    const others = allOtherAvail[date] ?? [];
    if (others.length === 0) return;

    let conflicting = [];
    for (const o of others) {
        if (type === 'full_day') {
            conflicting.push(o.teacher_name);
        } else if (type === 'time_range' && start && end) {
            if (o.type === 'full_day') {
                conflicting.push(o.teacher_name);
            } else if (o.type === 'time_range' && o.start_time < end && o.end_time > start) {
                conflicting.push(o.teacher_name);
            }
        }
    }

    if (conflicting.length > 0) {
        const names = [...new Set(conflicting)].join(', ');
        warn.textContent = `⚠ sudah dipilih: ${names}`;
    }
}

function updateCounter() {
    const rows = document.querySelectorAll('#slots-container > div');
    let count = 0;
    rows.forEach(row => {
        const type = row.querySelector('.slot-type').value;
        if (type === 'full_day' || type === 'time_range') count++;
    });

    const el = document.getElementById('session-counter');
    el.textContent = `Sesi tersedia: ${count} / minimal 2`;
    el.style.color = count >= 2 ? 'green' : 'red';
}

// ============================================
// SAVE AVAILABILITY
// ============================================
async function saveAvailability() {
    const tid  = currentTeacherId;
    const msg  = document.getElementById('save-msg');

    if (!tid) { msg.textContent = 'Pilih teacher terlebih dahulu.'; msg.style.color = 'red'; return; }

    const rows = document.querySelectorAll('#slots-container > div');
    if (rows.length === 0) { msg.textContent = 'Tambah minimal 1 slot terlebih dahulu.'; msg.style.color = 'red'; return; }

    const availabilities = [];
    for (let row of rows) {
        const date       = row.querySelector('.slot-date').value;
        const type       = row.querySelector('.slot-type').value;
        const start_time = row.querySelector('.slot-start').value;
        const end_time   = row.querySelector('.slot-end').value;
        if (!date) { msg.textContent = 'Semua baris harus memiliki tanggal.'; msg.style.color = 'red'; return; }
        availabilities.push({ date, type, start_time, end_time });
    }

    msg.textContent = 'Menyimpan...';
    msg.style.color = '#333';

    const res  = await fetch('/api/teacher-availability', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ teacher_id: tid, availabilities })
    });
    const data = await res.json();

    if (!res.ok) {
        msg.style.color = 'red';
        msg.textContent = 'Error: ' + (data.error || 'Gagal menyimpan.');
        return;
    }
    msg.style.color = 'green';
    msg.textContent = 'Berhasil disimpan! Status: ' + data.message;
    document.getElementById('slots-container').innerHTML = '';
    updateCounter();
    loadAvailability();
}

// ============================================
// CANCELLATION
// ============================================
async function loadCancellationStats() {
    const tid = currentTeacherId;
    if (!tid) return;

    try {
        const res = await fetch(`/api/teachers/${tid}/sanction-status`);
        const data = await res.json();

        const countEl = document.getElementById('cancel-count');
        countEl.textContent = `${data.cancellation_count} / 2 maks`;
        countEl.style.color = data.exceeded ? 'red' : (data.cancellation_count >= 2 ? 'orange' : 'green');

        const banner = document.getElementById('sanction-banner');
        banner.style.display = data.exceeded ? 'block' : 'none';
    } catch (e) {
        document.getElementById('cancel-count').textContent = '0 / 2 maks';
    }
}

async function loadTeacherSessions() {
    const tid = currentTeacherId;
    if (!tid) return;

    try {
        const res = await fetch(`/api/teachers/${tid}/sessions`);
        const sessions = await res.json();

        const container = document.getElementById('cancel-sessions-list');
        const upcoming = (sessions.data || sessions).filter(s => {
            return s.status === 'scheduled' && new Date(s.start_time) > new Date(Date.now() + 86400000);
        });

        if (upcoming.length === 0) {
            container.innerHTML = '<p>Tidak ada sesi yang bisa dibatalkan (harus H-1 sebelum kelas).</p>';
            return;
        }

        let html = '<ul>';
        upcoming.forEach(s => {
            const dt = new Date(s.start_time);
            const dateStr = dt.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            const timeStr = dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            html += `<li>
                Sesi #${s.id} — ${dateStr}, ${timeStr}
                <button onclick="openCancelForm(${s.id})" style="color:red;">Batalkan</button>
            </li>`;
        });
        html += '</ul>';
        container.innerHTML = html;
    } catch (e) {
        document.getElementById('cancel-sessions-list').innerHTML = '<p>Gagal memuat sesi.</p>';
    }
}

function openCancelForm(sessionId) {
    document.getElementById('cancel-session-id').value = sessionId;
    document.getElementById('cancel-reason').value = '';
    document.getElementById('cancel-proof').value = '';
    document.getElementById('cancel-form').style.display = 'block';
    document.getElementById('cancel-msg').textContent = '';
}

function closeCancelForm() {
    document.getElementById('cancel-form').style.display = 'none';
}

async function submitCancellation() {
    const tid       = currentTeacherId;
    const sessionId = document.getElementById('cancel-session-id').value;
    const reason    = document.getElementById('cancel-reason').value;
    const proofFile = document.getElementById('cancel-proof').files[0];
    const msg       = document.getElementById('cancel-msg');

    if (!reason || reason.length < 10) {
        msg.textContent = 'Alasan harus minimal 10 karakter.';
        msg.style.color = 'red';
        return;
    }
    if (!proofFile) {
        msg.textContent = 'Bukti file harus diunggah.';
        msg.style.color = 'red';
        return;
    }

    const formData = new FormData();
    formData.append('teacher_id', tid);
    formData.append('class_session_id', sessionId);
    formData.append('reason', reason);
    formData.append('proof_file', proofFile);

    msg.textContent = 'Mengirim pembatalan...';
    msg.style.color = '#333';

    const res = await fetch('/api/cancellation-logs', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();

    if (!res.ok) {
        msg.textContent = 'Error: ' + (data.error || 'Gagal membatalkan.');
        msg.style.color = 'red';
        return;
    }

    let successMsg = data.message;
    if (data.warning) {
        successMsg += ' ⚠️ ' + data.warning;
    }
    msg.textContent = successMsg;
    msg.style.color = data.sanction_applied ? 'red' : 'green';

    closeCancelForm();
    loadTeacherSessions();
    loadCancellationStats();
}

// ============================================
// RULES TOGGLE
// ============================================
function toggleRules() {
    const content = document.getElementById('rules-content');
    const arrow = document.getElementById('rules-arrow');
    const isOpen = content.style.display === 'block';
    content.style.display = isOpen ? 'none' : 'block';
    arrow.textContent = isOpen ? '▼' : '▲';
}

// ============================================
// INIT
// ============================================
calcPeriod();
loadTeachers();
</script>

@endsection
