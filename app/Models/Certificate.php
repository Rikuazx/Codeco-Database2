<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificate extends Model
{
    protected $fillable = [
        'enrollment_id',
        'student_id',
        'course_id',
        'certificate_number',
        'certificate_url',
        'issued_at',
        'issued_by',
        'certification_status',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relasi ke tabel classes (course_id → classes.id)
     */
    public function course()
    {
        return $this->belongsTo(Classes::class, 'course_id');
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    /**
     * Generate certificate number format: <KODE_KURSUS>/<TAHUN>/<NOMOR>
     * Contoh: ENG/2026/001
     */
    public static function generateNumber(string $courseCode, int $year): string
    {
        $lastNumber = self::whereYear('issued_at', $year)->count() + 1;
        return strtoupper($courseCode) . '/' . $year . '/' . str_pad($lastNumber, 3, '0', STR_PAD_LEFT);
    }
}
