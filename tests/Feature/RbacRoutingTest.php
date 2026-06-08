<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RbacRoutingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed RBAC tables for the test
        $this->artisan('db:seed', ['--class' => 'RbacSeeder']);
    }

    public function test_guest_is_redirected_to_landing_page()
    {
        // Guests cannot access admin pages and are redirected to Home
        $response = $this->get('/admin/users');
        $response->assertRedirect('/');
        
        $response = $this->get('/teacher/availability');
        $response->assertRedirect('/');

        $response = $this->get('/student/my-certificates');
        $response->assertRedirect('/');
    }

    public function test_admin_can_access_admin_pages()
    {
        // Simulate login as admin in session
        $response = $this->withSession(['simulated_role' => 'admin'])->get('/admin/users');
        $response->assertStatus(200);
    }

    public function test_teacher_can_access_teacher_pages_but_not_admin_pages()
    {
        $response = $this->withSession(['simulated_role' => 'teacher'])->get('/teacher/availability');
        $response->assertStatus(200);

        $response2 = $this->withSession(['simulated_role' => 'teacher'])->get('/admin/users');
        $response2->assertRedirect('/');
    }

    public function test_student_can_access_student_pages_but_not_teacher_pages()
    {
        $response = $this->withSession(['simulated_role' => 'student'])->get('/student/my-certificates');
        $response->assertStatus(200);

        $response2 = $this->withSession(['simulated_role' => 'student'])->get('/teacher/availability');
        $response2->assertRedirect('/');
    }

    public function test_simulated_login_logout_routes()
    {
        // 1. Login
        $response = $this->post('/simulate-login', ['role' => 'teacher']);
        $response->assertRedirect('/');
        $this->assertEquals('teacher', session('simulated_role'));

        // 2. Logout
        $response = $this->post('/simulate-logout');
        $response->assertRedirect('/');
        $this->assertNull(session('simulated_role'));
    }
}
