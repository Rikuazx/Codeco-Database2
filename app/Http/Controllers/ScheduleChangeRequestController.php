<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ClassSession;
use App\Models\ScheduleChangeRequest;

class ScheduleChangeRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'class_session_id' => 'required|exists:class_sessions,id',
            'teacher_id' => 'required|exists:teachers,id',
            'reason' => 'required|string',
            'proof_file' => 'required|file|mimes:pdf,png,jpg,jpeg|max:2048',
            'new_start_time' => 'nullable|date',
            'new_end_time' => 'nullable|date',
        ]);

        $session = ClassSession::findOrFail($request->class_session_id);

        // 🧠 Enforce that the class session belongs to this teacher
        if ($session->teacher_id != $request->teacher_id) {
            return response()->json([
                'error' => 'This class session is not assigned to the specified teacher.'
            ], 400);
        }

        // 🧠 Enforce H-1 limit (must submit at least 24 hours before class starts)
        $sessionStart = Carbon::parse($session->start_time);
        if ($sessionStart->isBefore(now()->addDay())) {
            return response()->json([
                'error' => 'Perubahan jadwal hanya diperbolehkan maksimal H-1 sebelum kelas berlangsung.'
            ], 400);
        }

        // 🧠 Save proof file
        $path = $request->file('proof_file')->store('proofs', 'public');

        $changeRequest = ScheduleChangeRequest::create([
            'class_session_id' => $request->class_session_id,
            'teacher_id' => $request->teacher_id,
            'reason' => $request->reason,
            'proof_file' => $path,
            'status' => 'pending',
            'new_start_time' => $request->new_start_time,
            'new_end_time' => $request->new_end_time,
            'requested_at' => now(),
        ]);

        return response()->json([
            'message' => 'Schedule change request submitted successfully',
            'data' => $changeRequest
        ], 201);
    }

    public function approve($id)
    {
        $changeRequest = ScheduleChangeRequest::findOrFail($id);

        if ($changeRequest->status !== 'pending') {
            return response()->json([
                'error' => 'Only pending requests can be approved.'
            ], 400);
        }

        $changeRequest->update(['status' => 'approved']);

        // 🧠 Update the ClassSession schedule or status accordingly
        $session = $changeRequest->classSession;
        if ($changeRequest->new_start_time && $changeRequest->new_end_time) {
            $session->update([
                'start_time' => $changeRequest->new_start_time,
                'end_time' => $changeRequest->new_end_time,
                'status' => 'scheduled'
            ]);
        } else {
            $session->update([
                'status' => 'cancelled'
            ]);
        }

        return response()->json([
            'message' => 'Schedule change request approved',
            'data' => $changeRequest
        ]);
    }

    public function reject($id)
    {
        $changeRequest = ScheduleChangeRequest::findOrFail($id);

        if ($changeRequest->status !== 'pending') {
            return response()->json([
                'error' => 'Only pending requests can be rejected.'
            ], 400);
        }

        $changeRequest->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Schedule change request rejected',
            'data' => $changeRequest
        ]);
    }
}
