<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TeacherKpiController extends Controller
{
    public function calculateMonthlyKpi($teacher_id, $month, $year)
{
    
    $feedbacks = Feedback::where('teacher_id', $teacher_id)
    ->whereMonth('created_at', $month)
    ->count();

$totalSessions = ClassSession::where('teacher_id', $teacher_id)
    ->whereMonth('session_date', $month)
    ->count();

$feedbackScore = $totalSessions > 0 
    ? ($feedbacks / $totalSessions) * 30 
    : 0;

    $attended = ClassSession::where('teacher_id', $teacher_id)
    ->where('status', 'completed')
    ->whereMonth('session_date', $month)
    ->count();

$scheduled = ClassSession::where('teacher_id', $teacher_id)
    ->whereMonth('session_date', $month)
    ->count();

$attendanceScore = $scheduled > 0 
    ? ($attended / $scheduled) * 40 
    : 0;

    $expected = 2;

$submitted = TeacherAvailability::where('teacher_id', $teacher_id)
    ->whereMonth('period_start', $month)
    ->distinct('period_start')
    ->count();

$availabilityScore = ($submitted / $expected) * 30;


    // save result
    \App\Models\TeacherKpi::updateOrCreate(
        [
            'teacher_id' => $teacher_id,
            'month' => $month,
            'year' => $year,
        ],
        [
            'feedback_score' => $feedbackScore,
            'attendance_score' => $attendanceScore,
            'availability_score' => $availabilityScore,
            'total_score' => $total,
        ]
    );

    return response()->json([
        'message' => 'KPI calculated',
        'total_score' => $total
    ]);
}
}
