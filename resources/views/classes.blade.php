@extends('layout')

@section('content')

<h2>Classes</h2>

<button onclick="loadClasses()">Load Classes</button>
<button onclick="loadClasses()">Reload</button>
<ul id="classes"></ul>

<script>
async function loadClasses() {
    const res = await fetch('/api/classes');
    const data = await res.json();
    console.log('Classes data:', data);

    let html = '';
    data.forEach(c => {
        html += `<li>${c.name} (Sessions: ${c.sessions ? c.sessions.length : 0}) <button onclick="deleteClass(${c.id})">Delete</button></li>`;
    });

    document.getElementById('classes').innerHTML = html;
}
</script>
<h3>Create Class</h3>

<input id="class_name" placeholder="Class Name">
<input id="price" placeholder="Price">
<input id="total_sessions" placeholder="Total Sessions">

<button onclick="createClass()">Create</button>

<script>
    
async function createClass() {

    const payload = {
        name: document.getElementById('class_name').value,
        total_sessions: document.getElementById('total_sessions').value,
        price: document.getElementById('price').value
    };

    console.log(payload);

    const res = await fetch('http://127.0.0.1:8000/api/classes', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    const text = await res.text();

    console.log(text);

    loadClasses();
}

async function deleteClass(id) {
    await fetch(`/api/classes/${id}`, {
        method: 'DELETE'
    });

    loadClasses();
}

</script>



@endsection