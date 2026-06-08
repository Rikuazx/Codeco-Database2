@extends('layout')

@section('content')

<h2>Simulated Auth & RBAC Switcher</h2>

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

<div style="display: flex; gap: 20px; margin-bottom: 20px;">
    <!-- Admin Card -->
    <div style="border: 1px solid #ccc; padding: 15px; border-radius: 8px; flex: 1; background-color: #f9f9f9; text-align: center;">
        <h3>Admin Role</h3>
        <p style="color: #666; font-size: 0.9em; min-height: 50px;">Access to User Management, Classes, Sessions, and Certificates.</p>
        <form action="/simulate-login" method="POST">
            @csrf
            <input type="hidden" name="role" value="admin">
            <button type="submit" style="background-color: #1a73e8; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                Simulate Admin
            </button>
        </form>
    </div>

    <!-- Teacher Card -->
    <div style="border: 1px solid #ccc; padding: 15px; border-radius: 8px; flex: 1; background-color: #f9f9f9; text-align: center;">
        <h3>Teacher Role</h3>
        <p style="color: #666; font-size: 0.9em; min-height: 50px;">Access to Availability schedules, Mark Attendance, and Submit Feedback.</p>
        <form action="/simulate-login" method="POST">
            @csrf
            <input type="hidden" name="role" value="teacher">
            <button type="submit" style="background-color: #12b5cb; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                Simulate Teacher
            </button>
        </form>
    </div>

    <!-- Student Card -->
    <div style="border: 1px solid #ccc; padding: 15px; border-radius: 8px; flex: 1; background-color: #f9f9f9; text-align: center;">
        <h3>Student Role</h3>
        <p style="color: #666; font-size: 0.9em; min-height: 50px;">Access to My Certificates area.</p>
        <form action="/simulate-login" method="POST">
            @csrf
            <input type="hidden" name="role" value="student">
            <button type="submit" style="background-color: #e8710a; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                Simulate Student
            </button>
        </form>
    </div>
</div>

<div style="border-top: 1px solid #eee; padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <strong>Current Simulated Role:</strong> 
        @if (session('simulated_role'))
            <span style="background-color: #e8f0fe; color: #1a73e8; padding: 4px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase;">
                {{ session('simulated_role') }}
            </span>
        @else
            <span style="background-color: #f1f3f4; color: #5f6368; padding: 4px 8px; border-radius: 4px; font-weight: bold;">
                GUEST (No role selected)
            </span>
        @endif
    </div>

    @if (session('simulated_role'))
        <form action="/simulate-logout" method="POST">
            @csrf
            <button type="submit" style="background-color: #d93025; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                Clear Simulated Role
            </button>
        </form>
    @endif
</div>

@endsection
