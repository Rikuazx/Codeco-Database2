<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'specialization',
        'priority_score',
        'salary',
        'salary_per_session',
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

    public function cancellationLogs()
    {
        return $this->hasMany(CancellationLog::class);
    }

    // 🧠 Helper: hitung pembatalan dalam 1 bulan
    public function getMonthlyCancellationCount($month = null, $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        return $this->cancellationLogs()
            ->whereMonth('cancelled_at', $month)
            ->whereYear('cancelled_at', $year)
            ->count();
    }

    // 🧠 Helper: apakah teacher sudah melebihi batas pembatalan (>2x/bulan)
    public function shouldReducePriority()
    {
        return $this->getMonthlyCancellationCount() > 2;
    }
}