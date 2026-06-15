<?php

namespace App\Http\Controllers;

use App\Models\TeacherRequest;
use App\Models\ClassSession;
use App\Models\Classes;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TeacherRequestController extends Controller
{
    // POST /api/teacher-requests
    public function store(Request $request)
    {
        $request->validate([
            'student_id'          => 'required|exists:students,id',
            'teacher_id'          => 'required|exists:teachers,id',
            'message'             => 'nullable|string|max:500',
            'preferred_date'      => 'nullable|date_format:Y-m-d',
            'preferred_start_time'=> 'nullable|date_format:H:i',
            'preferred_end_time'  => 'nullable|date_format:H:i',
            'class_id'            => 'nullable|exists:classes,id',
        ]);

        // Cegah duplikasi request pending ke teacher yang sama
        $exists = TeacherRequest::where('student_id', $request->student_id)
            ->where('teacher_id', $request->teacher_id)
            ->where('teacher_response', 'pending')
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Kamu sudah mengirim request ke teacher ini dan masih menunggu respons.'
            ], 400);
        }

        $req = TeacherRequest::create([
            'student_id'           => $request->student_id,
            'teacher_id'           => $request->teacher_id,
            'class_id'             => $request->class_id,
            'message'              => $request->message,
            'preferred_date'       => $request->preferred_date,
            'preferred_start_time' => $request->preferred_start_time,
            'preferred_end_time'   => $request->preferred_end_time,
            'status'               => 'pending',
            'teacher_response'     => 'pending',
        ]);

        return response()->json([
            'message' => 'Request berhasil dikirim ke teacher.',
            'data'    => $req->load('teacher.user'),
        ], 201);
    }

    // GET /api/teacher-requests?student_id=x&status=x&teacher_response=x
    public function index(Request $request)
    {
        $query = TeacherRequest::with(['student.user', 'teacher.user', 'class_', 'classSession.class'])->latest();

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('teacher_response')) {
            $query->where('teacher_response', $request->teacher_response);
        }

        return response()->json(['data' => $query->get()]);
    }

    // GET /api/teacher-requests/teacher/{teacher_id}
    public function byTeacher($teacher_id)
    {
        $requests = TeacherRequest::with(['student.user', 'teacher.user', 'class_', 'classSession.class'])
            ->where('teacher_id', $teacher_id)
            ->latest()
            ->get();

        return response()->json(['data' => $requests]);
    }

    // PUT /api/teacher-requests/{id}/teacher-respond
    // Teacher approves or rejects the student request
    public function teacherRespond(Request $request, $id)
    {
        $request->validate([
            'teacher_id'       => 'required|exists:teachers,id',
            'teacher_response' => 'required|in:approved,rejected',
            'teacher_notes'    => 'nullable|string|max:500',
            'class_id'         => 'nullable|exists:classes,id',
        ]);

        $req = TeacherRequest::findOrFail($id);

        // Validasi: hanya teacher yang diminta yang bisa respond
        if ((int)$req->teacher_id !== (int)$request->teacher_id) {
            return response()->json([
                'error' => 'Kamu tidak bisa merespon request yang bukan ditujukan kepadamu.'
            ], 403);
        }

        // Validasi: hanya bisa respond jika masih pending
        if ($req->teacher_response !== 'pending') {
            return response()->json([
                'error' => 'Request ini sudah direspon sebelumnya.'
            ], 400);
        }

        $req->update([
            'teacher_response'    => $request->teacher_response,
            'teacher_notes'       => $request->teacher_notes,
            'teacher_responded_at' => now(),
        ]);

        // 🧠 Jika teacher APPROVE: buat ClassSession sebanyak total_sessions kelas
        if ($request->teacher_response === 'approved') {
            $classId = $req->class_id;

            if (!$classId) {
                $req->update(['status' => 'processed']);
                return response()->json([
                    'message' => 'Request di-approve, tapi tidak ada kelas yang terkait.',
                    'data'    => $req->load('teacher.user'),
                ]);
            }

            $class = Classes::find($classId);
            $totalSessions = $class->total_sessions ?? 1;

            // Tentukan start date: dari preferred_date atau besok
            $startDate = $req->preferred_date
                ? Carbon::parse($req->preferred_date)
                : Carbon::tomorrow();

            $startTimeStr = $req->preferred_start_time ?? '09:00';
            $endTimeStr   = $req->preferred_end_time ?? '11:00';

            $createdSessions = [];

            for ($i = 0; $i < $totalSessions; $i++) {
                $sessionDate = $startDate->copy()->addWeeks($i);
                $startTime = $sessionDate->format('Y-m-d') . ' ' . $startTimeStr . ':00';
                $endTime   = $sessionDate->format('Y-m-d') . ' ' . $endTimeStr . ':00';

                $session = ClassSession::create([
                    'class_id'   => $classId,
                    'teacher_id' => $req->teacher_id,
                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                    'status'     => 'scheduled',
                ]);

                $createdSessions[] = $session;
            }

            // Link request ke session pertama yang dibuat
            $req->update([
                'class_session_id' => $createdSessions[0]->id ?? null,
                'status'           => 'processed',
            ]);

            return response()->json([
                'message' => "Request di-approve! {$totalSessions} session telah dijadwalkan untuk kelas {$class->name}.",
                'data'    => $req->load('classSession.class', 'teacher.user'),
                'sessions_created' => count($createdSessions),
            ]);
        }

        // Jika REJECT: status tetap pending agar admin bisa handle
        return response()->json([
            'message' => 'Request ditolak. Admin akan menangani selanjutnya.',
            'data'    => $req->load('teacher.user'),
        ]);
    }

    // PUT /api/teacher-requests/{id}/admin-action
    // Admin handles requests that were rejected by teacher
    public function adminAction(Request $request, $id)
    {
        $request->validate([
            'action'      => 'required|in:assign_other,create_open,reject',
            'admin_notes' => 'nullable|string|max:500',
            // For assign_other
            'teacher_id'  => 'nullable|exists:teachers,id',
            'class_id'    => 'nullable|exists:classes,id',
            'start_time'  => 'nullable|date',
            'end_time'    => 'nullable|date',
        ]);

        $req = TeacherRequest::findOrFail($id);
        $action = $request->action;

        if ($action === 'assign_other') {
            // 🧠 Admin assigns a different teacher
            $request->validate([
                'teacher_id' => 'required|exists:teachers,id',
                'class_id'   => 'required|exists:classes,id',
                'start_time' => 'required|date',
                'end_time'   => 'required|date|after:start_time',
            ]);

            $session = ClassSession::create([
                'class_id'   => $request->class_id,
                'teacher_id' => $request->teacher_id,
                'start_time' => $request->start_time,
                'end_time'   => $request->end_time,
                'status'     => 'scheduled',
            ]);

            $req->update([
                'status'           => 'processed',
                'admin_notes'      => $request->admin_notes ?? 'Teacher lain di-assign oleh admin.',
                'class_session_id' => $session->id,
            ]);

            return response()->json([
                'message' => 'Teacher lain berhasil di-assign.',
                'data'    => $req->load('classSession.class', 'teacher.user'),
            ]);

        } elseif ($action === 'create_open') {
            // 🧠 Admin creates an open session for booking
            $request->validate([
                'class_id'   => 'required|exists:classes,id',
                'start_time' => 'required|date',
                'end_time'   => 'required|date|after:start_time',
            ]);

            $session = ClassSession::create([
                'class_id'            => $request->class_id,
                'start_time'          => $request->start_time,
                'end_time'            => $request->end_time,
                'status'              => 'scheduled',
                'is_open_for_booking' => true,
            ]);

            $req->update([
                'status'           => 'processed',
                'admin_notes'      => $request->admin_notes ?? 'Open session dibuat, menunggu teacher booking.',
                'class_session_id' => $session->id,
            ]);

            return response()->json([
                'message' => 'Open session berhasil dibuat. Teacher dapat mem-booking.',
                'data'    => $req->load('classSession.class'),
            ]);

        } elseif ($action === 'reject') {
            // 🧠 Admin rejects the request entirely
            $req->update([
                'status'      => 'rejected',
                'admin_notes' => $request->admin_notes ?? 'Ditolak oleh admin.',
            ]);

            return response()->json([
                'message' => 'Request ditolak.',
                'data'    => $req,
            ]);
        }

        return response()->json(['error' => 'Invalid action.'], 400);
    }

    // PUT /api/teacher-requests/{id}/respond  (legacy — admin direct respond)
    public function respond(Request $request, $id)
    {
        $request->validate([
            'status'      => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $req = TeacherRequest::findOrFail($id);
        $req->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return response()->json([
            'message' => 'Request diperbarui.',
            'data'    => $req,
        ]);
    }
}
