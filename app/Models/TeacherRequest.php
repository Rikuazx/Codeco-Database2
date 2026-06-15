<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherRequest extends Model
{
    protected $fillable = [
        'student_id',
        'teacher_id',
        'class_id',
        'message',
        'preferred_date',
        'preferred_start_time',
        'preferred_end_time',
        'status',
        'admin_notes',
        'teacher_response',
        'teacher_notes',
        'teacher_responded_at',
        'class_session_id',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function class_()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function classSession()
    {
        return $this->belongsTo(ClassSession::class);
    }
}
