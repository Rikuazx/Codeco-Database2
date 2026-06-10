<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancellationLog extends Model
{
    protected $fillable = [
        'teacher_id',
        'class_session_id',
        'reason',
        'proof_file',
        'cancelled_at',
        'is_valid',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'is_valid' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classSession()
    {
        return $this->belongsTo(ClassSession::class);
    }
}
