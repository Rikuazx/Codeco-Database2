<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\ClassSession;
use Carbon\Carbon;
use App\Models\Feedback;
use App\Models\Teacher;
use App\Models\Enrollment;
class ClassSessionController extends Controller
{
    /**
     * Create a single session (used by admin schedule page).
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_id'   => 'required|exists:classes,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'start_time' => 'required|date',
            'end_time'   => 'required|date|after:start_time',
            'status'     => 'nullable|in:scheduled,completed,cancelled',
        ]);

        $session = ClassSession::create([
            'class_id'   => $request->class_id,
            'teacher_id' => $request->teacher_id,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
            'status'     => $request->status ?? 'scheduled',
        ]);

        return response()->json([
            'message' => 'Session created successfully',
            'data'    => $session->load('teacher.user', 'class'),
        ], 201);
    }

public function generateSessions($class_id)
{
    $class = Classes::findOrFail($class_id);


    $existing = ClassSession::where('class_id', $class_id)->exists();

    if ($existing) {
        return response()->json([
         'error' => 'Sessions already generated for this class'
    ], 400);
}

    $startDate = now();
    $total = $class->total_sessions;

    for ($i = 1; $i <= $total; $i++) {
        ClassSession::create([
            'class_id' => $class->id,
            'start_time' => $startDate->copy()->addDays($i * 2)->setTime(10, 0),
            'end_time' => $startDate->copy()->addDays($i * 2)->setTime(12, 0),
            'status' => 'scheduled',
        ]);
    }

    return response()->json([
        'message' => 'Session generated',
        'class_id' => $class_id
    ]);
}

    public function assignTeacher(Request $request)
{
    $request->validate([
        'session_id' => 'required|exists:class_sessions,id',
        'teacher_id' => 'required|exists:teachers,id',
    ]);

    $session = ClassSession::findOrFail($request->session_id);

    $session->teacher_id = $request->teacher_id;
    $session->save();

    return response()->json([
        'message' => 'Teacher assigned successfully',
        'data' => $session
    ]);
}
public function complete($id)
{
    try {
        $session = ClassSession::findOrFail($id);

        $feedbackExists = Feedback::where('class_session_id', $session->id)->exists();

        if (!$feedbackExists) {
            return response()->json([
                'error' => 'Cannot complete session without feedback'
            ], 400);
        }

        $session->status = 'completed';
        $session->save();

        // ── Auto-complete enrollment ──────────────────────────────────────────
        // Jika semua sesi di kelas sudah completed → set enrollment jadi completed
        $totalSessions     = ClassSession::where('class_id', $session->class_id)->count();
        $completedSessions = ClassSession::where('class_id', $session->class_id)
                                ->where('status', 'completed')->count();

        if ($totalSessions > 0 && $totalSessions === $completedSessions) {
            Enrollment::where('class_id', $session->class_id)
                      ->whereIn('status', ['pending', 'active'])
                      ->update(['status' => 'completed']);
        }
        // ─────────────────────────────────────────────────────────────────────

        return response()->json([
            'message' => 'Session completed successfully',
            'data'    => $session
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

public function autoAssignTeacher($id)
{
    $session = ClassSession::findOrFail($id);

    //  Prevent re-assign
    if ($session->teacher_id) {
        return response()->json([
            'error' => 'Teacher already assigned'
        ], 400);
    }

    //  Find available teacher
    $teacher = Teacher::whereHas('availabilities', function ($q) use ($session) {
        $q->where('date', Carbon::parse($session->start_time)->toDateString())
          ->where('type', '!=', 'unavailable')
          ->where(function ($q2) use ($session) {
              $q2->where('type', 'full_day')
                 ->orWhere(function ($q3) use ($session) {
                     $sessionStart = Carbon::parse($session->start_time)->toTimeString();
                     $sessionEnd = Carbon::parse($session->end_time)->toTimeString();
                     $q3->where('type', 'time_range')
                        ->where('start_time', '<=', $sessionStart)
                        ->where('end_time', '>=', $sessionEnd);
                 });
          });
    })
    //  Prevent time conflict
    ->whereDoesntHave('classSessions', function ($q) use ($session) {
        $q->whereBetween('start_time', [$session->start_time, $session->end_time]);
    })
    ->first();

    if (!$teacher) {
        return response()->json([
            'error' => 'No available teacher found'
        ], 400);
    }

    // Assign teacher
    $session->update([
        'teacher_id' => $teacher->id
    ]);

    return response()->json([
        'message' => 'Teacher assigned successfully',
        'teacher_id' => $teacher->id
    ]);
}

public function index()
{
    return \App\Models\ClassSession::with([
        'teacher.user',
        'class'
    ])->get();
}

public function byTeacher($teacher_id)
{
    $sessions = ClassSession::with(['class', 'teacher.user'])
        ->where('teacher_id', $teacher_id)
        ->orderBy('start_time', 'asc')
        ->get()
        ->map(function ($s) {
            // Count enrolled students in this class
            $enrolledCount = Enrollment::where('class_id', $s->class_id)
                ->whereIn('status', ['active', 'pending', 'completed'])
                ->count();
            // Count feedbacks submitted for this session
            $feedbackCount = Feedback::where('class_session_id', $s->id)->count();
            return array_merge($s->toArray(), [
                'enrolled_count'  => $enrolledCount,
                'feedback_count'  => $feedbackCount,
                'feedback_done'   => $feedbackCount >= $enrolledCount && $enrolledCount > 0,
            ]);
        });

    return response()->json(['data' => $sessions]);
}
public function update(Request $request, $id)
{
    $session = ClassSession::findOrFail($id);
    
    $request->validate([
        'start_time' => 'nullable|date',
        'end_time' => 'nullable|date',
        'status' => 'nullable|in:scheduled,completed,cancelled',
        'teacher_id' => 'nullable|exists:teachers,id',
    ]);

    // 🧠 Check for H-1 restriction on schedule changes
    if ($request->has('start_time') || $request->has('end_time')) {
        $newStart = $request->input('start_time');
        $newEnd = $request->input('end_time');

        if (($newStart && Carbon::parse($newStart)->ne(Carbon::parse($session->start_time))) || 
            ($newEnd && Carbon::parse($newEnd)->ne(Carbon::parse($session->end_time)))) {
            
            $sessionStart = Carbon::parse($session->start_time);
            if ($sessionStart->isBefore(now()->addDay())) {
                return response()->json([
                    'error' => 'Perubahan jadwal hanya diperbolehkan maksimal H-1 sebelum kelas berlangsung.'
                ], 400);
            }
        }
    }

    $session->update($request->all());

    return response()->json([
        'message' => 'Session updated successfully',
        'data' => $session
    ]);
}
public function destroy($id)
{
    $session = \App\Models\ClassSession::findOrFail($id);

    $session->delete();

    return response()->json([
        'message' => 'Session deleted successfully'
    ]);
}
}