@extends('layout')

@section('content')

<h2>Sessions</h2>

<button onclick="loadSessions()">Load Sessions</button>
<ul id="sessions"></ul>

<h3>Create Sessions from Class</h3>
<select id="class_selector"></select>
<button onclick="generateClassSessions(document.getElementById('class_selector').value)">Generate Sessions</button>

<h3>Edit Session</h3>

<input id="session_id" hidden>
<input id="start_time" placeholder="Start Time">
<input id="end_time" placeholder="End Time">
<select id="teacher_selector">
    <option value="">No teacher assigned</option>
</select>

<button onclick="updateSession()">Update</button>

<script>
let teachersData = [];
let sessionsData = [];

async function loadClassSelector() {
    const res = await fetch('/api/classes');
    const classes = await res.json();
    const selector = document.getElementById('class_selector');
    selector.innerHTML = '<option value="">Select class</option>';
    classes.forEach(c => {
        selector.innerHTML += `<option value="${c.id}">${c.name} (${c.total_sessions} sessions)</option>`;
    });
}

async function loadTeachers() {
    const res = await fetch('/api/teachers');
    teachersData = await res.json();
    const selector = document.getElementById('teacher_selector');
    selector.innerHTML = '<option value="">No teacher assigned</option>';
    teachersData.forEach(t => {
        const teacherName = t.user ? t.user.name : `Teacher ${t.id}`;
        selector.innerHTML += `<option value="${t.id}">${teacherName}</option>`;
    });
}

async function loadSessions() {

    const res = await fetch('/api/sessions');
    const data = await res.json();

    sessionsData = data;

    let html = '';

    data.forEach(s => {

        const teacherName =
            s.teacher && s.teacher.user
                ? s.teacher.user.name
                : (s.teacher
                    ? `Teacher ${s.teacher.id}`
                    : 'None');

        html += `
        <li>
            Session ${s.id} |

            Start: ${s.start_time} |

            End: ${s.end_time} |

            Teacher: ${teacherName}

            <button onclick="complete(${s.id})">
                Complete
            </button>

            <button onclick="editSession(${s.id})">
                Edit
            </button>

            <button onclick="deleteSession(${s.id})">
                Delete
            </button>
        </li>`;
    });

    document.getElementById('sessions').innerHTML = html;
}

async function generateClassSessions(classId) {

    const res = await fetch(
        `http://127.0.0.1:8000/api/generate-sessions/${classId}`,
        {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            }
        }
    );

    const text = await res.text();

    console.log("STATUS:", res.status);
    console.log("RESPONSE:", text);

    if (!res.ok) {
        alert(text);
        return;
    }

    loadSessions();
}

async function complete(id)
{
    const res = await fetch(
        `http://127.0.0.1:8000/api/sessions/${id}/complete`,
        {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            }
        }
    );

    const text = await res.text();

    console.log(text);

    loadSessions();
}

function editSession(id) {
    const s = sessionsData.find(session => session.id === id);
    if (!s) return;

    document.getElementById('session_id').value = s.id;
    document.getElementById('start_time').value = s.start_time;
    document.getElementById('end_time').value = s.end_time;
    document.getElementById('teacher_selector').value = s.teacher ? s.teacher.id : '';
}

async function updateSession() {
    const sessionId = document.getElementById('session_id').value;
    if (!sessionId) {
        alert('Select a session to edit.');
        return;
    }

    await fetch(`/api/sessions/${sessionId}`, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            start_time: document.getElementById('start_time').value,
            end_time: document.getElementById('end_time').value,
            teacher_id: document.getElementById('teacher_selector').value || null
        })
    });

    loadSessions();
}

async function deleteSession(id) {
    if (!confirm('Are you sure you want to delete this session?')) {
        return;
    }

    await fetch(`/api/sessions/${id}`, {
        method: 'DELETE'
    });

    loadSessions();
}

loadClassSelector();
loadTeachers();
loadSessions();
</script>

@endsection