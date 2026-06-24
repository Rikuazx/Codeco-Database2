@extends('layout')

@section('content')

<h2>Login</h2>

@if ($errors->any())
    <div style="background-color: #fce8e6; color: #c5221f; border: 1px solid #fad2cf; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
        <strong>Error:</strong> {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="/login">
    @csrf
    <div style="margin-bottom: 10px;">
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
    </div>

    <div style="margin-bottom: 10px;">
        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" placeholder="Password" required>
    </div>

    <div style="margin-bottom: 10px;">
        <label>
            <input type="checkbox" name="remember" id="remember"> Remember me
        </label>
    </div>

    <button type="submit">Login</button>
</form>

@endsection
