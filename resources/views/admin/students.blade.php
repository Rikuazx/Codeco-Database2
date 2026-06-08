@extends('layout')

@section('content')

<h2>Students</h2>

<button onclick="loadStudents()">Load Students</button>

<ul id="student-list"></ul>

<h3>Tambah Student</h3>

<label>User ID: <input id="user_id" type="number" placeholder="User ID"></label><br>
<label>Status:
    <select id="status">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
    </select>
</label><br>
<label>Type:
    <select id="type">
        <option value="regular">Regular</option>
        <option value="premium">Premium</option>
    </select>
</label><br><br>

<button onclick="saveStudent()">Save Student</button>

<script>
async function loadStudents() {
    const res = await fetch('/api/students');
    const data = await res.json();

    let html = '';
    data.forEach(s => {
        const name = s.user ? s.user.name : 'Unknown';
        html += `<li>
            [ID: ${s.id}] ${name} — Status: ${s.status} | Type: ${s.type}
            <button onclick="editStudent(${s.id}, ${s.user_id}, '${s.status}', '${s.type}')">Edit</button>
        </li>`;
    });

    document.getElementById('student-list').innerHTML = html || '<li>No students found.</li>';
}

function editStudent(id, userId, status, type) {
    document.getElementById('user_id').value = userId;
    document.getElementById('status').value = status;
    document.getElementById('type').value = type;
    document.getElementById('save-btn').textContent = 'Update Student';
    document.getElementById('save-btn').onclick = () => updateStudent(id);
}

async function saveStudent() {
    const payload = {
        user_id: document.getElementById('user_id').value,
        status:  document.getElementById('status').value,
        type:    document.getElementById('type').value,
    };

    if (!payload.user_id) {
        alert('User ID wajib diisi.');
        return;
    }

    const res = await fetch('/api/students', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    const data = await res.json();
    if (!res.ok) {
        alert('Error: ' + (data.message || JSON.stringify(data)));
        return;
    }

    alert('Student berhasil ditambahkan!');
    clearForm();
    loadStudents();
}

async function updateStudent(id) {
    const payload = {
        status: document.getElementById('status').value,
        type:   document.getElementById('type').value,
    };

    const res = await fetch(`/api/students/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    const data = await res.json();
    if (!res.ok) {
        alert('Error: ' + (data.message || JSON.stringify(data)));
        return;
    }

    alert('Student berhasil diupdate!');
    clearForm();
    loadStudents();
}

function clearForm() {
    document.getElementById('user_id').value = '';
    document.getElementById('status').value = 'active';
    document.getElementById('type').value = 'regular';
    document.getElementById('save-btn').textContent = 'Save Student';
    document.getElementById('save-btn').onclick = saveStudent;
}

// ganti tombol save supaya bisa di-reassign onclick-nya
document.querySelector('button[onclick="saveStudent()"]').id = 'save-btn';

loadStudents();
</script>

@endsection
