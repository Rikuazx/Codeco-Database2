<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
class TeacherAvailabilityController extends Controller
{
  public function store(Request $request)
{
    $request->validate([
        'teacher_id' => 'required|exists:teachers,id',
        'type' => 'required|in:time_range,full_day,unavailable',
        'start_time' => 'nullable',
        'end_time' => 'nullable',
        
    ]);

    $now = now();

    // 🧠 2-week period
    $periodStart = now()->startOfWeek();
    $periodEnd = (clone $periodStart)->addDays(13);

    // 🧠 deadline check
    $deadline = now()->startOfWeek()->addDays(4)->setTime(18, 0);
    $isLate = $now->gt($deadline);

    // 🧠 validation for time_range
    if ($request->type === 'time_range') {
        if (!$request->start_time || !$request->end_time) {
            return response()->json([
                'error' => 'Start and end time required for time_range'
            ], 400);
        }
    }

    $availability = \App\Models\TeacherAvailability::create([
        'teacher_id' => $request->teacher_id,
        'date' => $periodStart,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'type' => $request->type,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
        'submitted_at' => $now,
    ]);

    return response()->json([
        'message' => $isLate ? 'Submitted late' : 'Submitted on time',
        'data' => $availability
    ]);
}

public function index()
{
    return \App\Models\TeacherAvailability::with(
        'teacher.user'
    )->get();
}

public function destroy($id)
{
    \App\Models\TeacherAvailability::findOrFail($id)
        ->delete();

    return response()->json([
        'message' => 'Availability deleted'
    ]);
}
}
