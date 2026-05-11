<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'priority_score',
        'specialization'
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
    return $this->belongsTo(\App\Models\User::class);
}
}