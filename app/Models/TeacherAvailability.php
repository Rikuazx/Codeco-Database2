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
        'start_time',
        'end_time',
        'is_full_day',
        'is_available',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}