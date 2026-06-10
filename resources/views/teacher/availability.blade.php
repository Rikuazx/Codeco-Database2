@extends('layout')

@section('content')

<h2>Teacher Availability</h2>

<p><strong>Periode Target:</strong> <span id="target-period">Menghitung periode...</span></p>
<p><strong>Deadline:</strong> Jumat pukul 18:00 WIB pada minggu berjalan.</p>

<label>Pilih Teacher:
    <select id="sel-teacher" onchange="loadAvailability()">
        <option value="">-- Pilih Teacher --</option>
    </select>
</label>
<button onclick="loadAvailability()">Load Availability</button>

<h3>Availability Saat Ini</h3>

<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:700px;">
    <thead>
        <tr style="background:#eee;">
            <th>Tanggal</th>
            <th>Tipe</th>
            <th>Jam</th>
            <th>Dipakai Teacher Lain</th>
        </tr>
    </thead>
    <tbody id="avail-list">
        <tr><td colspan="4">Pilih teacher untuk melihat availability.</td></tr>
    </tbody>
</table>

<h3>Tambah / Update Availability (min. 2 slot tersedia)</h3>

<div id="slots-container" style="margin-bottom:8px;"></div>
<button onclick="addSlot()">+ Tambah Slot</button>
<button onclick="saveAvailability()" style="font-weight:bold; margin-left:8px;">Simpan Availability</button>
<p id="save-msg" style="margin-top:6px;"></p>

<script>
let periodStart = '', periodEnd = '';
let allOtherAvail = {};  // date -> [{teacher_name, type, start_time, end_time}]

function calcPeriod() {
    const today = new Date();
    const day   = today.getDay();
    const diff  = day === 0 ? 1 : 8 - day;
    const mon   = new Date(today);
    mon.setDate(today.getDate() + diff);

    const sun = new Date(mon);
    sun.setDate(mon.getDate() + 13);

    const fmt = d => d.toISOString().slice(0, 10);
    periodStart = fmt(mon);
    periodEnd   = fmt(sun);

    document.getElementById('target-period').textContent = `${periodStart} s/d ${periodEnd}`;
}

async function loadTeachers() {
    const res  = await fetch('/api/teachers');
    const data = await res.json();
    const sel  = document.getElementById('sel-teacher');
    sel.innerHTML = '<option value="">-- Pilih Teacher --</option>';
    data.forEach(t => {
        sel.innerHTML += `<option value="${t.id}">${t.user ? t.user.name : 'Teacher #' + t.id} (ID: ${t.id})</option>`;
    });
}

async function loadAvailability() {
    const tid = document.getElementById('sel-teacher').value;
    if (!tid) return;

    // Load this teacher's own availability
    const res1   = await fetch(`/api/teacher-availability/${tid}`);
    const own    = await res1.json();

    // Load all teachers' availability (for conflict info)
    const res2   = await fetch('/api/teacher-availability');
    const allRes = (await res2.json()).data ?? [];

    allOtherAvail = {};
    allRes.forEach(a => {
        if (String(a.teacher_id) === String(tid)) return;
        if (!allOtherAvail[a.date]) allOtherAvail[a.date] = [];
        allOtherAvail[a.date].push(a);
    });

    const tbody = document.getElementById('avail-list');
    if (own.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4">Belum ada availability terdaftar.</td></tr>';
        return;
    }

    let html = '';
    own.forEach(a => {
        const jam     = a.type === 'time_range' ? `${a.start_time ?? ''} - ${a.end_time ?? ''}` : '-';
        const others  = allOtherAvail[a.date] ?? [];
        const conflict = others.length > 0
            ? others.map(o => o.teacher_name).join(', ')
            : '—';
        html += `<tr>
            <td>${a.date}</td>
            <td>${a.type}</td>
            <td>${jam}</td>
            <td>${conflict}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function addSlot() {
    const container = document.getElementById('slots-container');
    const id = 'slot-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.style.marginBottom = '4px';
    div.innerHTML = `
        <input type="date" class="slot-date" min="${periodStart}" max="${periodEnd}" onchange="checkSlotConflict(this.parentNode)">
        <select class="slot-type" onchange="toggleTime(this); checkSlotConflict(this.parentNode)">
            <option value="full_day">Full Day</option>
            <option value="time_range">Time Range</option>
            <option value="unavailable">Unavailable</option>
        </select>
        <input type="time" class="slot-start" style="display:none;" onchange="checkSlotConflict(this.parentNode)">
        s/d
        <input type="time" class="slot-end" style="display:none;" onchange="checkSlotConflict(this.parentNode)">
        <button onclick="document.getElementById('${id}').remove()">Hapus</button>
        <span class="conflict-warn" style="color:red; font-weight:bold; margin-left:8px;"></span>
    `;
    container.appendChild(div);
}

function checkSlotConflict(row) {
    const warn  = row.querySelector('.conflict-warn');
    const date  = row.querySelector('.slot-date').value;
    const type  = row.querySelector('.slot-type').value;
    const start = row.querySelector('.slot-start').value;
    const end   = row.querySelector('.slot-end').value;

    warn.textContent = '';
    if (!date || type === 'unavailable') return;

    const others = allOtherAvail[date] ?? [];
    if (others.length === 0) return;

    let conflicting = [];
    for (const o of others) {
        if (type === 'full_day') {
            // full_day conflicts with any non-unavailable slot
            conflicting.push(o.teacher_name);
        } else if (type === 'time_range' && start && end) {
            if (o.type === 'full_day') {
                conflicting.push(o.teacher_name);
            } else if (o.type === 'time_range' && o.start_time < end && o.end_time > start) {
                conflicting.push(o.teacher_name);
            }
        }
    }

    if (conflicting.length > 0) {
        const names = [...new Set(conflicting)].join(', ');
        warn.textContent = `⚠ sudah dipilih: ${names}`;
    }
}

function toggleTime(sel) {
    const row   = sel.parentNode;
    const show  = sel.value === 'time_range';
    row.querySelector('.slot-start').style.display = show ? 'inline' : 'none';
    row.querySelector('.slot-end').style.display   = show ? 'inline' : 'none';
}

async function saveAvailability() {
    const tid  = document.getElementById('sel-teacher').value;
    const msg  = document.getElementById('save-msg');

    if (!tid) { msg.textContent = 'Pilih teacher terlebih dahulu.'; return; }

    const rows = document.querySelectorAll('#slots-container > div');
    if (rows.length === 0) { msg.textContent = 'Tambah minimal 1 slot terlebih dahulu.'; return; }

    const availabilities = [];
    for (let row of rows) {
        const date       = row.querySelector('.slot-date').value;
        const type       = row.querySelector('.slot-type').value;
        const start_time = row.querySelector('.slot-start').value;
        const end_time   = row.querySelector('.slot-end').value;
        if (!date) { msg.textContent = 'Semua baris harus memiliki tanggal.'; return; }
        availabilities.push({ date, type, start_time, end_time });
    }

    msg.textContent = 'Menyimpan...';
    const res  = await fetch('/api/teacher-availability', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ teacher_id: tid, availabilities })
    });
    const data = await res.json();

    if (!res.ok) {
        msg.style.color = 'red';
        msg.textContent = 'Error: ' + (data.error || 'Gagal menyimpan.');
        return;
    }
    msg.style.color = 'green';
    msg.textContent = 'Berhasil disimpan! Status: ' + data.message;
    document.getElementById('slots-container').innerHTML = '';
    loadAvailability();
}

calcPeriod();
loadTeachers();
</script>

@endsection
