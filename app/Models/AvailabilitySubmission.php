<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailabilitySubmission extends Model
{
    protected $fillable = [
        'teacher_id',
        'period_start',
        'period_end',
        'submitted_at',
        'is_late',
    ];
}
