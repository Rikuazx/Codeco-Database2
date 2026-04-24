<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;


class AttendanceController extends Controller
{
   public function markAttendance(Request $request)
{
    $request->validate([
        'class_session_id' => 'required|exists:class_sessions,id',
        'student_id' => 'required|exists:students,id',
        'status' => 'required|in:present,absent',
    ]);

    $attendance = Attendance::updateOrCreate(
        [
            'class_session_id' => $request->class_session_id,
            'student_id' => $request->student_id,
        ],
        [
            'status' => $request->status,
        ]
    );

    return response()->json([
        'message' => 'Attendance recorded',
        'data' => $attendance
    ]);
}
}
