<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ClassSession;
use App\Models\ScheduleChangeRequest;
use App\Models\TeacherAvailability;

class ScheduleChangeRequestController extends Controller
{
    /**
     * GET /api/schedule-change-requests
     * List semua reschedule requests, support filter by status
     */
    public function index(Request $request)
    {
        $query = ScheduleChangeRequest::with(['classSession.class', 'teacher.user'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $requests = $query->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'class_session_id' => $r->class_session_id,
                'teacher_id' => $r->teacher_id,
                'teacher_name' => $r->teacher?->user?->name ?? "Teacher #{$r->teacher_id}",
                'class_name' => $r->classSession?->class?->name ?? "Session #{$r->class_session_id}",
                'session_start_time' => $r->classSession?->start_time,
                'session_end_time' => $r->classSession?->end_time,
                'reason' => $r->reason,
                'proof_file' => $r->proof_file,
                'status' => $r->status,
                'admin_notes' => $r->admin_notes,
                'reviewed_at' => $r->reviewed_at,
                'new_date' => $r->new_date,
                'new_start_time' => $r->new_start_time,
                'new_end_time' => $r->new_end_time,
                'requested_at' => $r->requested_at,
                'created_at' => $r->created_at,
            ];
        });

        return response()->json(['data' => $requests]);
    }

    /**
     * POST /api/schedule-change-requests
     * Teacher submit reschedule request — hanya untuk sesi minggu 2 (tentatif)
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_session_id' => 'required|exists:class_sessions,id',
            'teacher_id' => 'required|exists:teachers,id',
            'reason' => 'required|string|min:10',
            'proof_file' => 'required|file|mimes:pdf,png,jpg,jpeg|max:2048',
            'new_date' => 'nullable|date_format:Y-m-d',
            'new_start_time' => 'nullable|date',
            'new_end_time' => 'nullable|date',
        ]);

        $session = ClassSession::findOrFail($request->class_session_id);

        // 🧠 Enforce that the class session belongs to this teacher
        if ($session->teacher_id != $request->teacher_id) {
            return response()->json([
                'error' => 'Sesi kelas ini tidak ditugaskan kepada teacher yang dimaksud.'
            ], 400);
        }

        // 🧠 Enforce H-1 limit (must submit at least 24 hours before class starts)
        $sessionStart = Carbon::parse($session->start_time);
        if ($sessionStart->isBefore(now()->addDay())) {
            return response()->json([
                'error' => 'Perubahan jadwal hanya diperbolehkan maksimal H-1 sebelum kelas berlangsung.'
            ], 400);
        }

        // 🧠 Check that the session falls within week 2 (tentative) availability
        $sessionDate = $sessionStart->toDateString();
        $tentativeAvailability = TeacherAvailability::where('teacher_id', $request->teacher_id)
            ->where('date', $sessionDate)
            ->where('week_number', 2)
            ->where('week_status', 'tentative')
            ->first();

        if (!$tentativeAvailability) {
            return response()->json([
                'error' => 'Reschedule hanya diperbolehkan untuk sesi pada jadwal Minggu 2 (Tentatif). Sesi pada Minggu 1 (Tetap) tidak dapat diubah melalui fitur ini.'
            ], 403);
        }

        // 🧠 Prevent duplicate pending requests for the same session
        $existingPending = ScheduleChangeRequest::where('class_session_id', $request->class_session_id)
            ->where('teacher_id', $request->teacher_id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return response()->json([
                'error' => 'Sudah ada request reschedule yang masih pending untuk sesi ini. Tunggu konfirmasi admin terlebih dahulu.'
            ], 409);
        }

        // 🧠 Save proof file
        $path = $request->file('proof_file')->store('proofs', 'public');

        $changeRequest = ScheduleChangeRequest::create([
            'class_session_id' => $request->class_session_id,
            'teacher_id' => $request->teacher_id,
            'reason' => $request->reason,
            'proof_file' => $path,
            'status' => 'pending',
            'new_date' => $request->new_date,
            'new_start_time' => $request->new_start_time,
            'new_end_time' => $request->new_end_time,
            'requested_at' => now(),
        ]);

        return response()->json([
            'message' => 'Reschedule request berhasil diajukan. Menunggu konfirmasi admin.',
            'data' => $changeRequest
        ], 201);
    }

    /**
     * POST /api/schedule-change-requests/{id}/approve
     * Admin approve reschedule request
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string',
        ]);

        $changeRequest = ScheduleChangeRequest::findOrFail($id);

        if ($changeRequest->status !== 'pending') {
            return response()->json([
                'error' => 'Hanya request dengan status pending yang dapat disetujui.'
            ], 400);
        }

        $changeRequest->update([
            'status' => 'approved',
            'admin_notes' => $request->admin_notes,
            'reviewed_at' => now(),
        ]);

        // 🧠 Update the ClassSession schedule accordingly
        $session = $changeRequest->classSession;
        $oldSessionDate = Carbon::parse($session->start_time)->toDateString();

        if ($changeRequest->new_date || $changeRequest->new_start_time || $changeRequest->new_end_time) {
            $updateData = ['status' => 'scheduled'];

            if ($changeRequest->new_start_time && $changeRequest->new_end_time) {
                $updateData['start_time'] = $changeRequest->new_start_time;
                $updateData['end_time'] = $changeRequest->new_end_time;
            }

            // Jika ada new_date, update tanggal pada start_time dan end_time
            if ($changeRequest->new_date && $session->start_time && $session->end_time) {
                $oldStart = Carbon::parse($session->start_time);
                $oldEnd = Carbon::parse($session->end_time);
                $newDate = Carbon::parse($changeRequest->new_date);

                $updateData['start_time'] = $newDate->copy()->setTime($oldStart->hour, $oldStart->minute, $oldStart->second);
                $updateData['end_time'] = $newDate->copy()->setTime($oldEnd->hour, $oldEnd->minute, $oldEnd->second);

                // Override with new times if also provided
                if ($changeRequest->new_start_time) {
                    $updateData['start_time'] = $changeRequest->new_start_time;
                }
                if ($changeRequest->new_end_time) {
                    $updateData['end_time'] = $changeRequest->new_end_time;
                }
            }

            $session->update($updateData);

            // 🧠 Sync TeacherAvailability records
            $teacherId = $changeRequest->teacher_id;
            $finalStart = Carbon::parse($updateData['start_time'] ?? $session->start_time);
            $finalEnd = Carbon::parse($updateData['end_time'] ?? $session->end_time);
            $newSessionDate = $finalStart->toDateString();

            if ($changeRequest->new_date && $newSessionDate !== $oldSessionDate) {
                // Tanggal berubah: ambil metadata sebelum dihapus
                $oldAvail = TeacherAvailability::where('teacher_id', $teacherId)
                    ->where('date', $oldSessionDate)
                    ->first();

                $periodStart = $oldAvail->period_start ?? $newSessionDate;
                $periodEnd = $oldAvail->period_end ?? $newSessionDate;
                $weekNumber = $oldAvail->week_number ?? 2;

                // Hapus availability lama agar teacher lain bisa mengisi tanggal tersebut
                TeacherAvailability::where('teacher_id', $teacherId)
                    ->where('date', $oldSessionDate)
                    ->delete();

                // Buat/update availability baru untuk tanggal baru
                TeacherAvailability::updateOrCreate(
                    [
                        'teacher_id' => $teacherId,
                        'date' => $newSessionDate,
                    ],
                    [
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'week_number' => $weekNumber,
                        'week_status' => 'confirmed',
                        'is_locked' => false,
                        'type' => 'time_range',
                        'start_time' => $finalStart->toTimeString(),
                        'end_time' => $finalEnd->toTimeString(),
                        'submitted_at' => now(),
                    ]
                );
            } else {
                // Tanggal sama, update waktu di availability
                TeacherAvailability::where('teacher_id', $teacherId)
                    ->where('date', $oldSessionDate)
                    ->update([
                        'type' => 'time_range',
                        'start_time' => $finalStart->toTimeString(),
                        'end_time' => $finalEnd->toTimeString(),
                        'week_status' => 'confirmed',
                    ]);
            }
        } else {
            // No new date/time provided → cancel the session
            $session->update(['status' => 'cancelled']);

            // 🧠 Hapus availability lama agar teacher lain bisa mengisi tanggal tersebut
            TeacherAvailability::where('teacher_id', $changeRequest->teacher_id)
                ->where('date', $oldSessionDate)
                ->delete();
        }

        return response()->json([
            'message' => 'Reschedule request disetujui.',
            'data' => $changeRequest
        ]);
    }

    /**
     * POST /api/schedule-change-requests/{id}/reject
     * Admin reject reschedule request
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string',
        ]);

        $changeRequest = ScheduleChangeRequest::findOrFail($id);

        if ($changeRequest->status !== 'pending') {
            return response()->json([
                'error' => 'Hanya request dengan status pending yang dapat ditolak.'
            ], 400);
        }

        $changeRequest->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Reschedule request ditolak.',
            'data' => $changeRequest
        ]);
    }
}
