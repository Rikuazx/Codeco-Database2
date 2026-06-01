<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    protected $fillable = [
        'teacher_id',
        'date',          
        'period_start',
        'period_end',
        'start_time',
        'end_time',
    ];
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
