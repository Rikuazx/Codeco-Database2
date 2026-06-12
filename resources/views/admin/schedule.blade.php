@extends('layout')

@section('content')

<h2>Schedule</h2>

<button onclick="loadAllData()">Load Schedule</button>

<h3>Availability Teacher</h3>

<label>Filter Teacher:
    <select id="filter-teacher" onchange="renderAvailabilityList()">
        <option value="">-- Semua Teacher --</option>
    </select>
</label>

<p>Periode: <span id="period-info">Menghitung...</span></p>

<ul id="avail-list"></ul>

<h3>Buat Jadwal Session</h3>

<input id="session_id" hidden>
<select id="sched-teacher" onchange="loadTeacherSlots()">
    <option value="">-- Pilih Teacher --</option>
</select>
<select id="sched-class">
    <option value="">-- Pilih Kelas --</option>
</select>
<select id="sched-date" onchange="fillTimeFromSlot()">
    <option value="">-- Pilih Tanggal --</option>
</select>
<span id="sched-slot-info"></span>
<br><br>
<input type="datetime-local" id="sched-start" placeholder="Start Time">
<input type="datetime-local" id="sched-end" placeholder="End Time">

<button onclick="createSession()">Save</button>
<span id="sched-msg"></span>

<h3>Jadwal Session</h3>

<ul id="sessions-list"></ul>

<!-- ============ RESCHEDULE REQUESTS ============ -->
<h3>📋 Reschedule Requests (Minggu 2 — Tentatif)</h3>
<p>Daftar request reschedule dari teacher. Hanya request yang diajukan maks H-1 sebelum kelas yang valid.</p>

<label>Filter Status:
    <select id="filter-reschedule-status" onchange="loadRescheduleRequests()">
        <option value="">-- Semua --</option>
        <option value="pending" selected>Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
    </select>
</label>

<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:1100px; margin-top:10px;">
    <thead>
        <tr style="background:#e3f2fd;">
            <th>ID</th>
            <th>Teacher</th>
            <th>Sesi / Kelas</th>
            <th>Jadwal Saat Ini</th>
            <th>Alasan</th>
            <th>Bukti</th>
            <th>Jadwal Baru</th>
            <th>Status</th>
            <th>Diajukan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody id="reschedule-requests-list">
        <tr><td colspan="10">Klik "Load Schedule" untuk memuat data.</td></tr>
    </tbody>
</table>

<!-- Admin Notes Modal -->
<div id="admin-notes-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:999;">
    <div style="background:white; max-width:500px; margin:100px auto; padding:20px; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        <h4 id="modal-title" style="margin:0 0 12px;">Konfirmasi</h4>
        <input type="hidden" id="modal-request-id">
        <input type="hidden" id="modal-action">
        <label>Catatan Admin (opsional):<br>
            <textarea id="modal-admin-notes" rows="3" style="width:100%;" placeholder="Tulis catatan untuk teacher..."></textarea>
        </label><br><br>
        <button id="modal-confirm-btn" onclick="confirmModalAction()" style="font-weight:bold; padding:6px 16px; border:none; border-radius:4px; color:white; cursor:pointer;">Konfirmasi</button>
        <button onclick="closeModal()" style="margin-left:4px;">Batal</button>
        <p id="modal-msg" style="margin-top:6px;"></p>
    </div>
</div>

<script>
let allAvailabilities = [];
let allTeachers = [];
let allClasses = [];
let allSessions = [];
let teacherSlots = [];
let periodStart = '', periodEnd = '';

function calcPeriod() {
    const today = new Date();
    const day   = today.getDay();
    const diff  = day === 0 ? 1 : 8 - day;
    const mon   = new Date(today);
    mon.setDate(today.getDate() + diff);

    const sun = new Date(mon);
    sun.setDate(mon.getDate() + 13);

    const fmt = d => d.toISOString().slice(0, 10);
    periodStart = fmt(mon);
    periodEnd   = fmt(sun);

    document.getElementById('period-info').textContent = `${periodStart} s/d ${periodEnd}`;
}

async function loadAllData() {
    await Promise.all([
        loadTeachers(),
        loadClasses(),
        loadAvailabilities(),
        loadSessions(),
        loadRescheduleRequests(),
    ]);
    renderAvailabilityList();
}

async function loadTeachers() {
    const res = await fetch('/api/teachers');
    allTeachers = await res.json();

    const filterSel = document.getElementById('filter-teacher');
    const schedSel  = document.getElementById('sched-teacher');

    filterSel.innerHTML = '<option value="">-- Semua Teacher --</option>';
    schedSel.innerHTML  = '<option value="">-- Pilih Teacher --</option>';

    allTeachers.forEach(t => {
        const name = t.user ? t.user.name : `Teacher #${t.id}`;
        filterSel.innerHTML += `<option value="${t.id}">${name}</option>`;
        schedSel.innerHTML  += `<option value="${t.id}">${name}</option>`;
    });
}

async function loadClasses() {
    const res = await fetch('/api/classes');
    allClasses = await res.json();
    const sel = document.getElementById('sched-class');
    sel.innerHTML = '<option value="">-- Pilih Kelas --</option>';
    allClasses.forEach(c => {
        sel.innerHTML += `<option value="${c.id}">${c.name} (${c.total_sessions} sesi)</option>`;
    });
}

async function loadAvailabilities() {
    const res = await fetch('/api/teacher-availability');
    const data = await res.json();
    allAvailabilities = data.data ?? [];
}

async function loadSessions() {
    const res = await fetch('/api/sessions');
    allSessions = await res.json();
    renderSessionsList();
}

function renderAvailabilityList() {
    const ul = document.getElementById('avail-list');
    const filterTeacherId = document.getElementById('filter-teacher').value;

    let filtered = allAvailabilities;
    if (filterTeacherId) {
        filtered = filtered.filter(a => String(a.teacher_id) === filterTeacherId);
    }

    if (filtered.length === 0) {
        ul.innerHTML = '<li>Belum ada availability yang di-submit teacher.</li>';
        return;
    }

    let html = '';
    filtered.sort((a, b) => a.date.localeCompare(b.date));
    filtered.forEach(a => {
        const jam = a.type === 'time_range' ? `${a.start_time} - ${a.end_time}` : (a.type === 'full_day' ? 'Sepanjang hari' : '-');
        html += `
        <li>
            ${a.teacher_name} | ${a.date} | ${a.type} | ${jam}
            <button onclick="quickAssign('${a.teacher_id}', '${a.date}', '${a.type}', '${a.start_time}', '${a.end_time}')">Buat Jadwal</button>
        </li>`;
    });

    ul.innerHTML = html;
}

function quickAssign(teacherId, date, type, startTime, endTime) {
    document.getElementById('sched-teacher').value = teacherId;
    loadTeacherSlots();

    setTimeout(() => {
        const dateSel = document.getElementById('sched-date');
        dateSel.value = date;
        fillTimeFromSlot();

        const startInput = document.getElementById('sched-start');
        const endInput   = document.getElementById('sched-end');

        if (type === 'full_day') {
            startInput.value = `${date}T09:00`;
            endInput.value   = `${date}T11:00`;
        } else if (type === 'time_range' && startTime && endTime) {
            startInput.value = `${date}T${startTime.substring(0, 5)}`;
            endInput.value   = `${date}T${endTime.substring(0, 5)}`;
        }
    }, 200);
}

function loadTeacherSlots() {
    const tid = document.getElementById('sched-teacher').value;
    const dateSel = document.getElementById('sched-date');
    dateSel.innerHTML = '<option value="">-- Pilih Tanggal --</option>';
    document.getElementById('sched-slot-info').textContent = '';

    if (!tid) return;

    teacherSlots = allAvailabilities.filter(a => String(a.teacher_id) === tid);

    if (teacherSlots.length === 0) {
        dateSel.innerHTML = '<option value="">Tidak ada availability</option>';
        return;
    }

    const uniqueDates = [...new Set(teacherSlots.map(s => s.date))].sort();
    uniqueDates.forEach(d => {
        const slot = teacherSlots.find(s => s.date === d);
        const label = slot.type === 'full_day'
            ? `${d} (Full Day)`
            : `${d} (${slot.start_time} - ${slot.end_time})`;
        dateSel.innerHTML += `<option value="${d}">${label}</option>`;
    });
}

function fillTimeFromSlot() {
    const date = document.getElementById('sched-date').value;
    const info = document.getElementById('sched-slot-info');
    if (!date) { info.textContent = ''; return; }
    const slot = teacherSlots.find(s => s.date === date);
    if (!slot) { info.textContent = 'Tidak tersedia'; return; }
    if (slot.type === 'full_day') {
        info.textContent = 'Full Day — bebas atur jam';
    } else {
        info.textContent = `Tersedia: ${slot.start_time} - ${slot.end_time}`;
    }
}

async function createSession() {
    const msg       = document.getElementById('sched-msg');
    const teacherId = document.getElementById('sched-teacher').value;
    const classId   = document.getElementById('sched-class').value;
    const startTime = document.getElementById('sched-start').value;
    const endTime   = document.getElementById('sched-end').value;

    if (!teacherId || !classId || !startTime || !endTime) {
        msg.textContent = 'Semua field harus diisi.';
        return;
    }

    msg.textContent = 'Menyimpan...';

    try {
        const res = await fetch('/api/sessions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                class_id:   classId,
                teacher_id: teacherId,
                start_time: startTime,
                end_time:   endTime,
                status:     'scheduled',
            })
        });

        const data = await res.json();
        if (!res.ok) {
            msg.textContent = 'Error: ' + (data.error || data.message || 'Gagal membuat session.');
            return;
        }

        msg.textContent = 'Session berhasil dibuat!';
        loadSessions();

    } catch (err) {
        msg.textContent = 'Error: ' + err.message;
    }
}

function renderSessionsList() {
    const ul = document.getElementById('sessions-list');
    if (allSessions.length === 0) {
        ul.innerHTML = '<li>Belum ada session.</li>';
        return;
    }

    let html = '';
    allSessions.forEach(s => {
        const teacherName = s.teacher?.user?.name
            ?? (s.teacher ? `Teacher #${s.teacher.id}` : 'None');
        const className = s.class?.name ?? `Class #${s.class_id}`;

        html += `
        <li>
            Session ${s.id} | ${className} | Teacher: ${teacherName} | ${s.start_time} - ${s.end_time} | ${s.status}
            ${!s.teacher_id ? `<button onclick="autoAssign(${s.id})">Auto Assign</button>` : ''}
            <button onclick="deleteSessionAdmin(${s.id})">Delete</button>
        </li>`;
    });

    ul.innerHTML = html;
}

async function autoAssign(sessionId) {
    const res = await fetch(`/api/sessions/${sessionId}/auto-assign`, { method: 'POST' });
    const data = await res.json();
    if (!res.ok) {
        alert('Gagal: ' + (data.error || 'Tidak ada teacher tersedia.'));
        return;
    }
    alert('Teacher berhasil di-assign!');
    loadSessions();
}

async function deleteSessionAdmin(sessionId) {
    if (!confirm('Yakin ingin menghapus session ini?')) return;
    await fetch(`/api/sessions/${sessionId}`, { method: 'DELETE' });
    loadSessions();
}

// ============================================
// RESCHEDULE REQUESTS MANAGEMENT
// ============================================
async function loadRescheduleRequests() {
    const statusFilter = document.getElementById('filter-reschedule-status').value;
    let url = '/api/schedule-change-requests';
    if (statusFilter) url += `?status=${statusFilter}`;

    try {
        const res = await fetch(url);
        const json = await res.json();
        const requests = json.data || [];

        const tbody = document.getElementById('reschedule-requests-list');

        if (requests.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10">Tidak ada reschedule request.</td></tr>';
            return;
        }

        let html = '';
        requests.forEach(r => {
            const statusBadge = r.status === 'pending'
                ? '<span style="color:white; background:#FF9800; padding:2px 8px; border-radius:4px; font-size:0.85em;">⏳ Pending</span>'
                : r.status === 'approved'
                ? '<span style="color:white; background:#4CAF50; padding:2px 8px; border-radius:4px; font-size:0.85em;">✅ Approved</span>'
                : '<span style="color:white; background:#F44336; padding:2px 8px; border-radius:4px; font-size:0.85em;">❌ Rejected</span>';

            const proofLink = r.proof_file
                ? `<a href="/storage/${r.proof_file}" target="_blank">📎 Lihat Bukti</a>`
                : '—';

            let newSchedule = '—';
            if (r.new_date) newSchedule = r.new_date;
            if (r.new_start_time) newSchedule += `<br>${r.new_start_time}`;
            if (r.new_end_time) newSchedule += ` s/d ${r.new_end_time}`;

            const currentSchedule = r.session_start_time
                ? `${r.session_start_time}<br>s/d ${r.session_end_time}`
                : '—';

            const requestedAt = r.requested_at
                ? new Date(r.requested_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                : '—';

            // H-1 check indicator
            let h1Indicator = '';
            if (r.session_start_time && r.requested_at) {
                const sessionDate = new Date(r.session_start_time);
                const requestDate = new Date(r.requested_at);
                const diffHours = (sessionDate - requestDate) / 3600000;
                h1Indicator = diffHours >= 24
                    ? '<br><small style="color:green;">✅ H-1 terpenuhi</small>'
                    : '<br><small style="color:red;">⚠️ Kurang dari H-1</small>';
            }

            let actionButtons = '';
            if (r.status === 'pending') {
                actionButtons = `
                    <button onclick="openAdminModal(${r.id}, 'approve')" style="color:white; background:#4CAF50; border:none; padding:4px 10px; border-radius:4px; cursor:pointer; margin-bottom:4px;">✅ Approve</button><br>
                    <button onclick="openAdminModal(${r.id}, 'reject')" style="color:white; background:#F44336; border:none; padding:4px 10px; border-radius:4px; cursor:pointer;">❌ Reject</button>
                `;
            } else {
                actionButtons = `<small>${r.admin_notes || '—'}</small>`;
            }

            html += `<tr>
                <td>${r.id}</td>
                <td>${r.teacher_name}</td>
                <td>Sesi #${r.class_session_id}<br><small>${r.class_name}</small></td>
                <td>${currentSchedule}${h1Indicator}</td>
                <td style="max-width:200px; word-wrap:break-word;">${r.reason}</td>
                <td>${proofLink}</td>
                <td>${newSchedule}</td>
                <td>${statusBadge}</td>
                <td>${requestedAt}</td>
                <td>${actionButtons}</td>
            </tr>`;
        });
        tbody.innerHTML = html;
    } catch (e) {
        document.getElementById('reschedule-requests-list').innerHTML = '<tr><td colspan="10">Gagal memuat data.</td></tr>';
    }
}

function openAdminModal(requestId, action) {
    document.getElementById('modal-request-id').value = requestId;
    document.getElementById('modal-action').value = action;
    document.getElementById('modal-admin-notes').value = '';
    document.getElementById('modal-msg').textContent = '';

    const title = document.getElementById('modal-title');
    const btn = document.getElementById('modal-confirm-btn');

    if (action === 'approve') {
        title.textContent = '✅ Approve Reschedule Request #' + requestId;
        btn.style.background = '#4CAF50';
        btn.textContent = 'Approve';
    } else {
        title.textContent = '❌ Reject Reschedule Request #' + requestId;
        btn.style.background = '#F44336';
        btn.textContent = 'Reject';
    }

    document.getElementById('admin-notes-modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('admin-notes-modal').style.display = 'none';
}

async function confirmModalAction() {
    const requestId = document.getElementById('modal-request-id').value;
    const action = document.getElementById('modal-action').value;
    const adminNotes = document.getElementById('modal-admin-notes').value;
    const msg = document.getElementById('modal-msg');

    msg.textContent = 'Memproses...';
    msg.style.color = '#333';

    try {
        const res = await fetch(`/api/schedule-change-requests/${requestId}/${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_notes: adminNotes })
        });
        const data = await res.json();

        if (!res.ok) {
            msg.textContent = 'Error: ' + (data.error || 'Gagal memproses.');
            msg.style.color = 'red';
            return;
        }

        msg.textContent = data.message;
        msg.style.color = 'green';

        setTimeout(() => {
            closeModal();
            loadRescheduleRequests();
            loadSessions();
        }, 800);
    } catch (e) {
        msg.textContent = 'Error: ' + e.message;
        msg.style.color = 'red';
    }
}

calcPeriod();
loadAllData();
</script>

@endsection
