<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleChangeRequest extends Model
{
    protected $fillable = [
        'class_session_id',
        'teacher_id',
        'reason',
        'proof_file',
        'status',
        'admin_notes',
        'reviewed_at',
        'new_date',
        'new_start_time',
        'new_end_time',
        'requested_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function classSession()
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
