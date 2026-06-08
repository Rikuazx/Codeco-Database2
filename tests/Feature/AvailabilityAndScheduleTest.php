<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\TeacherAvailability;
use App\Models\AvailabilitySubmission;
use App\Models\ScheduleChangeRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AvailabilityAndScheduleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Force timezone to Asia/Jakarta for consistency in testing
        date_default_timezone_set('Asia/Jakarta');
        config(['app.timezone' => 'Asia/Jakarta']);
    }

    public function test_availability_submission_validates_minimum_sessions()
    {
        $user = User::factory()->create();
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'specialization' => 'Math'
        ]);

        $nextMonday = now()->addWeek()->startOfWeek()->toDateString();

        // ❌ Fail: only 1 session provided
        $response = $this->postJson('/api/teacher-availability', [
            'teacher_id' => $teacher->id,
            'availabilities' => [
                [
                    'date' => $nextMonday,
                    'type' => 'full_day'
                ]
            ]
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'error' => 'Minimal availability di booking system sejumlah minimal 2 sesi dalam 2 minggu.'
        ]);
    }

    public function test_availability_submission_validates_dates_within_period()
    {
        $user = User::factory()->create();
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'specialization' => 'Math'
        ]);

        // ❌ Fail: Date is in the current week (outside 2-week period)
        $invalidDate = now()->startOfWeek()->toDateString();

        $response = $this->postJson('/api/teacher-availability', [
            'teacher_id' => $teacher->id,
            'availabilities' => [
                [
                    'date' => $invalidDate,
                    'type' => 'full_day'
                ],
                [
                    'date' => now()->addWeek()->startOfWeek()->addDay()->toDateString(),
                    'type' => 'full_day'
                ]
            ]
        ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('outside the allowed 2-week period', $response->json('error'));
    }

    public function test_availability_submission_populates_14_days_correctly()
    {
        $user = User::factory()->create();
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'specialization' => 'Math'
        ]);

        $nextMonday = now()->addWeek()->startOfWeek();
        $date1 = $nextMonday->copy()->toDateString();
        $date2 = $nextMonday->copy()->addDay()->toDateString();

        // Submit availability
        $response = $this->postJson('/api/teacher-availability', [
            'teacher_id' => $teacher->id,
            'availabilities' => [
                [
                    'date' => $date1,
                    'type' => 'full_day'
                ],
                [
                    'date' => $date2,
                    'type' => 'time_range',
                    'start_time' => '08:00',
                    'end_time' => '10:00'
                ]
            ]
        ]);

        $response->assertStatus(200);

        // Verify that 14 entries are created in DB
        $this->assertEquals(14, TeacherAvailability::where('teacher_id', $teacher->id)->count());

        // Verify the details
        $this->assertDatabaseHas('availabilities', [
            'teacher_id' => $teacher->id,
            'date' => $date1,
            'type' => 'full_day'
        ]);

        $this->assertDatabaseHas('availabilities', [
            'teacher_id' => $teacher->id,
            'date' => $date2,
            'type' => 'time_range',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00'
        ]);

        // Verifying an unprovided date defaults to 'unavailable'
        $unprovidedDate = $nextMonday->copy()->addDays(5)->toDateString();
        $this->assertDatabaseHas('availabilities', [
            'teacher_id' => $teacher->id,
            'date' => $unprovidedDate,
            'type' => 'unavailable'
        ]);

        // Verifying metadata submission is logged
        $this->assertDatabaseHas('availability_submissions', [
            'teacher_id' => $teacher->id,
            'period_start' => $nextMonday->toDateString(),
            'period_end' => $nextMonday->copy()->addDays(13)->toDateString(),
        ]);
    }

    public function test_auto_assign_teacher_availability_matching()
    {
        $user = User::factory()->create();
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'specialization' => 'Math'
        ]);

        // Submit availability (e.g. available next Monday full day, and next Tuesday time range)
        $nextMonday = now()->addWeek()->startOfWeek();
        $date1 = $nextMonday->copy()->toDateString();
        $date2 = $nextMonday->copy()->addDay()->toDateString();

        $this->postJson('/api/teacher-availability', [
            'teacher_id' => $teacher->id,
            'availabilities' => [
                [
                    'date' => $date1,
                    'type' => 'full_day'
                ],
                [
                    'date' => $date2,
                    'type' => 'time_range',
                    'start_time' => '08:00:00',
                    'end_time' => '12:00:00'
                ]
            ]
        ]);

        $class = Classes::create([
            'name' => 'Math 101',
            'total_sessions' => 2,
            'price' => 100000
        ]);

        // Create class session next Monday (10:00 to 12:00)
        $session = ClassSession::create([
            'class_id' => $class->id,
            'start_time' => $date1 . ' 10:00:00',
            'end_time' => $date1 . ' 12:00:00',
            'status' => 'scheduled'
        ]);

        // Auto assign teacher
        $response = $this->postJson("/api/sessions/{$session->id}/auto-assign");
        $response->assertStatus(200);
        $response->assertJsonFragment(['teacher_id' => $teacher->id]);

        $this->assertEquals($teacher->id, $session->fresh()->teacher_id);
    }

    public function test_schedule_change_request_h1_rule()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'specialization' => 'Math'
        ]);

        $class = Classes::create([
            'name' => 'Math 101',
            'total_sessions' => 2,
            'price' => 100000
        ]);

        // Session starts in 12 hours (violates H-1)
        $lateSession = ClassSession::create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'start_time' => now()->addHours(12)->toDateTimeString(),
            'end_time' => now()->addHours(14)->toDateTimeString(),
            'status' => 'scheduled'
        ]);

        // Session starts in 48 hours (passes H-1)
        $okSession = ClassSession::create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'start_time' => now()->addDays(2)->toDateTimeString(),
            'end_time' => now()->addDays(2)->addHours(2)->toDateTimeString(),
            'status' => 'scheduled'
        ]);

        $file = UploadedFile::fake()->create('sick_note.pdf', 100);

        // ❌ Request for lateSession should fail
        $response = $this->postJson('/api/schedule-change-requests', [
            'class_session_id' => $lateSession->id,
            'teacher_id' => $teacher->id,
            'reason' => 'Sick',
            'proof_file' => $file,
            'new_start_time' => now()->addDays(3)->toDateTimeString(),
            'new_end_time' => now()->addDays(3)->addHours(2)->toDateTimeString(),
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'error' => 'Perubahan jadwal hanya diperbolehkan maksimal H-1 sebelum kelas berlangsung.'
        ]);

        // Direct update for lateSession should also be blocked by H-1 check
        $directUpdateResponse = $this->putJson("/api/sessions/{$lateSession->id}", [
            'start_time' => now()->addDays(3)->toDateTimeString()
        ]);
        $directUpdateResponse->assertStatus(400);

        // ✔️ Request for okSession should succeed
        $file = UploadedFile::fake()->create('conference.pdf', 100);
        $newStart = now()->addDays(4)->setTime(10, 0, 0)->toDateTimeString();
        $newEnd = now()->addDays(4)->setTime(12, 0, 0)->toDateTimeString();

        $response = $this->postJson('/api/schedule-change-requests', [
            'class_session_id' => $okSession->id,
            'teacher_id' => $teacher->id,
            'reason' => 'Conference',
            'proof_file' => $file,
            'new_start_time' => $newStart,
            'new_end_time' => $newEnd,
        ]);

        $response->assertStatus(201);
        $request_id = $response->json('data.id');

        // Approve request and verify reschedule occurs
        $approveResponse = $this->postJson("/api/schedule-change-requests/{$request_id}/approve");
        $approveResponse->assertStatus(200);

        $okSession = $okSession->fresh();
        $this->assertEquals($newStart, $okSession->start_time);
        $this->assertEquals($newEnd, $okSession->end_time);
        $this->assertEquals('scheduled', $okSession->status);
    }
}
