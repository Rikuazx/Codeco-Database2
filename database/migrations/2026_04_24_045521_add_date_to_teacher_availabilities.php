<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('teacher_availabilities', 'date')) {
            Schema::table('teacher_availabilities', function (Blueprint $table) {
                $table->date('date')->after('teacher_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('teacher_availabilities', 'date')) {
            Schema::table('teacher_availabilities', function (Blueprint $table) {
                $table->dropColumn('date');
            });
        }
    }
};
