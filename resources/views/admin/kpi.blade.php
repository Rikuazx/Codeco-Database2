@extends('layout')

@section('content')

<h2>KPI Teacher (Admin)</h2>

<p>Hitung dan lihat KPI semua teacher berdasarkan 3 komponen:</p>
<ul>
    <li><strong>Feedback Form (30%)</strong> — Mengumpulkan feedback form tepat waktu setelah mengajar</li>
    <li><strong>Kehadiran & Komitmen (40%)</strong> — Hadir sesuai jadwal, tidak sering membatalkan</li>
    <li><strong>Kedisiplinan Availability (30%)</strong> — Mengirim jadwal ketersediaan tepat waktu</li>
</ul>

<h3>Pilih Periode</h3>

<label>Bulan:
    <select id="sel-month">
        <option value="1">Januari</option>
        <option value="2">Februari</option>
        <option value="3">Maret</option>
        <option value="4">April</option>
        <option value="5">Mei</option>
        <option value="6">Juni</option>
        <option value="7">Juli</option>
        <option value="8">Agustus</option>
        <option value="9">September</option>
        <option value="10">Oktober</option>
        <option value="11">November</option>
        <option value="12">Desember</option>
    </select>
</label>
<label style="margin-left:8px;">Tahun:
    <input type="number" id="sel-year" value="2026" min="2024" max="2030" style="width:80px;">
</label>
<button onclick="loadKpi()" style="margin-left:8px;">Load KPI</button>
<button onclick="calculateAll()" style="margin-left:4px; font-weight:bold;">Hitung Semua KPI</button>
<p id="calc-msg" style="margin-top:6px;"></p>

<h3>Hasil KPI</h3>

<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%;">
    <thead>
        <tr style="background:#eee;">
            <th>Teacher</th>
            <th>Feedback (30%)</th>
            <th>Kehadiran (40%)</th>
            <th>Availability (30%)</th>
            <th>Total</th>
            <th>Kategori</th>
            <th>Catatan</th>
        </tr>
    </thead>
    <tbody id="kpi-list">
        <tr><td colspan="7">Pilih periode dan klik Load KPI.</td></tr>
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

<h3>Hitung KPI Individual</h3>

<label>Pilih Teacher:
    <select id="sel-teacher">
        <option value="">-- Pilih Teacher --</option>
    </select>
</label>
<button onclick="calculateSingle()" style="margin-left:4px;">Hitung KPI</button>
<p id="single-msg" style="margin-top:6px;"></p>

<div id="detail-panel" style="display:none; margin-top:10px; padding:10px; border:1px solid #ccc; border-radius:4px; background:#f9f9f9;">
    <h4 style="margin:0 0 8px;">Detail KPI</h4>
    <pre id="detail-content" style="white-space: pre-wrap; font-size:0.9em;"></pre>
</div>

<script>
// Set default month
document.getElementById('sel-month').value = new Date().getMonth() + 1;
document.getElementById('sel-year').value = new Date().getFullYear();

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
    const month = document.getElementById('sel-month').value;
    const year = document.getElementById('sel-year').value;

    const res = await fetch(`/api/kpi?month=${month}&year=${year}`);
    const json = await res.json();
    const data = json.data ?? [];

    const tbody = document.getElementById('kpi-list');
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7">Belum ada data KPI untuk periode ini. Klik "Hitung Semua KPI".</td></tr>';
        return;
    }

    let html = '';
    data.forEach(k => {
        const catColor = k.category === 'A' ? 'green' : k.category === 'B' ? 'blue' : 'red';
        html += `<tr>
            <td>${k.teacher_name}</td>
            <td>${k.feedback_score} / 30</td>
            <td>${k.attendance_score} / 40</td>
            <td>${k.availability_score} / 30</td>
            <td><strong>${k.total_score}</strong></td>
            <td style="color:${catColor}; font-weight:bold; font-size:1.2em;">${k.category}</td>
            <td style="font-size:0.85em;">${k.notes ?? '—'}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

async function calculateAll() {
    const month = document.getElementById('sel-month').value;
    const year = document.getElementById('sel-year').value;
    const msg = document.getElementById('calc-msg');

    msg.textContent = 'Menghitung KPI semua teacher...';
    msg.style.color = '#333';

    const res = await fetch('/api/kpi/calculate-all', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ month, year })
    });
    const data = await res.json();

    if (!res.ok) {
        msg.textContent = 'Error: ' + (data.error || 'Gagal menghitung.');
        msg.style.color = 'red';
        return;
    }

    msg.textContent = data.message;
    msg.style.color = 'green';
    loadKpi();
}

async function calculateSingle() {
    const tid = document.getElementById('sel-teacher').value;
    const month = document.getElementById('sel-month').value;
    const year = document.getElementById('sel-year').value;
    const msg = document.getElementById('single-msg');

    if (!tid) { msg.textContent = 'Pilih teacher terlebih dahulu.'; msg.style.color = 'red'; return; }

    msg.textContent = 'Menghitung...';
    msg.style.color = '#333';

    const res = await fetch(`/api/kpi/calculate/${tid}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ month, year })
    });
    const data = await res.json();

    if (!res.ok) {
        msg.textContent = 'Error: ' + (data.error || 'Gagal menghitung.');
        msg.style.color = 'red';
        return;
    }

    msg.textContent = 'KPI berhasil dihitung!';
    msg.style.color = 'green';

    document.getElementById('detail-panel').style.display = 'block';
    document.getElementById('detail-content').textContent = JSON.stringify(data.data, null, 2);
    loadKpi();
}

loadTeachers();
</script>

@endsection
