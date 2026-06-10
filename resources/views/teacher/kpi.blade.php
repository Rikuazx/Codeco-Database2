@extends('layout')

@section('content')

<h2>KPI Saya</h2>

<p>Nilai KPI dihitung berdasarkan 3 komponen:</p>
<ul>
    <li><strong>Feedback Form (30%)</strong> — Mengumpulkan feedback form tepat waktu setelah mengajar</li>
    <li><strong>Kehadiran & Komitmen (40%)</strong> — Hadir sesuai jadwal, tidak sering membatalkan kelas</li>
    <li><strong>Kedisiplinan Availability (30%)</strong> — Mengirim jadwal ketersediaan mengajar tepat waktu</li>
</ul>

<label>Pilih Teacher:
    <select id="sel-teacher" onchange="loadKpi()">
        <option value="">-- Pilih Teacher --</option>
    </select>
</label>
<button onclick="loadKpi()">Load KPI</button>

<h3>Riwayat KPI</h3>

<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:900px;">
    <thead>
        <tr style="background:#eee;">
            <th>Bulan</th>
            <th>Tahun</th>
            <th>Feedback (30%)</th>
            <th>Kehadiran (40%)</th>
            <th>Availability (30%)</th>
            <th>Total</th>
            <th>Kategori</th>
            <th>Catatan</th>
        </tr>
    </thead>
    <tbody id="kpi-list">
        <tr><td colspan="8">Pilih teacher untuk melihat KPI.</td></tr>
    </tbody>
</table>

<h3>Keterangan Kategori</h3>
<ul>
    <li><strong style="color:green;">A (90% ke atas)</strong> → Mendapat bonus insentif tambahan.</li>
    <li><strong style="color:blue;">B (75%–89%)</strong> → Tetap mendapatkan prioritas jadwal mengajar.</li>
    <li><strong style="color:red;">C (di bawah 75%)</strong> → Akan dievaluasi dan bisa mendapat pengurangan jadwal.</li>
</ul>

<h3>Pengaruh KPI</h3>
<ul>
    <li>Menentukan pembagian jadwal mengajar.</li>
    <li>Memutuskan perpanjangan kontrak.</li>
    <li>Mengevaluasi kerja sama mentor dengan Codeco.</li>
</ul>

<script>
const MONTHS = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

async function loadTeachers() {
    const res = await fetch('/api/teachers');
    const data = await res.json();
    const sel = document.getElementById('sel-teacher');
    sel.innerHTML = '<option value="">-- Pilih Teacher --</option>';
    data.forEach(t => {
        sel.innerHTML += `<option value="${t.id}">${t.user ? t.user.name : 'Teacher #' + t.id} (ID: ${t.id})</option>`;
    });
}

async function loadKpi() {
    const tid = document.getElementById('sel-teacher').value;
    if (!tid) return;

    const res = await fetch(`/api/kpi/${tid}`);
    const json = await res.json();
    const data = json.data ?? [];

    const tbody = document.getElementById('kpi-list');
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8">Belum ada data KPI. Hubungi admin untuk menghitung KPI.</td></tr>';
        return;
    }

    let html = '';
    data.forEach(k => {
        const catColor = k.category === 'A' ? 'green' : k.category === 'B' ? 'blue' : 'red';
        html += `<tr>
            <td>${MONTHS[k.month] ?? k.month}</td>
            <td>${k.year}</td>
            <td>${k.feedback_score} / 30</td>
            <td>${k.attendance_score} / 40</td>
            <td>${k.availability_score} / 30</td>
            <td><strong>${k.total_score}</strong></td>
            <td style="color:${catColor}; font-weight:bold; font-size:1.2em;">${k.category ?? '—'}</td>
            <td style="font-size:0.85em;">${k.notes ?? '—'}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

loadTeachers();
</script>

@endsection
