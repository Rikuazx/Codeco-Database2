<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ClassSession;
use App\Models\Feedback;
use App\Models\TeacherAvailability;
use App\Models\AvailabilitySubmission;
use App\Models\CancellationLog;
use App\Models\TeacherKpi;
use App\Models\Teacher;
use App\Models\Enrollment;

class TeacherKpiController extends Controller
{
    /**
     * POST /api/kpi/calculate/{teacher_id}
     * Hitung KPI bulanan berdasarkan 3 komponen:
     *   - Feedback timeliness (30%)
     *   - Kehadiran & komitmen (40%)
     *   - Kedisiplinan availability (30%)
     */
    public function calculate(Request $request, $teacher_id)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $teacher = Teacher::with('user')->findOrFail($teacher_id);

        // ============================================
        // 1. FEEDBACK TIMELINESS (30%)
        // ============================================
        // Setelah mengajar, mentor harus mengisi feedback form tepat waktu.
        // Skor = (sesi yang feedback-nya lengkap tepat waktu / total sesi completed) * 30

        $completedSessions = ClassSession::where('teacher_id', $teacher_id)
            ->where('status', 'completed')
            ->whereMonth('start_time', $month)
            ->whereYear('start_time', $year)
            ->get();

        $totalCompleted = $completedSessions->count();
        $feedbackOnTime = 0;

        foreach ($completedSessions as $session) {
            // Cek apakah semua feedback untuk sesi ini sudah disubmit
            $enrolledCount = Enrollment::where('class_id', $session->class_id)
                ->whereIn('status', ['active', 'pending', 'completed'])
                ->count();

            $feedbackCount = Feedback::where('class_session_id', $session->id)->count();

            if ($enrolledCount > 0 && $feedbackCount >= $enrolledCount) {
                // Cek apakah feedback terakhir disubmit dalam 48 jam setelah kelas selesai
                $lastFeedback = Feedback::where('class_session_id', $session->id)
                    ->orderBy('submitted_at', 'desc')
                    ->first();

                $sessionEnd = Carbon::parse($session->end_time);
                $deadline = $sessionEnd->copy()->addDays(2);

                if ($lastFeedback && Carbon::parse($lastFeedback->submitted_at)->lte($deadline)) {
                    $feedbackOnTime++;
                }
            }
        }

        $feedbackScore = $totalCompleted > 0
            ? round(($feedbackOnTime / $totalCompleted) * 30, 2)
            : 0;

        // ============================================
        // 2. KEHADIRAN & KOMITMEN MENGAJAR (40%)
        // ============================================
        // - Hadir sesuai jadwal (completed vs total scheduled)
        // - Tidak sering membatalkan kelas
        // - Menjalankan kelas yang sudah disepakati
        //
        // Sub-skor:
        //   a) Completion rate (25%): completed / (scheduled + completed + cancelled)
        //   b) Non-cancellation rate (15%): 1 - (cancellations / total) — penalti per pembatalan

        $allSessions = ClassSession::where('teacher_id', $teacher_id)
            ->whereMonth('start_time', $month)
            ->whereYear('start_time', $year)
            ->get();

        $totalSessions = $allSessions->count();
        $completedCount = $allSessions->where('status', 'completed')->count();
        $cancelledCount = $allSessions->where('status', 'cancelled')->count();

        // a) Completion rate → 25 poin
        $completionRate = $totalSessions > 0
            ? round(($completedCount / $totalSessions) * 25, 2)
            : 0;

        // b) Non-cancellation → 15 poin
        // Setiap pembatalan mengurangi skor. >2 pembatalan = penalti besar
        $cancellationLogs = CancellationLog::where('teacher_id', $teacher_id)
            ->whereMonth('cancelled_at', $month)
            ->whereYear('cancelled_at', $year)
            ->count();

        if ($totalSessions > 0) {
            $cancelRatio = $cancellationLogs / $totalSessions;
            $nonCancelScore = round((1 - $cancelRatio) * 15, 2);
            $nonCancelScore = max(0, $nonCancelScore); // Floor at 0
        } else {
            $nonCancelScore = $cancellationLogs > 0 ? 0 : 15; // Jika tidak ada sesi tapi ada pembatalan = 0
        }

        $attendanceScore = round($completionRate + $nonCancelScore, 2);

        // ============================================
        // 3. KEDISIPLINAN AVAILABILITY (30%)
        // ============================================
        // - Mengirim jadwal ketersediaan tepat waktu
        // - Responsif terhadap proses penjadwalan
        //
        // Dalam 1 bulan ada ~2 periode submission (setiap Jumat utk 2 minggu ke depan)
        // expected = jumlah Jumat dalam bulan tersebut yang relevan (biasanya 4-5, tapi
        //            kita hitung berapa kali seharusnya submit = ~2 siklus 2 mingguan)

        $submissions = AvailabilitySubmission::where('teacher_id', $teacher_id)
            ->whereMonth('submitted_at', $month)
            ->whereYear('submitted_at', $year)
            ->get();

        $totalSubmissions = $submissions->count();
        $onTimeSubmissions = $submissions->where('is_late', false)->count();
        $lateSubmissions = $submissions->where('is_late', true)->count();

        // Hitung expected submissions: berapa Jumat dalam bulan ini
        $expectedSubmissions = 0;
        $firstDay = Carbon::create($year, $month, 1);
        $lastDay = $firstDay->copy()->endOfMonth();
        $current = $firstDay->copy();
        while ($current->lte($lastDay)) {
            if ($current->isFriday()) {
                $expectedSubmissions++;
            }
            $current->addDay();
        }
        // Minimal expected 2
        $expectedSubmissions = max(2, $expectedSubmissions);

        // Skor berdasarkan: on-time submission rate + penalti late
        if ($expectedSubmissions > 0) {
            // Full credit untuk on-time, half credit untuk late, 0 untuk miss
            $submissionCredit = $onTimeSubmissions + ($lateSubmissions * 0.5);
            $availabilityScore = round(min(1, $submissionCredit / $expectedSubmissions) * 30, 2);
        } else {
            $availabilityScore = 0;
        }

        // ============================================
        // TOTAL & CATEGORY
        // ============================================
        $totalScore = round($feedbackScore + $attendanceScore + $availabilityScore, 2);

        // Tentukan kategori
        if ($totalScore >= 90) {
            $category = 'A';
        } elseif ($totalScore >= 75) {
            $category = 'B';
        } else {
            $category = 'C';
        }

        // Generate notes
        $notes = $this->generateNotes($category, $feedbackScore, $attendanceScore, $availabilityScore, $cancellationLogs);

        // Simpan ke database
        $kpi = TeacherKpi::updateOrCreate(
            [
                'teacher_id' => $teacher_id,
                'month' => $month,
                'year' => $year,
            ],
            [
                'feedback_score' => $feedbackScore,
                'attendance_score' => $attendanceScore,
                'availability_score' => $availabilityScore,
                'total_score' => $totalScore,
                'category' => $category,
                'notes' => $notes,
            ]
        );

        return response()->json([
            'message' => 'KPI berhasil dihitung',
            'data' => [
                'teacher_id' => $teacher_id,
                'teacher_name' => $teacher->user?->name ?? "Teacher #{$teacher_id}",
                'month' => $month,
                'year' => $year,
                'breakdown' => [
                    'feedback' => [
                        'score' => $feedbackScore,
                        'max' => 30,
                        'detail' => "{$feedbackOnTime}/{$totalCompleted} sesi feedback tepat waktu",
                    ],
                    'attendance' => [
                        'score' => $attendanceScore,
                        'max' => 40,
                        'detail' => [
                            'completion' => "{$completedCount}/{$totalSessions} sesi completed ({$completionRate}/25)",
                            'non_cancel' => "{$cancellationLogs} pembatalan ({$nonCancelScore}/15)",
                        ],
                    ],
                    'availability' => [
                        'score' => $availabilityScore,
                        'max' => 30,
                        'detail' => "{$onTimeSubmissions} on-time, {$lateSubmissions} late dari {$expectedSubmissions} expected",
                    ],
                ],
                'total_score' => $totalScore,
                'category' => $category,
                'notes' => $notes,
            ],
        ]);
    }

    /**
     * GET /api/kpi/{teacher_id}
     * Lihat riwayat KPI teacher
     */
    public function show($teacher_id)
    {
        $teacher = Teacher::with('user')->findOrFail($teacher_id);

        $kpis = TeacherKpi::where('teacher_id', $teacher_id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'teacher_id' => $teacher_id,
            'teacher_name' => $teacher->user?->name ?? "Teacher #{$teacher_id}",
            'data' => $kpis,
        ]);
    }

    /**
     * GET /api/kpi
     * Admin: Lihat semua KPI bulan tertentu
     */
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $kpis = TeacherKpi::with('teacher.user')
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('total_score', 'desc')
            ->get()
            ->map(fn($k) => [
                'id' => $k->id,
                'teacher_id' => $k->teacher_id,
                'teacher_name' => $k->teacher?->user?->name ?? "Teacher #{$k->teacher_id}",
                'month' => $k->month,
                'year' => $k->year,
                'feedback_score' => $k->feedback_score,
                'attendance_score' => $k->attendance_score,
                'availability_score' => $k->availability_score,
                'total_score' => $k->total_score,
                'category' => $k->category,
                'notes' => $k->notes,
            ]);

        return response()->json(['data' => $kpis]);
    }

    /**
     * POST /api/kpi/calculate-all
     * Admin: Hitung KPI untuk semua teacher sekaligus
     */
    public function calculateAll(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $teachers = Teacher::all();
        $results = [];

        foreach ($teachers as $teacher) {
            // Buat sub-request
            $subRequest = new Request(['month' => $month, 'year' => $year]);
            $response = $this->calculate($subRequest, $teacher->id);
            $results[] = json_decode($response->getContent(), true)['data'] ?? null;
        }

        return response()->json([
            'message' => 'KPI dihitung untuk ' . count($results) . ' teacher',
            'month' => $month,
            'year' => $year,
            'data' => $results,
        ]);
    }

    /**
     * Generate catatan berdasarkan skor
     */
    private function generateNotes($category, $feedbackScore, $attendanceScore, $availabilityScore, $cancellations)
    {
        $notes = [];

        if ($category === 'A') {
            $notes[] = 'Performa sangat baik. Berhak mendapat bonus insentif tambahan.';
        } elseif ($category === 'B') {
            $notes[] = 'Performa baik. Tetap mendapatkan prioritas jadwal mengajar.';
        } else {
            $notes[] = 'Performa perlu ditingkatkan. Akan dievaluasi dan bisa mendapat pengurangan jadwal.';
        }

        if ($feedbackScore < 15) {
            $notes[] = 'Feedback form sering terlambat atau tidak diisi.';
        }

        if ($attendanceScore < 20) {
            $notes[] = 'Kehadiran dan komitmen mengajar rendah.';
        }

        if ($cancellations > 2) {
            $notes[] = "Pembatalan kelas tinggi ({$cancellations}x bulan ini). Sanksi prioritas jadwal berlaku.";
        }

        if ($availabilityScore < 15) {
            $notes[] = 'Kedisiplinan mengirim availability perlu diperbaiki.';
        }

        return implode(' | ', $notes);
    }
}
