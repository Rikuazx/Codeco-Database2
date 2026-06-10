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
    <a href="/"><strong> Home (Role Switcher)</strong></a> |

    @if (session('simulated_role') === 'admin')
        <span class="step">[Admin]</span>
        <a href="/admin/users">Users</a> |
        <a href="/admin/students">Students</a> |
        <a href="/admin/classes">Classes</a> |
        <a href="/admin/enrollments">Enrollments</a> |
        <a href="/admin/sessions">Sessions</a> |
        <a href="/admin/schedule"> Schedule</a> |
        <a href="/admin/certificates">Certificates</a>
    @elseif (session('simulated_role') === 'teacher')
        <span class="step">[Teacher]</span>
        <a href="/teacher/my-classes">My Classes</a> |
        <a href="/teacher/availability">Availability</a> |
        <a href="/teacher/feedback">Feedback</a> |
        <a href="/teacher/salary">Salary</a>
    @elseif (session('simulated_role') === 'student')
        <span class="step">[Student]</span>
        <a href="/student/my-classes">My Classes</a> |
        <a href="/student/teachers">Teachers</a> |
        <a href="/student/my-feedback">My Feedback</a> |
        <a href="/student/my-certificates">My Certificates</a>
    @else
        <span style="color: #d93025; font-weight: bold;">[GUEST - Please choose a role on Home]</span>
    @endif
</nav>

<hr>

@yield('content')

</body>
</html>