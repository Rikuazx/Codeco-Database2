<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherKpi extends Model
{
    protected $fillable = [
        'teacher_id',
        'month',
        'year',
        'category',
        'notes',
        'feedback_score',
        'attendance_score',
        'availability_score',
        'total_score',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}