@extends('layout')

@section('content')

<h2>Feedback</h2>
<p>Isi feedback untuk setiap student setelah sesi selesai. Salary otomatis cair setelah semua feedback diisi.</p>

<label>Pilih Teacher:
    <select id="sel-teacher" onchange="loadSessions()">
        <option value="">-- Pilih Teacher --</option>
    </select>
</label>

<h3>Daftar Sesi</h3>
<ul id="sessions-list"><li>Pilih teacher untuk melihat sesi.</li></ul>

<hr>

<h3 id="students-title" style="display:none;">Student dalam Sesi</h3>
<p id="fb-progress"></p>
<ul id="students-list"></ul>

<h3 id="fb-form-title" style="display:none;">Kirim Feedback</h3>
<div id="fb-form" style="display:none;">
    <label>Student:
        <select id="sel-student"></select>
    </label><br>
    <label>Rating (1-5): <input id="fb-rating" type="number" min="1" max="5" style="width:60px;"></label><br>
    <label>Komentar: <textarea id="fb-comment" rows="3" cols="40" placeholder="Komentar untuk student..."></textarea></label><br>
    <button onclick="submitFeedback()">Kirim Feedback</button>
    <p id="fb-msg"></p>
</div>

<script>
let currentTeacherId = null;
let currentSessionId = null;
let studentsData     = [];

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
    if (!tid) return;
    currentTeacherId = tid;

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
        const done      = s.feedback_count ?? 0;
        const total     = s.enrolled_count ?? 0;
        const salary    = s.is_salary_paid ? '💰 Salary Cair' : '⏳ Pending';
        html += `<li>
            Sesi #${s.id} | ${className} | ${start} | Status: ${s.status} |
            Feedback: ${done}/${total} | ${salary}
            <button onclick="selectSession(${s.id}, '${className.replace(/'/g, "\\'")}')">Isi Feedback</button>
        </li>`;
    });
    document.getElementById('sessions-list').innerHTML = html;

    // Reset student section
    document.getElementById('students-title').style.display = 'none';
    document.getElementById('fb-form-title').style.display  = 'none';
    document.getElementById('fb-form').style.display        = 'none';
}

async function selectSession(sessionId, className) {
    currentSessionId = sessionId;
    document.getElementById('students-title').textContent = `Student dalam Sesi #${sessionId} — ${className}`;
    document.getElementById('students-title').style.display = 'block';

    const res  = await fetch(`/api/sessions/${sessionId}/enrolled-students`);
    const json = await res.json();
    studentsData = json.data ?? [];

    const done  = studentsData.filter(s => s.has_feedback).length;
    const total = studentsData.length;
    document.getElementById('fb-progress').textContent = `Feedback: ${done}/${total} student sudah dinilai.`;

    let listHtml = '';
    studentsData.forEach(s => {
        const status = s.has_feedback
            ? `✅ Sudah dinilai (Rating: ${s.feedback?.rating ?? '—'}, Komentar: ${s.feedback?.comment || '—'})`
            : '⏳ Belum dinilai';
        listHtml += `<li>[ID: ${s.student_id}] ${s.student_name} — ${status}</li>`;
    });
    document.getElementById('students-list').innerHTML = listHtml;

    // Populate student dropdown for feedback form (only those without feedback)
    const pending = studentsData.filter(s => !s.has_feedback);
    if (pending.length > 0) {
        let selHtml = '';
        pending.forEach(s => {
            selHtml += `<option value="${s.student_id}">${s.student_name} (ID: ${s.student_id})</option>`;
        });
        document.getElementById('sel-student').innerHTML = selHtml;
        document.getElementById('fb-form-title').textContent = 'Kirim Feedback untuk Student';
        document.getElementById('fb-form-title').style.display = 'block';
        document.getElementById('fb-form').style.display       = 'block';
    } else {
        document.getElementById('fb-form-title').style.display = 'none';
        document.getElementById('fb-form').style.display       = 'none';
    }

    document.getElementById('fb-msg').textContent = '';
}

async function submitFeedback() {
    const msg       = document.getElementById('fb-msg');
    const studentId = parseInt(document.getElementById('sel-student').value);
    const rating    = document.getElementById('fb-rating').value || null;
    const comment   = document.getElementById('fb-comment').value.trim() || null;

    if (!rating && !comment) {
        msg.textContent = 'Isi rating atau komentar.';
        return;
    }

    msg.textContent = 'Mengirim...';

    const res  = await fetch('/api/feedback', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
            teacher_id:       parseInt(currentTeacherId),
            student_id:       studentId,
            class_session_id: currentSessionId,
            rating:           rating ? parseInt(rating) : null,
            comment:          comment
        })
    });
    const data = await res.json();

    if (!res.ok) {
        msg.textContent = 'Error: ' + (data.error || 'Gagal mengirim feedback.');
        return;
    }

    let statusMsg = 'Feedback berhasil dikirim!';
    if (data.salary_status) statusMsg += ' | ' + data.salary_status;
    msg.textContent = statusMsg;

    document.getElementById('fb-rating').value  = '';
    document.getElementById('fb-comment').value = '';

    // Reload students list
    selectSession(currentSessionId, '');
}

loadTeachers();
</script>

@endsection