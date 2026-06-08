@extends('layout')

@section('content')

<h2>Salary</h2>
<p>Salary cair otomatis setelah semua feedback student dalam 1 sesi diisi (dalam 2 hari setelah sesi selesai).</p>

<label>Pilih Teacher:
    <select id="sel-teacher" onchange="loadSalary()">
        <option value="">-- Pilih Teacher --</option>
    </select>
</label>
<button onclick="loadSalary()">Load Salary</button>

<h3>Ringkasan Salary</h3>
<ul id="salary-summary"><li>Pilih teacher untuk melihat salary.</li></ul>

<h3>Riwayat Pembayaran per Sesi</h3>
<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:600px;">
    <thead>
        <tr style="background:#eee;">
            <th>No</th>
            <th>Kelas</th>
            <th>Tanggal Sesi</th>
            <th>Salary Diterima</th>
        </tr>
    </thead>
    <tbody id="salary-body">
        <tr><td colspan="4">Pilih teacher untuk melihat riwayat.</td></tr>
    </tbody>
    <tfoot id="salary-foot"></tfoot>
</table>

<script>
async function loadTeachers() {
    const res  = await fetch('/api/teachers');
    const data = await res.json();
    const sel  = document.getElementById('sel-teacher');
    sel.innerHTML = '<option value="">-- Pilih Teacher --</option>';
    data.forEach(t => {
        sel.innerHTML += `<option value="${t.id}">${t.user ? t.user.name : 'Teacher #' + t.id} (ID: ${t.id})</option>`;
    });
}

async function loadSalary() {
    const tid = document.getElementById('sel-teacher').value;
    if (!tid) return;

    const res  = await fetch(`/api/teachers/${tid}/salary`);
    const data = await res.json();

    if (!res.ok) {
        alert('Gagal memuat data salary.');
        return;
    }

    const total  = Number(data.total_salary ?? 0);
    const perSes = Number(data.salary_per_session ?? 0);

    document.getElementById('salary-summary').innerHTML = `
        <li>Nama Teacher: ${data.teacher_name}</li>
        <li>Total Salary Terkumpul: Rp ${total.toLocaleString('id-ID')}</li>
        <li>Salary per Sesi: Rp ${perSes.toLocaleString('id-ID')}</li>
        <li>Total Sesi Dibayar: ${data.total_paid_sessions}</li>`;

    const sessions = data.paid_sessions ?? [];
    if (sessions.length === 0) {
        document.getElementById('salary-body').innerHTML =
            '<tr><td colspan="4">Belum ada sesi yang salary-nya cair. Selesaikan semua feedback untuk memicu salary.</td></tr>';
        document.getElementById('salary-foot').innerHTML = '';
        return;
    }

    let html = '';
    let grand = 0;
    sessions.forEach((s, i) => {
        const amt = Number(s.salary_earned ?? 0);
        grand += amt;
        html += `<tr>
            <td>${i + 1}</td>
            <td>${s.class_name}</td>
            <td>${s.date}</td>
            <td>Rp ${amt.toLocaleString('id-ID')}</td>
        </tr>`;
    });

    document.getElementById('salary-body').innerHTML = html;
    document.getElementById('salary-foot').innerHTML =
        `<tr style="background:#eee;font-weight:bold;">
            <td colspan="3">TOTAL</td>
            <td>Rp ${grand.toLocaleString('id-ID')}</td>
        </tr>`;
}

loadTeachers();
</script>

@endsection
