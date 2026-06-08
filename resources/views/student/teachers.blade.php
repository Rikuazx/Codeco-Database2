@extends('layout')

@section('content')

<h2>Daftar Teacher</h2>
<p>Lihat profil teacher dan kirim request untuk diajar oleh mereka.</p>

<label>Student ID kamu: <input id="my_student_id" type="number" placeholder="Contoh: 1"></label>
<button onclick="setStudentId()">Set ID</button>
<p id="student-id-label"></p>

<button onclick="loadTeachers()">Load Teachers</button>

<ul id="teachers-list"><li>Klik Load Teachers untuk melihat daftar.</li></ul>

<hr>

<h3>Kirim Request ke Teacher</h3>
<label>Teacher ID: <input id="req-teacher-id" type="number" placeholder="Teacher ID"></label><br>
<label>Pesan (opsional): <textarea id="req-message" rows="3" cols="40" placeholder="Tulis alasan atau pesan..."></textarea></label><br>
<button onclick="submitRequest()">Kirim Request</button>
<p id="req-msg"></p>

<hr>

<h3>Request Saya</h3>
<button onclick="loadMyRequests()">Load Request Saya</button>
<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:700px; margin-top:8px;">
    <thead>
        <tr style="background:#eee;">
            <th>Teacher</th>
            <th>Pesan</th>
            <th>Status</th>
            <th>Catatan Admin</th>
            <th>Tanggal</th>
        </tr>
    </thead>
    <tbody id="req-body">
        <tr><td colspan="5">Klik "Load Request Saya" untuk melihat.</td></tr>
    </tbody>
</table>

<script>
let currentStudentId = null;

function setStudentId() {
    const sid = parseInt(document.getElementById('my_student_id').value);
    if (!sid || sid < 1) { alert('Student ID tidak valid.'); return; }
    currentStudentId = sid;
    document.getElementById('student-id-label').textContent = `Student ID aktif: ${sid}`;
    loadMyRequests();
}

async function loadTeachers() {
    const res  = await fetch('/api/teachers');
    const data = await res.json();

    if (data.length === 0) {
        document.getElementById('teachers-list').innerHTML = '<li>Belum ada teacher terdaftar.</li>';
        return;
    }

    let html = '';
    data.forEach(t => {
        const name = t.user?.name ?? 'Unknown';
        const spec = t.specialization ?? 'Umum';
        const score= t.priority_score ?? '—';
        html += `<li>
            [ID: ${t.id}] ${name} | Spesialisasi: ${spec} | Priority Score: ${score}
            <button onclick="fillRequestForm(${t.id}, '${name.replace(/'/g, "\\'")}')">Request Teacher Ini</button>
        </li>`;
    });
    document.getElementById('teachers-list').innerHTML = html;
}

function fillRequestForm(teacherId, teacherName) {
    document.getElementById('req-teacher-id').value = teacherId;
    document.getElementById('req-message').focus();
    if (!currentStudentId) {
        alert('Isi Student ID kamu terlebih dahulu (di bagian atas).');
    }
}

async function submitRequest() {
    const msg       = document.getElementById('req-msg');
    const teacherId = parseInt(document.getElementById('req-teacher-id').value);
    const message   = document.getElementById('req-message').value.trim();

    if (!currentStudentId) { msg.textContent = 'Set Student ID terlebih dahulu.'; return; }
    if (!teacherId)         { msg.textContent = 'Isi Teacher ID.'; return; }

    msg.textContent = 'Mengirim...';

    const res  = await fetch('/api/teacher-requests', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ student_id: currentStudentId, teacher_id: teacherId, message })
    });
    const data = await res.json();

    if (!res.ok) { msg.textContent = 'Error: ' + (data.message || 'Gagal mengirim.'); return; }

    msg.textContent = 'Request berhasil dikirim!';
    document.getElementById('req-teacher-id').value = '';
    document.getElementById('req-message').value    = '';
    loadMyRequests();
}

async function loadMyRequests() {
    if (!currentStudentId) return;

    const res  = await fetch(`/api/teacher-requests?student_id=${currentStudentId}`);
    const json = await res.json();
    const data = json.data ?? [];

    if (data.length === 0) {
        document.getElementById('req-body').innerHTML =
            '<tr><td colspan="5">Belum ada request yang dikirim.</td></tr>';
        return;
    }

    let html = '';
    data.forEach(r => {
        const tname = r.teacher?.user?.name ?? `Teacher #${r.teacher_id}`;
        const date  = r.created_at
            ? new Date(r.created_at).toLocaleDateString('id-ID')
            : '—';
        html += `<tr>
            <td>${tname}</td>
            <td>${r.message || '—'}</td>
            <td>${r.status}</td>
            <td>${r.admin_notes || '—'}</td>
            <td>${date}</td>
        </tr>`;
    });
    document.getElementById('req-body').innerHTML = html;
}

loadTeachers();
</script>

@endsection
