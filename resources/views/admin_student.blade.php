@extends('layout')

@section('content')

<h2>Students Management</h2>

<div>
    Total: <span id="total"></span><br>
    Active: <span id="active"></span><br>
    Stopped: <span id="stopped"></span><br>
    Hibernating: <span id="hibernating"></span><br>

    <hr>

    Regular: <span id="regular"></span><br>
    Weekend: <span id="weekend"></span><br>
</div>

<hr>

<button onclick="loadStudents()">Load Students</button>

<ul id="students"></ul>

<h3>Edit Student</h3>

<input id="student_id" hidden>

<input id="student_name" placeholder="Name">

<input id="student_email" placeholder="Email">

<input id="student_password" placeholder="New Password (optional)">

<select id="student_status">
    <option value="active">Active</option>
    <option value="stopped">Stopped</option>
    <option value="hibernating">Hibernating</option>
</select>

<select id="student_type">
    <option value="regular">Regular</option>
    <option value="weekend">Weekend</option>
</select>

<button onclick="saveStudent()">
    Save Student
</button>

<h3>Student Details</h3>

<div id="student_details">
    Select a student...
</div>


<h3>
Showing:
<span id="student_count">0</span>
students
</h3>

<input
    id="search_student"
    placeholder="Search by name"
    onkeyup="loadStudents()">

<select id="filter_status" onchange="loadStudents()">
    <option value="">All Status</option>
    <option value="active">Active</option>
    <option value="stopped">Stopped</option>
    <option value="hibernating">Hibernating</option>
</select>

<select id="filter_type" onchange="loadStudents()">
    <option value="">All Types</option>
    <option value="regular">Regular</option>
    <option value="weekend">Weekend</option>
</select>

<script>

async function loadStats()
{
    const res = await fetch('/api/students/stats');
    const stats = await res.json();

    document.getElementById('total').innerText = stats.total;
    document.getElementById('active').innerText = stats.active;
    document.getElementById('stopped').innerText = stats.stopped;
    document.getElementById('hibernating').innerText = stats.hibernating;

    document.getElementById('regular').innerText = stats.regular;
    document.getElementById('weekend').innerText = stats.weekend;
}
let studentsData = [];

async function loadStudents()
{
    const res = await fetch('/api/students');
    let data = await res.json();

    const search =
        document.getElementById('search_student')
        .value
        .toLowerCase();

    const status =
        document.getElementById('filter_status')
        .value;

    const type =
        document.getElementById('filter_type')
        .value;

    data = data.filter(student => {

        const name =
            student.user.name.toLowerCase();

        const matchesSearch =
            name.includes(search);

        const matchesStatus =
            !status ||
            student.status === status;

        const matchesType =
            !type ||
            student.type === type;

        return (
            matchesSearch &&
            matchesStatus &&
            matchesType
        );
    });

    document.getElementById('student_count')
    .innerText = data.length;
    studentsData = data;

    let html = '';

    data.forEach(student => {

       html += `
        <li>
            <strong>${student.user.name}</strong>
            <br>
            Status: ${student.status}
            <br>
            Type: ${student.type}
            <br>

            <button onclick="editStudent(${student.id})">
                View / Edit
            </button>

            <button onclick="deleteStudent(${student.id})">
                Delete
            </button>
        </li>
        `;
    });

    document.getElementById('students').innerHTML =
        html;
}

function editStudent(id)
{
    const student = studentsData.find(
        s => s.id === id
    );

    if (!student) return;

    document.getElementById('student_id').value =
        student.id;

    document.getElementById('student_name').value =
        student.user.name;

    document.getElementById('student_email').value =
        student.user.email;

    document.getElementById('student_status').value =
        student.status;

    document.getElementById('student_type').value =
        student.type;

    document.getElementById('student_password').value =
        '';

    document.getElementById('student_details').innerHTML = `
        <p><strong>ID:</strong> ${student.id}</p>
        <p><strong>Name:</strong> ${student.user.name}</p>
        <p><strong>Email:</strong> ${student.user.email}</p>
        <p><strong>Status:</strong> ${student.status}</p>
        <p><strong>Type:</strong> ${student.type}</p>
        <p><strong>Registered:</strong> ${student.registration_date}</p>
    `;
}

async function deleteStudent(id)
{
    if (!confirm('Delete this student?'))
    {
        return;
    }

    const res = await fetch(
        `/api/students/${id}`,
        {
            method: 'DELETE'
        }
    );

    const result = await res.json();

    alert(result.message);

    loadStudents();
    loadStats();
}

async function saveStudent()
{
    const id =
        document.getElementById('student_id').value;

    if (!id)
    {
        alert('Select a student first');
        return;
    }

    const payload = {
        name:
            document.getElementById('student_name').value,

        email:
            document.getElementById('student_email').value,

        password:
            document.getElementById('student_password').value,

        status:
            document.getElementById('student_status').value,

        type:
            document.getElementById('student_type').value
    };

    const res = await fetch(
        `/api/students/${id}`,
        {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        }
    );

    const result = await res.json();

    alert(result.message);

    loadStudents();
    loadStats();
}

loadStats();
loadStudents();
</script>

@endsection

