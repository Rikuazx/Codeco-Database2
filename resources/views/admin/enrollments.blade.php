@extends('layout')

@section('content')

<h2>Enrollments</h2>

<h3>Enroll Student ke Kelas</h3>

<label>Pilih Student:
    <select id="sel-student">
        <option value="">-- Memuat... --</option>
    </select>
</label><br>
<label>Pilih Kelas:
    <select id="sel-class">
        <option value="">-- Memuat... --</option>
    </select>
</label><br><br>
<button onclick="enrollStudent()">Enroll</button>
<p id="enroll-msg"></p>

<hr>

<h3>Semua Enrollment</h3>
<button onclick="loadAllEnrollments()">Load Enrollments</button>

<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:800px; margin-top:8px;">
    <thead>
        <tr style="background:#eee;">
            <th>ID</th>
            <th>Student</th>
            <th>Kelas</th>
            <th>Harga</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody id="enrollment-body">
        <tr><td colspan="6">Klik Load Enrollments untuk melihat data.</td></tr>
    </tbody>
</table>

<hr>

<h3>Ringkasan Kelas per Student</h3>
<button onclick="loadStudentSummary()">Load Ringkasan</button>

<ul id="student-summary-list">
    <li>Klik Load Ringkasan untuk melihat data.</li>
</ul>

<script>
let studentsData = [];
let classesData  = [];

async function initDropdowns() {
    const [sRes, cRes] = await Promise.all([
        fetch('/api/students'),
        fetch('/api/classes'),
    ]);
    studentsData = await sRes.json();
    classesData  = await cRes.json();

    const sSel = document.getElementById('sel-student');
    const cSel = document.getElementById('sel-class');

    sSel.innerHTML = '<option value="">-- Pilih Student --</option>';
    studentsData.forEach(s => {
        const name = s.user?.name ?? 'Unknown';
        sSel.innerHTML += `<option value="${s.id}">[#${s.id}] ${name} (${s.status})</option>`;
    });

    cSel.innerHTML = '<option value="">-- Pilih Kelas --</option>';
    classesData.forEach(c => {
        const price = Number(c.price).toLocaleString('id-ID');
        cSel.innerHTML += `<option value="${c.id}">[#${c.id}] ${c.name} — Rp ${price}</option>`;
    });
}

async function enrollStudent() {
    const msg       = document.getElementById('enroll-msg');
    const studentId = parseInt(document.getElementById('sel-student').value);
    const classId   = parseInt(document.getElementById('sel-class').value);

    msg.textContent = '';

    if (!studentId || !classId) {
        msg.textContent = 'Pilih student dan kelas terlebih dahulu.';
        return;
    }

    const res  = await fetch('/api/enroll', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ student_id: studentId, class_id: classId }),
    });
    const data = await res.json();

    if (!res.ok) {
        msg.textContent = 'Error: ' + (data.message || JSON.stringify(data));
        return;
    }

    const sName = studentsData.find(s => s.id === studentId)?.user?.name ?? `Student #${studentId}`;
    const cName = classesData.find(c => c.id === classId)?.name ?? `Kelas #${classId}`;
    msg.textContent = `${sName} berhasil didaftarkan ke kelas "${cName}"!`;

    document.getElementById('sel-class').value = '';
    loadAllEnrollments();
    loadStudentSummary();
}

async function loadAllEnrollments() {
    const tbody = document.getElementById('enrollment-body');
    tbody.innerHTML = '<tr><td colspan="6">Memuat...</td></tr>';

    const res  = await fetch('/api/enrollments');
    const json = await res.json();
    const data = json.data ?? [];

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">Belum ada enrollment.</td></tr>';
        return;
    }

    let html = '';
    data.forEach(e => {
        const studentName = e.student?.user?.name ?? '—';
        const className   = e.course?.name ?? '—';
        const price       = Number(e.price).toLocaleString('id-ID');
        html += `<tr>
            <td>${e.id}</td>
            <td>#${e.student_id} ${studentName}</td>
            <td>#${e.class_id} ${className}</td>
            <td>Rp ${price}</td>
            <td>${e.status}</td>
            <td>
                <button onclick="deleteEnrollment(${e.id})">Hapus</button>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

async function deleteEnrollment(id) {
    if (!confirm(`Hapus enrollment #${id}?`)) return;

    const res = await fetch(`/api/enrollments/${id}`, { method: 'DELETE' });
    if (!res.ok) {
        alert('Gagal menghapus enrollment.');
        return;
    }
    loadAllEnrollments();
    loadStudentSummary();
}

async function loadStudentSummary() {
    const list = document.getElementById('student-summary-list');
    list.innerHTML = '<li>Memuat...</li>';

    const [sRes, eRes] = await Promise.all([
        fetch('/api/students'),
        fetch('/api/enrollments'),
    ]);
    const students          = await sRes.json();
    const { data: enrollments } = await eRes.json();

    if (students.length === 0) {
        list.innerHTML = '<li>Belum ada student terdaftar.</li>';
        return;
    }

    const byStudent = {};
    enrollments.forEach(e => {
        if (!byStudent[e.student_id]) byStudent[e.student_id] = [];
        byStudent[e.student_id].push(e);
    });

    let html = '';
    students.forEach(s => {
        const name    = s.user?.name ?? 'Unknown';
        const enrList = byStudent[s.id] ?? [];

        if (enrList.length === 0) {
            html += `<li>#${s.id} ${name} — Belum terdaftar di kelas manapun.</li>`;
        } else {
            const classes = enrList.map(e => {
                const cn = e.course?.name ?? `Kelas #${e.class_id}`;
                return `${cn} (${e.status})`;
            }).join(', ');
            html += `<li>#${s.id} ${name} — ${enrList.length} kelas: ${classes}</li>`;
        }
    });

    list.innerHTML = html;
}

initDropdowns();
loadAllEnrollments();
loadStudentSummary();
</script>

@endsection
