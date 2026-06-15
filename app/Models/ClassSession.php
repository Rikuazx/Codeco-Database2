<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    protected $fillable = [
        'class_id',
        'teacher_id',
        'start_time',
        'end_time',
        'status',
        'is_salary_paid',
        'is_open_for_booking',
        'booked_at',
        'booked_by_teacher_id',
    ];

    protected $casts = [
        'is_open_for_booking' => 'boolean',
    ];

    // 🔗 Relationships
    public function class()
    {
        return $this->belongsTo(\App\Models\Classes::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function bookedByTeacher()
    {
        return $this->belongsTo(Teacher::class, 'booked_by_teacher_id');
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class);
    }
}