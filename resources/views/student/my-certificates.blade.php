@extends('layout')

@section('content')

<h2>My Certificates (Student)</h2>
<p>Sertifikat untuk kelas yang telah diselesaikan.</p>

<label>Student ID: <input id="student_id" type="number" placeholder="Masukkan Student ID"></label>
<button onclick="loadCertificates()">Load Sertifikat</button>

<h3>Daftar Sertifikat</h3>
<ul id="certs-list"><li>Masukkan Student ID dan klik Load.</li></ul>

<script>
async function loadCertificates() {
    const sid = document.getElementById('student_id').value;
    if (!sid) {
        alert('Masukkan Student ID terlebih dahulu.');
        return;
    }

    const res  = await fetch(`/api/students/${sid}/certificates`);
    const json = await res.json();

    if (!res.ok) {
        alert('Error: ' + (json.message || 'Gagal memuat sertifikat.'));
        return;
    }

    const data = json.data ?? [];

    if (data.length === 0) {
        document.getElementById('certs-list').innerHTML =
            '<li>Belum ada sertifikat. Selesaikan kelas dan minta admin menerbitkan sertifikat.</li>';
        return;
    }

    let html = '';
    data.forEach(c => {
        html += `<li>
            Nomor: ${c.certificate_number} | Kelas: ${c.course_name} |
            Diterbitkan: ${c.issued_at ?? '—'} | Status: ${c.certification_status}
            <a href="${c.download_url}" target="_blank">Download PDF</a>
        </li>`;
    });

    document.getElementById('certs-list').innerHTML = html;
}
</script>

@endsection
