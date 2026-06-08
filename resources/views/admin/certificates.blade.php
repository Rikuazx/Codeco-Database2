@extends('layout')

@section('content')

<h2>Certificates</h2>

<button onclick="loadCertificates()">Load Certificates</button>

<ul id="cert-list"></ul>

<hr>

<h3>Terbitkan Sertifikat Baru</h3>

<label>Enrollment ID: <input id="enrollment_id" type="number" placeholder="Enrollment ID"></label><br>
<label>Signer (Kanan): <input id="signer_right" type="text" placeholder="Mr. Ilham" value="Mr. Ilham"></label><br><br>

<button onclick="issueCertificate()">Terbitkan Sertifikat</button>

<p id="issue-msg" style="color:green; margin-top:8px;"></p>

<script>
async function loadCertificates() {
    const list = document.getElementById('cert-list');
    list.innerHTML = '<li>Loading...</li>';

    const res = await fetch('/api/certificates');
    const json = await res.json();
    const data = json.data ?? [];

    if (data.length === 0) {
        list.innerHTML = '<li>Belum ada sertifikat.</li>';
        return;
    }

    let html = '';
    data.forEach(c => {
        const studentName = c.student?.user?.name ?? '—';
        const courseName  = c.course?.name ?? '—';
        const issuedAt    = c.issued_at ? c.issued_at.substring(0, 10) : '—';

        html += `
        <li style="margin-bottom:8px;">
            <strong>${c.certificate_number}</strong>
            &mdash; ${studentName} &mdash; ${courseName}
            &mdash; <em>${issuedAt}</em>
            &nbsp;
            <a href="/api/certificates/${c.id}/download" target="_blank">[Download PDF]</a>
        </li>`;
    });

    list.innerHTML = html;
}

async function issueCertificate() {
    const msg = document.getElementById('issue-msg');
    msg.textContent = '';

    const payload = {
        enrollment_id: parseInt(document.getElementById('enrollment_id').value),
        signer_right:  document.getElementById('signer_right').value || 'Mr. Ilham',
    };

    if (!payload.enrollment_id) {
        msg.style.color = 'red';
        msg.textContent = 'Enrollment ID wajib diisi.';
        return;
    }

    const res = await fetch('/api/certificates', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (!res.ok) {
        msg.style.color = 'red';
        msg.textContent = 'Error: ' + (data.error || data.message || JSON.stringify(data));
        return;
    }

    msg.style.color = 'green';
    msg.textContent = 'Sertifikat berhasil diterbitkan! No: ' + data.data.certificate_number;

    document.getElementById('enrollment_id').value = '';
    loadCertificates();
}

loadCertificates();
</script>

@endsection
