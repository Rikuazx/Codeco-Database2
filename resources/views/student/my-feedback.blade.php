@extends('layout')

@section('content')

<h2>My Feedback (Student)</h2>
<p>Lihat feedback dari teacher untuk setiap sesi yang kamu ikuti.</p>

<label>Student ID: <input id="student_id" type="number" placeholder="Masukkan Student ID"></label>
<button onclick="loadFeedback()">Load Feedback</button>

<h3>Feedback dari Teacher</h3>
<ul id="feedback-list"><li>Masukkan Student ID dan klik Load.</li></ul>

<script>
async function loadFeedback() {
    const sid = document.getElementById('student_id').value;
    if (!sid) {
        alert('Masukkan Student ID terlebih dahulu.');
        return;
    }

    const res  = await fetch(`/api/students/${sid}/feedback`);
    const json = await res.json();

    if (!res.ok) {
        alert('Error: ' + (json.message || 'Gagal memuat feedback.'));
        return;
    }

    const data = json.data ?? [];

    if (data.length === 0) {
        document.getElementById('feedback-list').innerHTML =
            '<li>Belum ada feedback dari teacher. Teacher akan mengisi feedback setelah sesi selesai.</li>';
        return;
    }

    let html = '';
    data.forEach(f => {
        const stars  = f.rating ? '★'.repeat(f.rating) + '☆'.repeat(5 - f.rating) : '—';
        const comment = f.comment || '(tidak ada komentar)';
        html += `<li>
            Kelas: ${f.class_name} | Tanggal: ${f.session_date} |
            Teacher: ${f.teacher_name} | Rating: ${stars} (${f.rating ?? '—'}/5) |
            Komentar: ${comment}
        </li>`;
    });

    document.getElementById('feedback-list').innerHTML = html;
}
</script>

@endsection
