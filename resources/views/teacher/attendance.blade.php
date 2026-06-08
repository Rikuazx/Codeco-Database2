@extends('layout')

@section('content')

<h2>Attendance</h2>
<p>Catat kehadiran student untuk setiap sesi kelas.</p>

<table id="attendance-table" border="1" cellpadding="6" cellspacing="0" style="margin-top:10px; border-collapse:collapse; width:100%;">
    <thead>
        <tr>
            <th>Session ID</th>
            <th>Kelas</th>
            <th>Waktu</th>
            <th>Status Sesi</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody id="session-body">
        <tr><td colspan="5">Memuat sesi...</td></tr>
    </tbody>
</table>

<hr>

<h3>Tandai Kehadiran</h3>

<label>Session ID: <input id="att_session_id" type="number" placeholder="Session ID"></label><br>
<label>Student ID: <input id="att_student_id" type="number" placeholder="Student ID"></label><br>
<label>Status:
    <select id="att_status">
        <option value="present">Present ✅</option>
        <option value="absent">Absent ❌</option>
    </select>
</label><br><br>

<button onclick="markAttendance()">Simpan Kehadiran</button>
<p id="att-msg" style="margin-top:8px;"></p>

<script>
async function loadSessions() {
    const res  = await fetch('/api/sessions');
    const data = await res.json();
    const tbody = document.getElementById('session-body');

    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="5">Belum ada sesi.</td></tr>';
        return;
    }

    let html = '';
    data.forEach(s => {
        const className = s.class?.name ?? '—';
        const start     = s.start_time ? s.start_time.substring(0, 16).replace('T', ' ') : '—';
        const statusColor = s.status === 'completed' ? 'green'
                          : s.status === 'ongoing'   ? 'blue'
                          : 'gray';

        html += `<tr>
            <td>${s.id}</td>
            <td>${className}</td>
            <td>${start}</td>
            <td style="color:${statusColor}; font-weight:bold;">${s.status}</td>
            <td>
                <button onclick="fillSession(${s.id})">Isi Form ↓</button>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

function fillSession(sessionId) {
    document.getElementById('att_session_id').value = sessionId;
    document.getElementById('att_student_id').focus();
}

async function markAttendance() {
    const msg = document.getElementById('att-msg');
    msg.textContent = '';

    const payload = {
        class_session_id: parseInt(document.getElementById('att_session_id').value),
        student_id:       parseInt(document.getElementById('att_student_id').value),
        status:           document.getElementById('att_status').value,
    };

    if (!payload.class_session_id || !payload.student_id) {
        msg.style.color = 'red';
        msg.textContent = 'Session ID dan Student ID wajib diisi.';
        return;
    }

    const res  = await fetch('/api/attendance', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(payload)
    });

    const data = await res.json();

    if (!res.ok) {
        msg.style.color = 'red';
        msg.textContent = 'Error: ' + (data.message || JSON.stringify(data));
        return;
    }

    msg.style.color = 'green';
    msg.textContent = `Kehadiran dicatat: Student ${payload.student_id} → ${payload.status === 'present' ? '✅ Present' : '❌ Absent'}`;

    document.getElementById('att_student_id').value = '';
}

loadSessions();
</script>

@endsection
