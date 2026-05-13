<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
</head>
<body>

<h1>Scheduling System</h1>

<style>
  nav a { margin-right: 4px; }
  nav .step { font-size: 0.75em; color: #888; }
</style>
<nav>
    <span class="step">① </span><a href="/users">Users</a> |
    <span class="step">② </span><a href="/students">Students</a> |
    <span class="step">③ </span><a href="/classes">Classes</a> |
    <span class="step">④ </span><a href="/enrollments">Enrollments</a> |
    <span class="step">⑤ </span><a href="/sessions">Sessions</a> |
    <span class="step">⑥ </span><a href="/attendance">Attendance</a> |
    <span class="step">⑦ </span><a href="/feedback">Feedback</a> |
    <span class="step">⑧ </span><a href="/certificates">Certificates (Admin)</a> |
    <span class="step">⑨ </span><a href="/my-certificates"><strong>My Certificates</strong></a>
</nav>

<hr>

@yield('content')

</body>
</html>