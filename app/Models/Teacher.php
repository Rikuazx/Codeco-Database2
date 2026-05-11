<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'specialization',
        'priority_score',
    ];

    // 🔗 Relationships
    public function classSessions()
    {
        return $this->hasMany(ClassSession::class);
    }

    public function availabilities()
    {
        return $this->hasMany(TeacherAvailability::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}