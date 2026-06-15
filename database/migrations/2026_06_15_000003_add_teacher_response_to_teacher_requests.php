<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_requests', function (Blueprint $table) {
            // Only add columns that don't exist yet
            if (!Schema::hasColumn('teacher_requests', 'teacher_response')) {
                $table->string('teacher_response')->default('pending')->after('status');
            }
            if (!Schema::hasColumn('teacher_requests', 'teacher_responded_at')) {
                $table->timestamp('teacher_responded_at')->nullable()->after('teacher_notes');
            }
            if (!Schema::hasColumn('teacher_requests', 'preferred_date')) {
                $table->date('preferred_date')->nullable()->after('message');
            }
            if (!Schema::hasColumn('teacher_requests', 'preferred_start_time')) {
                $table->time('preferred_start_time')->nullable()->after('preferred_date');
            }
            if (!Schema::hasColumn('teacher_requests', 'preferred_end_time')) {
                $table->time('preferred_end_time')->nullable()->after('preferred_start_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_requests', function (Blueprint $table) {
            $cols = ['teacher_response', 'teacher_responded_at', 'preferred_date', 'preferred_start_time', 'preferred_end_time'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('teacher_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
