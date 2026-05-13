@extends('layout')

@section('content')

<h2>My Certificates</h2>
<p>Masukkan Student ID kamu untuk melihat sertifikat yang sudah diterbitkan.</p>

<label>Student ID: <input id="student_id" type="number" placeholder="Contoh: 3"></label>
<button onclick="loadMyCertificates()">Cek Sertifikat</button>

<p id="status-msg" style="color:gray; margin-top:8px;"></p>

<ul id="cert-list" style="margin-top:12px;"></ul>

<script>
async function loadMyCertificates() {
    const studentId = document.getElementById('student_id').value;
    const msg       = document.getElementById('status-msg');
    const list      = document.getElementById('cert-list');

    if (!studentId) {
        msg.style.color = 'red';
        msg.textContent = 'Student ID wajib diisi.';
        return;
    }

    msg.style.color = 'gray';
    msg.textContent = 'Mengambil data...';
    list.innerHTML  = '';

    const res  = await fetch(`/api/students/${studentId}/certificates`);
    const json = await res.json();
    const data = json.data ?? [];

    if (!res.ok) {
        msg.style.color = 'red';
        msg.textContent = 'Error: ' + (json.message || 'Terjadi kesalahan.');
        return;
    }

    if (data.length === 0) {
        msg.style.color = 'orange';
        msg.textContent = 'Belum ada sertifikat untuk student ini.';
        return;
    }

    msg.style.color = 'green';
    msg.textContent = `Ditemukan ${data.length} sertifikat.`;

    let html = '';
    data.forEach(c => {
        html += `
        <li style="margin-bottom: 10px; border: 1px solid #ccc; padding: 10px; border-radius: 6px;">
            <strong>${c.certificate_number}</strong><br>
            Kursus: ${c.course_name}<br>
            Diterbitkan: ${c.issued_at ?? '—'}<br>
            <a href="${c.download_url}" target="_blank">
                ⬇️ Download PDF
            </a>
        </li>`;
    });

    list.innerHTML = html;
}
</script>

@endsection
