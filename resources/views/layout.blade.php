<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<h1>Scheduling System</h1>

<style>
  nav a { margin-right: 4px; }
  nav .step { font-size: 0.75em; color: #888; }
</style>
<nav>
    <a href="/"><strong> Home (Role Switcher)</strong></a> |

    @php
        $currentRole = null;
        if (auth()->check()) {
            $currentRole = auth()->user()->role ? auth()->user()->role->slug : null;
        } elseif (session('simulated_role')) {
            $currentRole = session('simulated_role');
        }
    @endphp

    @if ($currentRole === 'admin')
        <span class="step">[Admin]</span>
        <a href="/admin/users">Users</a> |
        <a href="/admin/students">Students</a> |
        <a href="/admin/classes">Classes</a> |
        <a href="/admin/enrollments">Enrollments</a> |
        <a href="/admin/sessions">Sessions</a> |
        <a href="/admin/schedule"> Schedule</a> |
        <a href="/admin/certificates">Certificates</a> |
        <a href="/admin/kpi">KPI</a>
    @elseif ($currentRole === 'teacher')
        <span class="step">[Teacher]</span>
        <a href="/teacher/my-classes">My Classes</a> |
        <a href="/teacher/availability">Availability</a> |
        <a href="/teacher/booking">Booking</a> |
        <a href="/teacher/requests">Requests</a> |
        <a href="/teacher/feedback">Feedback</a> |
        <a href="/teacher/salary">Salary</a> |
        <a href="/teacher/kpi">KPI</a>
    @elseif ($currentRole === 'student')
        <span class="step">[Student]</span>
        <a href="/student/my-classes">My Classes</a> |
        <a href="/student/teachers">Teachers</a> |
        <a href="/student/my-feedback">My Feedback</a> |
        <a href="/student/my-certificates">My Certificates</a>
    @else
        <span style="color: #d93025; font-weight: bold;">[GUEST - Please login or choose a role on Home]</span>
    @endif

    &nbsp;&nbsp;|&nbsp;&nbsp;

    @if (auth()->check())
        <strong>{{ auth()->user()->name }}</strong> ({{ $currentRole }})
        <form action="/logout" method="POST" style="display: inline;">
            @csrf
            <button type="submit">Logout</button>
        </form>
    @elseif (session('simulated_role'))
        <strong>Simulated {{ ucfirst(session('simulated_role')) }}</strong>
        <form action="/simulate-logout" method="POST" style="display: inline;">
            @csrf
            <button type="submit">Clear Role</button>
        </form>
    @else
        <a href="/login"><strong>Login</strong></a>
    @endif
</nav>

<hr>

@if (session('error'))
    <div style="background-color: #fce8e6; color: #c5221f; border: 1px solid #fad2cf; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
        <strong>Error:</strong> {{ session('error') }}
    </div>
@endif

@if (session('success'))
    <div style="background-color: #e6f4ea; color: #137333; border: 1px solid #ceead6; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
        <strong>Success:</strong> {{ session('success') }}
    </div>
@endif

@yield('content')

<script>
// Global fetch interceptor: auto-inject CSRF token & Accept header for all API calls
(function() {
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        if (typeof url === 'string' && url.startsWith('/api/')) {
            options.headers = options.headers || {};
            if (options.headers instanceof Headers) {
                if (!options.headers.has('X-CSRF-TOKEN')) {
                    const token = document.querySelector('meta[name="csrf-token"]');
                    if (token) options.headers.set('X-CSRF-TOKEN', token.getAttribute('content'));
                }
                if (!options.headers.has('Accept')) {
                    options.headers.set('Accept', 'application/json');
                }
            } else {
                if (!options.headers['X-CSRF-TOKEN']) {
                    const token = document.querySelector('meta[name="csrf-token"]');
                    if (token) options.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
                }
                if (!options.headers['Accept']) {
                    options.headers['Accept'] = 'application/json';
                }
            }
        }
        return originalFetch.call(this, url, options);
    };
})();
</script>

</body>
</html>