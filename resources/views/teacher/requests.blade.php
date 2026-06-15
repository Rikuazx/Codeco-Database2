@extends('layout')

@section('content')

<h2>📬 Request dari Student</h2>
<p>Lihat dan respon request dari student yang ingin diajar oleh kamu.</p>

<label>Pilih Teacher:
    <select id="sel-teacher" onchange="loadRequests()">
        <option value="">-- Pilih Teacher --</option>
    </select>
</label>

<h3>Request Masuk (Menunggu Respon)</h3>

<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:1000px; margin-top:10px;">
    <thead>
        <tr style="background:#e3f2fd;">
            <th>ID</th>
            <th>Student</th>
            <th>Kelas</th>
            <th>Pesan</th>
            <th>Total Session</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody id="pending-list">
        <tr><td colspan="7">Pilih teacher terlebih dahulu.</td></tr>
    </tbody>
</table>

<h3>Riwayat Respon</h3>

<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:1000px; margin-top:10px;">
    <thead>
        <tr style="background:#f5f5f5;">
            <th>ID</th>
            <th>Student</th>
            <th>Kelas</th>
            <th>Preferensi</th>
            <th>Respon Kamu</th>
            <th>Catatan</th>
            <th>Status Akhir</th>
            <th>Session</th>
        </tr>
    </thead>
    <tbody id="history-list">
        <tr><td colspan="8">Pilih teacher terlebih dahulu.</td></tr>
    </tbody>
</table>

<span id="req-msg" style="font-weight:bold;"></span>

<!-- Respond Modal -->
<div id="respond-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:999;">
    <div style="background:white; max-width:500px; margin:100px auto; padding:20px; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        <h4 id="respond-title" style="margin:0 0 12px;">Respon Request</h4>
        <input type="hidden" id="respond-request-id">
        <input type="hidden" id="respond-action">

        <label>Pilih Kelas (untuk sesi yang akan dibuat):<br>
            <select id="respond-class" style="width:100%; margin-top:4px;">
                <option value="">-- Pilih Kelas --</option>
            </select>
        </label><br><br>

        <label>Catatan (opsional):<br>
            <textarea id="respond-notes" rows="3" style="width:100%;" placeholder="Tulis catatan untuk student..."></textarea>
        </label><br><br>
        <button id="respond-confirm-btn" onclick="confirmRespond()" style="font-weight:bold; padding:6px 16px; border:none; border-radius:4px; color:white; cursor:pointer;">Konfirmasi</button>
        <button onclick="closeRespondModal()" style="margin-left:4px;">Batal</button>
        <p id="respond-msg" style="margin-top:6px;"></p>
    </div>
</div>

<script>
let allRequests = [];
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

async function loadClasses() {
    const res = await fetch('/api/classes');
    const data = await res.json();
    const sel = document.getElementById('respond-class');
    sel.innerHTML = '<option value="">-- Pilih Kelas --</option>';
    data.forEach(c => {
        sel.innerHTML += `<option value="${c.id}">${c.name}</option>`;
    });
}

async function loadRequests() {
    currentTeacherId = document.getElementById('sel-teacher').value;
    if (!currentTeacherId) return;

    const res = await fetch(`/api/teacher-requests/teacher/${currentTeacherId}`);
    const json = await res.json();
    allRequests = json.data || [];

    renderPending();
    renderHistory();
}

function renderPending() {
    const tbody = document.getElementById('pending-list');
    const pending = allRequests.filter(r => r.teacher_response === 'pending');

    if (pending.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7">Tidak ada request yang menunggu respon.</td></tr>';
        return;
    }

    let html = '';
    pending.forEach(r => {
        const studentName = r.student?.user?.name ?? `Student #${r.student_id}`;
        const className = r.class_?.name ?? (r.class_id ? `Kelas #${r.class_id}` : '—');
        const totalSessions = r.class_?.total_sessions ?? '?';

        html += `<tr>
            <td>${r.id}</td>
            <td>${studentName}</td>
            <td><strong>${className}</strong></td>
            <td style="max-width:200px; word-wrap:break-word;">${r.message || '—'}</td>
            <td><strong>${totalSessions} session</strong></td>
            <td><span style="color:white; background:#FF9800; padding:2px 8px; border-radius:4px; font-size:0.85em;">⏳ Pending</span></td>
            <td>
                <button onclick="openRespondModal(${r.id}, 'approved')" style="color:white; background:#4CAF50; border:none; padding:4px 10px; border-radius:4px; cursor:pointer; margin-bottom:4px;">✅ Approve</button><br>
                <button onclick="openRespondModal(${r.id}, 'rejected')" style="color:white; background:#F44336; border:none; padding:4px 10px; border-radius:4px; cursor:pointer;">❌ Reject</button>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function renderHistory() {
    const tbody = document.getElementById('history-list');
    const history = allRequests.filter(r => r.teacher_response !== 'pending');

    if (history.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8">Belum ada riwayat respon.</td></tr>';
        return;
    }

    let html = '';
    history.forEach(r => {
        const studentName = r.student?.user?.name ?? `Student #${r.student_id}`;
        const className = r.class_?.name ?? (r.class_id ? `Kelas #${r.class_id}` : '—');
        const pref = r.preferred_date || '—';

        const responseBadge = r.teacher_response === 'approved'
            ? '<span style="color:white; background:#4CAF50; padding:2px 8px; border-radius:4px; font-size:0.85em;">✅ Approved</span>'
            : '<span style="color:white; background:#F44336; padding:2px 8px; border-radius:4px; font-size:0.85em;">❌ Rejected</span>';

        const statusBadge = r.status === 'processed'
            ? '<span style="color:white; background:#2196F3; padding:2px 8px; border-radius:4px; font-size:0.85em;">✅ Processed</span>'
            : r.status === 'rejected'
            ? '<span style="color:white; background:#F44336; padding:2px 8px; border-radius:4px; font-size:0.85em;">❌ Rejected</span>'
            : '<span style="color:white; background:#FF9800; padding:2px 8px; border-radius:4px; font-size:0.85em;">⏳ Pending Admin</span>';

        const sessionInfo = r.class_session
            ? `Sesi #${r.class_session.id}<br><small>${r.class_session.class?.name ?? ''}</small>`
            : '—';

        html += `<tr>
            <td>${r.id}</td>
            <td>${studentName}</td>
            <td><strong>${className}</strong></td>
            <td>${pref}</td>
            <td>${responseBadge}</td>
            <td>${r.teacher_notes || '—'}</td>
            <td>${statusBadge}</td>
            <td>${sessionInfo}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function openRespondModal(requestId, action) {
    document.getElementById('respond-request-id').value = requestId;
    document.getElementById('respond-action').value = action;
    document.getElementById('respond-notes').value = '';
    document.getElementById('respond-msg').textContent = '';

    const title = document.getElementById('respond-title');
    const btn = document.getElementById('respond-confirm-btn');

    if (action === 'approved') {
        title.textContent = '✅ Approve Request #' + requestId;
        btn.style.background = '#4CAF50';
        btn.textContent = 'Approve';
    } else {
        title.textContent = '❌ Reject Request #' + requestId;
        btn.style.background = '#F44336';
        btn.textContent = 'Reject';
    }

    document.getElementById('respond-modal').style.display = 'block';
}

function closeRespondModal() {
    document.getElementById('respond-modal').style.display = 'none';
}

async function confirmRespond() {
    const requestId = document.getElementById('respond-request-id').value;
    const action = document.getElementById('respond-action').value;
    const notes = document.getElementById('respond-notes').value;
    const classId = document.getElementById('respond-class').value;
    const msg = document.getElementById('respond-msg');

    msg.textContent = 'Memproses...';
    msg.style.color = '#333';

    try {
        const res = await fetch(`/api/teacher-requests/${requestId}/teacher-respond`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                teacher_id: currentTeacherId,
                teacher_response: action,
                teacher_notes: notes,
                class_id: classId || null,
            })
        });
        const data = await res.json();

        if (!res.ok) {
            msg.textContent = 'Error: ' + (data.error || 'Gagal.');
            msg.style.color = 'red';
            return;
        }

        msg.textContent = data.message;
        msg.style.color = 'green';

        setTimeout(() => {
            closeRespondModal();
            loadRequests();
        }, 800);
    } catch (e) {
        msg.textContent = 'Error: ' + e.message;
        msg.style.color = 'red';
    }
}

loadTeachers();
loadClasses();
</script>

@endsection
