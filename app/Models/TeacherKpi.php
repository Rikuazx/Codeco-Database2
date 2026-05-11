<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherKpi extends Model
{
    protected $fillable = [
        'teacher_id',
        'month',
        'year',
        'feedback_score',
        'attendance_score',
        'availability_score',
        'total_score',
    ];
}