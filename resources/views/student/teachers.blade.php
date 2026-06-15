@extends('layout')

@section('content')

<h2>Daftar Teacher</h2>
<p>Pilih kelas yang kamu ambil dan request teacher untuk mengajar semua session di kelas tersebut.</p>

<label>Student ID kamu: <input id="my_student_id" type="number" placeholder="Contoh: 1"></label>
<button onclick="setStudentId()">Set ID</button>
<p id="student-id-label"></p>

<button onclick="loadTeachers()">Load Teachers</button>

<ul id="teachers-list"><li>Klik Load Teachers untuk melihat daftar.</li></ul>

<hr>

<h3>Kirim Request ke Teacher</h3>

<label>Teacher:
    <select id="req-teacher-id">
        <option value="">-- Pilih Teacher --</option>
    </select>
</label><br>

<label>Kelas yang kamu ambil:
    <select id="req-class-id">
        <option value="">-- Set Student ID dulu --</option>
    </select>
</label>
<span id="class-info" style="margin-left:8px; color:#666; font-size:0.9em;"></span>
<br>

<label>Pesan (opsional): <textarea id="req-message" rows="3" cols="40" placeholder="Tulis alasan atau pesan..."></textarea></label><br>
<button onclick="submitRequest()">Kirim Request</button>
<p id="req-msg"></p>

<hr>

<h3>Request Saya</h3>
<button onclick="loadMyRequests()">Load Request Saya</button>
<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:1000px; margin-top:8px;">
    <thead>
        <tr style="background:#eee;">
            <th>Teacher</th>
            <th>Kelas</th>
            <th>Total Session</th>
            <th>Pesan</th>
            <th>Respon Teacher</th>
            <th>Catatan Teacher</th>
            <th>Status Akhir</th>
            <th>Catatan Admin</th>
            <th>Tanggal</th>
        </tr>
    </thead>
    <tbody id="req-body">
        <tr><td colspan="9">Klik "Load Request Saya" untuk melihat.</td></tr>
    </tbody>
</table>

<script>
let currentStudentId = null;
let allTeachersData = [];
let enrolledClasses = [];
let allClassesData = [];

function setStudentId() {
    const sid = parseInt(document.getElementById('my_student_id').value);
    if (!sid || sid < 1) { alert('Student ID tidak valid.'); return; }
    currentStudentId = sid;
    document.getElementById('student-id-label').textContent = `Student ID aktif: ${sid}`;
    loadEnrolledClasses();
    loadMyRequests();
}

async function loadTeachers() {
    const res  = await fetch('/api/teachers');
    const data = await res.json();
    allTeachersData = data;

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
            <button onclick="fillRequestForm(${t.id})">Request Teacher Ini</button>
        </li>`;
    });
    document.getElementById('teachers-list').innerHTML = html;

    // Populate teacher dropdown
    const sel = document.getElementById('req-teacher-id');
    sel.innerHTML = '<option value="">-- Pilih Teacher --</option>';
    data.forEach(t => {
        const name = t.user?.name ?? `Teacher #${t.id}`;
        sel.innerHTML += `<option value="${t.id}">${name} (ID: ${t.id})</option>`;
    });
}

async function loadAllClasses() {
    const res = await fetch('/api/classes');
    allClassesData = await res.json();
}

async function loadEnrolledClasses() {
    if (!currentStudentId) return;

    await loadAllClasses();

    const res = await fetch(`/api/students/${currentStudentId}/enrollments`);
    const data = await res.json();
    enrolledClasses = data.data || data || [];

    const sel = document.getElementById('req-class-id');
    sel.innerHTML = '<option value="">-- Pilih Kelas --</option>';

    enrolledClasses.forEach(e => {
        // Cari info kelas lengkap
        const classInfo = allClassesData.find(c => c.id === e.class_id);
        const className = classInfo?.name ?? e.course?.name ?? `Kelas #${e.class_id}`;
        const totalSessions = classInfo?.total_sessions ?? '?';
        sel.innerHTML += `<option value="${e.class_id}" data-sessions="${totalSessions}">${className} (${totalSessions} session)</option>`;
    });

    sel.onchange = function() {
        const opt = sel.options[sel.selectedIndex];
        const sessions = opt?.dataset?.sessions;
        const info = document.getElementById('class-info');
        if (sessions && sel.value) {
            info.textContent = `→ Teacher akan mengajar ${sessions} session jika di-approve`;
        } else {
            info.textContent = '';
        }
    };
}

function fillRequestForm(teacherId) {
    document.getElementById('req-teacher-id').value = teacherId;
    if (!currentStudentId) {
        alert('Isi Student ID kamu terlebih dahulu (di bagian atas).');
    }
}

async function submitRequest() {
    const msg       = document.getElementById('req-msg');
    const teacherId = document.getElementById('req-teacher-id').value;
    const classId   = document.getElementById('req-class-id').value;
    const message   = document.getElementById('req-message').value.trim();

    if (!currentStudentId) { msg.textContent = 'Set Student ID terlebih dahulu.'; return; }
    if (!teacherId)         { msg.textContent = 'Pilih Teacher.'; return; }
    if (!classId)           { msg.textContent = 'Pilih Kelas yang kamu ambil.'; return; }

    msg.textContent = 'Mengirim...';

    const payload = {
        student_id: currentStudentId,
        teacher_id: parseInt(teacherId),
        class_id: parseInt(classId),
        message: message || null,
    };

    const res  = await fetch('/api/teacher-requests', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(payload)
    });
    const data = await res.json();

    if (!res.ok) { msg.textContent = 'Error: ' + (data.message || 'Gagal mengirim.'); return; }

    msg.textContent = 'Request berhasil dikirim ke teacher!';
    document.getElementById('req-teacher-id').value = '';
    document.getElementById('req-class-id').value = '';
    document.getElementById('class-info').textContent = '';
    document.getElementById('req-message').value = '';
    loadMyRequests();
}

async function loadMyRequests() {
    if (!currentStudentId) return;

    const res  = await fetch(`/api/teacher-requests?student_id=${currentStudentId}`);
    const json = await res.json();
    const data = json.data ?? [];

    if (data.length === 0) {
        document.getElementById('req-body').innerHTML =
            '<tr><td colspan="9">Belum ada request yang dikirim.</td></tr>';
        return;
    }

    let html = '';
    data.forEach(r => {
        const tname = r.teacher?.user?.name ?? `Teacher #${r.teacher_id}`;
        const className = r.class_?.name ?? (r.class_id ? `Kelas #${r.class_id}` : '—');
        const totalSessions = r.class_?.total_sessions ?? '—';
        const date  = r.created_at
            ? new Date(r.created_at).toLocaleDateString('id-ID')
            : '—';

        // Teacher response badge
        let teacherBadge = '';
        if (r.teacher_response === 'approved') {
            teacherBadge = '<span style="color:white; background:#4CAF50; padding:2px 6px; border-radius:4px; font-size:0.85em;">✅ Approved</span>';
        } else if (r.teacher_response === 'rejected') {
            teacherBadge = '<span style="color:white; background:#F44336; padding:2px 6px; border-radius:4px; font-size:0.85em;">❌ Rejected</span>';
        } else {
            teacherBadge = '<span style="color:white; background:#FF9800; padding:2px 6px; border-radius:4px; font-size:0.85em;">⏳ Pending</span>';
        }

        // Status badge
        let statusBadge = '';
        if (r.status === 'processed') {
            statusBadge = '<span style="color:white; background:#4CAF50; padding:2px 6px; border-radius:4px; font-size:0.85em;">✅ Processed</span>';
        } else if (r.status === 'rejected') {
            statusBadge = '<span style="color:white; background:#F44336; padding:2px 6px; border-radius:4px; font-size:0.85em;">❌ Ditolak</span>';
        } else {
            statusBadge = '<span style="color:white; background:#FF9800; padding:2px 6px; border-radius:4px; font-size:0.85em;">⏳ Pending</span>';
        }

        html += `<tr>
            <td>${tname}</td>
            <td><strong>${className}</strong></td>
            <td>${totalSessions}</td>
            <td>${r.message || '—'}</td>
            <td>${teacherBadge}</td>
            <td>${r.teacher_notes || '—'}</td>
            <td>${statusBadge}</td>
            <td>${r.admin_notes || '—'}</td>
            <td>${date}</td>
        </tr>`;
    });
    document.getElementById('req-body').innerHTML = html;
}

loadTeachers();
</script>

@endsection
