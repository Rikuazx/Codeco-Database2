<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassSession;
use App\Models\Feedback;
use App\Models\TeacherAvailability;


class TeacherKpiController extends Controller
{
    public function calculateMonthlyKpi($teacher_id, $month, $year)
{
    
    $feedbacks = Feedback::where('teacher_id', $teacher_id)
    ->whereMonth('submitted_at', $month)
    ->count();

$totalSessions = ClassSession::where('teacher_id', $teacher_id)
    ->whereMonth('start_time', $month)
    ->count();

$feedbackScore = $totalSessions > 0 
    ? ($feedbacks / $totalSessions) * 30 
    : 0;

$attended = ClassSession::where('teacher_id', $teacher_id)
    ->where('status', 'completed')
->whereMonth('start_time', $month)
->count();

$scheduled = ClassSession::where('teacher_id', $teacher_id)
    ->whereMonth('start_time', $month)
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

$total = $feedbackScore + $attendanceScore + $availabilityScore;

    if ($total >= 90) {
        $category = 'A';
    }
    elseif ($total >= 75) {
        $category = 'B';
    }
    else {
        $category = 'C';
}

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
        'category' => $category,
    ]
);

    return response()->json([
        'message' => 'KPI calculated',
        'total_score' => $total
    ]);
}

public function index()
{
    return \App\Models\TeacherKpi::with(
        'teacher.user'
    )->get();
}
}
