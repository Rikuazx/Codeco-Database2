@extends('layout')

@section('content')

<h2>Users</h2>

<button onclick="loadUsers()">Load Users</button>

<ul id="users"></ul>

<h3>Create / Edit User</h3>

<input id="user_id" hidden>
<input id="name" placeholder="Name">
<input id="email" placeholder="Email">
<input id="password" placeholder="Password">
<select id="role">
    <option value="student">Student</option>
    <option value="teacher">Teacher</option>
    <option value="admin">Admin</option>
</select>

<button onclick="saveUser()">Save</button>

<script>
let usersData = [];

async function loadUsers() {
    const res = await fetch('/api/users');
    const data = await res.json();
    usersData = data;

    let html = '';
    data.forEach(u => {
        const roleName = u.role ? u.role.name : 'No Role';
        html += `
        <li>
            ${u.name} (${roleName})
            <button onclick="editUser(${u.id})">Edit</button>
            <button onclick="deleteUser(${u.id})">Delete</button>
        </li>`;
    });

    document.getElementById('users').innerHTML = html;
}

function editUser(id) {
    const u = usersData.find(user => user.id === id);
    if (!u) return;

    document.getElementById('user_id').value = u.id;
    document.getElementById('name').value = u.name;
    document.getElementById('email').value = u.email;
    document.getElementById('role').value = u.role ? u.role.slug : 'student';
    document.getElementById('password').value = '';
}

async function saveUser() {
    const userId = document.getElementById('user_id').value;
    const url = userId ? `/api/users/${userId}` : '/api/users';
    const method = userId ? 'PUT' : 'POST';
    const payload = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        role: document.getElementById('role').value
    };

    if (!payload.name || !payload.email || (!userId && !payload.password)) {
        alert('Name, email and password are required for new users.');
        return;
    }

    try {
        const res = await fetch(url, {
            method: method,
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });

        const text = await res.text();
        console.log('Response status:', res.status, 'Content:', text.substring(0, 100));

        let responseData;
        try {
            responseData = JSON.parse(text);
        } catch (parseError) {
            alert('Server error (non-JSON response). Status: ' + res.status + '. Check browser console.');
            console.error('Full response:', text);
            return;
        }

        if (!res.ok) {
            alert('Error: ' + (responseData.error || responseData.message || 'Unable to save user'));
            return;
        }

        document.getElementById('user_id').value = '';
        document.getElementById('name').value = '';
        document.getElementById('email').value = '';
        document.getElementById('password').value = '';
        document.getElementById('role').value = 'student';
        alert('User saved successfully!');
        loadUsers();
    } catch (error) {
        alert('Error: ' + error.message);
        console.error('Full error:', error);
    }
}

loadUsers();

async function deleteUser(id) {
    await fetch(`/api/users/${id}`, {
        method: 'DELETE'
    });

    loadUsers();
}
</script>

@endsection