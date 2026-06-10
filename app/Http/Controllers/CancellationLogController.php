<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\CancellationLog;
use App\Models\ClassSession;
use App\Models\Teacher;

class CancellationLogController extends Controller
{
    // 🧠 POST /api/cancellation-logs — Teacher membatalkan kelas
    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'class_session_id' => 'required|exists:class_sessions,id',
            'reason' => 'required|string|min:10',
            'proof_file' => 'required|file|mimes:pdf,png,jpg,jpeg|max:2048',
        ]);

        $session = ClassSession::findOrFail($request->class_session_id);

        // 🧠 Pastikan session milik teacher ini
        if ($session->teacher_id != $request->teacher_id) {
            return response()->json([
                'error' => 'Sesi kelas ini bukan milik teacher yang ditentukan.'
            ], 400);
        }

        // 🧠 Enforce H-1: pembatalan harus dilakukan minimal 1 hari sebelum kelas
        $sessionStart = Carbon::parse($session->start_time);
        if ($sessionStart->isBefore(now()->addDay())) {
            return response()->json([
                'error' => 'Pembatalan hanya diperbolehkan maksimal H-1 (24 jam) sebelum kelas dimulai.'
            ], 400);
        }

        // 🧠 Cek apakah sudah pernah dibatalkan
        $existingCancel = CancellationLog::where('class_session_id', $request->class_session_id)->first();
        if ($existingCancel) {
            return response()->json([
                'error' => 'Sesi kelas ini sudah pernah dibatalkan sebelumnya.'
            ], 400);
        }

        // 🧠 Upload bukti
        $proofPath = $request->file('proof_file')->store('cancellation_proofs', 'public');

        // 🧠 Simpan log pembatalan
        $log = CancellationLog::create([
            'teacher_id' => $request->teacher_id,
            'class_session_id' => $request->class_session_id,
            'reason' => $request->reason,
            'proof_file' => $proofPath,
            'cancelled_at' => now(),
            'is_valid' => false, // Menunggu validasi admin
        ]);

        // 🧠 Update status session menjadi cancelled
        $session->update(['status' => 'cancelled']);

        // 🧠 Cek sanksi: jika sudah >2 pembatalan bulan ini, kurangi priority
        $teacher = Teacher::findOrFail($request->teacher_id);
        $monthlyCount = $teacher->getMonthlyCancellationCount();
        $sanctionApplied = false;

        if ($monthlyCount > 2) {
            // Kurangi priority score
            $newScore = max(0, ($teacher->priority_score ?? 100) - 10);
            $teacher->update(['priority_score' => $newScore]);
            $sanctionApplied = true;
        }

        return response()->json([
            'message' => 'Pembatalan kelas berhasil dicatat.',
            'data' => $log,
            'monthly_cancellations' => $monthlyCount,
            'sanction_applied' => $sanctionApplied,
            'warning' => $monthlyCount >= 2
                ? "Perhatian: Anda sudah membatalkan {$monthlyCount} kelas bulan ini. Maksimal 2 kali per bulan."
                : null,
        ], 201);
    }

    // 🧠 GET /api/cancellation-logs — Admin melihat semua log pembatalan
    public function index(Request $request)
    {
        $query = CancellationLog::with(['teacher.user', 'classSession']);

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('cancelled_at', $request->month)
                  ->whereYear('cancelled_at', $request->year);
        }

        $logs = $query->orderBy('cancelled_at', 'desc')->get()->map(fn($log) => [
            'id' => $log->id,
            'teacher_id' => $log->teacher_id,
            'teacher_name' => $log->teacher?->user?->name ?? "Teacher #{$log->teacher_id}",
            'class_session_id' => $log->class_session_id,
            'reason' => $log->reason,
            'proof_file' => $log->proof_file,
            'cancelled_at' => $log->cancelled_at,
            'is_valid' => $log->is_valid,
        ]);

        return response()->json(['data' => $logs]);
    }

    // 🧠 PUT /api/cancellation-logs/{id}/validate — Admin validasi pembatalan
    public function validateCancellation(Request $request, $id)
    {
        $request->validate([
            'is_valid' => 'required|boolean',
        ]);

        $log = CancellationLog::findOrFail($id);
        $log->update(['is_valid' => $request->is_valid]);

        // 🧠 Jika pembatalan dianggap tidak valid, bisa tambahkan sanksi tambahan
        if (!$request->is_valid) {
            $teacher = Teacher::findOrFail($log->teacher_id);
            $monthlyCount = $teacher->getMonthlyCancellationCount();

            if ($monthlyCount > 2) {
                $newScore = max(0, ($teacher->priority_score ?? 100) - 15);
                $teacher->update(['priority_score' => $newScore]);
            }
        }

        return response()->json([
            'message' => $request->is_valid
                ? 'Pembatalan dinyatakan valid.'
                : 'Pembatalan dinyatakan tidak valid. Sanksi tambahan mungkin diterapkan.',
            'data' => $log,
        ]);
    }

    // 🧠 GET /api/teachers/{teacher_id}/sanction-status — Status sanksi teacher
    public function teacherSanctionStatus($teacher_id)
    {
        $teacher = Teacher::findOrFail($teacher_id);
        $month = now()->month;
        $year = now()->year;
        $count = $teacher->getMonthlyCancellationCount($month, $year);
        $exceeded = $count > 2;

        // Ambil detail pembatalan bulan ini
        $cancellations = CancellationLog::where('teacher_id', $teacher_id)
            ->whereMonth('cancelled_at', $month)
            ->whereYear('cancelled_at', $year)
            ->orderBy('cancelled_at', 'desc')
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'class_session_id' => $log->class_session_id,
                'reason' => $log->reason,
                'cancelled_at' => $log->cancelled_at,
                'is_valid' => $log->is_valid,
            ]);

        return response()->json([
            'teacher_id' => $teacher_id,
            'teacher_name' => $teacher->user?->name ?? "Teacher #{$teacher_id}",
            'month' => $month,
            'year' => $year,
            'cancellation_count' => $count,
            'max_allowed' => 2,
            'exceeded' => $exceeded,
            'priority_score' => $teacher->priority_score,
            'sanctions' => $exceeded ? [
                'priority_reduced' => true,
                'under_evaluation' => true,
                'possible_termination' => $count > 4,
            ] : [
                'priority_reduced' => false,
                'under_evaluation' => false,
                'possible_termination' => false,
            ],
            'cancellations' => $cancellations,
        ]);
    }
}
