@extends('layout')

@section('content')

<h2>Enrollments</h2>

<button onclick="loadEnrollments()">Load Enrollments</button>

<table id="enrollment-table" border="1" cellpadding="6" cellspacing="0" style="margin-top:12px; border-collapse:collapse;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Kelas</th>
            <th>Harga</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody id="enrollment-body">
        <tr><td colspan="5">Klik "Load Enrollments" untuk memuat data.</td></tr>
    </tbody>
</table>

<hr>

<h3>Enroll Student ke Kelas</h3>

<label>Student ID: <input id="student_id" type="number" placeholder="Student ID"></label><br>
<label>Class ID: &nbsp;&nbsp;<input id="class_id" type="number" placeholder="Class ID"></label><br><br>

<button onclick="enrollStudent()">Enroll</button>
<p id="enroll-msg" style="margin-top:8px;"></p>

<script>
async function loadEnrollments() {
    const res  = await fetch('/api/enrollments');
    const json = await res.json();
    const data = json.data ?? [];

    const tbody = document.getElementById('enrollment-body');

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5">Belum ada enrollment.</td></tr>';
        return;
    }

    let html = '';
    data.forEach(e => {
        const studentName = e.student?.user?.name ?? '—';
        const className   = e.course?.name ?? '—';
        const price       = Number(e.price).toLocaleString('id-ID');
        const statusColor = e.status === 'completed' ? 'green'
                          : e.status === 'active'    ? 'blue'
                          : 'orange';

        html += `<tr>
            <td>${e.id}</td>
            <td>[${e.student_id}] ${studentName}</td>
            <td>[${e.class_id}] ${className}</td>
            <td>Rp ${price}</td>
            <td style="color:${statusColor}; font-weight:bold;">${e.status}</td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

async function enrollStudent() {
    const msg = document.getElementById('enroll-msg');
    msg.textContent = '';

    const payload = {
        student_id: parseInt(document.getElementById('student_id').value),
        class_id:   parseInt(document.getElementById('class_id').value),
    };

    if (!payload.student_id || !payload.class_id) {
        msg.style.color = 'red';
        msg.textContent = 'Student ID dan Class ID wajib diisi.';
        return;
    }

    const res  = await fetch('/api/enroll', {
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
    msg.textContent = 'Enrollment berhasil dibuat! Status: ' + data.data.status;

    document.getElementById('student_id').value = '';
    document.getElementById('class_id').value   = '';
    loadEnrollments();
}

loadEnrollments();
</script>

@endsection
