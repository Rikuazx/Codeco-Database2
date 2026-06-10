<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAvailability extends Model
{
    protected $table = 'availabilities';

    protected $fillable = [
        'teacher_id',
        'date',          
        'period_start',
        'period_end',
        'week_number',
        'week_status',
        'is_locked',
        'type',
        'start_time',
        'end_time',
        'submitted_at',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}