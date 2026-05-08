@extends('layout')

@section('content')

<h2>Feedback</h2>

<button onclick="loadFeedback()">Load Feedback</button>

<h3>Submit Feedback</h3>
<input id="teacher_id" placeholder="Teacher ID">
<input id="student_id" placeholder="Student ID">
<input id="class_session_id" placeholder="Session ID">
<input id="rating" placeholder="Rating (1-5)">
<input id="comment_text" placeholder="Comment">
<button onclick="createFeedback()">Submit</button>

<ul id="feedback"></ul>

<script>
async function loadFeedback() {
    const res = await fetch('/api/feedback');
    const data = await res.json();

    let html = '';
    data.forEach(f => {
        html += `<li>Session ${f.class_session_id} - Rating: ${f.rating} <button onclick="deleteFeedback(${f.id})">Delete</button></li>`;
    });

    document.getElementById('feedback').innerHTML = html;
}

async function createFeedback() {
    await fetch('/api/feedback', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            teacher_id: document.getElementById('teacher_id').value,
            student_id: document.getElementById('student_id').value,
            class_session_id: document.getElementById('class_session_id').value,
            rating: document.getElementById('rating').value || null,
            comment: document.getElementById('comment_text').value || null
        })
    });

    document.getElementById('teacher_id').value = '';
    document.getElementById('student_id').value = '';
    document.getElementById('class_session_id').value = '';
    document.getElementById('rating').value = '';
    document.getElementById('comment_text').value = '';
    loadFeedback();
}

async function deleteFeedback(id) {
    await fetch(`/api/feedback/${id}`, {
        method: 'DELETE'
    });

    loadFeedback();
}
</script>

@endsection