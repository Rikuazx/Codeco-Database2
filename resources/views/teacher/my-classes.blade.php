@extends('layout')

@section('content')

<h2>My Classes (Teacher)</h2>

<label>Pilih Teacher:
    <select id="sel-teacher" onchange="loadSessions()">
        <option value="">-- Pilih Teacher --</option>
    </select>
</label>
<button onclick="loadSessions()">Load</button>

<ul id="sessions-list"><li>Pilih teacher untuk melihat sesi.</li></ul>

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

async function loadSessions() {
    const tid = document.getElementById('sel-teacher').value;
    if (!tid) {
        document.getElementById('sessions-list').innerHTML = '<li>Pilih teacher terlebih dahulu.</li>';
        return;
    }

    const res  = await fetch(`/api/teachers/${tid}/sessions`);
    const json = await res.json();
    const data = json.data ?? [];

    if (data.length === 0) {
        document.getElementById('sessions-list').innerHTML = '<li>Belum ada sesi yang di-assign ke teacher ini.</li>';
        return;
    }

    let html = '';
    data.forEach(s => {
        const className = s['class']?.name ?? `Kelas #${s.class_id}`;
        const start     = s.start_time ? s.start_time.substring(0, 16).replace('T', ' ') : '—';
        const end       = s.end_time   ? s.end_time.substring(0, 16).replace('T', ' ')   : '—';
        const fbDone    = s.feedback_count  ?? 0;
        const fbTotal   = s.enrolled_count  ?? 0;
        const salary    = s.is_salary_paid  ? 'Cair ✅' : 'Pending ⏳';
        html += `<li>
            Sesi #${s.id} | Kelas: ${className} | ${start} - ${end} |
            Status: ${s.status} | Feedback: ${fbDone}/${fbTotal} | Salary: ${salary}
        </li>`;
    });

    document.getElementById('sessions-list').innerHTML = html;
}

loadTeachers();
</script>

@endsection
