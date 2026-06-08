<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $teacherRole = Role::firstOrCreate(['slug' => 'teacher'], ['name' => 'Teacher']);
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        // 2. Create Permissions (sesuai tabel permission RBAC)
        $permissionsList = [
            // Admin-only permissions
            'approve_schedule_change'  => 'Approve Schedule Change',
            'manage_classes'           => 'Manage Classes',
            'manage_enrollments'       => 'Manage Enrollments',
            'manage_payments'          => 'Manage Payments',
            'manage_schedule'          => 'Manage Schedule',
            'manage_users'             => 'Manage Users',

            // Teacher-only permissions
            'request_schedule_change'  => 'Request Schedule Change',
            'submit_materi'            => 'Submit Materi',
            'view_salary'              => 'View Salary',

            // Teacher + Student
            'submit_attendance'        => 'Submit Attendance',

            // Teacher-only (submit)
            'submit_availability'      => 'Submit Availability',
            'submit_feedback'          => 'Submit Feedback',

            // Admin + Student
            'view_feedback'            => 'View Feedback',

            // Admin + Teacher
            'view_availability'        => 'View Availability',
            'view_calendar'            => 'View Calendar',
            'view_kpi'                 => 'View KPI',
            'view_reports'             => 'View Reports',

            // All roles
            'view_certificate'         => 'View Certificate',
            'view_classes'             => 'View Classes',
            'view_materi'              => 'View Materi',
            'view_schedule'            => 'View Schedule',
        ];

        $permissions = [];
        foreach ($permissionsList as $slug => $name) {
            $permissions[$slug] = Permission::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        // 3. Map Permissions to Roles (sesuai tabel RBAC)

        // Admin: approve_schedule_change, manage_classes, manage_enrollments, manage_payments,
        //        manage_schedule, manage_users, view_feedback, view_availability, view_calendar,
        //        view_kpi, view_reports, view_certificate, view_classes, view_materi, view_schedule
        $adminRole->permissions()->sync([
            $permissions['approve_schedule_change']->id,
            $permissions['manage_classes']->id,
            $permissions['manage_enrollments']->id,
            $permissions['manage_payments']->id,
            $permissions['manage_schedule']->id,
            $permissions['manage_users']->id,
            $permissions['view_feedback']->id,
            $permissions['view_availability']->id,
            $permissions['view_calendar']->id,
            $permissions['view_kpi']->id,
            $permissions['view_reports']->id,
            $permissions['view_certificate']->id,
            $permissions['view_classes']->id,
            $permissions['view_materi']->id,
            $permissions['view_schedule']->id,
        ]);

        // Teacher: request_schedule_change, submit_attendance, submit_availability,
        //          submit_feedback, submit_materi, view_salary, view_availability,
        //          view_calendar, view_certificate, view_classes, view_kpi,
        //          view_materi, view_reports, view_schedule
        $teacherRole->permissions()->sync([
            $permissions['request_schedule_change']->id,
            $permissions['submit_attendance']->id,
            $permissions['submit_availability']->id,
            $permissions['submit_feedback']->id,
            $permissions['submit_materi']->id,
            $permissions['view_salary']->id,
            $permissions['view_availability']->id,
            $permissions['view_calendar']->id,
            $permissions['view_certificate']->id,
            $permissions['view_classes']->id,
            $permissions['view_kpi']->id,
            $permissions['view_materi']->id,
            $permissions['view_reports']->id,
            $permissions['view_schedule']->id,
        ]);

        // Student: submit_attendance, view_feedback, view_certificate,
        //          view_classes, view_materi, view_schedule
        $studentRole->permissions()->sync([
            $permissions['submit_attendance']->id,
            $permissions['view_feedback']->id,
            $permissions['view_certificate']->id,
            $permissions['view_classes']->id,
            $permissions['view_materi']->id,
            $permissions['view_schedule']->id,
        ]);

        // 4. Update existing Users to set role_id
        User::where('role', 'admin')->update(['role_id' => $adminRole->id]);
        User::where('role', 'teacher')->update(['role_id' => $teacherRole->id]);
        User::where('role', 'student')->update(['role_id' => $studentRole->id]);
    }
}
