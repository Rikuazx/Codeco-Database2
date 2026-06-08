<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\TeacherAvailability;
use App\Models\AvailabilitySubmission;

class TeacherAvailabilityController extends Controller
{
    public function store(Request $request)
    {
        // 🧠 Normalize request to support single slot or array format
        if (!$request->has('availabilities') && $request->has('type')) {
            $request->merge([
                'availabilities' => [
                    [
                        'date' => $request->input('date'),
                        'type' => $request->input('type'),
                        'start_time' => $request->input('start_time'),
                        'end_time' => $request->input('end_time'),
                    ]
                ]
            ]);
        }

        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'availabilities' => 'required|array',
            'availabilities.*.date' => 'required|date_format:Y-m-d',
            'availabilities.*.type' => 'required|in:time_range,full_day,unavailable',
            'availabilities.*.start_time' => 'nullable',
            'availabilities.*.end_time' => 'nullable',
        ]);

        $now = now();
        
        // 🧠 2-week period starts next Monday and ends 14 days later
        $periodStart = now()->addWeek()->startOfWeek()->toDateString();
        $periodEnd = now()->addWeek()->startOfWeek()->addDays(13)->toDateString();

        // 🧠 Deadline check: Friday at 18:00 WIB of the current week
        $deadline = now()->startOfWeek()->addDays(4)->setTime(18, 0, 0);
        $isLate = $now->gt($deadline);

        $availabilitiesInput = $request->input('availabilities');
        $availableCount = 0;

        // 🧠 Validate input dates and time ranges
        foreach ($availabilitiesInput as $index => $item) {
            $date = Carbon::parse($item['date']);
            if ($date->lt(Carbon::parse($periodStart)) || $date->gt(Carbon::parse($periodEnd))) {
                return response()->json([
                    'error' => "Date {$item['date']} is outside the allowed 2-week period ($periodStart to $periodEnd)."
                ], 400);
            }

            if ($item['type'] === 'time_range') {
                if (empty($item['start_time']) || empty($item['end_time'])) {
                    return response()->json([
                        'error' => "Start and end time are required for time_range at index $index."
                    ], 400);
                }
            }

            if (in_array($item['type'], ['full_day', 'time_range'])) {
                $availableCount++;
            }
        }

        // 🧠 Enforce minimum 2 sessions rule
        if ($availableCount < 2) {
            return response()->json([
                'error' => 'Minimal availability di booking system sejumlah minimal 2 sesi dalam 2 minggu.'
            ], 400);
        }

        // 🧠 Save records in a database transaction
        $data = DB::transaction(function () use ($request, $periodStart, $periodEnd, $availabilitiesInput, $now, $isLate) {
            // Clear existing records for this teacher in this 2-week period
            TeacherAvailability::where('teacher_id', $request->teacher_id)
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->delete();

            $providedDates = [];
            foreach ($availabilitiesInput as $item) {
                $providedDates[$item['date']] = $item;
            }

            $savedAvailabilities = [];
            $startDate = Carbon::parse($periodStart);

            // Populate all 14 days (missing days default to unavailable)
            for ($i = 0; $i < 14; $i++) {
                $currentDate = $startDate->copy()->addDays($i)->toDateString();

                if (isset($providedDates[$currentDate])) {
                    $item = $providedDates[$currentDate];
                    $savedAvailabilities[] = TeacherAvailability::create([
                        'teacher_id' => $request->teacher_id,
                        'date' => $currentDate,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'type' => $item['type'],
                        'start_time' => $item['type'] === 'time_range' ? $item['start_time'] : null,
                        'end_time' => $item['type'] === 'time_range' ? $item['end_time'] : null,
                        'submitted_at' => $now,
                    ]);
                } else {
                    $savedAvailabilities[] = TeacherAvailability::create([
                        'teacher_id' => $request->teacher_id,
                        'date' => $currentDate,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'type' => 'unavailable',
                        'start_time' => null,
                        'end_time' => null,
                        'submitted_at' => $now,
                    ]);
                }
            }

            // Save submission metadata
            AvailabilitySubmission::updateOrCreate(
                [
                    'teacher_id' => $request->teacher_id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ],
                [
                    'submitted_at' => $now,
                    'is_late' => $isLate,
                ]
            );

            return $savedAvailabilities;
        });

        return response()->json([
            'message' => $isLate ? 'Submitted late' : 'Submitted on time',
            'data' => $data
        ]);
    }

    public function show($teacher_id)
    {
        $availabilities = TeacherAvailability::where('teacher_id', $teacher_id)
            ->orderBy('date', 'asc')
            ->get();
        return response()->json($availabilities);
    }

    // GET /api/teacher-availability  (all teachers, for conflict detection)
    public function all()
    {
        $availabilities = TeacherAvailability::with('teacher.user')
            ->where('type', '!=', 'unavailable')
            ->orderBy('date', 'asc')
            ->get()
            ->map(fn($a) => [
                'teacher_id'   => $a->teacher_id,
                'teacher_name' => $a->teacher?->user?->name ?? "Teacher #{$a->teacher_id}",
                'date'         => $a->date,
                'type'         => $a->type,
                'start_time'   => $a->start_time,
                'end_time'     => $a->end_time,
            ]);

        return response()->json(['data' => $availabilities]);
    }
}

