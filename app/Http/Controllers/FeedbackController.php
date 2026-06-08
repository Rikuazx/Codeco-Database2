<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\feedback;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class feedbackController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'student_id' => 'required|exists:students,id',
            'class_session_id' => 'required|exists:class_sessions,id',
            'rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        // prevent empty feedback
        if (!$request->rating && !$request->comment) {
            return response()->json([
                'error' => 'feedback cannot be empty'
            ], 400);
        }

        // prevent duplicate feedback per student per session
        $exists = feedback::where('class_session_id', $request->class_session_id)
            ->where('student_id', $request->student_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Feedback already submitted for this student in this session'
            ], 400);
        }

        $session = ClassSession::findOrFail($request->class_session_id);
        $salaryMessage = '';

        $feedback = DB::transaction(function () use ($request, $session, &$salaryMessage) {
            $feedback = feedback::create([
                'teacher_id' => $request->teacher_id,
                'student_id' => $request->student_id,
                'class_session_id' => $request->class_session_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'submitted_at' => now(),
            ]);

            // Find all active/pending student enrollments for this class
            $enrolledStudentIds = Enrollment::where('class_id', $session->class_id)
                ->whereIn('status', ['active', 'pending'])
                ->pluck('student_id')
                ->toArray();

            // Find all submitted feedbacks for this session
            $submittedFeedbackStudentIds = feedback::where('class_session_id', $session->id)
                ->pluck('student_id')
                ->toArray();

            $allFeedbackSubmitted = true;
            foreach ($enrolledStudentIds as $studentId) {
                if (!in_array($studentId, $submittedFeedbackStudentIds)) {
                    $allFeedbackSubmitted = false;
                    break;
                }
            }

            if (count($enrolledStudentIds) > 0 && $allFeedbackSubmitted && !$session->is_salary_paid) {
                $sessionEnd = Carbon::parse($session->end_time);
                $now = now();

                // Deadline is 2 days (48 hours) after class end_time
                if ($now->lte($sessionEnd->addDays(2))) {
                    $teacher = Teacher::findOrFail($request->teacher_id);
                    $teacher->increment('salary', $teacher->salary_per_session);

                    $session->update(['is_salary_paid' => true]);

                    $salaryMessage = "All feedbacks completed on time. Salary of {$teacher->salary_per_session} added.";
                } else {
                    $salaryMessage = "All feedbacks completed, but no salary was added because the submission was late (after 2 days).";
                }
            } else {
                $salaryMessage = "Feedback submitted. Some student feedbacks are still pending for this session.";
            }

            return $feedback;
        });

        return response()->json([
            'message' => 'Feedback submitted',
            'salary_status' => $salaryMessage,
            'data' => $feedback
        ]);
    }

    public function index()
    {
        $feedback = feedback::all();
        return response()->json($feedback);
    }

    // GET /api/teachers/{teacher_id}/feedback
    public function byTeacher($teacher_id)
    {
        $feedbacks = Feedback::with(['session.class'])
            ->where('teacher_id', $teacher_id)
            ->latest('submitted_at')
            ->get();
        return response()->json(['data' => $feedbacks]);
    }

    // GET /api/students/{student_id}/feedback
    public function byStudent($student_id)
    {
        $feedbacks = Feedback::with(['session.class', 'session.teacher.user'])
            ->where('student_id', $student_id)
            ->latest('submitted_at')
            ->get()
            ->map(function ($f) {
                return [
                    'id'               => $f->id,
                    'rating'           => $f->rating,
                    'comment'          => $f->comment,
                    'submitted_at'     => $f->submitted_at,
                    'teacher_name'     => $f->session?->teacher?->user?->name ?? '—',
                    'class_name'       => $f->session?->class?->name ?? '—',
                    'session_date'     => $f->session?->start_time
                        ? \Carbon\Carbon::parse($f->session->start_time)->format('d M Y')
                        : '—',
                ];
            });
        return response()->json(['data' => $feedbacks]);
    }

    // GET /api/sessions/{session_id}/enrolled-students
    // Returns students enrolled in session's class + whether feedback exists
    public function enrolledStudents($session_id)
    {
        $session = ClassSession::with('class')->findOrFail($session_id);

        $enrollments = Enrollment::with('student.user')
            ->where('class_id', $session->class_id)
            ->whereIn('status', ['active', 'pending', 'completed'])
            ->get();

        $data = $enrollments->map(function ($e) use ($session_id, $session) {
            $fb = Feedback::where('class_session_id', $session_id)
                ->where('student_id', $e->student_id)
                ->first();
            return [
                'student_id'   => $e->student_id,
                'student_name' => $e->student?->user?->name ?? 'Unknown',
                'has_feedback' => (bool) $fb,
                'feedback'     => $fb ? [
                    'id'           => $fb->id,
                    'rating'       => $fb->rating,
                    'comment'      => $fb->comment,
                    'submitted_at' => $fb->submitted_at,
                ] : null,
            ];
        });

        return response()->json([
            'session'       => [
                'id'         => $session->id,
                'class_name' => $session->class?->name,
                'start_time' => $session->start_time,
                'status'     => $session->status,
                'is_salary_paid' => $session->is_salary_paid,
            ],
            'data'          => $data,
            'total_students' => $data->count(),
            'feedback_count' => $data->where('has_feedback', true)->count(),
        ]);
    }

    // GET /api/teachers/{teacher_id}/salary
    public function salaryHistory($teacher_id)
    {
        $teacher = Teacher::with('user')->findOrFail($teacher_id);

        $paidSessions = ClassSession::with('class')
            ->where('teacher_id', $teacher_id)
            ->where('is_salary_paid', true)
            ->orderBy('end_time', 'desc')
            ->get()
            ->map(function ($s) use ($teacher) {
                return [
                    'session_id'   => $s->id,
                    'class_name'   => $s->class?->name ?? '—',
                    'date'         => $s->start_time
                        ? \Carbon\Carbon::parse($s->start_time)->format('d M Y H:i')
                        : '—',
                    'salary_earned' => $teacher->salary_per_session,
                ];
            });

        return response()->json([
            'teacher_name'       => $teacher->user?->name ?? '—',
            'total_salary'       => $teacher->salary,
            'salary_per_session' => $teacher->salary_per_session,
            'paid_sessions'      => $paidSessions,
            'total_paid_sessions'=> $paidSessions->count(),
        ]);
    }
}
