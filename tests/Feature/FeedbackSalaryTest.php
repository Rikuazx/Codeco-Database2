<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\feedback;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;

class FeedbackSalaryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Force timezone to Asia/Jakarta for consistency in testing
        date_default_timezone_set('Asia/Jakarta');
        config(['app.timezone' => 'Asia/Jakarta']);
    }

    public function test_feedback_completion_awards_salary_within_2_days()
    {
        $userTeacher = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $userTeacher->id,
            'specialization' => 'Math',
            'salary' => 0.00,
            'salary_per_session' => 150000.00
        ]);

        $userStudent1 = User::factory()->create(['role' => 'student']);
        $student1 = Student::create([
            'user_id' => $userStudent1->id,
            'status' => 'active',
            'type' => 'regular',
            'registration_date' => now()->toDateString(),
        ]);

        $userStudent2 = User::factory()->create(['role' => 'student']);
        $student2 = Student::create([
            'user_id' => $userStudent2->id,
            'status' => 'active',
            'type' => 'regular',
            'registration_date' => now()->toDateString(),
        ]);

        $class = Classes::create([
            'name' => 'Math Course',
            'total_sessions' => 5,
            'price' => 500000.00
        ]);

        // Enroll students
        Enrollment::create([
            'student_id' => $student1->id,
            'class_id' => $class->id,
            'price' => 100000.00,
            'status' => 'active'
        ]);
        Enrollment::create([
            'student_id' => $student2->id,
            'class_id' => $class->id,
            'price' => 100000.00,
            'status' => 'active'
        ]);

        // Class Session ended 1 hour ago (well within 2 days)
        $session = ClassSession::create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'start_time' => now()->subHours(3)->toDateTimeString(),
            'end_time' => now()->subHours(1)->toDateTimeString(),
            'status' => 'scheduled',
            'is_salary_paid' => false
        ]);

        // 1. Submit feedback for Student 1
        $response1 = $this->postJson('/api/feedback', [
            'teacher_id' => $teacher->id,
            'student_id' => $student1->id,
            'class_session_id' => $session->id,
            'rating' => 5,
            'comment' => 'Great student'
        ]);

        $response1->assertStatus(200);
        $response1->assertJsonFragment([
            'salary_status' => 'Feedback submitted. Some student feedbacks are still pending for this session.'
        ]);
        $this->assertEquals(0.00, $teacher->fresh()->salary);

        // 2. Submit feedback for Student 2 (completes feedback for the session)
        $response2 = $this->postJson('/api/feedback', [
            'teacher_id' => $teacher->id,
            'student_id' => $student2->id,
            'class_session_id' => $session->id,
            'rating' => 4,
            'comment' => 'Good participation'
        ]);

        $response2->assertStatus(200);
        $response2->assertJsonFragment([
            'salary_status' => 'All feedbacks completed on time. Salary of 150000.00 added.'
        ]);
        $this->assertEquals(150000.00, $teacher->fresh()->salary);
        $this->assertTrue((bool)$session->fresh()->is_salary_paid);
    }

    public function test_feedback_completion_does_not_award_salary_after_2_days()
    {
        $userTeacher = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $userTeacher->id,
            'specialization' => 'Science',
            'salary' => 0.00,
            'salary_per_session' => 200000.00
        ]);

        $userStudent = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'user_id' => $userStudent->id,
            'status' => 'active',
            'type' => 'regular',
            'registration_date' => now()->toDateString(),
        ]);

        $class = Classes::create([
            'name' => 'Science Course',
            'total_sessions' => 5,
            'price' => 500000.00
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'price' => 100000.00,
            'status' => 'active'
        ]);

        // Class Session ended 3 days ago (exceeds 2 days deadline)
        $session = ClassSession::create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'start_time' => now()->subDays(3)->subHours(2)->toDateTimeString(),
            'end_time' => now()->subDays(3)->toDateTimeString(),
            'status' => 'scheduled',
            'is_salary_paid' => false
        ]);

        // Submit feedback for the single student (completes feedback late)
        $response = $this->postJson('/api/feedback', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'class_session_id' => $session->id,
            'rating' => 5,
            'comment' => 'Late review'
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'salary_status' => 'All feedbacks completed, but no salary was added because the submission was late (after 2 days).'
        ]);
        $this->assertEquals(0.00, $teacher->fresh()->salary);
        $this->assertFalse((bool)$session->fresh()->is_salary_paid);
    }
}
