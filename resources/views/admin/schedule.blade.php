@extends('layout')

@section('content')

<h2>Schedule</h2>

<button onclick="loadAllData()">Load Schedule</button>

<h3>Availability Teacher</h3>

<label>Filter Teacher:
    <select id="filter-teacher" onchange="renderAvailabilityList()">
        <option value="">-- Semua Teacher --</option>
    </select>
</label>

<p>Periode: <span id="period-info">Menghitung...</span></p>

<ul id="avail-list"></ul>

<h3>Buat Jadwal Session</h3>

<input id="session_id" hidden>
<select id="sched-teacher" onchange="loadTeacherSlots()">
    <option value="">-- Pilih Teacher --</option>
</select>
<select id="sched-class">
    <option value="">-- Pilih Kelas --</option>
</select>
<select id="sched-date" onchange="fillTimeFromSlot()">
    <option value="">-- Pilih Tanggal --</option>
</select>
<span id="sched-slot-info"></span>
<br><br>
<input type="datetime-local" id="sched-start" placeholder="Start Time">
<input type="datetime-local" id="sched-end" placeholder="End Time">

<button onclick="createSession()">Save</button>
<span id="sched-msg"></span>

<h3>Jadwal Session</h3>

<ul id="sessions-list"></ul>

<script>
let allAvailabilities = [];
let allTeachers = [];
let allClasses = [];
let allSessions = [];
let teacherSlots = [];
let periodStart = '', periodEnd = '';

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

    document.getElementById('period-info').textContent = `${periodStart} s/d ${periodEnd}`;
}

async function loadAllData() {
    await Promise.all([
        loadTeachers(),
        loadClasses(),
        loadAvailabilities(),
        loadSessions(),
    ]);
    renderAvailabilityList();
}

async function loadTeachers() {
    const res = await fetch('/api/teachers');
    allTeachers = await res.json();

    const filterSel = document.getElementById('filter-teacher');
    const schedSel  = document.getElementById('sched-teacher');

    filterSel.innerHTML = '<option value="">-- Semua Teacher --</option>';
    schedSel.innerHTML  = '<option value="">-- Pilih Teacher --</option>';

    allTeachers.forEach(t => {
        const name = t.user ? t.user.name : `Teacher #${t.id}`;
        filterSel.innerHTML += `<option value="${t.id}">${name}</option>`;
        schedSel.innerHTML  += `<option value="${t.id}">${name}</option>`;
    });
}

async function loadClasses() {
    const res = await fetch('/api/classes');
    allClasses = await res.json();
    const sel = document.getElementById('sched-class');
    sel.innerHTML = '<option value="">-- Pilih Kelas --</option>';
    allClasses.forEach(c => {
        sel.innerHTML += `<option value="${c.id}">${c.name} (${c.total_sessions} sesi)</option>`;
    });
}

async function loadAvailabilities() {
    const res = await fetch('/api/teacher-availability');
    const data = await res.json();
    allAvailabilities = data.data ?? [];
}

async function loadSessions() {
    const res = await fetch('/api/sessions');
    allSessions = await res.json();
    renderSessionsList();
}

function renderAvailabilityList() {
    const ul = document.getElementById('avail-list');
    const filterTeacherId = document.getElementById('filter-teacher').value;

    let filtered = allAvailabilities;
    if (filterTeacherId) {
        filtered = filtered.filter(a => String(a.teacher_id) === filterTeacherId);
    }

    if (filtered.length === 0) {
        ul.innerHTML = '<li>Belum ada availability yang di-submit teacher.</li>';
        return;
    }

    let html = '';
    filtered.sort((a, b) => a.date.localeCompare(b.date));
    filtered.forEach(a => {
        const jam = a.type === 'time_range' ? `${a.start_time} - ${a.end_time}` : (a.type === 'full_day' ? 'Sepanjang hari' : '-');
        html += `
        <li>
            ${a.teacher_name} | ${a.date} | ${a.type} | ${jam}
            <button onclick="quickAssign('${a.teacher_id}', '${a.date}', '${a.type}', '${a.start_time}', '${a.end_time}')">Buat Jadwal</button>
        </li>`;
    });

    ul.innerHTML = html;
}

function quickAssign(teacherId, date, type, startTime, endTime) {
    document.getElementById('sched-teacher').value = teacherId;
    loadTeacherSlots();

    setTimeout(() => {
        const dateSel = document.getElementById('sched-date');
        dateSel.value = date;
        fillTimeFromSlot();

        const startInput = document.getElementById('sched-start');
        const endInput   = document.getElementById('sched-end');

        if (type === 'full_day') {
            startInput.value = `${date}T09:00`;
            endInput.value   = `${date}T11:00`;
        } else if (type === 'time_range' && startTime && endTime) {
            startInput.value = `${date}T${startTime.substring(0, 5)}`;
            endInput.value   = `${date}T${endTime.substring(0, 5)}`;
        }
    }, 200);
}

function loadTeacherSlots() {
    const tid = document.getElementById('sched-teacher').value;
    const dateSel = document.getElementById('sched-date');
    dateSel.innerHTML = '<option value="">-- Pilih Tanggal --</option>';
    document.getElementById('sched-slot-info').textContent = '';

    if (!tid) return;

    teacherSlots = allAvailabilities.filter(a => String(a.teacher_id) === tid);

    if (teacherSlots.length === 0) {
        dateSel.innerHTML = '<option value="">Tidak ada availability</option>';
        return;
    }

    const uniqueDates = [...new Set(teacherSlots.map(s => s.date))].sort();
    uniqueDates.forEach(d => {
        const slot = teacherSlots.find(s => s.date === d);
        const label = slot.type === 'full_day'
            ? `${d} (Full Day)`
            : `${d} (${slot.start_time} - ${slot.end_time})`;
        dateSel.innerHTML += `<option value="${d}">${label}</option>`;
    });
}

function fillTimeFromSlot() {
    const date = document.getElementById('sched-date').value;
    const info = document.getElementById('sched-slot-info');
    if (!date) { info.textContent = ''; return; }
    const slot = teacherSlots.find(s => s.date === date);
    if (!slot) { info.textContent = 'Tidak tersedia'; return; }
    if (slot.type === 'full_day') {
        info.textContent = 'Full Day — bebas atur jam';
    } else {
        info.textContent = `Tersedia: ${slot.start_time} - ${slot.end_time}`;
    }
}

async function createSession() {
    const msg       = document.getElementById('sched-msg');
    const teacherId = document.getElementById('sched-teacher').value;
    const classId   = document.getElementById('sched-class').value;
    const startTime = document.getElementById('sched-start').value;
    const endTime   = document.getElementById('sched-end').value;

    if (!teacherId || !classId || !startTime || !endTime) {
        msg.textContent = 'Semua field harus diisi.';
        return;
    }

    msg.textContent = 'Menyimpan...';

    try {
        const res = await fetch('/api/sessions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                class_id:   classId,
                teacher_id: teacherId,
                start_time: startTime,
                end_time:   endTime,
                status:     'scheduled',
            })
        });

        const data = await res.json();
        if (!res.ok) {
            msg.textContent = 'Error: ' + (data.error || data.message || 'Gagal membuat session.');
            return;
        }

        msg.textContent = 'Session berhasil dibuat!';
        loadSessions();

    } catch (err) {
        msg.textContent = 'Error: ' + err.message;
    }
}

function renderSessionsList() {
    const ul = document.getElementById('sessions-list');
    if (allSessions.length === 0) {
        ul.innerHTML = '<li>Belum ada session.</li>';
        return;
    }

    let html = '';
    allSessions.forEach(s => {
        const teacherName = s.teacher?.user?.name
            ?? (s.teacher ? `Teacher #${s.teacher.id}` : 'None');
        const className = s.class?.name ?? `Class #${s.class_id}`;

        html += `
        <li>
            Session ${s.id} | ${className} | Teacher: ${teacherName} | ${s.start_time} - ${s.end_time} | ${s.status}
            ${!s.teacher_id ? `<button onclick="autoAssign(${s.id})">Auto Assign</button>` : ''}
            <button onclick="deleteSessionAdmin(${s.id})">Delete</button>
        </li>`;
    });

    ul.innerHTML = html;
}

async function autoAssign(sessionId) {
    const res = await fetch(`/api/sessions/${sessionId}/auto-assign`, { method: 'POST' });
    const data = await res.json();
    if (!res.ok) {
        alert('Gagal: ' + (data.error || 'Tidak ada teacher tersedia.'));
        return;
    }
    alert('Teacher berhasil di-assign!');
    loadSessions();
}

async function deleteSessionAdmin(sessionId) {
    if (!confirm('Yakin ingin menghapus session ini?')) return;
    await fetch(`/api/sessions/${sessionId}`, { method: 'DELETE' });
    loadSessions();
}

calcPeriod();
loadAllData();
</script>

@endsection
