<?php

namespace App\Http\Controllers;

use App\Models\TeacherRequest;
use Illuminate\Http\Request;

class TeacherRequestController extends Controller
{
    // POST /api/teacher-requests
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'teacher_id' => 'required|exists:teachers,id',
            'message'    => 'nullable|string|max:500',
        ]);

        // Cegah duplikasi request pending ke teacher yang sama
        $exists = TeacherRequest::where('student_id', $request->student_id)
            ->where('teacher_id', $request->teacher_id)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Kamu sudah mengirim request ke teacher ini dan masih menunggu respons.'
            ], 400);
        }

        $req = TeacherRequest::create([
            'student_id' => $request->student_id,
            'teacher_id' => $request->teacher_id,
            'message'    => $request->message,
            'status'     => 'pending',
        ]);

        return response()->json([
            'message' => 'Request berhasil dikirim.',
            'data'    => $req,
        ], 201);
    }

    // GET /api/teacher-requests?student_id=x
    public function index(Request $request)
    {
        $query = TeacherRequest::with(['student.user', 'teacher.user'])->latest();

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    // PUT /api/teacher-requests/{id}/respond  (admin)
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
