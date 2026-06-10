<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\TeacherAvailability;
use App\Models\AvailabilitySubmission;
use App\Models\Teacher;

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

        // 🧠 Week boundary: first 7 days = week 1, next 7 = week 2
        $week1End = Carbon::parse($periodStart)->addDays(6)->toDateString();

        // 🧠 Deadline check: Friday at 18:00 WIB of the current week
        $deadline = now()->startOfWeek()->addDays(4)->setTime(18, 0, 0);
        $isLate = $now->gt($deadline);

        $availabilitiesInput = $request->input('availabilities');
        $availableCount = 0;
        $teacherId = $request->teacher_id;

        // 🧠 Check if there are locked slots for week-1 that shouldn't be modified
        $lockedSlots = TeacherAvailability::where('teacher_id', $teacherId)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->where('is_locked', true)
            ->pluck('date')
            ->toArray();

        // 🧠 Validate input dates, time ranges, and locked slots
        foreach ($availabilitiesInput as $index => $item) {
            $date = Carbon::parse($item['date']);
            if ($date->lt(Carbon::parse($periodStart)) || $date->gt(Carbon::parse($periodEnd))) {
                return response()->json([
                    'error' => "Date {$item['date']} is outside the allowed 2-week period ($periodStart to $periodEnd)."
                ], 400);
            }

            // 🧠 Block perubahan pada slot yang sudah di-lock (minggu 1 yang final)
            if (in_array($item['date'], $lockedSlots)) {
                return response()->json([
                    'error' => "Tanggal {$item['date']} sudah berstatus TETAP (locked) dan tidak bisa diubah. Hubungi admin jika perlu perubahan."
                ], 403);
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

        // 🧠 Conflict detection: availability tidak boleh sama dengan teacher lain
        foreach ($availabilitiesInput as $index => $item) {
            // Skip unavailable slots — no conflict possible
            if ($item['type'] === 'unavailable') {
                continue;
            }

            // Find other teachers' availability on the same date (excluding current teacher)
            $conflictQuery = TeacherAvailability::where('date', $item['date'])
                ->where('teacher_id', '!=', $teacherId)
                ->where('type', '!=', 'unavailable');

            if ($item['type'] === 'full_day') {
                // full_day conflicts with ANY other teacher's availability on that date
                $conflicts = $conflictQuery->with('teacher.user')->get();
            } else {
                // time_range: check for time overlap with other teachers
                $conflicts = $conflictQuery->where(function ($q) use ($item) {
                    $q->where(function ($q2) use ($item) {
                        // Other teacher has full_day → always conflicts
                        $q2->where('type', 'full_day');
                    })->orWhere(function ($q2) use ($item) {
                        // Other teacher has time_range with overlapping times
                        $q2->where('type', 'time_range')
                           ->where('start_time', '<', $item['end_time'])
                           ->where('end_time', '>', $item['start_time']);
                    });
                })->with('teacher.user')->get();
            }

            if ($conflicts->isNotEmpty()) {
                $conflictNames = $conflicts->map(function ($c) {
                    return $c->teacher?->user?->name ?? "Teacher #{$c->teacher_id}";
                })->unique()->implode(', ');

                $dateFormatted = $item['date'];
                $timeInfo = $item['type'] === 'time_range'
                    ? " ({$item['start_time']} - {$item['end_time']})"
                    : " (Full Day)";

                return response()->json([
                    'error' => "Konflik jadwal pada tanggal {$dateFormatted}{$timeInfo}: sudah diambil oleh {$conflictNames}. Silakan pilih waktu lain."
                ], 409);
            }
        }

        // 🧠 Enforce minimum 2 sessions rule
        if ($availableCount < 2) {
            return response()->json([
                'error' => 'Minimal availability di booking system sejumlah minimal 2 sesi dalam 2 minggu.'
            ], 400);
        }

        // 🧠 Save records in a database transaction
        $data = DB::transaction(function () use ($request, $periodStart, $periodEnd, $week1End, $availabilitiesInput, $now, $isLate, $teacherId, $lockedSlots) {
            // Clear existing records for this teacher in this 2-week period (except locked ones)
            TeacherAvailability::where('teacher_id', $teacherId)
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->where('is_locked', false)
                ->delete();

            $providedDates = [];
            foreach ($availabilitiesInput as $item) {
                $providedDates[$item['date']] = $item;
            }

            $savedAvailabilities = [];
            $startDate = Carbon::parse($periodStart);

            // Populate all 14 days (missing days default to unavailable, skip locked)
            for ($i = 0; $i < 14; $i++) {
                $currentDate = $startDate->copy()->addDays($i)->toDateString();

                // Skip locked slots — mereka sudah ada di database
                if (in_array($currentDate, $lockedSlots)) {
                    continue;
                }

                // 🧠 Determine week number and status
                $weekNumber = $currentDate <= $week1End ? 1 : 2;
                $weekStatus = $weekNumber === 1 ? 'confirmed' : 'tentative';

                if (isset($providedDates[$currentDate])) {
                    $item = $providedDates[$currentDate];
                    $savedAvailabilities[] = TeacherAvailability::create([
                        'teacher_id' => $teacherId,
                        'date' => $currentDate,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'week_number' => $weekNumber,
                        'week_status' => $weekStatus,
                        'is_locked' => false,
                        'type' => $item['type'],
                        'start_time' => $item['type'] === 'time_range' ? $item['start_time'] : null,
                        'end_time' => $item['type'] === 'time_range' ? $item['end_time'] : null,
                        'submitted_at' => $now,
                    ]);
                } else {
                    $savedAvailabilities[] = TeacherAvailability::create([
                        'teacher_id' => $teacherId,
                        'date' => $currentDate,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'week_number' => $weekNumber,
                        'week_status' => $weekStatus,
                        'is_locked' => false,
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
                    'teacher_id' => $teacherId,
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
                'week_number'  => $a->week_number,
                'week_status'  => $a->week_status,
                'is_locked'    => $a->is_locked,
            ]);

        return response()->json(['data' => $availabilities]);
    }

    // 🧠 POST /api/teacher-availability/lock-week — Lock semua slot minggu-1 (jadwal final)
    public function lockWeekOne(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'period_start' => 'required|date_format:Y-m-d',
        ]);

        $periodStart = $request->period_start;
        $week1End = Carbon::parse($periodStart)->addDays(6)->toDateString();

        $updated = TeacherAvailability::where('teacher_id', $request->teacher_id)
            ->where('week_number', 1)
            ->whereBetween('date', [$periodStart, $week1End])
            ->update([
                'is_locked' => true,
                'week_status' => 'confirmed',
            ]);

        return response()->json([
            'message' => "Minggu pertama telah dikunci (locked). $updated slot diperbarui.",
            'locked_count' => $updated,
        ]);
    }

    // 🧠 POST /api/teacher-availability/promote-week — Promote minggu-2 jadi confirmed
    public function promoteWeekTwo(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'period_start' => 'required|date_format:Y-m-d',
        ]);

        $periodStart = $request->period_start;
        $week2Start = Carbon::parse($periodStart)->addDays(7)->toDateString();
        $week2End = Carbon::parse($periodStart)->addDays(13)->toDateString();

        $updated = TeacherAvailability::where('teacher_id', $request->teacher_id)
            ->where('week_number', 2)
            ->whereBetween('date', [$week2Start, $week2End])
            ->update([
                'week_status' => 'confirmed',
                'is_locked' => true,
            ]);

        return response()->json([
            'message' => "Minggu kedua telah dipromosikan menjadi TETAP (confirmed). $updated slot diperbarui.",
            'promoted_count' => $updated,
        ]);
    }

    // 🧠 GET /api/teachers/{teacher_id}/cancellation-stats
    public function cancellationStats($teacher_id)
    {
        $teacher = Teacher::findOrFail($teacher_id);
        $month = now()->month;
        $year = now()->year;
        $count = $teacher->getMonthlyCancellationCount($month, $year);
        $shouldReduce = $teacher->shouldReducePriority();

        return response()->json([
            'teacher_id' => $teacher_id,
            'month' => $month,
            'year' => $year,
            'cancellation_count' => $count,
            'max_allowed' => 2,
            'exceeded' => $shouldReduce,
            'sanctions' => $shouldReduce ? [
                'Prioritas jadwal dikurangi',
                'Evaluasi kerja sama',
                'Kemungkinan kerja sama dihentikan',
            ] : [],
            'priority_score' => $teacher->priority_score,
        ]);
    }
}
