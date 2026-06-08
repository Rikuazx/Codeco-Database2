@extends('layout')

@section('content')

<h2>My Classes (Student)</h2>

<label>Student ID: <input id="student_id" type="number" placeholder="Masukkan Student ID"></label>
<button onclick="loadMyClasses()">Load Kelas</button>

<h3>Kelas yang Diikuti</h3>
<ul id="classes-list"><li>Masukkan Student ID dan klik Load.</li></ul>

<script>
async function loadMyClasses() {
    const sid  = document.getElementById('student_id').value;
    if (!sid) {
        alert('Masukkan Student ID terlebih dahulu.');
        return;
    }

    const res  = await fetch(`/api/students/${sid}/enrollments`);
    const json = await res.json();

    if (!res.ok) {
        alert('Error: ' + (json.message || 'Gagal memuat data.'));
        return;
    }

    const data = json.data ?? [];

    if (data.length === 0) {
        document.getElementById('classes-list').innerHTML =
            '<li>Belum terdaftar di kelas manapun. Hubungi admin.</li>';
        return;
    }

    let html = '';
    data.forEach(e => {
        const name  = e.course?.name ?? `Kelas #${e.class_id}`;
        const total = e.course?.total_sessions ?? '—';
        const price = Number(e.price).toLocaleString('id-ID');
        html += `<li>
            ${name} | Sesi: ${total} | Status: ${e.status} | Biaya: Rp ${price}
        </li>`;
    });

    document.getElementById('classes-list').innerHTML = html;
}
</script>

@endsection
