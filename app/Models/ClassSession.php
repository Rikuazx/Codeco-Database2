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

    public function feedback()
    {
        return $this->hasOne(Feedback::class);
    }
}